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

$agList = Agreement::getActiveList();
$agList[] = Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_AGREEMENT_DSBL');

$arComponentParameters = array(
    "GROUPS" => array(
        "DEF" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_GROUP_DEF'),
        ),
        "LOGIN" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_GROUP_LOGIN'),
        ),
        "LOGIN_SMS" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_GROUP_LOGIN_SMS'),
        ),
        "REGISTER" => array(
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_GROUP_REGISTER'),
        ),
    ),
    "PARAMETERS" => array(
        "COUNTRY_CODE"=>array(
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_COUNTRY_CODE'),
            "TYPE" => "STRING",
            "DEFAULT"=>"7"
        ),
        "PERSONAL_LINK"=>array(
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_PERSONAL_LINK'),
            "TYPE" => "STRING",
            "DEFAULT"=>"/personal/"
        ),
        "AGREEMENT"=>array(
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_AGREEMENT'),
            "TYPE" => "LIST",
            "VALUES" => $agList,
            "MULTIPLE" => "N",
            "DEFAULT" => "",
        ),
        "MODE_MESS"=>array(
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_MODE_MESS'),
            "TYPE" => "LIST",
            "VALUES" => array(
                'sms'=>Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_MODE_MESS_SMS'),
                'call'=>Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_MODE_MESS_CALL')
            ),
            "MULTIPLE" => "N",
            "DEFAULT" => "",
        ),
        "LOGIN_GROUPS_DEL2" => array(
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_LOGIN_GROUPS_DEL2'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ),
        "LOGIN_GROUPS_DEL3" => array(
            "PARENT" => "DEF",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_LOGIN_GROUPS_DEL3'),
            "TYPE" => "STRING",
            "VALUE" => "1",
        ),
        "LOGIN_GROUPS" => array(
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_LOGIN_GROUPS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ),
        "LOGIN_GROUPS_DEL" => array(
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_LOGIN_GROUPS_DEL'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ),
        "LOGIN_SMS_GROUPS" => array(
            "PARENT" => "LOGIN_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_LOGIN_SMS_GROUPS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ),
        "LOGIN_SMS_GROUPS_DEL" => array(
            "PARENT" => "LOGIN_SMS",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_LOGIN_SMS_GROUPS_DEL'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ),
        "REGISTER_GROUPS" => array(
            "PARENT" => "REGISTER",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_REGISTER_GROUPS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arUserGroup,
        ),
        "REGISTER_LOGIN" => array(
            "PARENT" => "REGISTER",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_REGISTER_LOGIN'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
        "CHECK_LOGIN" => array(
            "PARENT" => "LOGIN",
            "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_CHECK_LOGIN'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ),
    ),
);

$arFindOption = array(
    'user'=>Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_FIND_TYPE_USER')
);
$saleProps = array();
$salePropIsPhone = false;
if(Loader::includeModule('sale')){
    $arFindOption['order'] = Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_FIND_TYPE_ORDER');
    $arFindOption['orderuser'] = Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_FIND_TYPE_ORDER').', '.Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_FIND_TYPE_USER');
    $arFindOption['userorder'] = Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_FIND_TYPE_USER').', '.Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_FIND_TYPE_ORDER');

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
    "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_FIND_TYPE'),
    "TYPE" => "LIST",
    "MULTIPLE" => "N",
    "VALUES" => $arFindOption,
    "DEFAULT"=>"user"
);

if(!empty($saleProps)){
    if(!$salePropIsPhone) $salePropIsPhone = 'PHONE';
    $arComponentParameters['PARAMETERS']['SALE_PROP'] = array(
        "PARENT" => "DEF",
        "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_SALE_PROP'),
        "TYPE" => "LIST",
        "MULTIPLE" => "N",
        "VALUES" => $saleProps,
        "DEFAULT"=>$salePropIsPhone
    );
}