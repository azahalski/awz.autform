<?php

namespace Awz\AutForm;

use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;
use Mlife\Smsservices\Sender;
use Bitrix\Main\Security\Random;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Service\GeoIp\Manager;

Loc::loadMessages(__FILE__);

class HandlersV2 {

    const CODE_LEN_EMAIL = 6;
    const CODE_LEN_PHONE = 5;

    const STEP_CODE_SEND = 'code_send';

    public static function OnGetCurrentSiteTemplate(){
        $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
        if($templateId = $request->get('SITE_TEMPLATE')){
            $signer = new \Bitrix\Main\Security\Sign\Signer();
            try{
                if($val = $signer->unsign($request->get('SITE_TEMPLATE'),'awz.autform')){
                    return new \Bitrix\Main\EventResult(
                        \Bitrix\Main\EventResult::SUCCESS,
                        $val
                    );
                }
            }catch (\Exception $e){

            }
        }
    }

    public static function onCheckCode(Event $event): ?EventResult
    {
        $component = $event->getParameter('component');
        if(!$component) return null;

        $mode = $event->getParameter('mode');
        if(in_array($mode, ['LOGIN_EMAIL_ACTIVE','REGISTER_ACTIVE','REGISTER_SMS_ACTIVE','LOGIN_SMS_ACTIVE']))
        {
            $code = $event->getParameter('code');
            $email_or_phone = $event->getParameter('param');
            if(in_array($mode, ['REGISTER_SMS_ACTIVE','LOGIN_SMS_ACTIVE'])){
                $email_or_phone = preg_replace('/([^0-9])/is','',$email_or_phone);
            }
            $result = $event->getParameter('result');
            if(!$code){
                $component->addError(new Error(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_NOCODE'), 'code'));
                return null;
            }

            //Для торопыг, если выслано несколько кодов
            $curDate = DateTime::createFromTimestamp(time());
            $checkRes = CodesTable::getList(array(
                'select'=>array('*'),
                'filter'=>array(
                    '=PHONE'=>$email_or_phone,
                    '=CODE'=>$code
                ),
                'order'=>array(
                    'ID'=>'DESC'
                ),
                'limit'=>1
            ))->fetch();
            //если не передан верный код, то проверка по последнему добавленному
            if(!$checkRes){
                $checkRes = CodesTable::getList(array(
                    'select'=>array('*'),
                    'filter'=>array(
                        '=PHONE'=>$email_or_phone
                    ),
                    'order'=>array(
                        'ID'=>'DESC'
                    ),
                    'limit'=>1
                ))->fetch();
            }
            $addCnt = 0;
            if(!$checkRes){
                $component->addError(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_ERR_CODE_NOT_EXISTS'), 'code');
            }elseif($checkRes['PRM']['count'] >= Option::get(Events::MODULE_ID, "MAX_CHECK", "10", "")){
                $component->addError(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_MAX_LIMIT_ERR'), 'code');
            }elseif(\bitrix_sessid() != $checkRes['PRM']['csrf']){
                $component->addError(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_ERR_SESS'), 'code');
            }elseif(strtotime($curDate->toString()) > strtotime($checkRes['EXPIRED_DATE']->toString())){
                $component->addError(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_ERR_CODE_EXPIRED'), 'code');
            }elseif($checkRes['CODE']!=$code){
                $component->addError(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_ERR_CODE'), 'code');
                $addCnt++;
            }else{
                $result->setData(['result'=>'ok', 'item'=>$checkRes]);
            }
            if($addCnt){
                $upRes = CodesTable::getList(array(
                    'select'=>array('*'),
                    'filter'=>array(
                        '=PHONE'=>$email_or_phone
                    )
                ));
                while($row = $upRes->fetch()){
                    if(!isset($row['PRM']['count']))
                        $row['PRM']['count'] = 0;
                    $row['PRM']['count'] += $addCnt;
                    CodesTable::update(['ID'=>$row['ID']],[
                        'PRM'=>$row['PRM']
                    ]);
                }
            }
        }else{
            return null;
        }

        return new EventResult(
            EventResult::SUCCESS,
            $event->getParameters()
        );
    }

    public static function checkPhone(Event $event): ?EventResult
    {
        $component = $event->getParameter('component');
        if(!$component) return null;

        if(Option::get('awz.autform', 'CHECK_PHONE_MLIFE', 'N', '')!='Y'){
            return null;
        }
        if(!Loader::includeModule('mlife.smsservices')){
            return null;
        }

        $phone = $event->getParameter('phone');

        $smsOb = new Sender();
        $check = $smsOb->checkPhoneNumber($phone);
        $phone = $check['phone'];

        $countryCode = '+'.preg_replace('/([^0-9])/','',$component->arParams['COUNTRY_CODE']);

        if($countryCode != substr($phone, 0, strlen($countryCode))){
            $check['check'] = false;
        }

        if(!$check['check']){
            $component->addError(new Error(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_PHONE_ERR'), 'phone'));
        }else{
            $event->setParameter('phone', $phone);
        }

        return new EventResult(
            EventResult::SUCCESS,
            $event->getParameters()
        );
    }

    public static function checkEmail(Event $event): ?EventResult
    {
        $component = $event->getParameter('component');
        if(!$component) return null;

        $email = $event->getParameter('email');

        if(function_exists('filter_var')){
            if(!($newEmail = filter_var($email, FILTER_VALIDATE_EMAIL))){
                $component->addError(new Error(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_ERR_FORMAT'), 'email'));
            }else{
                $event->setParameter('email', $newEmail);
            }
        }

        return new EventResult(
            EventResult::SUCCESS,
            $event->getParameters()
        );
    }

    public static function checkRule(Event $event){

        $rule = $event->getParameter('rule');

        if($rule['controlId']=='actionSendSmsCode' && Loader::includeModule('mlife.smsservices'))
        {
            $phone = '+'.$event->getParameter('param');

            $code = Random::getStringByCharsets(static::CODE_LEN_PHONE, '123456789');
            $siteId = Application::getInstance()->getContext()->getSite();

            if(!$rule['site_id'] || ($rule['site_id']=='-') || ($rule['site_id'] == $siteId)){

                $smsOb = new \Mlife\Smsservices\Sender();
                $checkPhone = $smsOb->checkPhoneNumber($phone);

                if($checkPhone['check']){
                    $arMakros = [
                        '#AWZ_CODE#'=>$code,
                        '#AWZ_PHONE#'=>$phone
                    ];

                    $filter = ["=ID"=>$rule['tmpl_code'],"ACTIVE"=>"Y"];
                    if($rule['site_id'] && ($rule['site_id']!='-')){
                        $filter["SITE_ID"] = $rule['site_id'];
                    }
                    $res = \Mlife\Smsservices\EventlistTable::getList(
                        [
                            'select' => ["*"],
                            'filter' => $filter
                        ]
                    );
                    while($arData = $res->fetch()){
                        $arData['PARAMS'] = unserialize($arData['PARAMS'], ["allowed_classes" => false]);
                        if($arData['PARAMS']['PHONE']){
                            $arData['TEMPLATE'] = \Mlife\Smsservices\Events::compileTemplate($arData['TEMPLATE'], $arMakros);
                            $phoneAr = str_replace(array_keys($arMakros), $arMakros, $arData['PARAMS']['PHONE']);

                            $phoneAr = preg_replace("/([^0-9,])/is","",$phoneAr);
                            $phoneAr = explode(",",$phoneAr);
                            $sender = ($arData['SENDER']) ? $arData['SENDER'] : "";

                            foreach($phoneAr as $phone){
                                if(strlen($phone)>7){
                                    if(trim($arData['TEMPLATE'])){
                                        $smsOb->app = ($arData['PARAMS']['APPSMS']=='Y') ? true : false;
                                        $smsOb->sendSms($phone, $arData['TEMPLATE'],0,$sender);

                                        $result = new Result();
                                        $result->setData([
                                            'code'=>$code,
                                            'nextCode'=>time()+(int)$rule['timeout_code'],
                                            'message'=>Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_SEND_CODE'),
                                            'button'=>Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_SEND_CODE_BTN'),
                                        ]);
                                        $event->setParameter('result', $result);

                                    }
                                    break;
                                }
                            }
                        }
                    }
                }else{
                    $result = new Result();
                    $result->addError(new Error(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_PHONE_ERR'),'phone'));
                    $event->setParameter('result', $result);
                }

            }
        }
        elseif($rule['controlId']=='actionSendCode')
        {
            $email = $event->getParameter('param');
            $code = Random::getStringByCharsets(static::CODE_LEN_EMAIL, '123456789');
            $siteId = Application::getInstance()->getContext()->getSite();

            if(!$rule['site_id'] || ($rule['site_id']=='-') || ($rule['site_id'] == $siteId)){
                $eventsFields = [
                    'AWZ_CODE'=>$code,
                    'AWZ_EMAIL'=>$email
                ];

                if($rule['method']=='im'){
                    $sendResult = \CEvent::SendImmediate($rule['tmpl_code'], $siteId, $eventsFields, "Y", $rule['tmpl_id']);
                }else{
                    $sendResult = \CEvent::Send($rule['tmpl_code'], $siteId, $eventsFields, "Y", $rule['tmpl_id']);
                }
                if($sendResult !== false){
                    $result = new Result();
                    $result->setData([
                        'code'=>$code,
                        'nextCode'=>time()+(int)$rule['timeout_code'],
                        'message'=>Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_SEND_CODE_EMAIL'),
                        'button'=>Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_SEND_CODE_BTN_EMAIL'),
                    ]);
                    $event->setParameter('result', $result);
                }
            }
        }

        return new EventResult(
            EventResult::SUCCESS,
            $event->getParameters()
        );
    }

    public static function checkRuleWrapper(Event $event){

        $eventReturn = new Event(
            Events::MODULE_ID, Events::CHECK_RULE,
            $event->getParameters()
        );
        $eventReturn->send();

        return new EventResult(
            EventResult::SUCCESS,
            $eventReturn->getParameters()
        );

    }

    public static function onGenerateCode(Event $event){

        $result = new \Bitrix\Main\Result();

        $param = $event->getParameter('param');
        $mode = $event->getParameter('mode');
        if(in_array($mode, ['REGISTER_SMS_ACTIVE','LOGIN_SMS_ACTIVE'])){
            $param = preg_replace('/([^0-9])/is','',$param);
        }

        $maxTime = intval(Option::get('awz.autform', 'MAX_TIME', '10', '')) * 60;
        $curDate = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());
        $expiredDate = \Bitrix\Main\Type\DateTime::createFromTimestamp(time() + $maxTime);

        $checkRes = CodesTable::getList([
            'select'=> ['*'],
            'filter'=> [
                '=PHONE'=>$param,
                '>EXPIRED_DATE'=>$curDate
            ],
            'order'=>['ID'=>'DESC']
        ]);
        $codeTimeMax = time();
        $codeTimeMaxId = 0;
        $step = 0;
        while($data = $checkRes->fetch()){
            if(!$codeTimeMaxId) $codeTimeMaxId = $data['ID'];
            if(isset($data['PRM']['nextCode']) && $data['PRM']['nextCode']>$codeTimeMax){
                $codeTimeMax = (int)$data['PRM']['nextCode'];
                $codeTimeMaxId = $data['ID'];
            }
            $step = (int)$data['PRM']['step'];
        }
        if($codeTimeMax>time()){
            $result->setData(['timer'=>$codeTimeMax,'id'=>$codeTimeMaxId, 'step'=>static::STEP_CODE_SEND]);
            $result->addError(
                new Error(
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_NOT_EXPIRED', array('#DATE#'=>date('d.m.Y H:i:s', $codeTimeMax))
                ), 107)
            );
        }

        $ip = Manager::getRealIp();
        $checkBanResult = Helper::checkLimits($param, $ip);
        if(!$checkBanResult->isSuccess()) {
            $result->addErrors($checkBanResult->getErrors());
        }

        if($result->isSuccess()){

            $rules = [];
            if(in_array($mode,['LOGIN_EMAIL_ACTIVE', 'REGISTER_ACTIVE'])){
                $rules = Option::get(Events::MODULE_ID, 'RULES_EMAIL', '', '');
                try{
                    $rules = Json::decode($rules);
                }catch (\Exception $e){
                    $rules = [];
                }
                if(empty($rules)){
                    $result->addError(new Error(Loc::getMessage("AWZ_AUTFORM_HANDLERSV2_ERR_NORIGHT")));
                }
            }
            elseif(in_array($mode, ['LOGIN_SMS_ACTIVE','REGISTER_SMS_ACTIVE']))
            {
                $rules = Option::get(Events::MODULE_ID, 'RULES_SMS', '', '');
                try{
                    $rules = Json::decode($rules);
                }catch (\Exception $e){
                    $rules = [];
                }
                if(empty($rules)){
                    $result->addError(new Error(Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_ERR_NORIGHT2')));
                }
            }
            $curStep = 0;
            foreach($rules as $rule){
                if(!isset($rule['right_cnt'])) continue;
                for($i=0;$i<$rule['right_cnt'];$i++){
                    $curStep += $rule['right_cnt'];
                    $event->setParameter('rule', $rule);
                    if($step<$curStep){
                        $eventResult = self::checkRuleWrapper($event);
                        if($params = $eventResult->getParameters()){
                            if($params['result'] instanceof \Bitrix\Main\Result){
                                $data = $params['result']->getData();
                                if($params['result']->isSuccess() && isset($data['code'])){

                                    $fields = [
                                        'PHONE'=>$param,
                                        'CREATE_DATE'=>$curDate,
                                        'EXPIRED_DATE'=>$expiredDate,
                                        'IP_STR'=>$ip,
                                        'PRM'=> [
                                            'count'=>0,
                                            'csrf'=>\bitrix_sessid(),
                                            'nextCode'=>$data['nextCode'] ? $data['nextCode'] : (time()+(int)$rule['timeout_code']),
                                            'step'=>$curStep,
                                            'rule_result'=>$data
                                        ],
                                        'CODE'=>$data['code']
                                    ];

                                    $rAdd = CodesTable::add($fields);
                                    if($rAdd->isSuccess()) {
                                        $result->setData(['id'=>$rAdd->getId()]);
                                        break 2;
                                    }
                                }
                            }
                        }
                        break 2;
                    }
                }
            }
        }

        $event->setParameter('result', $result);

        return new EventResult(
            EventResult::SUCCESS,
            $event->getParameters()
        );
    }

    public static function buildRules(\Bitrix\Main\Event $event)
    {
        $rules = $event->getParameter('rules');

        $siteRes = \Bitrix\Main\SiteTable::getList(['select'=>['LID','NAME'],'filter'=>['ACTIVE'=>'Y']])->fetchAll();
        $siteValues = ['-'=>Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS_SITE_ALL')];
        foreach($siteRes as $arSite){
            $siteValues[$arSite['LID']] = '['.$arSite['LID'].'] - '.$arSite['NAME'];
        }

        if(Loader::includeModule('mlife.smsservices')){

            $templatesList = \Mlife\Smsservices\EventlistTable::getList(
                ['filter'=>['=EVENT'=>'AWZ_ONSENDSMSCODE']]
            )->fetchAll();
            $names = [];
            foreach($templatesList as $row){
                $names[$row['ID']] = '['.$row['ID'].'] - '.$row['NAME'];
            }
            $rules[] = [
                'controlId' => 'actionSendSmsCode',
                'group'=>true,
                'label'=>Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS'),
                'defaultText'=>Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS'),
                'showIn'=>['CondGroupSms'],
                'visual'=>[
                ],
                'control'=>[
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS').'.',
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS_SITE'),
                    [
                        'type'=>'select',
                        'id'=>'site_id',
                        'name'=>'site_id',
                        'values'=>$siteValues,
                        'defaultValue'=>'-'
                    ],
                    '.',
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS_TMPL'),
                    [
                        'type'=>'select',
                        'id'=>'tmpl_code',
                        'name'=>'tmpl_code',
                        'values'=>$names,
                        'defaultValue'=>count(array_keys($names)) ? array_keys($names)[0] : ''
                    ],
                    '.',
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS_TIMEOUT'),
                    [
                        'type'=>'input',
                        'id'=>'timeout_code',
                        'name'=>'timeout_code',
                        'defaultValue'=>'60'
                    ],
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS_SEC').'.',
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS_REPEAT'),
                    [
                        'type'=>'input',
                        'id'=>'right_cnt',
                        'name'=>'right_cnt',
                        'defaultValue'=>'1000'
                    ],
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS_RAZ').'. ',
                    Loc::getMessage('AWZ_AUTFORM_HANDLERSV2_RULE_MS_KOD').': ',
                    [
                        'type'=>'input',
                        'id'=>'right_code',
                        'name'=>'right_code',
                        'defaultValue'=>'-'
                    ],
                    '.'
                ]
            ];
        }

        $event->setParameter('rules', $rules);

        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            $event->getParameters()
        );
    }
}