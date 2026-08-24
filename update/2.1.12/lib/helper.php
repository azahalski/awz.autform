<?php

namespace Awz\AutForm;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Result;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

class Helper {

    const CHECK_OK = 'ok';
    const CHECK_ERR = 'err';

    /**
     * Возвращает массив кандидатов номера для поиска
     *
     * @param string $phone
     * @param int $countryCode
     * @return array
     */
    public static function getPhoneCandidates(string $phone, int $countryCode=7): array
    {
        $phoneArray = array(
            $phone
        );
        if(mb_substr($phone, 0, 2) == 80){
            $phoneArray[] = '+'.$countryCode.mb_substr($phone, 2);
        }elseif(mb_substr($phone, 0, 1) == 8){
            $phoneArray[] = '+'.$countryCode.mb_substr($phone, 1);
        }else{
            $phoneArray[] = '+'.$phone;
        }
        $candidateCountryCode = mb_substr($phone, 0, mb_strlen($countryCode));
        if($countryCode == $candidateCountryCode){
            $phoneArray[] = mb_substr($phone, mb_strlen($countryCode));
        }

        return $phoneArray;
    }

    /**
     * @param string $phone
     * @param string $ip
     * @return Result
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function checkLimits(string $phone, string $ip): Result
    {
        $result = new Result();

        $okData = array('status'=>'ok');

        $event = new Event(
            'awz.autform', Events::CHECK_LIMITS,
            array(
                'phone'=>$phone,
                'ip'=>$ip,
                'result'=>$result
            )
        );
        $event->send();

        if(empty($result->getData()) && empty($result->getErrors())){

            $maxTime = intval(Option::get('awz.autform', 'MAX_TIME', '10', '')) * 60;
            $expiredDate1 = DateTime::createFromTimestamp(time()+$maxTime-86400);
            $expiredDate2 = DateTime::createFromTimestamp(time()+$maxTime-3600);

            $counters = array(
                'PHONE'=>array(
                    'DAY'=>array(
                        '=PHONE'=>$phone,
                        '>EXPIRED_DATE'=>$expiredDate1
                    ),
                    'H'=>array(
                        '=PHONE'=>$phone,
                        '>EXPIRED_DATE'=>$expiredDate2
                    )
                ),
                'IP'=>array(
                    'DAY'=>array(
                        '=IP_STR'=>$ip,
                        '>EXPIRED_DATE'=>$expiredDate1
                    ),
                    'H'=>array(
                        '=IP_STR'=>$ip,
                        '>EXPIRED_DATE'=>$expiredDate2
                    )
                ),
                'DEF'=>array(
                    'DAY'=>array(
                        '>EXPIRED_DATE'=>$expiredDate1
                    ),
                    'H'=>array(
                        '>EXPIRED_DATE'=>$expiredDate2
                    )
                )
            );

            foreach($counters as $code=>$arCount){
                foreach($arCount as $code2=>$filter){
                    $cnt = CodesTable::getCount($filter);
                    $optionValue = (int)Option::get('awz.autform',$code.'_LIMIT_'.$code2, "", "");
                    if($cnt >= $optionValue){
                        $result->addError(new Error(Loc::getMessage('AWZ_AUTFORM_HELPER_ERR_LIMIT', array('#CODE#'=>$code2))));
                        break 2;
                    }
                }
            }

            if(empty($result->getErrors())){
                $result->setData($okData);
            }

        }

        $event = new Event(
            'awz.autform', Events::AFTER_CHECK_LIMITS,
            array(
                'phone'=>$phone,
                'ip'=>$ip,
                'results'=>$result
            )
        );
        $event->send();

        return $result;
    }

}