<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
Loc::loadMessages(__FILE__);

#not delete dynamic lang
#test Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_LOGIN_ACTIVE')
#test Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_LOGIN_EMAIL_ACTIVE')
#test Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_LOGIN_SMS_ACTIVE')
#test Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_REGISTER_ACTIVE')
#test Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_REGISTER_SMS_ACTIVE')
$currentMode = $arResult['VALUES']['mode'];

$mergedAut = false;
if($arParams['MERGE_PE']=='Y' &&
    $arParams['LOGIN_SMS_ACTIVE']=="Y" && $arParams['LOGIN_EMAIL_ACTIVE']=="Y"
){
    $mergedAut = true;
}

$mergedReg = false;
if($arParams['MERGE_PE_REG']=='Y' &&
    $arParams['REGISTER_ACTIVE']=="Y" && $arParams['REGISTER_SMS_ACTIVE']=="Y"
){
    $mergedReg = true;
}

?>
    <?
    $items = [];
    foreach($keys as $v){
        $tabName = Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_'.$v);
        if($arParams[$v]!='Y') continue;
        if($mergedAut && $v==='LOGIN_EMAIL_ACTIVE') continue;
        if($mergedReg && $v==='REGISTER_ACTIVE') continue;
        if($mergedAut && $v==='LOGIN_SMS_ACTIVE') {
            $tabName = Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_LOGIN_PE_ACTIVE');
        }
        if($mergedReg && $v==='REGISTER_SMS_ACTIVE') {
            $tabName = Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_LOGIN_PE_ACTIVE');
        }

        $currentActive = ($v==$currentMode);
        if($mergedAut && ($v=='LOGIN_SMS_ACTIVE') && ($currentMode=='LOGIN_EMAIL_ACTIVE'))
            $currentActive = true;
        if($mergedReg && ($v=='REGISTER_ACTIVE') && ($currentMode=='REGISTER_SMS_ACTIVE'))
            $currentActive = true;

        $items[] = [$v, $tabName, $currentActive];
        ?>
    <?}
    ?>
<?
if(count($items)>1){?>
<ul class="awz-autform2__nav">
    <?foreach($items as $item){
        $v = $item[0];
        $tabName = $item[1];
        $active = $item[2];
        ?>
        <li>
            <a data-mode="<?=$v?>" class="awz-autform2__link <?if($active){?>active<?}?>" id="awz-autform2__<?=$v?>_menu" href="#awz_<?=$v?>">
                <span><?=$tabName?></span>
            </a>
        </li>
    <?}?>
</ul>
<?}?>