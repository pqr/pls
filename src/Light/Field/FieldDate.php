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
    const UNTIL_FUTURE_NOTICE = '2099-01-01';

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

        /*
        if (Config::$useInputTypeDate || Request::isMobileOrTablet()) {
            // Если в конфиге явно указано, что будем использовать input type=date
            // или при использовании на мобильных устройствах
            $this->inputType = 'date';
        }
        */
    }

    public function getViewCssClass($value, array $data = [], $forTable = false, $excludeOverflow300 = false)
    {
        $viewCssClass = parent::getViewCssClass($value, $data, $forTable, $excludeOverflow300);

        if ($value
            && $value != '0000-00-00'
            && $value != self::UNTIL_FUTURE_NOTICE
            && ($this->cssClassFuture || $this->cssClassPast || $this->cssClassToday)) {
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

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if ($value === self::UNTIL_FUTURE_NOTICE) {
            return Config::$lang === 'ru' ? 'бессрочно' : 'until further notice';
        }

        return FieldDate::beautify($value);
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        if (!$value) {
            $out = '';
        } elseif (strpos($value, '0000-00-00') === 0) {
            $out = '';
        } else {
            if ($this->groupconcat && $groupconcatLinesCount > 1) {
                //Даты объеденённые через перевод строк не нужно переводить в нативный формат дат в Excel.
                //Нужно записать прямым текстом
                $out = FieldDate::beautify($value);
            } else {
                try {
                    $date = new \DateTime($value);
                    $out = Date::PHPToExcel($date);
                } catch (\Exception $e) {
                    $out = FieldDate::beautify($value);
                }
            }
        }

        return $out;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        if ($this->inputType !== 'date') {
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
        return $value !== '' && $value !== '0000-00-00';
    }

    public function getSqlWhereInner($value)
    {
        $sqlWhere = '';

        if ($this->filterFromUntil) {

            //Одно из полей тандема "From ... until ..."
            $dbKey = preg_replace('/(From|Until)$/', '', $this->key);
            if (substr($this->key, -4) === 'From') {
                if (strlen($value) === 10) {
                    $value .= ' 00:00';
                }
                if ($this->asInterval) {
                    $dbKey .= 'Until';
                }
                // $dbKeyQuoted = Db::quote($dbKey);
                $sqlWhere = "$dbKey >= '" . Db::esc($value) . "'";
            } elseif (substr($this->key, -5) === 'Until') {
                if (strlen($value) === 10) {
                    $value .= ' 23:59';
                }
                if ($this->asInterval) {
                    $dbKey .= 'From';
                }
                //$dbKeyQuoted = Db::quote($dbKey);
                $sqlWhere = "$dbKey <= '" . Db::esc($value) . "' AND $dbKey != '0000-00-00'";
            }

        } else {

            //Обычная дата, проверяем на полное совпадение
            $sqlWhere = "{fieldKey} = '{value}'";

        }

        return $sqlWhere;
    }

    public static function beautify($mysqlDate): string
    {
        if (!is_string($mysqlDate) || $mysqlDate === '' || $mysqlDate[0] === '0') {
            return '';
        }

        $timestamp = strtotime($mysqlDate);
        if ($timestamp === false) {
            return $mysqlDate;
        }

        return date(Config::$defaultDateFormat, $timestamp) ?: '';
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
            $days2000 = 36526; // 01.01.2000 в формате Excel
            return @date('Y-m-d', @mktime(0, 0, 0, 1, (int)$dateStr - $days2000 + 1, 2000));
        }

        $timestamp = strtotime($dateStr);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    public static function isEmpty($value): bool
    {
        return !$value || $value === '0000-00-00';
    }
}
