<?php
namespace Light\Traits;

/**
 * Для реализации Submodule используем Trait, а не abstract class, т.к. конечная реализация Submodule зачастую будет
 * потомком класса Table (например, ReportAbstract).
 */
trait SubmoduleTrait
{
    protected static $_title;
    protected static $_description;

    public static function getTitle()
    {
        return static::$_title;
    }

    public static function getDescription()
    {
        return static::$_description;
    }

    protected function getSubmoduleClass()
    {
        $class = explode('\\', get_class($this));
        return end($class);
    }
}