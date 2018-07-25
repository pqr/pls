<?php

namespace Forward\Field;

use Light\Field\FieldAbstract as F;

class RateFields
{
    public static function name()
    {
        return F::createString(
            'Name',
            [F::PARAM_AC_SOURCE => 'list_chargedescription', F::PARAM_AC_READONLY => true]
        );
    }

    public static function minAmount($prefix = '')
    {
        return F::createMoney($prefix . 'MinAmount',
            ['name' => 'Min', 'nameDbLog' => trim($prefix . ' Min'), 'editWidth' => 'mini', 'total' => false]);
    }

    public static function maxAmount($prefix = '')
    {
        return F::createMoney($prefix . 'MaxAmount',
            ['name' => 'Max', 'nameDbLog' => trim($prefix . ' Max'), 'editWidth' => 'mini', 'total' => false]);
    }

    public static function amountN($prefix = '')
    {
        return F::createMoney($prefix . 'AmountN',
            ['name' => 'N', 'nameDbLog' => trim($prefix . ' N'), 'editWidth' => 'mini', 'total' => false]);
    }

    public static function amount45($prefix = '')
    {
        return self::amountForParticularWeight(45, $prefix);
    }

    public static function amount100($prefix = '')
    {
        return self::amountForParticularWeight(100, $prefix);
    }

    public static function amount200($prefix = '')
    {
        return self::amountForParticularWeight(200, $prefix);
    }

    public static function amount300($prefix = '')
    {
        return self::amountForParticularWeight(300, $prefix);
    }

    public static function amount500($prefix = '')
    {
        return self::amountForParticularWeight(500, $prefix);
    }

    public static function amount750($prefix = '')
    {
        return self::amountForParticularWeight(750, $prefix);
    }

    public static function amount1000($prefix = '')
    {
        return self::amountForParticularWeight(1000, $prefix);
    }

    public static function amount2000($prefix = '')
    {
        return self::amountForParticularWeight(2000, $prefix);
    }

    private static function amountForParticularWeight($weight, $prefix = '')
    {
        return F::createMoney($prefix . 'Amount' . $weight,
            [
                'name' => $weight . '+',
                'nameDbLog' => trim($prefix . ' ' . $weight . '+'),
                'editWidth' => 'mini',
                'total' => false
            ]);
    }

    public static function fscAmount($prefix = '')
    {
        return F::createMoney($prefix . 'FSCAmount',
            ['name' => 'FSC', 'nameDbLog' => trim($prefix . ' FSC'), 'editWidth' => 'mini', 'total' => false]);
    }

    public static function sscAmount($prefix = '')
    {
        return F::createMoney($prefix . 'SSCAmount',
            ['name' => 'SSC', 'nameDbLog' => trim($prefix . ' SSC'), 'editWidth' => 'mini', 'total' => false]);
    }

    public static function perKgAmount()
    {
        return F::createMoney('PerKgAmount', ['name' => 'Per Kg', 'editWidth' => 'mini', 'total' => false]);
    }

    public static function baseAmount()
    {
        return F::createMoney('BaseAmount', ['name' => 'Base', 'editWidth' => 'mini', 'total' => false]);
    }
} 
