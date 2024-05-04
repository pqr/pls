<?php
namespace Light\Field;

/**
 * Этот тип поля предназначен только для вывода на экран
 * @param int|bool $truncatePartly - показывает первую часть текста и скрывает вторую
 * @param int|bool $truncate - полностью скрывает текст, если он длинный
 */
class FieldHtml extends FieldAbstract
{
    #[\Override]
    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->tableWidthExcel = 50;
    }

    #[\Override]
    protected function getHtmlInner($value, array $data, $forTable)
    {
        return $value;
    }

    #[\Override]
    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        return $value;
    }

    #[\Override]
    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        return $value;
    }

    #[\Override]
    protected function getDataFromRequestInner(array $request)
    {
        return null;
    }

    #[\Override]
    public static function empty($value): bool
    {
        return trim((string) $value) === '';
    }
}
