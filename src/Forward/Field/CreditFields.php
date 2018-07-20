<?php

namespace Forward\Field;

use Light\Field\FieldAbstract as F;

class CreditFields
{
    public static function creditPeriod()
    {
        return F::createString('CreditPeriod', [
            'name' => 'Credit Period',
            'uom' => 'days',
            'editCssClass' => 'input-int',
            'editWidth' => 'mini',
            'tableAlign' => 'center'
        ]);
    }

    public static function creditAmount()
    {
        return F::createMoneyWithCurrency('CreditAmount', [
            'name' => 'Credit Amount',
            'short' => true,
            'zeroEmpty' => true
        ]);
    }
} 
