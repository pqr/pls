<?php

namespace Light\Field;

/**
 * @param string $editMaxLength
 * @param string $inputType
 * @param string $inputPattern
 *
 * @method $this setEditMaxLength(int $value)
 * @method $this setInputType(string $value)
 * @method $this setInputPattern(string $value)
 *
 * @method int getEditMaxLength()
 * @method string getInputType()
 * @method string getInputPattern()
 */
abstract class FieldInputAbstract extends FieldAbstract
{
    public $editMaxLength;
    public $inputType;
    public $inputPattern; // можно задавать regex выражения, например [0-9]* http://htmlbook.ru/html/input/pattern

    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->default = '';
        $this->inputType = 'text';
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $tdata = $this->getHtmlInputTplData($value, $data, $idPrefix);

        $outHtml = '<input type="' . $this->inputType . '" data-type="' . $tdata['type']
            . '" name="' . $tdata['editName']
            . '" id="' . $tdata['editId']
            . '" value="' . htmlspecialchars($tdata['value']) . '"';

        if ($this->editMaxLength) {
            $outHtml .= ' maxlength="' . $this->editMaxLength . '"';
        }
        if ($this->placeholder) {
            $outHtml .= ' placeholder="' . htmlspecialchars($this->placeholder) . '"';
        }
        if ($this->inputPattern) {
            $outHtml .= ' pattern="' . $this->inputPattern . '"';
        }

        $outHtml .= $tdata['cssClass'] . $tdata['tagParams'] . $tdata['title'] . '>';

        return $outHtml;
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value;
    }
}
