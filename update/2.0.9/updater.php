<?
$moduleId = "awz.autform";
if(IsModuleInstalled($moduleId)) {
    $updater->CopyFiles(
        "install/components",
        "components"
    );
}