# Внедрение AWZ формы авторизации в aspro max

# 1. копируем шаблон компонента формы в свое пространство имен

если шаблон аспро расположено по пути `/bitrix/templates/aspro_max/`, то копируем

содержимое папки `/bitrix/components/awz/autform2/templates/.default/` в 
`/bitrix/templates/aspro_max/components/awz/autform2/.default/`

копируем стандартный шаблон авторизации:

содержимое папки `/bitrix/components/bitrix/system.auth.authorize/templates/.default/` в
`/bitrix/templates/aspro_max/components/bitrix/system.auth.authorize/.default/`

# 2. Добавляем подключение компонента в стандартный шаблон

`/bitrix/templates/aspro_max/components/bitrix/system.auth.authorize/.default/template.php`

код

```php 
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
```

заменить на

```php 
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$this->setFrameMode(false);
$page = $_REQUEST['backurl'] ? $_REQUEST['backurl'] : $APPLICATION->GetCurPage(false);
if(strpos($page, '/bitrix/')!==false){
    $page = $_SERVER["HTTP_REFERER"];
}
?>
<style>.auth-page-awz{margin-bottom:55px;}
</style>
<div class="auth-page pk-page auth-page-awz">
    <div class="auth_wrapp">
        <div class="wrap_md1">
            <div class="form_body">
                <?$APPLICATION->IncludeComponent(
                        "awz:autform2",
                        ".default",
                        Array(
                                "COMPONENT_TEMPLATE" => ".default",
                                "AGR_ANCOR" => "публичной оферты, пользовательского соглашения",
                                "AGR_LINK" => "/info/more/",
                                "AGR_SET" => "N",
                                "AGR_TITLE" => "Условия #LINK#",
                                "CHECK_EMAIL" => "N",
                                "CHECK_LOGIN" => "N",
                                "CHECK_PHONE" => "Y",
                                "COMPOSITE_FRAME_MODE" => "A",
                                "COMPOSITE_FRAME_TYPE" => "AUTO",
                                "COUNTRY_CODE" => "375",
                                "FIND_TYPE" => "orderuser",
                                "LOGIN_ACTIVE" => "N",
                                "LOGIN_EMAIL_ACTIVE" => "N",
                                "LOGIN_EMAIL_GROUPS" => array(),
                                "LOGIN_EMAIL_GROUPS_DEL" => array(),
                                "LOGIN_GROUPS" => array("6"),
                                "LOGIN_GROUPS_DEL" => array("1"),
                                "LOGIN_GROUPS_DEL2" => array("6"),
                                "LOGIN_GROUPS_DEL3" => "",
                                "LOGIN_REGISTER" => "Y",
                                "LOGIN_SMS_ACTIVE" => "Y",
                                "LOGIN_SMS_GROUPS" => array("6"),
                                "LOGIN_SMS_GROUPS_DEL" => array("1"),
                                "MERGE_PE" => "N",
                                "MERGE_PE_REG" => "N",
                                "PERSONAL_LINK" => $page,
                                "PERSONAL_LINK_EDIT" => $page,
                                "REGISTER_ACTIVE" => "N",
                                "REGISTER_ACTIVE_DSBL_CODE" => "N",
                                "REGISTER_ACTIVE_NAME" => "N",
                                "REGISTER_ACTIVE_PHONE" => "N",
                                "REGISTER_ACTIVE_PSW" => "N",
                                "REGISTER_ACTIVE_SYSLOGIN" => "N",
                                "REGISTER_GROUPS" => array(),
                                "REGISTER_LOGIN" => "Y",
                                "REGISTER_SMS_ACTIVE" => "Y",
                                "REGISTER_SMS_ACTIVE_NAME" => "N",
                                "REGISTER_SMS_ACTIVE_PSW" => "N",
                                "REGISTER_SMS_ACTIVE_SYSEMAIL" => "N",
                                "REGISTER_SMS_ACTIVE_SYSLOGIN" => "N",
                                "REGISTER_SMS_GROUPS" => array("6"),
                                "REM_ME" => "Y",
                                "SALE_PROP" => "PHONE",
                                "THEME" => "blue"
                        )
                );?>
            </div>
        </div>
    </div>
</div>
<?
return;
?>
```

# 2. Добавляем подключение компонента в шаблон aspro max

`/bitrix/templates/aspro_max/components/bitrix/system.auth.form/main/template.php`

код

```php 
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
```

заменить на

```php 
<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
$this->setFrameMode(false);
$page = $_REQUEST['backurl'] ? $_REQUEST['backurl'] : $APPLICATION->GetCurPage(false);
if(strpos($page, '/bitrix/')!==false){
    $page = $_SERVER["HTTP_REFERER"];
}
?>
<style>.auth-page-awz{margin-bottom:55px;}</style>
    <div class="auth-page pk-page auth-page-awz">
    <div class="auth_wrapp">
    <div class="wrap_md1">
        <div class="form_body">
<?$APPLICATION->IncludeComponent(
    "awz:autform2",
    ".default",
    Array(
        "COMPONENT_TEMPLATE" => ".default",
        "AGR_ANCOR" => "публичной оферты, пользовательского соглашения",
        "AGR_LINK" => "/info/more/",
        "AGR_SET" => "N",
        "AGR_TITLE" => "Условия #LINK#",
        "CHECK_EMAIL" => "N",
        "CHECK_LOGIN" => "N",
        "CHECK_PHONE" => "Y",
        "COMPOSITE_FRAME_MODE" => "A",
        "COMPOSITE_FRAME_TYPE" => "AUTO",
        "COUNTRY_CODE" => "375",
        "FIND_TYPE" => "orderuser",
        "LOGIN_ACTIVE" => "N",
        "LOGIN_EMAIL_ACTIVE" => "N",
        "LOGIN_EMAIL_GROUPS" => array(),
        "LOGIN_EMAIL_GROUPS_DEL" => array(),
        "LOGIN_GROUPS" => array("6"),
        "LOGIN_GROUPS_DEL" => array("1"),
        "LOGIN_GROUPS_DEL2" => array("6"),
        "LOGIN_GROUPS_DEL3" => "",
        "LOGIN_REGISTER" => "Y",
        "LOGIN_SMS_ACTIVE" => "Y",
        "LOGIN_SMS_GROUPS" => array("6"),
        "LOGIN_SMS_GROUPS_DEL" => array("1"),
        "MERGE_PE" => "N",
        "MERGE_PE_REG" => "N",
        "PERSONAL_LINK" => $page,
        "PERSONAL_LINK_EDIT" => $page,
        "REGISTER_ACTIVE" => "N",
        "REGISTER_ACTIVE_DSBL_CODE" => "N",
        "REGISTER_ACTIVE_NAME" => "N",
        "REGISTER_ACTIVE_PHONE" => "N",
        "REGISTER_ACTIVE_PSW" => "N",
        "REGISTER_ACTIVE_SYSLOGIN" => "N",
        "REGISTER_GROUPS" => array(),
        "REGISTER_LOGIN" => "Y",
        "REGISTER_SMS_ACTIVE" => "Y",
        "REGISTER_SMS_ACTIVE_NAME" => "N",
        "REGISTER_SMS_ACTIVE_PSW" => "N",
        "REGISTER_SMS_ACTIVE_SYSEMAIL" => "N",
        "REGISTER_SMS_ACTIVE_SYSLOGIN" => "N",
        "REGISTER_SMS_GROUPS" => array(),
        "REM_ME" => "Y",
        "SALE_PROP" => "PHONE",
        "THEME" => "blue"
    )
);?>
    </div>
    </div>
    </div>
    </div>
<?
return;
?>
```