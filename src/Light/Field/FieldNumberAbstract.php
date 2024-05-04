<?php
namespace Light\Field;

/**
 * @param string $cssClassNegative
 * @param string $cssClassPositive
 * @param string $cssClassZero
 * @param bool $zeroEmpty
 * @param bool $zeroEmptyExcel
 * @param bool $groupconcatSum
 *
 * @method $this setCssClassNegative(string $cssClass)
 * @method $this setCssClassPositive(string $cssClass)
 * @method $this setCssClassZero(string $cssClass)
 * @method $this setZeroEmpty(bool $value)
 * @method $this setZeroEmptyExcel(bool $value)
 * @method $this setGroupconcatSum(bool $value)
 *
 */
abstract class FieldNumberAbstract extends FieldInputAbstract
{
    public $cssClassNegative;
    public $cssClassPositive;
    public $cssClassZero;
    public $zeroEmpty = false;
    public $zeroEmptyExcel = false;
    public $groupconcatSum = false;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->tableWidthExcel = 10;
        $this->default = 0;
    }

    protected function getEditTagParams($value, array $data)
    {
        return parent::getEditTagParams($value, $data) . ' autocomplete="off"';
    }

    public function getViewCssClass($value, array $data = [], $forTable = false, $excludeViewMaxWidth = false)
    {
        $viewCssClass = parent::getViewCssClass($value, $data, $forTable, $excludeViewMaxWidth);

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

    public function getCalculatedValue($value, array $data = [], $forTable = false)
    {
        if ($this->groupconcat && $this->groupconcatSum && $value && strpos((string) $value, "\n")) {
            // Для этого поля определена настройка $this->groupconcatSum
            // и внутри действительно есть несколько значений для суммирования - выполним его!
            $value = $this->calcGroupconcatSum($value);
        }

        return parent::getCalculatedValue($value, $data, $forTable);
    }

//    public function getHtml($value, array $data = [], $forTable = false, $forLog = false)
//    {
//        if ($this->groupconcat && $this->groupconcatSum && $value && strpos($value, "\n")) {
//            // Для этого поля определена настройка $this->groupconcatSum
//            // и внутри действительно есть несколько значений для суммирования - выполним его!
//            $value = $this->calcGroupconcatSum($value);
//        }
//        return parent::getHtml($value, $data, $forTable, $forLog);
//    }
//
//    public function getExcel($value, array $data = [])
//    {
//        if ($this->groupconcat && $this->groupconcatSum && $value && strpos($value, "\n")) {
//            // Для этого поля определена настройка $this->groupconcatSum
//            // и внутри действительно есть несколько значений для суммирования - выполним его!
//            $value = $this->calcGroupconcatSum($value);
//        }
//        return parent::getExcel($value, $data);
//    }

    protected function calcGroupconcatSum($value) {
        $lines = explode("\n", (string) $value);
        return array_sum($lines);
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
            $value = str_replace(' ', '', (string) $value);
        }

        return $value;
    }
}
