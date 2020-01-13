<?php
namespace Light\Field;

trait PipeSeparatedIdsTrait
{
    private $cacheParsedSelectedValueArr;

    public $oneLine = false;

    public static function parseValue($value)
    {
        $valueArr = explode('|', $value);
        //уберём пустые значения
        $valueArr = array_filter($valueArr);

        return array_values($valueArr);
    }

    protected function isOptionSelected($optionValue, $selectedValue)
    {
        if ($this->cacheParsedSelectedValueArr === null) {
            $parsedArr = self::parseValue($selectedValue);
            $this->cacheParsedSelectedValueArr = array_flip($parsedArr);
        }

        return isset($this->cacheParsedSelectedValueArr[$optionValue]);
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        $options = $this->getEvaluatedOptions();
        $valueArr = self::parseValue($value);
        $outHtmlArr = [];
        foreach ($valueArr as $singleValue) {

            if (!isset($options[$singleValue])) {
                continue;
            }

            $currentOption = $options[$singleValue];
            $outHtmlArr[] = $this->getOptionHtml($currentOption, $singleValue, $forTable);
        }

        $separator = $this->oneLine ? ', ' : '<br>';
        return implode($separator, $outHtmlArr);
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        $options = $this->getEvaluatedOptions();
        $valueArr = self::parseValue($value);
        $outArr = [];
        foreach ($valueArr as $singleValue) {

            if (!isset($options[$singleValue])) {
                continue;
            }

            $currentOption = $options[$singleValue];
            $outArr[] = $this->getOptionText($currentOption, $singleValue);
        }

        return implode(', ', $outArr);
    }

    public static function empty($value): bool
    {
        if (!$value) {
            return false;
        }

        $elements = self::parseValue($value);
        // Если массив elements пуст, значит isEmpty = true
        return !$elements;
    }
}
