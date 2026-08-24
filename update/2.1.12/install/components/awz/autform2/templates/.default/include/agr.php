<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}?>
<?
if($arParams['AGR_TITLE']){
    ?>
    <div class="awz-autform2__agr_wrap">
        <label class="awz-autform2__agrement">
            <input class="awz-autform2__agrement-checkbox" type="checkbox" value="Y" name="oferta"<?if($arResult['VALUES']['oferta']=="Y"){?> checked="checked"<?}?> autocomplete="off">
            <span class="awz-autform2__agrement-checkbox-checkmark"></span>
            <span class="awz-autform2__agrement-checkbox-text">
                <?=htmlspecialcharsBack($arParams['AGR_TITLE'])?>
            </span>
        </label>
    </div>
    <?
}
?>