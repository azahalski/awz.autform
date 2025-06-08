<?php

namespace Awz\AutForm;

use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Result;
use Mlife\Smsservices\Sender;
use Bitrix\Main\Service\GeoIp\Manager;
use Bitrix\Main\Web\Json;

Loc::loadMessages(__FILE__);

class Handlers {

    /**
     * @param Event $event
     * @return EventResult
     */
    public static function onAfterEventsAdd(Event $event): EventResult
    {
        $arParam = $event->getParameters();

        $arParam['EVENTS']['AWZ_'.mb_strtoupper(Events::SEND_SMS_CODE)] = array(
            "BX_EVENT" => array(
                array(
                    'awz.autform',
                    Events::SEND_SMS_CODE,
                    'awz.autform',
                    '\Awz\AutForm\HandleSms\Aut',
                    'OnAfterAddEvent',
                    'new'
                ),
            ),
            "FIELD" => array(
                "HTML" => array('\Awz\AutForm\HandleSms\Aut','tmplHtml'),
                "BEFORE_SAVE" => array('\Awz\AutForm\HandleSms\Aut','tmplHtmlSave')
            ),
            "NAME" => Loc::getMessage('AWZ_AUTFORM_HANDLERS_MLIFE_SMS_TMPL_NAME')
        );

        return new EventResult(
            EventResult::SUCCESS,
            $arParam
        );
    }

    /**
     * Проверка и форматирование номера телефона
     *
     * @param Event $event
     * @return EventResult
     * @throws \Bitrix\Main\LoaderException
     */
    public static function checkPhone(Event $event): ?EventResult
    {
        if(Option::get('awz.autform', 'CHECK_PHONE_MLIFE', 'N', '')!='Y'){
            return null;
        }

        $phone = $event->getParameter('phone');
        $params = $event->getParameter('params');

        $result = new Result;

        if(!Loader::includeModule('mlife.smsservices')){
            $result->addError(new Error(Loc::getMessage('AWZ_AUTFORM_HANDLERS_MLIFE_SMS_MODULE_ERR')));
            return new EventResult(
                EventResult::SUCCESS,
                array('result'=>$result)
            );
        }

        $smsOb = new Sender();
        $check = $smsOb->checkPhoneNumber($phone);
        $phone = $check['phone'];

        $countryCode = '+'.preg_replace('/([^0-9])/','',$params['COUNTRY_CODE']);

        if($countryCode != substr($phone, 0, strlen($countryCode))){
            $check['check'] = false;
        }

        if(!$check['check']){
            $result->addError(new Error(Loc::getMessage('AWZ_AUTFORM_HANDLERS_MLIFE_SMS_PHONE_ERR')));
            return new EventResult(
                EventResult::SUCCESS,
                array('result'=>$result)
            );
        }

        $result->setData(array(
            'phone'=>$phone
        ));

        return new EventResult(
            EventResult::SUCCESS,
            array('result'=>$result)
        );
    }

    public static function onCheckCode(Event $event): ?EventResult
    {
        if(Option::get('awz.autform', 'SEND_SMS_AWZ_FLASH', 'N', '')!='Y'){
            return null;
        }

        if(!Loader::includeModule('awz.flashcallapi')){
            return null;
        }

        $result = new Result;
        $entityRow = $event->getParameter('entityRow');
        $code = $event->getParameter('code');
        $code = preg_replace('/([^0-9])/','',$code);

        $sender = new \Awz\FlashCallApi\Sender();
        $codeResult = $sender->getCode((int)$entityRow['CODE']);

        if($codeResult->isSuccess()){
            $data = $codeResult->getData();
            $result->setData(array(
                'status'=>($data['code'] == $code) ? Helper::CHECK_OK : Helper::CHECK_ERR
            ));
            return new EventResult(
                EventResult::SUCCESS,
                array('result'=>$result)
            );
        }else{
            return new EventResult(
                EventResult::SUCCESS,
                array('result'=>$codeResult)
            );
        }

    }

    public static function onSendSmsCode(Event $event): ?EventResult
    {

        if(Option::get('awz.autform', 'SEND_SMS_AWZ_FLASH', 'N', '')!='Y'){
            return null;
        }

        if(!Loader::includeModule('awz.flashcallapi')){
            return null;
        }

        $eventParams = $event->getParameters();

        $result = new Result;

        $phone = $eventParams['phone'];

        $sender = new \Awz\FlashCallApi\Sender();
        $transport = $sender->getTransport();
        $codeLength = 4;
        if($transport){
            $transport->setParameter('ip', \Bitrix\Main\Service\GeoIp\Manager::getRealIp());
            $codeLength = $transport->getParameter('len_code', 4);
        }

        $senderResult = $sender->send($phone);

        if($senderResult->isSuccess()){
            $data = $senderResult->getData();

            $langCodeLength = 2; //последние N цифр
            if(in_array($codeLength, array(3,4))){
                $langCodeLength = 1; //последние N цифры
            }

            $result->setData(array(
                'send'=>'ok',
                'message'=>Loc::getMessage('AWZ_AUTFORM_HANDLERS_AWZ_F_CALL_API_OK_'.$langCodeLength, array('#LEN#'=>$codeLength))
            ));

            $event->setParameter('code', $data['id']); //перезапись значения кода по ссылке

            return new EventResult(
                EventResult::SUCCESS,
                array(
                    'result'=>$result
                )
            );

        }else{
            return new EventResult(
                EventResult::SUCCESS,
                array('result'=>$senderResult)
            );
        }

    }

}