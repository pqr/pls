<?php
namespace Light\Field;

/**
 * @param string $cssClassNegative
 * @param string $cssClassPositive
 * @param string $cssClassZero
 * @param bool $zeroEmpty
 * @param bool $zeroEmptyExcel
 *
 * @method $this setCssClassNegative(string $cssClass)
 * @method $this setCssClassPositive(string $cssClass)
 * @method $this setCssClassZero(string $cssClass)
 * @method $this setZeroEmpty(bool $value)
 * @method $this setZeroEmptyExcel(bool $value)
 *
 */
abstract class FieldNumberAbstract extends FieldInputAbstract
{
    public $cssClassNegative;
    public $cssClassPositive;
    public $cssClassZero;
    public $zeroEmpty = false;
    public $zeroEmptyExcel = false;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->tableWidthExcel = 10;
        $this->default = 0;
        $this->tableAlign = 'right';
    }

    protected function getEditTagParams($value, array $data)
    {
        return parent::getEditTagParams($value, $data) . ' autocomplete="off"';
    }

    public function getViewCssClass($value, array $data = [], $forTable = false)
    {
        $viewCssClass = parent::getViewCssClass($value, $data, $forTable);

        if (is_numeric($value)) {
            if ($value > 0) {
                $addCssClass = $this->cssClassPositive;
            } elseif ($value < 0) {
                $addCssClass = $this->cssClassNegative;
            } else {
                $addCssClass = $this->cssClassZero;
            }

            if ($addCssClass) {
                $viewCssClass .= ' ' . $addCssClass;
            }
        }

        return $viewCssClass;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        if ($value == 0) {
            $value = '';
        }

        return parent::getHtmlInputInner($value, $data, $idPrefix);
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);
        if ($value !== null) {
            $value = str_replace(' ', '', $value);
        }

        return $value;
    }
}
