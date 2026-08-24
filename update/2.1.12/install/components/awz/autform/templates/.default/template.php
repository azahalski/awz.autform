<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Localization\Loc;

/**
 * @var CBitrixComponentTemplate $this
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFolder
 * @var array $arParams
 */

CJSCore::Init('ajax');
$this->addExternalCss($templateFolder.'/theme/'.$arParams['THEME'].'.css');

$autFormId = 'awz_autform'.$this->randString();
$arParams['autFormId'] = $autFormId;

$callCodes = array(
        'AWZ_AUTFORM_TMPL_TITLE_AUTHSMS_CALL',
        'AWZ_AUTFORM_TMPL_LABEL_SMSCODE_CALL',
        'AWZ_AUTFORM_TMPL_LABEL_BTN_CODE_CALL',
        'AWZ_AUTFORM_TMPL_LABEL_BTN_CODE2_CALL'
);

$messKeys = array(
    'AWZ_AUTFORM_TMPL_TITLE_AUTH',
    'AWZ_AUTFORM_TMPL_TITLE_AUTHSMS',
    'AWZ_AUTFORM_TMPL_TITLE_REGISTER',
    'AWZ_AUTFORM_TMPL_CLOSE',
    'AWZ_AUTFORM_TMPL_ERR_AJAX',
    'AWZ_AUTFORM_TMPL_LABEL_PHONE',
    'AWZ_AUTFORM_TMPL_LABEL_PASSW',
    'AWZ_AUTFORM_TMPL_LABEL_SMSCODE',
    'AWZ_AUTFORM_TMPL_LABEL_BTN_AUTH',
    'AWZ_AUTFORM_TMPL_LABEL_BTN_CODE',
    'AWZ_AUTFORM_TMPL_LABEL_BTN_CODE2',
    'AWZ_AUTFORM_TMPL_LABEL_BTN_CHECKCODE',
    'AWZ_AUTFORM_TMPL_LABEL_BTN_REGISTER',
    'AWZ_AUTFORM_TMPL_LOADER',
    'AWZ_AUTFORM_TMPL_LABEL_BTN_READ',
    'AWZ_AUTFORM_TMPL_LABEL_AGREEMENT',
    'AWZ_AUTFORM_TMPL_LABEL_PHONE_LOGIN'
);

$arLang = array();
foreach($messKeys as $code){
    $codeLang = $code;
    if($arParams['MODE_MESS']=='call'){
        if(in_array($code.'_CALL', $callCodes)){
            $codeLang = $code.'_CALL';
        }
    }
    $arLang[$code] = Loc::getMessage($codeLang) ? Loc::getMessage($codeLang) : $code;
}

?>
<div id="<?=$autFormId?>" class="awz-autform-link-block">
    <?php
    $frame = $this->createFrame($autFormId, false)->begin();
    ?>
    <?global $USER;?>
    <?if($USER->IsAuthorized()){?>
        <a href="<?=$arParams['PERSONAL_LINK']?>"><?=Loc::getMessage('AWZ_AUTFORM_TMPL_PERSONAL_LINK')?></a>
    <?}else{?>
        <a id="<?=$autFormId?>_lnk" href="#"><?=Loc::getMessage('AWZ_AUTFORM_TMPL_LINK')?></a>
    <?}?>
    <?
    $frame->beginStub();
    $arResult['COMPOSITE_STUB'] = 'Y';
    //тут заглушка по умолчанию
    ?>
    <span class="awz-autform-link-stub"></span>
    <?
    unset($arResult['COMPOSITE_STUB']);
    $frame->end();
    ?>
</div>
<script>
    <?$options = array(
        'theme'=>$arParams['THEME'],
        'lang'=>$arLang,
        'AGREEMENT'=>$arParams['AGREEMENT'],
        'modes'=>array(),
        'mode'=>false,
        'hiddenReg'=>$arParams['REGISTER_LOGIN'],
        'checkLogin'=>$arParams['CHECK_LOGIN']
    );?>
    <?
    if(!empty($arParams['LOGIN_GROUPS'])){
        $options['modes'][] = 'login';
    }
    if(!empty($arParams['LOGIN_SMS_GROUPS'])){
        $options['modes'][] = 'loginsms';
    }
    if(!empty($arParams['REGISTER_GROUPS'])){
        $options['modes'][] = 'register';
    }
    if(!empty($options['modes'])){
        $options['mode'] = $options['modes'][0];
    }
    ?>
    var <?=$autFormId?> = new AwzAutFormComponent;
    <?=$autFormId?>.siteId = '<?=SITE_ID?>';
    <?=$autFormId?>.autFormId = '<?=$autFormId?>';
    <?=$autFormId?>.ajaxPath = '<?=$componentPath?>/ajax.php';
    <?=$autFormId?>.templateName = '<?=$templateName?>';
    <?=$autFormId?>.signedParameters = '<?=$this->getComponent()->getSignedParameters()?>';
    <?=$autFormId?>.componentName = '<?=$this->getComponent()->getName()?>';
    <?=$autFormId?>.activate(<?=CUtil::PHPToJSObject($options)?>);
</script>