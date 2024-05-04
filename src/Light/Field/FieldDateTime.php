<?php

namespace Light\Field;

use Light\Config;
use Light\Css;
use Light\Db;
use Light\Js;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class FieldDateTime extends FieldInputAbstract
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
        $this->tableWidthExcel = 18;
        $this->editMaxLength = 12;
        $this->tableWidthHtml = 110;
    }

    protected function getEditCssClass($value, array $data)
    {
        return 'input-datetime ' . parent::getEditCssClass($value, $data);
    }

    protected function getEditTagParams($value, array $data)
    {
        return parent::getEditTagParams($value, $data) . ' autocomplete="off"';
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if ($this->ajax) {
            Js::addLight('jquery.datetimepicker.js');
            Css::addLight('jquery.datetimepicker.css');
        }

        if (!$value) {
            $outHtml = '';
        } elseif (str_starts_with($value, '0000-00-00')) {
            $outHtml = '';
        } elseif (str_starts_with($value, '2099-01-01')) {
            $outHtml = 'until further notice';
        } else {
            $outHtml = FieldDateTime::beautify($value);
        }

        return $outHtml;
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        if (!$value) {
            $out = '';
        } elseif (str_starts_with($value, '0000-00-00')) {
            $out = '';
        } else {
            if ($this->groupconcat && $groupconcatLinesCount > 1) {
                //Даты объеденённые через перевод строк не нужно переводить в нативный формат дат в Excel.
                //Нужно записать прямым текстом
                $out = FieldDateTime::beautify($value);
            } else {
                $date = new \DateTime($value);
                $out = Date::PHPToExcel($date);
            }
        }

        return $out;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        Js::addLight('jquery.datetimepicker.js');
        Css::addLight('jquery.datetimepicker.css');

        $value = FieldDateTime::beautify($value);
        return parent::getHtmlInputInner($value, $data, $idPrefix);
    }

    public static function beautify($mysqlDateTime)
    {
        if ($mysqlDateTime === '' || str_starts_with($mysqlDateTime, '0000-00-00')) {
            return '';
        }

        return date(Config::$defaultDateTimeFormat, strtotime($mysqlDateTime));
    }

    protected function getDataFromRequestInner(array $requset)
    {
        $value = parent::getDataFromRequestInner($requset);

        if ($value !== null && str_contains($value, ' ')) {
            [$date, $time] = explode(' ', $value);
            $date = FieldDate::parse($date);

            if ($time === null) {
                if ($date === '') {
                    return '';
                } else {
                    $time = '00:00:00';
                }
            }

            $value = $date . ' ' . $time;
        }

        return $value;
    }

    public function allowSqlWhereValue($value): bool
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
                $value .= ' 00:00:00';
            }
            $dbKey .= $this->asInterval ? 'Until' : '';
            return $dbKey . " >= '" . Db::esc($value) . "'";
        }

        if (str_ends_with($this->key, 'Until')) {
            if (strlen($value) === 10) {
                $value .= ' 23:59:59';
            }
            $dbKey .= $this->asInterval ? 'From' : '';
            return $dbKey . " <= '" . Db::esc($value) . "'";
        }

        return '';
    }


    public static function empty($value): bool
    {
        return !$value || $value === '0000-00-00 00:00:00';
    }
}
