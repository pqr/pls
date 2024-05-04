<?php
namespace Light\Field;

class FieldCheckboxSet extends FieldOptionsAbstract
{
    use PipeSeparatedIdsTrait;

    #[\Override]
    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->default = '';
        $this->tableWidthExcel = 40;
    }

    #[\Override]
    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $options = $this->getEvaluatedOptions();
        $tdata = $this->getHtmlInputTplData($value, $data, $idPrefix);

        $outHtml = '<ul class="checkboxset">';
        foreach ($options as $optionValue => $option) {

            if (is_array($option) && isset($option['cssClass'])) {
                $cssClass = ' class="' . $option['cssClass'] . '"';
            } else {
                $cssClass = '';
            }

            $checked = $this->isOptionSelected($optionValue, $value) ? ' checked=""' : '';

            $outHtml .= '<li' . $cssClass . '>'
                . '<label>'
                . '<input type="checkbox" name="' . $tdata['editName'] . '[]" value="' . htmlspecialchars($optionValue) . '"' . $tdata['tagParams'] . $checked . '> '
                . $this->getOptionText($option, $optionValue)
                . '</label>'
                . '</li>';
        }

        $outHtml .= '</ul>';

        return $outHtml;
    }

    #[\Override]
    protected function getDataFromRequestInner(array $request)
    {
        $fieldKey = $this->getKey();
        if (!isset($request[$fieldKey]) || empty($request[$fieldKey])) {
            $value = '';
        } else {
            if (is_array($request[$fieldKey])) {
                $valueArr = $request[$fieldKey];
                $value = '|' . implode('|', $valueArr) . '|';
            } else {
                $value = $request[$fieldKey];
            }
        }

        return $value;
    }
}
