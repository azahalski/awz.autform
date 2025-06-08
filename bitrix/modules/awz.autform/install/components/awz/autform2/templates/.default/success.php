<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * @var CBitrixComponentTemplate $this
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFolder
 * @var array $arParams
 * @var array $arResult
 */
$randStr = $this->randString();
?>
<div class="awz-autform2-form">
    <div class="awz-autform2-form-border">
<?if($arResult['VALUES']['step'] == 'ok_auth'){?>
    <div class="awz-autform2-form-border-h4">
        <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LOGIN_OK')?>
    </div>
    <?
    if($arParams['PERSONAL_LINK']){?>
    <script>
        setTimeout(function(){
            location.href = '<?=$arParams['PERSONAL_LINK']?>';
        },1000);
    </script>
    <?}?>

<?}?>

<?if($arResult['VALUES']['step'] == 'ok_register'){?>
    <div class="awz-autform2-form-border-h4">
        <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_REGISTER_OK')?>
    </div>
    <?
    if($arParams['PERSONAL_LINK_EDIT']){?>
    <script>
        setTimeout(function(){
            location.href = '<?=$arParams['PERSONAL_LINK_EDIT']?>';
        },1000);
    </script>
    <?}?>

<?}?>
</div>
</div>
