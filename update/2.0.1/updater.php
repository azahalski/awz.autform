<?
$moduleId = "awz.autform";
if(IsModuleInstalled($moduleId)) {
    $updater->CopyFiles(
        "install/components",
        "components"
    );
	\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
		'main', 'OnGetCurrentSiteTemplate',
		$moduleId, '\Awz\AutForm\HandlersV2', 'OnGetCurrentSiteTemplate'
	);
}