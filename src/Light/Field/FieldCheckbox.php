<?php

namespace Light\Field;

use Light\Config;

class FieldCheckbox extends FieldAbstract
{

    public $textYes = 'Yes';
    public $textNo = 'No';

    public $cssClassYes = 'yes';
    public $cssClassNo = 'no';

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->default = 0;
        $this->tableAlign = 'center';
        $this->tableWidthExcel = 10;

        if (Config::$lang === 'ru') {
            $this->textYes = 'Да';
            $this->textNo = 'Нет';
        }
    }

    public function getNameTableSettings()
    {
        return parent::getNameTableSettings() . ' (' . $this->textYes . '/' . $this->textNo . ')';
    }

    public function getViewCssClass($value, array $data = [], $forTable = false, $excludeViewMaxWidth = false)
    {
        $viewCssClass = parent::getViewCssClass($value, $data, $forTable, $excludeViewMaxWidth);
        if ($value !== null) {
            $viewCssClass .= ' ' . ($value ? $this->cssClassYes : $this->cssClassNo);
        }

        return trim((string) $viewCssClass);
    }

    public function getText($value, array $data = [], $forTable = false)
    {
        if (!$this->checkAccessFunction($data)) {
            return '';
        }

        if ($value === null) {
            return '';
        }

        return $value ? $this->textYes : $this->textNo;
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        return $this->getText($value);
    }

    protected function ajaxify($value, array $data, $forTable, $outHtml)
    {
        if ($this->groupconcat) {
            return $outHtml;
        }

        if (!isset($data['ID']) || !$data['ID']) {
            return $outHtml;
        }

        if (!$this->hasMode(self::MODE_EDIT) || !$this->checkEditAccessFunction($data)) {
            return $outHtml;
        }

        $cssClass = $this->getViewCssClass($value, $data, $forTable);
        $cssClass = trim($cssClass . ' ajax-checkbox');

        $ajaxSuccessHandler = $this->ajaxSuccessHandler ? ' data-ajax-success-handler="' . $this->ajaxSuccessHandler . '"' : '';

        return '<a href="#" class="' . $cssClass . '" data-id="' . $data['ID'] . '" data-fieldkey="' . $this->key . '" data-value="' . $value . '"' . $ajaxSuccessHandler . '>'
            . $this->getText($value)
            . '</a>';
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        return $this->getText($value);
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $tdata = $this->getHtmlInputTplData($value, $data, $idPrefix);
        $checked = ($value && $value !== 'off' && $value !== 'false') ? ' checked=""' : '';

        return '<input type="checkbox" name="' . $tdata['editName'] . '" id="' . $tdata['editId'] . '" value="on"' .
            $checked . $tdata['cssClass'] . $tdata['tagParams'] . '>';
    }

    protected function getDataFromRequestInner(array $request)
    {
        $val = $request[$this->key] ?? false;

        // Здесь перечислены все возможные значения, которые могут прийти от браузера и которые будут интерпретированы
        // как false
        return $val === '0' || $val === 0 || $val === false || $val === 'false' ? 0 : 1;
    }

    public function allowSqlWhereValue($value)
    {
        return !empty($this->sqlWhere);
    }
}
