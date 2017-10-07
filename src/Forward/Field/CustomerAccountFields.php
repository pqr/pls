<?php
namespace Forward\Field;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;

class CustomerAccountFields
{
    public static function creditAmount()
    {
        return F::createMoneyWithCurrency('CreditAmount', [
            'name'=> 'Credit Amount',
            'nameTable' => "Credit\nAmount",
            'zeroEmpty' => true
        ]);
    }

    public static function creditCurrencyIso()
    {
        return CF::currency('Credit', ['modes' => F::MODE_EDITSUBMIT]);
    }

    public static function creditPeriod()
    {
        return F::create(
            'CreditPeriod',
            F::TYPE_INT,
            ['name' => 'Credit Period', 'zeroEmpty' => true, 'uom' => 'days', 'tableAlign' => 'center']
        );
    }
}
