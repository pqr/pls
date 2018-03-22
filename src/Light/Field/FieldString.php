<?php
namespace Light\Field;

class FieldString extends FieldInputAbstract
{
    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->editWidth = 'default';
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        return htmlspecialchars($value);
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        return (string)$value;
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value !== null) {
            $value = str_replace("\r", '', $value);
        }

        return $value;
    }

    public function getSqlWhereInner($filterValue)
    {
        $maxLength = (int)$this->editMaxLength;
        if ($maxLength && mb_strlen($filterValue) === $maxLength) {
            //Если длина строки совпадает с максимально заданной длиной, используем строгое сравнение
            //Удобно для IATA кодов, например
            $sqlWhere = "{fieldKey} = '{value}'";
        } else {
            //Длина строки не чёткая - сравнение тоже не чёткое
            $sqlWhere = "{fieldKey} LIKE '%{value}%'";
        }

        return $sqlWhere;
    }
}
