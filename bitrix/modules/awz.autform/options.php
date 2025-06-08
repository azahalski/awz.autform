<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Web\Json;
Loc::loadMessages(__FILE__);
global $APPLICATION;
$module_id = "awz.autform";
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
$zr = "";
if (! ($MODULE_RIGHT >= "R"))
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));

$APPLICATION->SetTitle(Loc::getMessage('AWZ_AUTFORM_OPT_TITLE'));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

Loader::includeModule($module_id);

$siteRes = SiteTable::getList(['select'=>['LID','NAME'],'filter'=>['ACTIVE'=>'Y']])->fetchAll();
$siteValues = ['-'=>'Все сайты'];
foreach($siteRes as $arSite){
    $siteValues[$arSite['LID']] = '['.$arSite['LID'].'] - '.$arSite['NAME'];
}

$mailTypes = \Bitrix\Main\Mail\Internal\EventTypeTable::getList(['filter'=>['=EVENT_TYPE'=>'email','=LID'=>'ru'],'order'=>['SORT'=>'DESC']])->fetchAll();
$mailTypesValues = [];
foreach($mailTypes as $type){
    $mailTypesValues[$type['EVENT_NAME']] = '['.$type['EVENT_NAME'].'] - '.$type['NAME'];
}
if(!isset($mailTypesValues['AWZ_AUTFORM_EMAIL_CODE'])){
    $type = [
        'EVENT_NAME'=>'AWZ_AUTFORM_EMAIL_CODE',
        'LID'=>'ru',
        'NAME'=>Loc::getMessage('AWZ_AUTFORM_EVENT_TYPE_NAME'),
        'EVENT_TYPE'=>'email',
        'DESCRIPTION'=>Loc::getMessage('AWZ_AUTFORM_EVENT_TYPE_DESC')
    ];
    $r = \Bitrix\Main\Mail\Internal\EventTypeTable::add($type);
    if($r->isSuccess()){
        $mailTypesValues[$type['EVENT_NAME']] = '['.$type['EVENT_NAME'].'] - '.$type['NAME'];
    }
}
if(Loader::includeModule('awz.admin')) {
    $arTreeDescr = [
        'js' => '/bitrix/js/awz.admin/core_tree.js',
        'css' => '/bitrix/panel/awz.admin/catalog_cond.css',
        'lang' => '/bitrix/modules/awz.admin/lang/ru/conditions.php',
        'rel' => [
            'core',
            'date',
            'window',
        ],
    ];
    CJSCore::RegisterExt('awz_core_condtree', $arTreeDescr);
    CJSCore::Init(['awz_core_condtree','jquery3']);
}
$actionRules = [];
$actionRules[] = [
    'controlId'=>'CondGroupEmail',
    'defaultText'=>"",
    'group'=>true,
    'label'=>"",
    "showIn"=>[],
    'visual'=>[
        'controls'=>['fLevel'],
        'values'=>[
            ['fLevel'=>'AND']
        ],
        'logic'=>[
            ['style'=>'condition-logic-and', 'message'=>'+']
        ]
    ],
    'control'=>[
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_RUN')." ",
        [
            'id'=>'fLevel',
            'name'=>'aggregator',
            'type'=>'select',
            'values'=>[
                'AND'=>Loc::getMessage('AWZ_AUTFORM_OPT_RULE_NAME1')
            ],
            'defaultText'=>'...',
            'defaultValue'=>'AND'
        ]
    ]
];
$actionRules[] = [
    'controlId' => 'actionSendCode',
    'group'=>true,
    'label'=>Loc::getMessage('AWZ_AUTFORM_OPT_RULE_NAME2'),
    'defaultText'=>Loc::getMessage('AWZ_AUTFORM_OPT_RULE_NAME2'),
    'showIn'=>['CondGroupEmail'],
    'visual'=>[
    ],
    'control'=>[
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_NAME2').'.',
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_SITE'),
        [
            'type'=>'select',
            'id'=>'site_id',
            'name'=>'site_id',
            'values'=>$siteValues,
            'defaultValue'=>'-'
        ],
        '.',
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_TMPL_TYPE'),
        [
            'type'=>'select',
            'id'=>'tmpl_code',
            'name'=>'tmpl_code',
            'values'=>$mailTypesValues,
            'defaultValue'=>'AWZ_AUTFORM_EMAIL_CODE'
        ],
        'c ID',
        [
            'type'=>'input',
            'id'=>'tmpl_id',
            'name'=>'tmpl_id',
            'defaultValue'=>''
        ],
        '.',
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_TIMEOUT'),
        [
            'type'=>'input',
            'id'=>'timeout_code',
            'name'=>'timeout_code',
            'defaultValue'=>'60'
        ],
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_SEC').'.',
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_SEND_MAIL'),
        [
            'type'=>'select',
            'id'=>'method',
            'name'=>'method',
            'values'=>[
                'b_event'=>Loc::getMessage('AWZ_AUTFORM_OPT_RULE_SEND_MAIL_1'),
                'im'=>Loc::getMessage('AWZ_AUTFORM_OPT_RULE_SEND_MAIL_2')
            ],
            'defaultValue'=>'im'
        ],
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_SEND_REPEAT'),
        [
            'type'=>'input',
            'id'=>'right_cnt',
            'name'=>'right_cnt',
            'defaultValue'=>'1000'
        ],
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_RAZ').'. ',
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_KOD').': ',
        [
            'type'=>'input',
            'id'=>'right_code',
            'name'=>'right_code',
            'defaultValue'=>'-'
        ],
        '.'
    ]
];

