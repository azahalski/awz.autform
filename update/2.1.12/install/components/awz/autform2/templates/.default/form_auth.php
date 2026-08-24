<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

Loc::loadLanguageFile(__DIR__.'/template.php');
Loc::loadMessages(__FILE__);

/**
 * @var CBitrixComponentTemplate $this
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFolder
 * @var array $arParams
 * @var array $arResult
 */
$keys[] = 'LOGIN_ACTIVE';
$keys[] = 'LOGIN_SMS_ACTIVE';
$keys[] = 'LOGIN_EMAIL_ACTIVE';

$merged = false;
if($arParams['MERGE_PE']=='Y' &&
    $arParams['LOGIN_SMS_ACTIVE']=="Y" && $arParams['LOGIN_EMAIL_ACTIVE']=="Y"
){
    $merged = true;
}
$showRegisterLink = $arParams['REGISTER_ACTIVE']=='Y' || $arParams['REGISTER_SMS_ACTIVE']=='Y';
if($arParams['REGISTER_LOGIN']=='Y' && $arParams['LOGIN_REGISTER']=='Y'){
    $showRegisterLink = false;
}

?>
<div class="awz-autform2__title">
    <span><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_TITLE_1')?></span>
    <?if($showRegisterLink){?>
        <a data-mode="REGISTER_SMS_ACTIVE" class="awz-autform2__link" href="#">
            <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_TITLE_REGISTER')?>
        </a>
    <?}?>
</div>
<div class="awz-autform2__form-border">
    <?include('include/menu.php')?>
    <form class="awz-autform2__form" id="awz-autform2__form">
        <?if($arResult['VALUES']['mode']=='LOGIN_ACTIVE'){
            $names = [];
            if($arParams['CHECK_LOGIN']=='Y') $names[] = Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_LOGIN');
            if($arParams['CHECK_EMAIL']=='Y') $names[] = Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_EMAIL');
            if($arParams['CHECK_PHONE']=='Y') $names[] = Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PHONE');
            ?>
        <div class="awz-autform2__field_wrap">
            <div class="ui-ctl ui-ctl-textbox">
                <div class="ui-ctl-tag"><?=implode(" / ", $names)?></div>
                <input type="text" class="ui-ctl-element" name="login" autocomplete="username" value="<?=htmlspecialcharsEx($arResult['VALUES']['login'])?>">
            </div>
        </div>
        <?}?>
        <?if($merged && $arResult['VALUES']['mode']!='LOGIN_ACTIVE'){
            ?>
            <div class="awz-autform2__field_wrap">
                <div class="ui-ctl ui-ctl-textbox">
                    <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PHONE')?> / <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_EMAIL')?></div>
                    <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" name="phone" autocomplete="phone" value="<?=htmlspecialcharsEx($arResult['VALUES']['phone'])?>">
                </div>
            </div>
            <?
        }else{?>
            <?if($arResult['VALUES']['mode']=='LOGIN_SMS_ACTIVE'){?>
                <div class="awz-autform2__field_wrap">
                    <div class="ui-ctl ui-ctl-textbox">
                        <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PHONE')?></div>
                        <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" name="phone" autocomplete="phone" value="<?=htmlspecialcharsEx($arResult['VALUES']['phone'])?>">
                    </div>
                </div>
            <?}?>
            <?if($arResult['VALUES']['mode']=='LOGIN_EMAIL_ACTIVE'){?>
                <div class="awz-autform2__field_wrap">
                    <div class="ui-ctl ui-ctl-textbox">
                        <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_EMAIL')?></div>
                        <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" autocomplete="email" name="email" value="<?=htmlspecialcharsEx($arResult['VALUES']['email'])?>">
                    </div>
                </div>
            <?}?>
        <?}?>
        <?if($arResult['VALUES']['mode']=='LOGIN_ACTIVE'){?>
        <div class="awz-autform2__field_wrap">
            <div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon">
                <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PASSWORD')?></div>
                <input type="password" class="ui-ctl-element" name="password" value="<?=htmlspecialcharsEx($arResult['VALUES']['password'])?>">
                <div class="ui-ctl-after awz-autform2__ui-ctl-icon-password-show"></div>
            </div>
        </div>
        <?}?>
        <?include_once('include/code.php');?>
        <?include('include/agr.php')?>
        <input type="hidden" name="send" value="Y">
        <input type="hidden" name="mode" value="<?=htmlspecialcharsEx($arResult['VALUES']['mode'])?>">
        <input type="hidden" name="step" value="<?=htmlspecialcharsEx($arResult['VALUES']['step'])?>">
        <div class="awz-autform2__err">
            <?if(!empty($arResult['ERRORS'])){?>
                <?foreach($arResult['ERRORS'] as $err){?>
                    <p><?=$err[0]?></p>
                <?}?>
            <?}?>
        </div>
        <?if($arResult['VALUES']['step'] == 'code_send'){?>
            <div class="awz-autform2__btn_wrap">
                <button class="awz-autform2__btn ui-btn ui-btn-primary" type="submit" name="submit">
                    <?=$arResult['RCODE_RES']['item']['PRM']['rule_result']['button'] ?? Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_SEND_CODE')?>
                </button>
            </div>
        <?}else{?>
            <?if(in_array($arResult['VALUES']['mode'], ['LOGIN_EMAIL_ACTIVE','LOGIN_SMS_ACTIVE'])){?>
                <div class="awz-autform2__btn_wrap">
                    <button class="awz-autform2__btn ui-btn ui-btn-primary" type="submit" name="submit">
                        <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_GET_CODE')?>
                    </button>
                </div>
            <?}?>
            <?if($arResult['VALUES']['mode']=='LOGIN_ACTIVE'){?>
                <div class="awz-autform2__btn_wrap">
                    <button class="awz-autform2__btn ui-btn ui-btn-primary" type="submit" name="submit">
                        <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_SEND_BTN')?>
                    </button>
                </div>
            <?}?>
        <?}?>

    </form>
</div>