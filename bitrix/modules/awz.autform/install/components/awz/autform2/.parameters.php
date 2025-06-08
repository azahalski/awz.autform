<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\OrderPropsTable;

//группы
$rsGroups = CGroup::GetList($by = "c_sort", $order = "asc", array());
$arUserGroup = array();
while($arGroups = $rsGroups->Fetch()){
    $arUserGroup[$arGroups["ID"]] = $arGroups["NAME"];
}

$arComponentParameters = array(
    "GROUPS" => array(
        "DEF" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_GROUP_DEF'),
        ),
        "AGR" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_GROUP_AGR'),
        ),
        "LOGIN" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_GROUP_LOGIN'),
        ),
        "LOGIN_SMS" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_GROUP_LOGIN_SMS'),
        ),
        "LOGIN_EMAIL" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_GROUP_LOGIN_EMAIL'),
        ),
        "REGISTER" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_GROUP_REGISTER'),
        ),
        "REGISTER_SMS" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_GROUP_REGISTER_SMS'),
        ),
        "MERGE" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_GROUP_MERGE'),
        ),
    ),
    "PARAMETERS" => [

        "AGR_TITLE" => [
            "PARENT" => "AGR",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_AGR_TITLE'),
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => ""
        ],
        "AGR_LINK" => [
            "PARENT" => "AGR",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_AGR_LINK'),
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => ""
        ],
        "AGR_ANCOR" => [
            "PARENT" => "AGR",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_AGR_ANCOR'),
            "TYPE" => "STRING",
            "MULTIPLE" => "N",
            "DEFAULT" => ""
        ],
        "AGR_SET" => [
            "PARENT" => "AGR",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_AGR_SET'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N"
        ],

        "LOGIN_ACTIVE" => [
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_ACTIVE'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
            "REFRESH" => "Y",
        ],
        "LOGIN_SMS_ACTIVE" => [
            "PARENT" => "LOGIN_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_SMS_ACTIVE'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
            "REFRESH" => "Y",
        ],
        "LOGIN_EMAIL_ACTIVE" => [
            "PARENT" => "LOGIN_EMAIL",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_EMAIL_ACTIVE'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
            "REFRESH" => "Y",
        ],

        "COUNTRY_CODE"=> [
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_COUNTRY_CODE'),
            "TYPE" => "STRING",
            "DEFAULT"=>"7"
        ],
        "PERSONAL_LINK"=> [
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_PERSONAL_LINK'),
            "TYPE" => "STRING",
            "DEFAULT"=>"/personal/"
        ],
        "PERSONAL_LINK_EDIT"=> [
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_PERSONAL_LINK_EDIT'),
            "TYPE" => "STRING",
            "DEFAULT"=>"/personal/private/"
        ],
        "LOGIN_GROUPS_DEL2" => [
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_GROUPS_DEL2'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "LOGIN_GROUPS_DEL3" => [
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_GROUPS_DEL3'),
            "TYPE" => "STRING",
            "VALUE" => "1",
        ],
        "LOGIN_GROUPS" => [
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_GROUPS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "LOGIN_GROUPS_DEL" => [
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_GROUPS_DEL'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "LOGIN_SMS_GROUPS" => [
            "PARENT" => "LOGIN_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_SMS_GROUPS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "LOGIN_SMS_GROUPS_DEL" => [
            "PARENT" => "LOGIN_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_SMS_GROUPS_DEL'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "LOGIN_EMAIL_GROUPS" => [
            "PARENT" => "LOGIN_EMAIL",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_SMS_GROUPS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "LOGIN_EMAIL_GROUPS_DEL" => [
            "PARENT" => "LOGIN_EMAIL",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_SMS_GROUPS_DEL'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "REGISTER_ACTIVE" => [
            "PARENT" => "REGISTER",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_ACTIVE'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        "REGISTER_ACTIVE_NAME" => [
            "PARENT" => "REGISTER",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_NAME'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        "REGISTER_ACTIVE_PSW" => [
            "PARENT" => "REGISTER",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_PSW'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        "REGISTER_GROUPS" => [
            "PARENT" => "REGISTER",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_GROUPS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "REGISTER_SMS_ACTIVE" => [
            "PARENT" => "REGISTER_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_SMS_ACTIVE'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        "REGISTER_SMS_ACTIVE_NAME" => [
            "PARENT" => "REGISTER_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_NAME'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        "REGISTER_SMS_ACTIVE_PSW" => [
            "PARENT" => "REGISTER_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_PSW'),
            "TYPE" => "CHECKBOX",
            "MULTIPLE" => "N",
            "DEFAULT" => "N",
        ],
        "REGISTER_SMS_GROUPS" => [
            "PARENT" => "REGISTER_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_GROUPS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ],
        "REGISTER_LOGIN" => [
            "PARENT" => "MERGE",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_REGISTER_LOGIN'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "LOGIN_REGISTER" => [
            "PARENT" => "MERGE",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_LOGIN_REGISTER'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "MERGE_PE" => [
            "PARENT" => "MERGE",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_MERGE_PE'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "MERGE_PE_REG" => [
            "PARENT" => "MERGE",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_MERGE_PE_REG'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "CHECK_LOGIN" => [
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_CHECK_LOGIN'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "CHECK_EMAIL" => [
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_CHECK_EMAIL'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        "CHECK_PHONE" => [
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_CHECK_PHONE'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
    ],
);

$arFindOption = array(
    'user'=>Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_FIND_TYPE_USER')
);
$saleProps = array();
$salePropIsPhone = false;
if(Loader::includeModule('sale')){
    $arFindOption['order'] = Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_FIND_TYPE_ORDER');
    $arFindOption['orderuser'] = Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_FIND_TYPE_ORDER').', '.Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_FIND_TYPE_USER');
    $arFindOption['userorder'] = Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_FIND_TYPE_USER').', '.Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_FIND_TYPE_ORDER');

    $propsRes = OrderPropsTable::getList(
        array(
            'select'=>array('ID','NAME','CODE','IS_PHONE'),
            'order'=>array('SORT'=>'DESC'),
            'filter'=>array('!CODE'=>false)
        )
    );
    while($data = $propsRes->fetch()){
        if(!$salePropIsPhone && $data['IS_PHONE']=='Y'){
            $salePropIsPhone = $data['CODE'];
        }
        $saleProps[$data['CODE']] = $data['CODE'].' - '.$data['NAME'];
    }
}

$arComponentParameters['PARAMETERS']['FIND_TYPE'] = array(
    "PARENT" => "DEF",
    "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_FIND_TYPE'),
    "TYPE" => "LIST",
    "MULTIPLE" => "N",
    "VALUES" => $arFindOption,
    "DEFAULT"=>"user"
);

if(!empty($saleProps)){
    if(!$salePropIsPhone) $salePropIsPhone = 'PHONE';
    $arComponentParameters['PARAMETERS']['SALE_PROP'] = array(
        "PARENT" => "DEF",
        "NAME" => Loc::getMessage('AWZ_AUTFORM2_PARAM_LABEL_SALE_PROP'),
        "TYPE" => "LIST",
        "MULTIPLE" => "N",
        "VALUES" => $saleProps,
        "DEFAULT"=>$salePropIsPhone
    );
}