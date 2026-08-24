<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

$arTemplateParameters = array(
    'THEME'=>array(
        "PARENT" => "DEF",
        "NAME" => Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_THEME'),
        "TYPE" => "LIST",
        "DEFAULT"=>"red",
        "VALUES" => array(
            'red'=>Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_THEME_RED'),
            'blue'=>Loc::getMessage('AWZ_AUTFORM_PARAM_LABEL_THEME_BLUE'),
        ),
    )
);