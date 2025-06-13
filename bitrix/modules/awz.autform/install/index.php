<?php
use Bitrix\Main\Localization\Loc,
    Bitrix\Main\EventManager,
    Bitrix\Main\ModuleManager,
    Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class awz_autform extends CModule {

    var $MODULE_ID = "awz.autform";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;

    var $errors = false;

    public function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__.'/version.php');

        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $this->MODULE_NAME = Loc::getMessage("AWZ_AUTFORM_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("AWZ_AUTFORM_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("AWZ_PARTNER_NAME");
        $this->PARTNER_URI = "https://zahalski.dev/";
    }

    function DoInstall()
    {
        global $APPLICATION, $step;

        $this->InstallFiles();
        $this->InstallDB();
        $this->checkOldInstallTables();
        $this->InstallEvents();
        $this->createAgents();

        ModuleManager::RegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("AWZ_AUTFORM_MODULE_NAME"),
            $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'. $this->MODULE_ID .'/install/solution.php'
        );

        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION, $step;

        $step = intval($step);
        if($step < 2) {
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('AWZ_AUTFORM_INSTALL_TITLE'),
                $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'. $this->MODULE_ID .'/install/unstep.php'
            );
        }
        elseif($step == 2) {
            if($_REQUEST['save'] != 'Y' && !isset($_REQUEST['save'])) {
                $this->UnInstallDB();
            }
            $this->UnInstallFiles();
            $this->UnInstallEvents();
            $this->deleteAgents();

            if($_REQUEST['saveopts'] != 'Y' && !isset($_REQUEST['saveopts'])) {
                \Bitrix\Main\Config\Option::delete($this->MODULE_ID);
            }

            ModuleManager::UnRegisterModule($this->MODULE_ID);
            return true;
        }
    }

    function InstallDB()
    {
        global $DB, $DBType, $APPLICATION;
        $connection = \Bitrix\Main\Application::getConnection();
        $this->errors = false;
        if(!$this->errors && !$DB->TableExists(implode('_', explode('.',$this->MODULE_ID)).'_codes')) {
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/".$connection->getType()."/install.sql");
        }
        if (!$this->errors) {
            return true;
        } else {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return $this->errors;
        }
    }


    function UnInstallDB()
    {
        global $DB, $DBType, $APPLICATION;
        $connection = \Bitrix\Main\Application::getConnection();
        $this->errors = false;
        if (!$this->errors) {
            $this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/" . $this->MODULE_ID . "/install/db/" . $connection->getType() . "/uninstall.sql");
        }
        if (!$this->errors) {
            return true;
        }
        else {
            $APPLICATION->ThrowException(implode("", $this->errors));
            return $this->errors;
        }
    }


    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            'mlife.smsservices', 'OnAfterEventsAdd',
            $this->MODULE_ID, '\Awz\AutForm\Handlers', 'onAfterEventsAdd'
        );
        $eventManager->registerEventHandler(
            'main', 'OnGetCurrentSiteTemplate',
            $this->MODULE_ID, '\Awz\AutForm\HandlersV2', 'OnGetCurrentSiteTemplate'
        );
        $eventManager->registerEventHandler(
            $this->MODULE_ID, 'checkPhone',
            $this->MODULE_ID, '\Awz\AutForm\Handlers', 'checkPhone'
        );
        $eventManager->registerEventHandler(
            $this->MODULE_ID, 'onSendSmsCode',
            $this->MODULE_ID, '\Awz\AutForm\Handlers', 'onSendSmsCode'
        );
        $eventManager->registerEventHandler(
            $this->MODULE_ID, 'onCheckCode',
            $this->MODULE_ID, '\Awz\AutForm\Handlers', 'onCheckCode'
        );

        return true;
    }

    function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'mlife.smsservices', 'OnAfterEventsAdd',
            $this->MODULE_ID, '\Awz\AutForm\Handlers', 'onAfterEventsAdd'
        );
        $eventManager->unRegisterEventHandler(
            'main', 'OnGetCurrentSiteTemplate',
            $this->MODULE_ID, '\Awz\AutForm\HandlersV2', 'OnGetCurrentSiteTemplate'
        );
        $eventManager->unRegisterEventHandler(
            $this->MODULE_ID, 'checkPhone',
            $this->MODULE_ID, '\Awz\AutForm\Handlers', 'checkPhone'
        );
        $eventManager->unRegisterEventHandler(
            $this->MODULE_ID, 'onSendSmsCode',
            $this->MODULE_ID, '\Awz\AutForm\Handlers', 'onSendSmsCode'
        );
        $eventManager->unRegisterEventHandler(
            $this->MODULE_ID, 'onCheckCode',
            $this->MODULE_ID, '\Awz\AutForm\Handlers', 'onCheckCode'
        );

        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/components/awz/autform/", $_SERVER['DOCUMENT_ROOT']."/bitrix/components/awz/autform", true, true);
        CopyDirFiles($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/".$this->MODULE_ID."/install/components/awz/autform2/", $_SERVER['DOCUMENT_ROOT']."/bitrix/components/awz/autform", true, true);
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFilesEx("/bitrix/components/awz/autform");
        DeleteDirFilesEx("/bitrix/components/awz/autform2");
        return true;
    }

    function createAgents() {

        CAgent::AddAgent(
            "\\Awz\\AutForm\\Agents::agentDeleteOldCodes();",
            $this->MODULE_ID,
            "N",
            86400);
        CAgent::AddAgent(
            "\\Awz\\AutForm\\LogTable::clearOld();",
            $this->MODULE_ID,
            "N",
            86400);

        return true;
    }

    function deleteAgents() {
        CAgent::RemoveModuleAgents("sale");
        return true;
    }

	function checkOldInstallTables(){
		
		return true;
		
	}
}