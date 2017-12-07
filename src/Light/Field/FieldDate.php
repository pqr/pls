<?php

namespace Light\Field;

use Light\Config;
use Light\Db;
use Light\Request;

class FieldDate extends FieldInputAbstract
{
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

    protected function getEditCssClass($value, array $data)
    {
        return 'input-date ' . parent::getEditCssClass($value, $data);
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if ($value === '2099-01-01') {
            return Config::$lang === 'ru' ? 'бессрочно' : 'until further notice';
        }

        return FieldDate::beautify($value);
    }

    protected function getExcelInner($value, array $data)
    {
        if (!$value) {
            $out = '';
        } elseif (strpos($value, '0000-00-00') === 0) {
            $out = '';
        } else {
            //К сожалению, класс \Light\Table пока не поддерживает вывод ячеек date разного формата в зависимости от $this->getGroupconcatLines() > 1
            if ($this->groupconcat /* && $this->getGroupconcatLines() > 1 */) {
                //Даты объеденённые через перевод строк не нужно переводить в нативный формат дат в Excel.
                //Нужно записать прямым текстом
                $out = FieldDate::beautify($value);
            } else {
                $date = new \DateTime($value);
                $out = \PHPExcel_Shared_Date::PHPToExcel($date);
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
                $dbKey .= $this->asInterval ? 'Until' : '';
                $sqlWhere = $dbKey . " >= '" . Db::esc($value) . "'";
            } elseif (substr($this->key, -5) === 'Until') {
                if (strlen($value) === 10) {
                    $value .= ' 23:59';
                }
                $dbKey .= $this->asInterval ? 'From' : '';
                $sqlWhere = $dbKey . " <= '" . Db::esc($value) . "'";
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
}
