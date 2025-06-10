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
$keys[] = 'REGISTER_SMS_ACTIVE';
$keys[] = 'REGISTER_ACTIVE';

$merged = false;
if($arParams['MERGE_PE_REG']=='Y' &&
$arParams['REGISTER_ACTIVE']=="Y" && $arParams['REGISTER_SMS_ACTIVE']=="Y"
){
    $merged = true;
}

?>
<div class="awz-autform2__title">
    <span><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_TITLE_2')?></span>
    <?if($arParams['LOGIN_ACTIVE']=='Y' || $arParams['LOGIN_SMS_ACTIVE']=='Y' || $arParams['LOGIN_EMAIL_ACTIVE']=='Y'){?>
        <a data-mode="LOGIN_ACTIVE" class="awz-autform2__link" href="#">
            <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_TITLE_LOGIN')?>
        </a>
    <?}?>
</div>
<div class="awz-autform2__form-border">
    <?include('include/menu.php')?>
    <form class="awz-autform2__form" id="awz-autform2__form">
        <?if(($arResult['VALUES']['mode']=='REGISTER_SMS_ACTIVE' && $arParams['REGISTER_SMS_ACTIVE_NAME'] == 'Y')
        || ($arResult['VALUES']['mode']=='REGISTER_ACTIVE' && $arParams['REGISTER_ACTIVE_NAME'] == 'Y')
            || ($merged && $arParams['REGISTER_SMS_ACTIVE_NAME'] == 'Y')
            || ($merged && $arParams['REGISTER_ACTIVE_NAME'] == 'Y')
        ){?>
            <div class="awz-autform2__field_wrap">
                <div class="ui-ctl ui-ctl-textbox">
                    <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_NAME')?></div>
                    <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" name="name" autocomplete="name" value="<?=htmlspecialcharsEx($arResult['VALUES']['name'])?>">
                </div>
            </div>
        <?}?>
        <?if(($arResult['VALUES']['mode']=='REGISTER_ACTIVE' && $arParams['REGISTER_ACTIVE_SYSLOGIN'] == 'Y')
            || ($merged && $arParams['REGISTER_SMS_ACTIVE_SYSLOGIN'] == 'Y')
            || ($merged && $arParams['REGISTER_ACTIVE_SYSLOGIN'] == 'Y')
        ){?>
            <div class="awz-autform2__field_wrap">
                <div class="ui-ctl ui-ctl-textbox">
                    <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_LOGIN')?></div>
                    <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" name="login" autocomplete="login" value="<?=htmlspecialcharsEx($arResult['VALUES']['login'])?>">
                </div>
            </div>
        <?}?>
        <?if($arResult['VALUES']['mode']=='REGISTER_ACTIVE' && $arParams['REGISTER_ACTIVE_PHONE'] == 'Y'){?>
            <div class="awz-autform2__field_wrap">
                <div class="ui-ctl ui-ctl-textbox">
                    <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PHONE')?></div>
                    <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" name="phone" autocomplete="phone" value="<?=htmlspecialcharsEx($arResult['VALUES']['login'])?>">
                </div>
            </div>
        <?}?>
        <?
        if($merged){
            ?>
            <div class="awz-autform2__field_wrap">
                <div class="ui-ctl ui-ctl-textbox">
                    <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PHONE')?> / <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_EMAIL')?></div>
                    <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" name="phone" autocomplete="phone" value="<?=htmlspecialcharsEx($arResult['VALUES']['phone'])?>">
                </div>
            </div>
            <?
        }else{?>
            <?if($arResult['VALUES']['mode']=='REGISTER_SMS_ACTIVE'){?>
                <div class="awz-autform2__field_wrap">
                    <div class="ui-ctl ui-ctl-textbox">
                        <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PHONE')?></div>
                        <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" name="phone" autocomplete="phone" value="<?=htmlspecialcharsEx($arResult['VALUES']['phone'])?>">
                    </div>
                </div>
            <?}elseif($arResult['VALUES']['mode']=='REGISTER_ACTIVE'){?>
                <div class="awz-autform2__field_wrap">
                    <div class="ui-ctl ui-ctl-textbox">
                        <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_EMAIL')?></div>
                        <input <?if($arResult['VALUES']['step'] == 'code_send'){?>readonly="readonly" <?}?>type="text" class="ui-ctl-element" autocomplete="email" name="email" value="<?=htmlspecialcharsEx($arResult['VALUES']['email'])?>">
                    </div>
                </div>
            <?}?>
        <?}?>
        <?if(($arResult['VALUES']['mode']=='REGISTER_SMS_ACTIVE' && $arParams['REGISTER_SMS_ACTIVE_PSW'] == 'Y')
        || ($arResult['VALUES']['mode']=='REGISTER_ACTIVE' && $arParams['REGISTER_ACTIVE_PSW'] == 'Y')
            || ($merged && $arParams['REGISTER_SMS_ACTIVE_PSW'] == 'Y')
            || ($merged && $arParams['REGISTER_ACTIVE_PSW'] == 'Y')
        ){?>
            <div class="awz-autform2__field_wrap">
                <div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon">
                    <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PASSWORD')?></div>
                    <input type="password" class="ui-ctl-element" name="password" value="<?=htmlspecialcharsEx($arResult['VALUES']['password'])?>">
                    <div class="ui-ctl-after awz-autform2__ui-ctl-icon-password-show"></div>
                </div>
            </div>
            <div class="awz-autform2__field_wrap">
                <div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon">
                    <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_PASSWORD2')?></div>
                    <input type="password" class="ui-ctl-element" name="password2" value="<?=htmlspecialcharsEx($arResult['VALUES']['password2'])?>">
                    <div class="ui-ctl-after awz-autform2__ui-ctl-icon-password-show"></div>
                </div>
            </div>
        <?}?>
        <?if($arResult['VALUES']['mode']=='REGISTER_ACTIVE' && $arParams['REGISTER_ACTIVE_DSBL_CODE']=='Y'){?>
        <?}else{?>
        <?include_once('include/code.php');?>
        <?}?>
        <?include('include/agr.php')?>
        <input type="hidden" name="send" value="Y">
        <input type="hidden" name="mode" value="<?=$arResult['VALUES']['mode']?>">
        <input type="hidden" name="step" value="<?=$arResult['VALUES']['step']?>">
        <div class="awz-autform2__err">
            <?if(!empty($arResult['ERRORS'])){?>
                <?foreach($arResult['ERRORS'] as $err){?>
                    <p><?=$err[0]?></p>
                <?}?>
            <?}?>
        </div>
        <?if($arResult['VALUES']['mode']=='REGISTER_ACTIVE' && $arParams['REGISTER_ACTIVE_DSBL_CODE']=='Y'){?>
            <div class="awz-autform2__btn_wrap">
                <button class="awz-autform2__btn ui-btn ui-btn-primary" type="submit" name="submit">
                    <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_BTN_REGISTER')?>
                </button>
            </div>
        <?}elseif($arResult['VALUES']['step'] == 'code_send'){?>
            <div class="awz-autform2__btn_wrap">
                <button class="awz-autform2__btn ui-btn ui-btn-primary" type="submit" name="submit">
                    <?=$arResult['RCODE_RES']['item']['PRM']['rule_result']['button'] ?? Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_SEND_CODE')?>
                </button>
            </div>
        <?}else{?>
            <?if(in_array($arResult['VALUES']['mode'], ['REGISTER_ACTIVE','REGISTER_SMS_ACTIVE'])){?>
                <div class="awz-autform2__btn_wrap">
                    <button class="awz-autform2__btn ui-btn ui-btn-primary" type="submit" name="submit">
                        <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LABEL_GET_CODE')?>
                    </button>
                </div>
            <?}?>
        <?}?>
    </form>
</div>