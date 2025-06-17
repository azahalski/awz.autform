<?php

namespace Awz\AutForm;

use Awz\Weather\App;
use Awz\Weather\HistoryTable;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class LogTable extends Entity\DataManager
{
    use ORM\Data\Internal\DeleteByFilterTrait;

    /**
     * @return string
     */
    public static function getFilePath(): string
    {
        return __FILE__;
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_awz_autform_logs';
    }

    /**
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap(): array
    {
        return [
            new Entity\IntegerField('ID', array(
                    'primary' => true,
                    'autocomplete' => false
                )
            ),
            new Entity\StringField('PHONE', array(
                    'required' => true
                )
            ),
            new Entity\DatetimeField('CREATE_DATE', array(
                    'required' => true
                )
            ),
            new Entity\StringField('PRM', array(
                    'required' => true,
                    'save_data_modification' => function(){
                        return [
                            function ($value) {
                                return serialize($value);
                            }
                        ];
                    },
                    'fetch_data_modification' => function(){
                        return [
                            function ($value) {
                                return unserialize($value, ["allowed_classes" => false]);
                            }
                        ];
                    },
                )
            ),
        ];
    }

    public static function addLog(string $param, string $type){
        $server = Application::getInstance()->getContext()->getServer();
        $requestData = Application::getInstance()->getContext()->getRequest()->toArray();
        unset($requestData['signedParameters']);
        unset($requestData['SITE_TEMPLATE']);
        self::add([
            'PHONE'=>$param,
            'CREATE_DATE'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
            'PRM'=>[
                'ip'=>\Bitrix\Main\Service\GeoIp\Manager::getRealIp(),
                'agent'=>$server->getUserAgent(),
                'referer'=>$server->get('HTTP_REFERER'),
                'request'=>$requestData,
                'type'=>$type
            ]
        ]);
    }

    public static function onGenerateCode(\Bitrix\Main\Event $event){
        if(Option::get(Events::MODULE_ID, "ZHURNAL_1", "N", "")=="Y"){
            self::addLog($event->getParameter('param'),'onGenerateCode');
        }
    }
    public static function onAfterAuthSms(\Bitrix\Main\Event $event){
        if(Option::get(Events::MODULE_ID, "ZHURNAL_2", "N", "")=="Y"){
            self::addLog($event->getParameter('phone').$event->getParameter('email'),'onAfterAuthSms');
        }
    }
    public static function onAfterAuthPsw(\Bitrix\Main\Event $event){
        if(Option::get(Events::MODULE_ID, "ZHURNAL_3", "N", "")=="Y"){
            self::addLog($event->getParameter('phone').$event->getParameter('email'),'onAfterAuthPsw');
        }
    }

    public static function clearOld(){

        $maxDays = (string) Option::get(Events::MODULE_ID, 'ZHURNAL_SROCK', '0', '');
        if($maxDays){
            $filter = [
                '<=CREATE_DATE'=>DateTime::createFromTimestamp(strtotime('-'.$maxDays.'days'))
            ];
            self::deleteByFilter($filter);
        }
        return "\\Awz\\AutForm\\LogTable::clearOld();";
    }
}