$actionRulesSms = [];
$actionRulesSms[] = [
    'controlId'=>'CondGroupSms',
    'defaultText'=>"",
    'group'=>true,
    'label'=>"",
    "showIn"=>[],
    'visual'=>[
        'controls'=>['fLevel'],
        'values'=>[
            ['fLevel'=>'AND']
        ],
        'logic'=>[
            ['style'=>'condition-logic-and', 'message'=>'+']
        ]
    ],
    'control'=>[
        Loc::getMessage('AWZ_AUTFORM_OPT_RULE_RUN')." ",
        [
            'id'=>'fLevel',
            'name'=>'aggregator',
            'type'=>'select',
            'values'=>[
                'AND'=>Loc::getMessage('AWZ_AUTFORM_OPT_RULE_NAME1')
            ],
            'defaultText'=>'...',
            'defaultValue'=>'AND'
        ]
    ]
];

$event = new \Bitrix\Main\Event(
    \Awz\AutForm\Events::MODULE_ID, \Awz\AutForm\Events::BUILD_RULES,
    array(
        'rules'=>&$actionRulesSms
    )
);
$event->send();

if ($_SERVER["REQUEST_METHOD"] == "POST" && $MODULE_RIGHT == "W" && strlen($_REQUEST["Update"]) > 0 && check_bitrix_sessid())
{

    $rule1 = $_REQUEST['email'];
    $newValue = [];
    $keyCnt = 0;
    foreach($rule1 as $k=>$v){
        $realK = '';
        if($k==0) {
            $realK = '0';
        }else{
            $realK = '0__'.$keyCnt;
            $keyCnt++;
        }
        $newValue[$realK] = $v;
    }
    if(!empty($newValue))
        Option::set($module_id, "RULES_EMAIL", Json::encode($newValue));

    $rule1 = $_REQUEST['sms'];
    $newValue = [];
    $keyCnt = 0;
    foreach($rule1 as $k=>$v){
        $realK = '';
        if($k==0) {
            $realK = '0';
        }else{
            $realK = '0__'.$keyCnt;
            $keyCnt++;
        }
        $newValue[$realK] = $v;
    }
    if(!empty($newValue))
        Option::set($module_id, "RULES_SMS", Json::encode($newValue));

    Option::set($module_id, "ZHURNAL_1", trim($_REQUEST["ZHURNAL_1"]));
    Option::set($module_id, "ZHURNAL_2", trim($_REQUEST["ZHURNAL_2"]));
    Option::set($module_id, "ZHURNAL_3", trim($_REQUEST["ZHURNAL_3"]));
    Option::set($module_id, "ZHURNAL_SROCK", preg_replace('/([^0-9])/is','',$_REQUEST["ZHURNAL_SROCK"]));

    Option::set($module_id, "CHECK_PHONE_MLIFE", trim($_REQUEST["CHECK_PHONE_MLIFE"]));
    Option::set($module_id, "SEND_SMS_MLIFE", trim($_REQUEST["SEND_SMS_MLIFE"]));
    Option::set($module_id, "SEND_SMS_AWZ_FLASH", trim($_REQUEST["SEND_SMS_AWZ_FLASH"]));
    Option::set($module_id, "MAX_TIME", preg_replace('/([^0-9])/','',$_REQUEST["MAX_TIME"]));
    Option::set($module_id, "MAX_CHECK", preg_replace('/([^0-9])/','',$_REQUEST["MAX_CHECK"]));
    Option::set($module_id, "PHONE_LIMIT_H", preg_replace('/([^0-9])/','',$_REQUEST["PHONE_LIMIT_H"]));
    Option::set($module_id, "PHONE_LIMIT_DAY", preg_replace('/([^0-9])/','',$_REQUEST["PHONE_LIMIT_DAY"]));
    Option::set($module_id, "IP_LIMIT_H", preg_replace('/([^0-9])/','',$_REQUEST["IP_LIMIT_H"]));
    Option::set($module_id, "IP_LIMIT_DAY", preg_replace('/([^0-9])/','',$_REQUEST["IP_LIMIT_DAY"]));
    Option::set($module_id, "DEF_LIMIT_H", preg_replace('/([^0-9])/','',$_REQUEST["DEF_LIMIT_H"]));
    Option::set($module_id, "DEF_LIMIT_DAY", preg_replace('/([^0-9])/','',$_REQUEST["DEF_LIMIT_DAY"]));

}

