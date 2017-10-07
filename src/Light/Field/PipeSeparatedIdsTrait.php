<?php
namespace Light\Field;

trait PipeSeparatedIdsTrait
{
    private $cacheParsedSelectedValueArr;

    public $oneLine = false;

    /** @return array */
    abstract protected function getEvaluatedOptions();

    /**
     * @param $option
     * @param $originalValue
     * @param bool $forTable
     * @return string
     */
    abstract protected function getOptionHtml($option, $originalValue, $forTable);

    /**
     * @param $option
     * @param $originalValue
     * @return string
     */
    abstract protected function getOptionText($option, $originalValue);

    /** @return string */
    abstract public function getKey();

    private function parseValue($value)
    {
        $valueArr = explode('|', $value);
        //уберём пустые значения
        $valueArr = array_filter($valueArr);

        return $valueArr;
    }

    protected function isOptionSelected($optionValue, $selectedValue)
    {
        if ($this->cacheParsedSelectedValueArr === null) {
            $parsedArr = $this->parseValue($selectedValue);
            $this->cacheParsedSelectedValueArr = array_flip($parsedArr);
        }

        return isset($this->cacheParsedSelectedValueArr[$optionValue]);
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        $options = $this->getEvaluatedOptions();
        $valueArr = $this->parseValue($value);
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

    protected function getExcelInner($value, array $data)
    {
        $options = $this->getEvaluatedOptions();
        $valueArr = $this->parseValue($value);
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
}