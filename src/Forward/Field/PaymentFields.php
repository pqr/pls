<?php
namespace Forward\Field;

use Light\Field\FieldAbstract as F;

class PaymentFields
{
    public static function name()
    {
        return F::create('Name', F::TYPE_STRING, ['name' => 'Reference']);
    }

    public static function paymentDate()
    {
        return F::createDate('PaymentDate', ['name' => 'Payment Date', 'default' => date('Y-m-d')]);
    }

    public static function cacheOverpayAmount()
    {
        return F::createMoney('CacheOverpayAmount', [
            'name' => 'Overpay Amount',
            'title' => 'Сумма переплаты',
            'modes' => F::MODE_READONLY,
            'nameTable' => "Overpay\nAmount",
            'sqlSelectRequires' => 'OverpayCleared',
            'viewCssClass' => function ($value, array $data) {
                if (isset($data['OverpayCleared']) && !$data['OverpayCleared'] && $value != '0.00') {
                    return 'bad';
                }

                return '';
            },

            'viewTitle' => function ($value, array $data) {
                if ($value != '0.00') {
                    if (isset($data['OverpayCleared']) && $data['OverpayCleared']) {
                        return 'Переплата погашена';
                    } else {
                        return 'Переплата НЕ погашена';
                    }
                }

                return '';
            },
            'zeroEmpty' => true
        ]);
    }

    public static function overpayCleared()
    {
        return F::createCheckbox(
            'OverpayCleared',
            [
                'name' => 'Overpay Cleared',
                'title' => 'Переплата погашена',
                'nameTable' => "Overpay\nCleared",
                'ajax' => true
            ]
        );
    }
}
