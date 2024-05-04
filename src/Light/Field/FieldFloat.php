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

    #[\Override]
    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->tableWidthHtml = 70;
        $this->editWidth = 'small';
        $this->inputPattern = '-?\d+([.,]\d+)?';
        $this->tableAlign = 'center';
    }

    #[\Override]
    protected function getEditCssClass($value, array $data)
    {
        return 'input-float ' . parent::getEditCssClass($value, $data);
    }

    #[\Override]
    protected function getHtmlInner($value, array $data, $forTable)
    {
        $value = (double)$value;
        if ($this->zeroEmpty && $value == 0) {
            return '';
        }
        $outHtml = (string)$value;
        if (!$forTable && $outHtml && $this->uom) {
            $outHtml .= ' ' . $this->uom;
        }
        return $outHtml;
    }

    #[\Override]
    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        $value = (double)$value;
        if ($this->zeroEmptyExcel && $value == 0) {
            return '';
        }
        return $value;
    }

    #[\Override]
    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $value = (double)$value;
        return parent::getHtmlInputInner($value, $data, $idPrefix);
    }

    #[\Override]
    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value !== null) {
            $value = str_replace(',', '.', (string) $value);
            $value = (double)$value;
        }

        return $value;
    }

    public static function parse($value)
    {
        return (double)str_replace(',', '.', (string) $value);
    }

    #[\Override]
    public function allowSqlWhereValue($value)
    {
        return (float)$value !== 0.0;
    }

    #[\Override]
    public static function parseFromDb($dbValue)
    {
        if ($dbValue === null) {
            return null;
        }
        return (float)$dbValue;
    }
}
