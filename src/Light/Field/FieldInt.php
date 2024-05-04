<?php
namespace Light\Field;

class FieldInt extends FieldNumberAbstract
{
    #[\Override]
    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->tableWidthHtml = 70;
        $this->editWidth = 'mini';
        //$this->inputType = 'number';
        $this->inputPattern = '\d*';
        $this->tableAlign = 'center';
    }

    #[\Override]
    protected function getEditCssClass($value, array $data)
    {
        return 'input-int ' . parent::getEditCssClass($value, $data);
    }

    #[\Override]
    protected function getHtmlInner($value, array $data, $forTable)
    {
        $value = (int)$value;
        if ($this->zeroEmpty && $value === 0) {
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
        $value = (int)round((double)$value);
        if ($this->zeroEmptyExcel && $value === 0) {
            $value = '';
        }
        return $value;
    }

    #[\Override]
    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $value = (int)$value;
        return parent::getHtmlInputInner($value, $data, $idPrefix);
    }

    #[\Override]
    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value === null) {
            return null;
        }

        return (int)$value;
    }

    #[\Override]
    public function allowSqlWhereValue($value)
    {
        return (int)$value !== 0;
    }

    #[\Override]
    public static function parseFromDb($dbValue)
    {
        if ($dbValue === null) {
            return null;
        }
        return (int)$dbValue;
    }

}
