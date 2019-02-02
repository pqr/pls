<?php
namespace Light\Field;

/**
 * Этот тип поля предназначен только для вывода на экран
 * @param int|bool $truncatePartly - показывает первую часть текста и скрывает вторую
 * @param int|bool $truncate - полностью скрывает текст, если он длинный
 */
class FieldHtml extends FieldAbstract
{
    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->tableWidthExcel = 50;
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        return $value;
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        return $value;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        return $value;
    }

    protected function getDataFromRequestInner(array $request)
    {
        return null;
    }

    public static function isEmpty($value): bool
    {
        return trim($value) === '';
    }
}
