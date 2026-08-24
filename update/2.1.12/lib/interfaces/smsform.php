<?php

namespace Awz\AutForm\Interfaces;

use Bitrix\Main\EventResult;
use Bitrix\Main\Event;

interface SmsForm {

    /**
     * Html формы шаблона смс
     *
     * @param string $value
     * @return string
     */
    public static function tmplHtml(string $value): string;

    /**
     * Формирование массива параметров шаблона смс для сохранения
     *
     * @param array $arFields
     * @return array
     */
    public static function tmplHtmlSave(array $arFields): array;

    /**
     * Взаимодействие с смс модулем и отправка смс
     *
     * @param Event $event
     * @return EventResult|null
     */
    public static function OnAfterAddEvent(Event $event): ?EventResult;

}