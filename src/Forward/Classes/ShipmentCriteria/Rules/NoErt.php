<?php
namespace Forward\Classes\ShipmentCriteria\Rules;

use Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase;

class NoErt extends RuleBase
{
    protected static function getQueryWhere()
    {
        return "PUPDate != '0000-00-00' AND ERTDate = '0000-00-00'";
    }
} 