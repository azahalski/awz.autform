# Настройки отправки смс для AWZ: Форма авторизации через свой обработчик

<!-- settany-start -->

## 1. Регистрируем обработчик, например в init.php

```php
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler(
    'awz.autform', 'onSendSmsCode',
    array('handlersAutForm','onSendSmsCode')
);
```

## 2. Реализуем отправку кода в нашем классе и функции handlersAutForm::onSendSmsCode

В метод setData Bitrix\Main\Result необходимо передать массив с параметрами:

| Параметр | Описание |
|---|---|
| send | необходимо передать константу "ok" |
| message | текст сообщения которое отобразится пользователю на этапе ввода кода подтверждения |


```php
class handlersAutForm {

    public static function onSendSmsCode(Bitrix\Main\Event $event)
    {

        $result = new Bitrix\Main\Result;

        $eventParams = $event->getParameters();

        /* параметры
         * 'phone'=>$phone,
         * 'user'=>$userId,
         * 'code'=>$code,
         * 'params'=>$parameters,
         * 'request'=>$this->request
         * */

        $phone = $eventParams['phone'];
        $phone = preg_replace('/([^0-9])/','', $phone);

        if($phone == '79217776655'){
            $result->addError(new Bitrix\Main\Error('Ваш номер в черном списке'));
        }elseif(strlen($phone)!=11){
            $result->addError(new Bitrix\Main\Error('Номер должен содержать 11 символов'));
        }else{
            $result->setData(array(
                'send'=>'ok',
                'message'=>'Код отправлен, на номер '.$eventParams['phone'].', код: '.$eventParams['code']
            ));
        }

        return new Bitrix\Main\EventResult(
            Bitrix\Main\EventResult::SUCCESS,
            array('result'=>$result)
        );

    }

}
```

Если реализуете поддержку компонента в своем модуле, обязательно учитывайте, что обработчик на отправку кода может быть 1! 
Необходимо наличие опции включения/отключения данного обработчика в настройках ваших модулей.

**Пример 1**<br> (Awz\AutForm\HandleSms::OnAfterAddEvent):
[ссылка](https://github.com/azahalski/awz.autform/blob/main/bitrix/modules/awz.autform/lib/handlesms/aut.php)

**Пример 2**<br> (Awz\AutForm\Handlers::onSendSmsCode):
[ссылка](https://github.com/azahalski/awz.autform/blob/main/bitrix/modules/awz.autform/lib/handlers.php)

<!-- settany-end -->