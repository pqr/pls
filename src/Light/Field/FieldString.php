<?php

namespace Light\Field;

class FieldString extends FieldInputAbstract
{
    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->editWidth = 'default';
        $this->filterOperator = self::FILTER_LIKE;
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        $outHtml = htmlspecialchars((string) $value);
        if (!$forTable && $outHtml && $this->uom) {
            $outHtml .= ' ' . $this->uom;
        }
        return $outHtml;
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        return (string)$value;
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value !== null) {
            $value = str_replace("\r", '', (string) $value);
        }

        return $value;
    }

    public function getSqlWhereInner($filterValue): string
    {
        $maxLength = (int)$this->editMaxLength;
        if (!$this->filterMany && $maxLength && mb_strlen($filterValue) === $maxLength) {
            // Если длина строки совпадает с максимально заданной длиной, используем строгое сравнение
            // Удобно для IATA кодов, например
            return "{fieldKey} = '{value}'";
        }

        // Длина строки не чёткая
        // либо для этого поля установлен флаг filterMany (что включает достаточно сложный алгоритм)
        // тогда используем стандартную схему сравнения описанную в FieldAbstract
        return parent::getSqlWhereInner($filterValue);
    }

    public static function empty($value): bool
    {
        return trim((string) $value) === '';
    }
}
