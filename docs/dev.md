# Для разработчиков
<!-- dev-start -->

Для более быстрого поиска по свойствам заказа рекомендуется создать индекс

```sql
create index AWZ_AUTFORM_IX_CODE_VALUE ON b_sale_order_props_value (CODE, VALUE);
```

## Пример подключение формы в модальное окно bootstrap

```php
<?
// поддержка композита
$dynamicArea = new \Bitrix\Main\Composite\StaticArea("awz_login_form2");
$dynamicArea->startDynamicArea();
if(\Bitrix\Main\Engine\CurrentUser::get()?->getId()){
//тут вывод для авторизованного пользователя (например ссылки на кабинет)
}else{
//кнопка вызова модального окна
?>
<a style="margin-bottom:2em;" class="btn btn-primary" href="#" data-target="#autform" data-toggle="modal">Войти в личный кабинет</a>
<?}?>
<div id="autform" class=" modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title">Авторизация</h4>
            </div>
            <div class="modal-body">
                <?$APPLICATION->IncludeComponent(
                    "awz:autform2",
                    ".default",
                    Array(
                        "AGR_ANCOR" => "условиями обработки",
                        "AGR_LINK" => "/",
                        "AGR_SET" => "N",
                        "AGR_TITLE" => "Согласен с #LINK# персональных данных",
                        "CHECK_EMAIL" => "Y",
                        "CHECK_LOGIN" => "Y",
                        "CHECK_PHONE" => "Y",
                        "COUNTRY_CODE" => "7",
                        "FIND_TYPE" => "orderuser",
                        "LOGIN_ACTIVE" => "Y",
                        "LOGIN_EMAIL_ACTIVE" => "N",
                        "LOGIN_EMAIL_GROUPS" => array(),
                        "LOGIN_EMAIL_GROUPS_DEL" => array(),
                        "LOGIN_GROUPS" => array(
                            0 => "5",
                            1 => "9",
                            2 => "10",
                            3 => "12",
                            4 => "17",
                            5 => "49",
                        ),
                        "LOGIN_GROUPS_DEL" => array(
                            0 => "1",
                        ),
                        "LOGIN_GROUPS_DEL2" => array(
                            0 => "5",
                            1 => "9",
                            2 => "10",
                            3 => "12",
                            4 => "17",
                            5 => "49",
                        ),
                        "LOGIN_GROUPS_DEL3" => "646642,626268,633638",
                        "LOGIN_REGISTER" => "Y",
                        "LOGIN_SMS_ACTIVE" => "Y",
                        "LOGIN_SMS_GROUPS" => array(
                            0 => "5",
                            1 => "9",
                            2 => "10",
                            3 => "12",
                        ),
                        "LOGIN_SMS_GROUPS_DEL" => array(
                            0 => "1",
                        ),
                        "MERGE_PE" => "N",
                        "MERGE_PE_REG" => "N",
                        "PERSONAL_LINK" => "/personal/",
                        "PERSONAL_LINK_EDIT" => "/personal/profile/",
                        "REGISTER_ACTIVE" => "N",
                        "REGISTER_ACTIVE_DSBL_CODE" => "N",
                        "REGISTER_ACTIVE_NAME" => "N",
                        "REGISTER_ACTIVE_PHONE" => "N",
                        "REGISTER_ACTIVE_PSW" => "N",
                        "REGISTER_ACTIVE_SYSLOGIN" => "N",
                        "REGISTER_GROUPS" => array(
                            0 => "5",
                        ),
                        "REGISTER_LOGIN" => "Y",
                        "REGISTER_SMS_ACTIVE" => "Y",
                        "REGISTER_SMS_ACTIVE_NAME" => "N",
                        "REGISTER_SMS_ACTIVE_PSW" => "N",
                        "REGISTER_SMS_ACTIVE_SYSLOGIN" => "N",
                        "REGISTER_SMS_GROUPS" => array("5"),
                        "SALE_PROP" => "PHONE",
                        "THEME" => "blue",
                        "COMPONENT_TEMPLATE" => ".default"
                    )
                );?>
            </div>
        </div>
    </div>
</div>
<script>
//обработчик подминит заголовки нашей модалки
BX.addCustomEvent('awz.autform2.resize', BX.delegate(function(data){
    $("#autform .modal-title").html($('.awz-autform2__title span').text());
}, document));
</script>
<style>
/* скрываем заголовок и рамку стандартной формы */
#autform .awz-autform2__title {display:none;}
#autform .awz-autform2__form-border {border:none;}
#autform {
    --ui-color-palette-blue-50: #2C5DA7;
}
</style>
```

<!-- dev-end -->