$aTabs = array();

$aTabs[] = array(
    "DIV" => "edit1",
    "TAB" => Loc::getMessage('AWZ_AUTFORM_OPT_SECT1'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_AUTFORM_OPT_SECT1')
);

$aTabs[] = array(
    "DIV" => "edit3",
    "TAB" => Loc::getMessage('AWZ_AUTFORM_OPT_SECT3'),
    "ICON" => "vote_settings",
    "TITLE" => Loc::getMessage('AWZ_AUTFORM_OPT_SECT3')
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>

<style>.adm-workarea option:checked {background-color: rgb(206, 206, 206);}</style>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($module_id)?>&lang=<?=LANGUAGE_ID?>&mid_menu=1" id="FORMACTION">

<?
$tabControl->BeginNextTab();
?>
<?if(Loader::includeModule('awz.admin')){?>
    <tr>
        <td width="50%">
            <?=Loc::getMessage('AWZ_AUTFORM_OPT_ACTION1')?>
        </td>
        <td>
            <?
            $actionValuesDef = [
                'id'=>'0',
                'controlId'=>'CondGroupEmail',
                'values'=>[],
                'children'=>[]
            ];
            $actionValues = Option::get($module_id, "RULES_EMAIL", "", "");
            try{
                if($actionValues){
                    $actionValues = Json::decode($actionValues);
                }
            }catch (\Exception $e){
                $actionValues = $actionValuesDef;
            }
            if(!$actionValues){
                $actionValues = $actionValuesDef;
            }

            $params = [];
            if(!isset($actionValues['id'])){
                foreach($actionValues as $k=>$v){
                    $kAr = explode('__',$k);
                    if(count($kAr)==1){
                        if(!isset($params[$kAr[0]])) $params[$kAr[0]] = [
                            'id'=>$kAr[0],
                            'controlId'=>$v['controlId'],
                            'values'=>['fLevel'=>$v['aggregator']],
                            'children'=>[]
                        ];
                    }elseif(count($kAr)==2){
                        if(!isset($params[$kAr[0]]['children'][$kAr[1]])) $params[$kAr[0]]['children'][$kAr[1]] = [
                            'id'=>$kAr[1],
                            'controlId'=>$v['controlId'],
                            'values'=>$v,
                            'children'=>[]
                        ];
                    }
                }
                if(!isset($params[0]['id']) || $params[0]['controlId']!='CondGroupEmail') {
                    $params[0] = $actionValuesDef;
                }
            }else{
                $params[0] = $actionValues;
            }

            if($_REQUEST['type']=='condRulesEmail'){
                $paramsNew = [];
                $keyAr = explode('__', $_REQUEST['down']);
                $key = end($keyAr);
                $next = false;
                foreach($params[0]['children'] as $keyReal=>$row){
                    if($keyReal==$key){
                        $next = $row;
                        continue;
                    }
                    $paramsNew[] = $row;
                    if($next!==false){
                        $paramsNew[] = $next;
                        $next = false;
                    }
                }
                if($next){
                    $paramsNew[] = $next;
                }
                $finJson = [
                    ['controlId'=>$params[0]['controlId'], 'aggregator'=>'AND']
                ];
                foreach($paramsNew as $k=>$v){
                    $finJson['0__'.$k] = $v['values'];
                }
                Option::set($module_id, "RULES_EMAIL", Json::encode($finJson), "");
                LocalRedirect($APPLICATION->GetCurPage().'?mid='.htmlspecialcharsbx($module_id).'&lang=LANGUAGE_ID&mid_menu=1');
            }

            ?>
            <style>
                .condition-wrapper .condition-container .num {
                    position:absolute;top:-5px;left:10px;width:36px;height:36px;
                    line-height:36px;
                    border-radius:50%;
                    text-align:center;
                    background:#113c7d;color:#ffffff;
                }
                .condition-wrapper .condition-container .up {
                    position:absolute;top:28px;left:0;width:56px;text-align:center;cursor: pointer;
                    text-decoration:underline;
                    border-bottom:0;
                }
                .condition-wrapper .condition-container .up:hover {cursor: pointer;}
                .condition-logic.condition-logic-and {display:none;}
                #condRulesEmail .condition-border {padding: 10px 8px 5px 10px!important;}
            </style>
            <div id="condRulesEmail"></div>
            <script>
                /*$(document).on('click','.condition-wrapper .condition-container .up', function(){
                    var cur_item = $(this).closest('.condition-wrapper');
                    var el = cur_item.prev();
                    if(!el.length) return false;
                    if(!el.hasClass('condition-wrapper')) return false;
                    var copy_from = cur_item.clone(true);
                    $(el).replaceWith(copy_from);

                    var copy_to = $(el).clone(true);
                    cur_item.replaceWith(copy_to);

                });*/
                BX.ready(function(){
                    let condRulesEmail = <?=\Bitrix\Main\Web\Json::encode($actionRules)?>;
                    let condValuesEmail = <?=\Bitrix\Main\Web\Json::encode($params[0])?>;
                    function initValues(){
                        initValues.worksTree = new BX.TreeConditions({
                            'parentContainer': 'condRulesEmail',
                            'form': 'FORMACTION',
                            'formName': 'FORMACTION',
                            'sepID': '__',
                            'prefix': 'email'
                        },condValuesEmail,condRulesEmail);

                        setTimeout(function(){
                            var cn_rule = 0;
                            $('#condRulesEmail .condition-wrapper .condition-container').each(function(){
                                cn_rule+=1;
                                $(this).append('<a class="up" href="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($module_id)?>&lang=<?=LANGUAGE_ID?>&mid_menu=1&type=condRulesEmail&down=">ниже</a>');
                                $(this).find('a.up').attr('href', $(this).find('a.up').attr('href')+$(this).attr('id'))
                                $(this).append('<span class="num">'+cn_rule+'</span>');
                            });
                        },0);

                    }
                    initValues();
                });
            </script>
        </td>
    </tr>
    <tr>
        <td width="50%">
            <?=Loc::getMessage('AWZ_AUTFORM_OPT_ACTION2')?>
        </td>
        <td>
            <?
            $actionValuesDef = [
                'id'=>'0',
                'controlId'=>'CondGroupSms',
                'values'=>[],
                'children'=>[]
            ];
            $actionValues = Option::get($module_id, "RULES_SMS", "", "");
            try{
                if($actionValues){
                    $actionValues = Json::decode($actionValues);
                }
            }catch (\Exception $e){
                $actionValues = $actionValuesDef;
            }
            if(!$actionValues){
                $actionValues = $actionValuesDef;
            }

            $params = [];
            if(!isset($actionValues['id'])){
                foreach($actionValues as $k=>$v){
                    $kAr = explode('__',$k);
                    if(count($kAr)==1){
                        if(!isset($params[$kAr[0]])) $params[$kAr[0]] = [
                            'id'=>$kAr[0],
                            'controlId'=>$v['controlId'],
                            'values'=>['fLevel'=>$v['aggregator']],
                            'children'=>[]
                        ];
                    }elseif(count($kAr)==2){
                        if(!isset($params[$kAr[0]]['children'][$kAr[1]])) $params[$kAr[0]]['children'][$kAr[1]] = [
                            'id'=>$kAr[1],
                            'controlId'=>$v['controlId'],
                            'values'=>$v,
                            'children'=>[]
                        ];
                    }
                }
                if(!isset($params[0]['id']) || $params[0]['controlId']!='CondGroupSms') {
                    $params[0] = $actionValuesDef;
                }
            }else{
                $params[0] = $actionValues;
            }

            if($_REQUEST['type']=='condRulesSms'){
                $paramsNew = [];
                $keyAr = explode('__', $_REQUEST['down']);
                $key = end($keyAr);
                $next = false;
                foreach($params[0]['children'] as $keyReal=>$row){
                    if($keyReal==$key){
                        $next = $row;
                        continue;
                    }
                    $paramsNew[] = $row;
                    if($next!==false){
                        $paramsNew[] = $next;
                        $next = false;
                    }
                }
                if($next){
                    $paramsNew[] = $next;
                }
                $finJson = [
                    ['controlId'=>$params[0]['controlId'], 'aggregator'=>'AND']
                ];
                foreach($paramsNew as $k=>$v){
                    $finJson['0__'.$k] = $v['values'];
                }
                Option::set($module_id, "RULES_SMS", Json::encode($finJson), "");
                LocalRedirect($APPLICATION->GetCurPage().'?mid='.htmlspecialcharsbx($module_id).'&lang=LANGUAGE_ID&mid_menu=1');
            }

            ?>
            <style>
                #condRulesSms .condition-border {padding: 10px 8px 5px 10px!important;}
            </style>
            <div id="condRulesSms"></div>
            <script>
                BX.ready(function(){
                    let condRulesSms = <?=\Bitrix\Main\Web\Json::encode($actionRulesSms)?>;
                    let condValuesSms = <?=\Bitrix\Main\Web\Json::encode($params[0])?>;
                    function initValues(){
                        initValues.worksTree = new BX.TreeConditions({
                            'parentContainer': 'condRulesSms',
                            'form': 'FORMACTION',
                            'formName': 'FORMACTION',
                            'sepID': '__',
                            'prefix': 'sms'
                        },condValuesSms,condRulesSms);
                        setTimeout(function(){
                            var cn_rule = 0;
                            $('#condRulesSms .condition-wrapper .condition-container').each(function(){
                                cn_rule+=1;
                                $(this).append('<a class="up" href="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($module_id)?>&lang=<?=LANGUAGE_ID?>&mid_menu=1&type=condRulesSms&down=">ниже</a>');
                                $(this).find('a.up').attr('href', $(this).find('a.up').attr('href')+$(this).attr('id'))
                                $(this).append('<span class="num">'+cn_rule+'</span>');
                            });
                        },0);
                    }
                    initValues();
                });
            </script>
        </td>
    </tr>
<?}
else{
    \Bitrix\Main\UI\Extension::load("ui.alerts");
    ?>
    <tr>
        <td>
            <?=Loc::getMessage('AWZ_AUTFORM_OPT_ACTION1')?><br><br>
            <?=Loc::getMessage('AWZ_AUTFORM_OPT_ACTION2')?>
        </td>
        <td>
            <div class="ui-alert ui-alert-danger">
                    <span class="ui-alert-message">
                        <?=Loc::getMessage('AWZ_AUTFORM_OPT_CHECK_ERR1')?><br>
                        <?=Loc::getMessage('AWZ_AUTFORM_OPT_CHECK_ERR2')?>:
            <a target="_blank" href="https://marketplace.1c-bitrix.ru/solutions/awz.admin/">
                <?=Loc::getMessage('AWZ_AUTFORM_OPT_CHECK_ERR3')?>
            </a> |
            <a target="_blank" href="https://github.com/azahalski/awz.admin">
                GitHub
            </a>
                    </span>
            </div>



        </td>
    </tr>

<?}?>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_MAX_TIME')?></td>
    <td>
        <?$val = Option::get($module_id, "MAX_TIME", "10", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="MAX_TIME"/>
    </td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_MAX_CHECK')?></td>
    <td>
        <?$val = Option::get($module_id, "MAX_CHECK", "3", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="MAX_CHECK"/>
    </td>
</tr>


<tr class="heading">
    <td colspan="2">
        <?=Loc::getMessage('AWZ_AUTFORM_OPT_LIMITS_TITLE')?>
    </td>
</tr>

<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_DEF_LIMIT_DAY')?></td>
    <td>
        <?$val = Option::get($module_id, "DEF_LIMIT_DAY", "5000", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="DEF_LIMIT_DAY"/>
    </td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_DEF_LIMIT_H')?></td>
    <td>
        <?$val = Option::get($module_id, "DEF_LIMIT_H", "500", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="DEF_LIMIT_H"/>
    </td>
</tr>

<tr class="heading">
    <td colspan="2">
        <?=Loc::getMessage('AWZ_AUTFORM_OPT_LIMITS_USER_TITLE')?>
    </td>
</tr>

<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_DEF_LIMIT_DAY')?></td>
    <td>
        <?$val = Option::get($module_id, "IP_LIMIT_DAY", "100", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="IP_LIMIT_DAY"/>
    </td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_DEF_LIMIT_H')?></td>
    <td>
        <?$val = Option::get($module_id, "IP_LIMIT_H", "10", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="IP_LIMIT_H"/>
    </td>
</tr>

<tr class="heading">
    <td colspan="2">
        <?=Loc::getMessage('AWZ_AUTFORM_OPT_LIMITS_PHONE_TITLE')?>
    </td>
</tr>

<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_DEF_LIMIT_DAY')?></td>
    <td>
        <?$val = Option::get($module_id, "PHONE_LIMIT_DAY", "100", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="PHONE_LIMIT_DAY"/>
    </td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_DEF_LIMIT_H')?></td>
    <td>
        <?$val = Option::get($module_id, "PHONE_LIMIT_H", "10", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="PHONE_LIMIT_H"/>
    </td>
</tr>

    <tr class="heading">
        <td colspan="2">
            <?=Loc::getMessage('AWZ_AUTFORM_OPT_SEND_CODE_TITLE')?> [<a href="https://marketplace.1c-bitrix.ru/solutions/mlife.smsservices/">mlife.smsservices</a>]
        </td>
    </tr>
<?if(Loader::includeModule('mlife.smsservices')){?>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_CHECK_PHONE_MLIFE')?></td>
    <td>
        <?$val = Option::get($module_id, "CHECK_PHONE_MLIFE", "N","");?>
        <input type="checkbox" value="Y" name="CHECK_PHONE_MLIFE" <?if ($val=="Y") echo "checked";?>></td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_SEND_SMS_MLIFE')?></td>
    <td>
        <?$val = Option::get($module_id, "SEND_SMS_MLIFE", "N","");?>
        <input type="checkbox" value="Y" name="SEND_SMS_MLIFE" <?if ($val=="Y") echo "checked";?>></td>
</tr>
<?}else{?>
    <tr>
        <td colspan="2" style="text-align:center;"><?=Loc::getMessage('AWZ_AUTFORM_OPT_CHECK_MODULE_NAME')?></td>
    </tr>
<?}?>
    <tr class="heading">
        <td colspan="2">
            <?=Loc::getMessage('AWZ_AUTFORM_OPT_SEND_CODE_TITLE')?> [<a href="https://marketplace.1c-bitrix.ru/solutions/awz.flashcallapi/">awz.flashcallapi</a>]
        </td>
    </tr>
<?if(Loader::includeModule('awz.flashcallapi')){?>
    <tr>
        <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_SEND_SMS_AWZ_FLASH')?></td>
        <td>
            <?$val = Option::get($module_id, "SEND_SMS_AWZ_FLASH", "N","");?>
            <input type="checkbox" value="Y" name="SEND_SMS_AWZ_FLASH" <?if ($val=="Y") echo "checked";?>></td>
    </tr>
<?}else{?>
    <tr>
        <td colspan="2" style="text-align:center;"><?=Loc::getMessage('AWZ_AUTFORM_OPT_CHECK_MODULE_NAME')?></td>
    </tr>
<?}?>

<tr class="heading">
    <td colspan="2" style="text-align:center;"><?=Loc::getMessage('AWZ_AUTFORM_OPT_ZHURNAL')?></td>
</tr>
    <tr>
    <td colspan="2" style="text-align:center;"><?=Loc::getMessage('AWZ_AUTFORM_OPT_ZHURNAL_DESC')?></td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_ZHURNAL_1')?></td>
    <td>
        <?$val = Option::get($module_id, "ZHURNAL_1", "N","");?>
        <input type="checkbox" value="Y" name="ZHURNAL_1" <?if ($val=="Y") echo "checked";?>></td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_ZHURNAL_2')?></td>
    <td>
        <?$val = Option::get($module_id, "ZHURNAL_2", "N","");?>
        <input type="checkbox" value="Y" name="ZHURNAL_2" <?if ($val=="Y") echo "checked";?>></td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_ZHURNAL_3')?></td>
    <td>
        <?$val = Option::get($module_id, "ZHURNAL_3", "N","");?>
        <input type="checkbox" value="Y" name="ZHURNAL_3" <?if ($val=="Y") echo "checked";?>></td>
</tr>
<tr>
    <td><?=Loc::getMessage('AWZ_AUTFORM_OPT_ZHURNAL_SROCK')?></td>
    <td>
        <?$val = Option::get($module_id, "ZHURNAL_SROCK", "0", "");?>
        <input type="text" size="35" maxlength="255" value="<?=$val?>" name="ZHURNAL_SROCK"/>
    </td>
</tr>
<?
$tabControl->BeginNextTab();
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
?>

<?
$tabControl->Buttons();
?>
<input <?if ($MODULE_RIGHT<"W") echo "disabled" ?> type="submit" class="adm-btn-green" name="Update" value="<?=Loc::getMessage('AWZ_AUTFORM_OPT_L_BTN_SAVE')?>" />
<input type="hidden" name="Update" value="Y" />
<?$tabControl->End();?>
</form>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");