<?php

namespace Light\Field;

use Light\Config;
use Light\Misc;

/**
 * @method string getAllYear()
 * @method string getYearFrom()
 * @method string getYearUntil()
 *
 * @method $this setAllYear(bool $allYear)
 * @method $this setYearFrom(int $yearFrom)
 * @method $this setYearUntil(int $yearUntil)
 */
class FieldMonth extends FieldInputAbstract
{
    public $allYear;
    public $yearFrom;
    public $yearUntil;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->tableAlign = 'center';
        $this->allYear = false;
        $this->yearFrom = (int)date('Y');
        $this->yearUntil = (int)date('Y') + 1;
        $this->inputPattern = '(\d{1,2})?/?(\d{4})?';
        $this->tableWidthHtml = 50;
        $this->tableAlign = 'center';
        $this->name = Config::$lang === 'ru' ? 'Месяц' : 'Month';
    }

    public function getHtmlInner($value, array $data, $forTable)
    {
        if (!$value) {
            $out = '';
        } elseif (0 === strpos($value, '0000-00-00')) {
            $out = '';
        } else {
            try {
                if (Config::$lang === 'ru') {
                    $out = Misc::getRusMonthNameYear($value, true);
                } else {
                    $date = new \DateTime($value);
                    $out = $date->format("M'y");
                }
            } catch (\Exception $e) {
                $out = '';
            }
        }

        return $out;
    }

    public function getExcelInner($value, array $data)
    {
        if (!$value) {
            $out = '';
        } elseif (0 === strpos($value, '0000-00-00')) {
            $out = '';
        } else {
            $out = substr($value, 0, 7);
        }

        return $out;
    }

    protected function getEditTagParams($value, array $data)
    {
        $editTagParams = parent::getEditTagParams($value, $data);

        $editTagParams .= ' data-allyear="' . ($this->allYear ? '1' : '0') . '"'
            . ' data-yearfrom="' . htmlspecialchars($this->yearFrom) . '"'
            . ' data-yearuntil="' . htmlspecialchars($this->yearUntil) . '"';

        return $editTagParams;
    }

    protected function getEditCssClass($value, array $data)
    {
        return parent::getEditCssClass($value, $data) . ' input-small';
    }

    public static function beautify($value)
    {
        if (!$value || $value === '0000-00-00') {
            return '';
        }
        if (strpos($value, '-') !== false) {
            [$year, $month] = explode('-', $value);
            return "$month/$year";
        }

        if (!is_numeric($value) || $value < 2000) {
            return '';
        }

        return $value;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $beautyMonth = self::beautify($value);
        return parent::getHtmlInputInner($beautyMonth, $data, $idPrefix);
    }

    protected function getDataFromRequestInner(array $requset)
    {
        $value = parent::getDataFromRequestInner($requset);
        if ($value !== null) {
            $value = FieldMonth::parse($value);
        }

        return $value;
    }

    protected function getSqlWhereInner($value)
    {
        if (strlen($value) === 10) {
            return "DATE_FORMAT({fieldKey}, '%Y-%m-01') = '{value}'";
        } else {
            //Задан целый год
            return "YEAR({fieldKey}) = '{value}'";
        }
    }

    public static function parse($value)
    {
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        if (preg_match('/\d{4}-\d{2}-\d{2}/', $value)) {
            return substr($value, 0, 8) . '01';
        }

        $slpashPos = strpos($value, '/');
        if ($slpashPos === false && is_numeric($value) && $value >= 2000) {
            //Задан только год (без месяца)
            return $value;
        }

        if ($slpashPos !== 1 && $slpashPos !== 2) {
            return '';
        }

        list($month, $year) = explode('/', $value);
        if (!is_numeric($month) || $month < 1 || $month > 12
            || !is_numeric($year) || $year < 2000
        ) {
            return '';
        }

        if (strlen($month) === 1) {
            $month = '0' . $month;
        }

        return $year . '-' . $month . '-01';
    }
}
