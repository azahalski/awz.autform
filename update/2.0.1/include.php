<?php
//not remove this comment, fix empty include.php
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Awz\AutForm\Events;
use Bitrix\Main\EventResult;

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler(
    'awz.autform', 'checkPhoneV2',
    ["\\Awz\\AutForm\\HandlersV2", "checkPhone"]
);

$eventManager->addEventHandler(
    'awz.autform', 'onCheckCodeV2',
    ["\\Awz\\AutForm\\HandlersV2", "onCheckCode"]
);

$eventManager->addEventHandler(
    'awz.autform', 'onGenerateCode',
    ["\\Awz\\AutForm\\HandlersV2", "onGenerateCode"]
);
$eventManager->addEventHandler(
    'awz.autform', 'checkRule',
    ["\\Awz\\AutForm\\HandlersV2", "checkRule"]
);

$eventManager->addEventHandler(
    'awz.autform', 'checkEmailV2',
    ["\\Awz\\AutForm\\HandlersV2", 'checkEmail']
);

$eventManager->addEventHandler(
    'awz.autform', 'buildRules',
    ["\\Awz\\AutForm\\HandlersV2", "buildRules"]
);