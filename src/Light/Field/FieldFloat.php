<?php

namespace Light\Field;

/**
 * @param int $precision 2 число знаков после запятой для округления при подсчёте Total в подтаблицах
 *
 * @method $this setPrecision(int $precision)
 *
 * @method int getPrecision()
 */
class FieldFloat extends FieldNumberAbstract
{
    public $precision = 2;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->tableWidthHtml = 70;
        $this->editWidth = 'small';
        $this->editAlign = 'right';
        $this->inputPattern = '-?\d+([.,]\d+)?';
    }

    protected function getEditCssClass($value, array $data)
    {
        return 'input-float ' . parent::getEditCssClass($value, $data);
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        $value = (double)$value;
        if ($this->zeroEmpty && $value == 0) {
            return '';
        }
        return (string)$value;
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        $value = (double)$value;
        if ($this->zeroEmptyExcel && $value == 0) {
            return '';
        }
        return $value;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $value = (double)$value;
        return parent::getHtmlInputInner($value, $data, $idPrefix);
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value !== null) {
            $value = str_replace(',', '.', $value);
            $value = (double)$value;
        }

        return $value;
    }

    public function allowSqlWhereValue($value)
    {
        return (float)$value !== 0.0;
    }

    public static function parseFromDb($dbValue)
    {
        if ($dbValue === null) {
            return null;
        }
        return (float)$dbValue;
    }
}
