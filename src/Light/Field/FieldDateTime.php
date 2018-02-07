<?php
namespace Light\Field;

use Light\Config;
use Light\Css;
use Light\Db;
use Light\Js;

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

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if (!$value) {
            $outHtml = '';
        } elseif (strpos($value, '0000-00-00') === 0) {
            $outHtml = '';
        } elseif (strpos($value, '2099-01-01') === 0) {
            $outHtml = 'until further notice';
        } else {
            $outHtml = FieldDateTime::beautify($value);
        }

        return $outHtml;
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
                $out = FieldDateTime::beautify($value);
            } else {
                $date = new \DateTime($value);
                $out = \PHPExcel_Shared_Date::PHPToExcel($date);
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
        if ($mysqlDateTime === '' || strpos($mysqlDateTime, '0000-00-00') === 0) {
            return '';
        }

        return date(Config::$defaultDateTimeFormat, strtotime($mysqlDateTime));
    }

    protected function getDataFromRequestInner(array $requset)
    {
        $value = parent::getDataFromRequestInner($requset);

        if ($value !== null && strpos($value, ' ') !== false) {
            list ($date, $time) = explode(' ', $value);
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

    public function allowSqlWhereValue($value)
    {
        $value = trim($value);
        return $value !== '' && $value !== '0000-00-00 00:00:00';
    }

    public function getSqlWhereInner($value)
    {
        $sqlWhere = '';

        if ($this->filterFromUntil) {
            //Одно из полей тандема "From ... until ..."
            $dbKey = preg_replace('/(From|Until)$/', '', $this->key);
            if (substr($this->key, -4) === 'From') {
                if (strlen($value) === 10) {
                    $value .= ' 00:00:00';
                }
                $dbKey .= $this->asInterval ? 'Until' : '';
                $sqlWhere = $dbKey . " >= '" . Db::esc($value) . "'";
            } elseif (substr($this->key, -5) === 'Until') {
                if (strlen($value) === 10) {
                    $value .= ' 23:59:59';
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
}
