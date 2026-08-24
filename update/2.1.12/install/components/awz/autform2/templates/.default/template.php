<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
    die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

$this->setFrameMode(true);
/**
 * @var CBitrixComponentTemplate $this
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFolder
 * @var array $arParams
 */
$randStr = 'awz_autform_cmp_'.$this->randString();
$autFormId = 'awz_autform_'.$this->randString();
$arParams['autFormId'] = $autFormId;
$keys = [];

if($arParams['TEMPLATE_FILE']=='form_auth' || $arParams['TEMPLATE_FILE']=='form_register' || $arParams['TEMPLATE_FILE']=='success'){
    include($arParams['TEMPLATE_FILE'].'.php');
    return;
}

use Bitrix\Main\Page\Asset;
CJSCore::Init(['ajax', 'jquery3']);
Asset::getInstance()->addCss($templateFolder.'/theme/'.$arParams['THEME'].'.css');
Asset::getInstance()->addJs($templateFolder.'/js/jquery.timer.js');
\Bitrix\Main\UI\Extension::load("ui.forms");
\Bitrix\Main\UI\Extension::load("ui.buttons");

$signer = new \Bitrix\Main\Security\Sign\Signer();

$options = array(
    'siteId'=>Application::getInstance()->getContext()->getSite(),
    'templateId'=>$signer->sign(SITE_TEMPLATE_ID, 'awz.autform'),
    'templateName'=>$this->getComponent()->getTemplateName(),
    'templateFolder'=>$templateFolder,
    'componentName'=>$this->getComponent()->getName(),
    'signedParameters'=>$this->getComponent()->getSignedParameters(),
    'theme'=>$arParams['THEME'],
    'autFormId'=>$autFormId,
    'loadFormNodeId'=>$autFormId.'__autform_block',
    'lang'=>[],
    'ajaxTimer'=>0
);
?>
<div id="<?=$autFormId?>" class="awz-autform2__link-block">
    <?php
    /** @var \Bitrix\Main\Page\FrameBuffered $frame */
    $frame = $this->createFrame($autFormId, false)->begin();
    ?>
    <?if(\Bitrix\Main\Engine\CurrentUser::get()?->getId()){?>
        <a href="<?=$arParams['PERSONAL_LINK']?>"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_PERSONAL_LINK')?></a>
    <?}else{?>
        <?if(false){?><a class="awz-autform2-aut-link" data-mode="" id="<?=$autFormId?>_lnk" href="#"><?=Loc::getMessage('AWZ_AUTFORM2_TMPL_LINK')?></a><?}?>
        <div id="<?=$options['loadFormNodeId']?>"></div>
    <?}?>
    <?
    $frame->beginStub();
    ?>
    <div id="<?=$randStr?>"></div>
    <?
    $frame->end();
    ?>
    <script type="text/javascript">
        var <?=$autFormId?> = new window.AwzAutFormComponentV2(<?=CUtil::PHPToJSObject($options)?>);
    </script>
</div>