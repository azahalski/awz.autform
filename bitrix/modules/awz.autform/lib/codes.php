<?php

namespace Awz\AutForm;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;

Loc::loadMessages(__FILE__);

class CodesTable extends Entity\DataManager
{
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
        return 'b_awz_autform_codes';
    }

    /**
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap(): array
    {
        return array(
            new Entity\IntegerField('ID', array(
                    'primary' => true,
                    'autocomplete' => false,
                    'title'=>Loc::getMessage('AWZ_AUTFORM_CODES_FIELDS_ID')
                )
            ),
            new Entity\StringField('PHONE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_AUTFORM_CODES_FIELDS_PHONE')
                )
            ),
            new Entity\StringField('CODE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_AUTFORM_CODES_FIELDS_CODE')
                )
            ),
            new Entity\StringField('IP_STR', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_AUTFORM_CODES_FIELDS_IP_STR')
                )
            ),
            new Entity\DatetimeField('CREATE_DATE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_AUTFORM_CODES_FIELDS_CREATE_DATE')
                )
            ),
            new Entity\DatetimeField('EXPIRED_DATE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_AUTFORM_CODES_FIELDS_EXPIRED_DATE')
                )
            ),
            new Entity\StringField('PRM', array(
                    'required' => false,
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
                    'title'=>Loc::getMessage('AWZ_AUTFORM_CODES_FIELDS_PRM')
                )
            ),
        );
    }

}