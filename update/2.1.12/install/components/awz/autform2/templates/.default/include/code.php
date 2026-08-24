<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<?if($arResult['VALUES']['step'] == 'code_send'){?>
    <div class="awz-autform2__field_wrap">
        <div class="awz-autform2__return-message"><?=htmlspecialcharsBack($arResult['RCODE_RES']['item']['PRM']['rule_result']['message'])?></div>
        <div class="ui-ctl ui-ctl-textbox ui-ctl-after-icon">
            <div class="ui-ctl-tag"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_CODE_CODE')?></div>
            <input type="text" class="ui-ctl-element" name="code" value="<?=htmlspecialcharsEx($arResult['VALUES']['code'])?>">
        </div>
        <?if($arResult['RCODE_RES']['item']['PRM']['nextCode']){?>
            <div class="awz-autform2__counter_wrap">
                <?if($arResult['RCODE_RES']['item']['PRM']['nextCode']>time()){?>
                    <a id="awz-autform2__repeat_code_button" class="disabled" href="#">
                        <?=Loc::getMessage('AWZ_AUTFORM2_TMPL_CODE_REPEAT')?>
                    </a>
                    <span class="awz-autform2__counter_text"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_CODE_REPEAT_INSTR')?></span>
                    <span id="awz-autform2__counter"></span>
                <?$timeNext = strtotime($arResult['RCODE_RES']['item']['EXPIRED_DATE']);
                if($arResult['RCODE_RES']['item']['PRM']['nextCode'] && ($arResult['RCODE_RES']['item']['PRM']['nextCode'] < $timeNext)){
                    $timeNext = $arResult['RCODE_RES']['item']['PRM']['nextCode'];
                }
                ?>
                    <script>
                        $(document).ready(function(){
                            var endDate = new Date().getTime() + <?=($timeNext-time())?>*1000;
                            $('#awz-autform2__counter').countdown({
                                timestamp: new Date(endDate),
                                showtext: false,
                                callback: function(d, h, m, s){
                                    if((d+h+m)<1 && s<=1){
                                        $('#awz-autform2__counter').hide();
                                        $('.awz-autform2__counter_text').hide();
                                        $('#awz-autform2__repeat_code_button').removeClass('disabled');
                                    }
                                }
                            });
                        });
                    </script>
                <?}?>
            </div>
        <?}?>
    </div>
<?}?>