<?php

namespace Awz\AutForm;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;

class Agents {

    public static function agentDeleteOldCodes(): string
    {

        $maxTime = intval(Option::get('awz.autform', 'MAX_TIME', '10', '')) * 60 + 86400*7;

        $r = CodesTable::getList(
            array(
                'select'=>array('ID'),
                'filter'=>array('<EXPIRED_DATE'=>DateTime::createFromTimestamp(time()-$maxTime))
            )
        );
        while($data = $r->fetch()){
            CodesTable::delete($data);
        }

        return "\\Awz\\AutForm\\Agents::agentDeleteOldCodes();";
    }

}