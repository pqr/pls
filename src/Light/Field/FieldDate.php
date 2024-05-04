<?php

namespace Light\Field;

use Light\Config;
use Light\Db;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * @param string $cssClassFuture
 * @param string $cssClassPast
 * @param string $cssClassToday
 * @param bool $filterFromUntil
 * @param bool $asInterval
 *
 * @method $this setCssClassFuture(string $cssClass)
 * @method $this setCssClassPast(string $cssClass)
 * @method $this setCssClassToday(string $cssClass)
 * @method $this setFilterFromUntil(bool $value)
 * @method $this setAsInterval(bool $value)
 *
 */
class FieldDate extends FieldInputAbstract
{
    public const UNTIL_FUTURE_NOTICE = '2099-01-01';

    private const EXCEL_DATE_2000 = 36526; // // 01.01.2000 в формате Excel

    public $cssClassFuture;
    public $cssClassPast;
    public $cssClassToday;

    public $filterFromUntil = false;

    /**
     * Актуально при параметре filterFromUntil == true. Если true, то переменная $this->key
     * воспринимается как префикс для двух полей в базе <$this->key>From и <$this->key>Until
     * @var bool
     */
    public $asInterval = false;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->tableAlign = 'center';
        $this->tableWidthExcel = 14;
        $this->editMaxLength = 12;
        $this->tableWidthHtml = 79;
    }

    public function getViewCssClass($value, array $data = [], $forTable = false, $excludeViewMaxWidth = false)
    {
        $viewCssClass = parent::getViewCssClass($value, $data, $forTable, $excludeViewMaxWidth);

        if ($value != self::UNTIL_FUTURE_NOTICE
            && ($this->cssClassFuture || $this->cssClassPast || $this->cssClassToday)
            && !self::empty($value)
        ) {
            $today = date('Y-m-d');
            if ($value > $today) {
                $addCssClass = $this->cssClassFuture;
            } elseif ($value < $today) {
                $addCssClass = $this->cssClassPast;
            } else {
                $addCssClass = $this->cssClassToday;
            }

            if ($addCssClass) {
                $viewCssClass .= ' ' . $addCssClass;
            }
        }

        return $viewCssClass;
    }

    protected function getEditCssClass($value, array $data)
    {
        return 'input-date ' . parent::getEditCssClass($value, $data);
    }

    protected function getEditTagParams($value, array $data)
    {
        return parent::getEditTagParams($value, $data) . ' autocomplete="off"';
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if ($value === self::UNTIL_FUTURE_NOTICE) {
            return Config::$lang === 'ru' ? 'бессрочно' : 'until further notice';
        }

        return FieldDate::beautify($value);
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        if (self::empty($value)) {
            return '';
        }

        if ($this->groupconcat && $groupconcatLinesCount > 1) {
            // Даты объеденённые через перевод строк не нужно переводить в нативный формат дат в Excel.
            // Нужно записать прямым текстом
            return FieldDate::beautify($value);
        }

        if ($value === self::UNTIL_FUTURE_NOTICE) {
            return FieldDate::beautify($value);
        }

        try {
            $date = new \DateTime($value);
            $out = Date::PHPToExcel($date);
        } catch (\Exception) {
            $out = FieldDate::beautify($value);
        }

        return $out;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        if ($value === self::UNTIL_FUTURE_NOTICE) {
            $value = '';
        } elseif ($this->inputType !== 'date') {
            $value = FieldDate::beautify($value);
        }

        return parent::getHtmlInputInner($value, $data, $idPrefix);
    }

    protected function getDataFromRequestInner(array $requset)
    {
        $value = parent::getDataFromRequestInner($requset);
        if ($value !== null) {
            $value = FieldDate::parse($value);
        }

        return $value;
    }

    public function allowSqlWhereValue($value)
    {
        $value = trim($value);
        return self::notEmpty($value);
    }

    public function getSqlWhereInner($value): string
    {
        if ($this->filterFromUntil) {
            // Одно из полей тандема "From ... until ..."
            return $this->getSqlWhereFromUntil($value);
        }

        // Обычная дата, проверяем на полное совпадение
        return "{fieldKey} = '{value}'";
    }

    private function getSqlWhereFromUntil($value): string
    {
        // Одно из полей тандема "From ... until ..."
        $dbKey = preg_replace('/(From|Until)$/', '', $this->getFilterFieldKeys()[0]);
        if (str_ends_with($this->key, 'From')) {
            if (strlen($value) === 10) {
                $value .= ' 00:00';
            }
            if ($this->asInterval) {
                $dbKey .= 'Until';
            }
            // $dbKeyQuoted = Db::quote($dbKey);
            return "$dbKey >= '" . Db::esc($value) . "'";
        }

        if (str_ends_with($this->key, 'Until')) {
            if (strlen($value) === 10) {
                $value .= ' 23:59';
            }
            if ($this->asInterval) {
                $dbKey .= 'From';
            }
            //$dbKeyQuoted = Db::quote($dbKey);
            return "$dbKey <= '" . Db::esc($value) . "' AND $dbKey != '0000-00-00'";
        }

        return '';
    }


    public static function beautify($mysqlDate, string $format = null): string
    {
        if (self::empty($mysqlDate)) {
            return '';
        }

        $timestamp = strtotime($mysqlDate);
        if ($timestamp === false) {
            return $mysqlDate;
        }

        if (!$format) {
            $format = Config::$defaultDateFormat;
        }
        return date($format, $timestamp) ?: '';
    }

    /**
     * Принимает строку с пользовательским вводом даты или с датой из Excel и возвращает SQL формат
     * @param string $dateStr
     * @return string дата в формате MySQL Y-m-d
     */
    public static function parse($dateStr): string
    {
        $dateStr = (string)$dateStr;

        $dateStr = trim(str_replace(chr(160), '', $dateStr));
        if ($dateStr === '' || $dateStr === '0000-00-00') {
            return '';
        }

        $dateStr = str_replace(['.', '/'], '-', $dateStr);

        if (is_numeric($dateStr) && (int)$dateStr < 10000000) {
            // На вход получили какое-то число - это это может быть дата в формате Excel или что-то типа YYYYMMDD
            // Мы провериили, что указанное число не подходит под описание YYYYMMDD, значит это формат Excel
            return @date('Y-m-d', @mktime(0, 0, 0, 1, (int)$dateStr - self::EXCEL_DATE_2000 + 1, 2000));
        }

        $timestamp = strtotime($dateStr);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    public static function empty($value): bool
    {
        return !$value || $value === '0000-00-00' || !is_string($value);
    }

    public static function valueOrNull($value): ?string
    {
        return FieldDate::empty($value) ? null : $value;
    }
}
