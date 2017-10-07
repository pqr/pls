<?php
namespace Forward\Field;

use Light\Field\FieldAbstract as F;

class CreditFields
{
    public static function creditPeriod()
    {
        return F::create(
            'CreditPeriod',
            F::TYPE_STRING,
            [
                'name' => 'Credit Period',
                'uom' => 'days',
                'editCssClass' => 'input-int',
                'editWidth' => 'mini',
                'tableAlign' => 'center'
            ]
        );
    }

    public static function creditAmount()
    {
        return F::create(
            'CreditAmount',
            F::TYPE_MONEY_WITH_CURRENCY,
            ['name' => 'Credit Amount', 'short' => true, 'zeroEmpty' => true]
        );
    }
} 