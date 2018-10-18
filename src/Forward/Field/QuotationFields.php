<?php

namespace Forward\Field;

use Light\Field\FieldAbstract as F;

class QuotationFields
{
    public static function name()
    {
        return F::createString('Name', ['name' => 'Description']);
    }

    public static function cacheGp()
    {
        return F::createMoney('CacheGP', ['name' => 'GP', 'modes' => F::MODE_READONLY, 'short' => true]);
    }

    public static function status()
    {
        return F::createSelect('Status', [
            'options' => [
                'draft' => ['text' => 'Draft', 'cssClass' => 'gray'],
                'pending' => ['text' => 'Pending', 'cssClass' => 'warning'],
                'win' => ['text' => 'Win', 'cssClass' => 'good'],
                'merged' => ['text' => 'Merged'],
                'lost' => ['text' => 'Lost', 'cssClass' => 'bad']
            ],
            'empty' => false,
            'tableAlign' => 'center'

        ]);
    }

    public static function commentsInternal()
    {
        return F::createText('CommentsInternal')->setName('Comments Internal');
    }

    public static function commentsCustomer()
    {
        return F::createText('CommentsCustomer')->setName('Comments for Customer');
    }
}