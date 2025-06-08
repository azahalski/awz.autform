<?php

namespace Awz\AutForm;

class Events {

    const MODULE_ID  = 'awz.autform';

    /**
     * Отправка смс кода
     */
    const SEND_SMS_CODE = 'onSendSmsCode';

    /**
     * Отправка смс кода
     */
    const SEND_CODE = 'onSendCode';

    /**
     * Проверка и форматирование номера телефона
     */
    const CHECK_PHONE = 'checkPhone';

    /**
     * Проверка и форматирование номера телефона
     */
    const CHECK_PHONE_V2 = 'checkPhoneV2';

    /**
     * Проверка и форматирование email
     */
    const CHECK_EMAIL = 'checkEmailV2';

    /**
     * Создание массива номеров для поиска
     */
    const AFTER_CREATE_PHONES = 'onAfterCreatePhones';

    /**
     * Переопределение поиска ид юзера
     */
    const FIND_USER = 'onFindUser';

    /**
     * Переопределение поиска ид юзера по логину
     */
    const FIND_USER_FROM_LOGIN = 'onFindUserLogin';

    /**
     * Переопределение поиска ид юзера по логину
     */
    const FIND_USER_FROM_EMAIL = 'onFindUserEmail';

    /**
     * Переопределение поиска ид юзера по логину
     */
    const FIND_USER_FROM_PHONE = 'onFindUserPhone';

    /**
     * Своя проверка лимитов
     */
    const CHECK_LIMITS = 'onCheckLimits';

    /**
     * Своя проверка кода v1
     */
    const CHECK_CODE = 'onCheckCode';

    /**
     * Своя проверка кода
     */
    const CHECK_CODE_V2 = 'onCheckCodeV2';

    /**
     * Своя генерация кода
     */
    const GENERATE_CODE = 'onGenerateCode';

    /**
     * После проверки лимитов
     */
    const AFTER_CHECK_LIMITS = 'onAfterCheckLimits';

    /**
     * После входа через смс
     */
    const AFTER_AUTH_SMS = 'onAfterAuthSms';

    /**
     * После входа по паролю
     */
    const AFTER_AUTH_PSW = 'onAfterAuthPsw';

    /**
     * Добавление правила в параметры модуля
     */
    const BUILD_RULES = 'buildRules';

    /**
     * Проверка правила, на разрешение генерации кода
     */
    const CHECK_RULE = 'checkRule';


}