<?php
namespace Light\Traits;

use Light\Misc;

/**
 * Для реализации Submodule используем Trait, а не abstract class, т.к. конечная реализация Submodule зачастую будет
 * потомком класса Table (например, ReportAbstract).
 */
trait SubmoduleTrait
{
    protected static $title;
    protected static $description;

    public static function getTitle()
    {
        return static::$title;
    }

    public static function getDescription()
    {
        return static::$description;
    }
}
