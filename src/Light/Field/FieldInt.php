<?php
namespace Light\Field;

class FieldInt extends FieldNumberAbstract
{
    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->tableWidthHtml = 70;
        $this->editWidth = 'mini';
        $this->editAlign = 'right';
        //$this->inputType = 'number';
        $this->inputPattern = '\d*';
    }

    protected function getEditCssClass($value, array $data)
    {
        return 'input-int ' . parent::getEditCssClass($value, $data);
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        $value = (int)$value;
        if ($this->zeroEmpty && $value === 0) {
            return '';
        }
        return (string)$value;
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        $value = (int)round((double)$value);
        if ($this->zeroEmptyExcel && $value === 0) {
            $value = '';
        }
        return $value;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $value = (int)$value;
        return parent::getHtmlInputInner($value, $data, $idPrefix);
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value === null) {
            return null;
        }

        return (int)$value;
    }

    public function allowSqlWhereValue($value)
    {
        return (int)$value !== 0;
    }

    public static function parseFromDb($dbValue)
    {
        if ($dbValue === null) {
            return null;
        }
        return (int)$dbValue;
    }

}
