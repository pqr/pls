<?php
namespace Forward\Field;

use Light\Field\FieldAbstract as F;

class HomeFields
{
    public static function name()
    {
        return F::create('Name', F::TYPE_STRING, ['name' => 'File Number']);
    }

    public static function mawbNumber()
    {
        return F::create('MAWBNumber', F::TYPE_STRING, ['name' => 'MAWB']);
    }
}