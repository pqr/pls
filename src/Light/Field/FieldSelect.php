<?php

namespace Light\Field;

use Light\Css;
use Light\Db;
use Light\Js;
use Light\Request;

/**
 * @method $this setAny(bool $value)
 * @method $this setAnyPlaceholder(string $value)
 * @method $this setEmpty(bool $value)
 * @method $this setEmptyText(string $value)
 * @method $this setText(string $value)
 * @method $this setAddNew(bool $value)
 * @method $this setAddNewPlaceholder(string $value)
 * @method $this setSelect2(bool $value)
 *
 * @method bool getAny()
 * @method string getAnyPlaceholder()
 * @method bool getEmpty()
 * @method string getEmptyText()
 * @method bool getAddNew()
 * @method string getAddNewPlaceholder()
 * @method bool getSelect2()
 */
class FieldSelect extends FieldOptionsAbstract
{
    public $any = false;
    public $anyPlaceholder = '...any...';

    public $empty = true;
    public $emptyText = '';

    public $addNew = false;
    public $addNewTable;
    public $addNewPlaceholder = 'Enter new value';

    public $select2 = false;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->default = '%';
        $this->editWidth = 'auto';
    }

    protected function getEditCssClass($value, array $data)
    {
        $cssClass = parent::getEditCssClass($value, $data);
        if ($this->select2 && !Request::isMobileOrTablet()) {
            $cssClass .= ' select-select2';
        }

        if ($this->addNew) {
            $cssClass .= ' select-addnew';
        }

        return $cssClass;
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        $option = $this->getOption($value);
        return $this->getOptionHtml($option, $value, $forTable);
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        $option = $this->getOption($value);
        return $this->getOptionText($option, $value);
    }

    protected function isOptionSelected($optionValue, $selectedValue)
    {
        return (string)$optionValue === $selectedValue;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        if ($this->select2) {
            // Это поле требует подключения библиотки select2 на стороне клиента
            Js::$select2 = true;
            Css::$select2 = true;
        }

        $tdata = $this->getHtmlInputTplData($value, $data, $idPrefix);

        $outHtml = '<select name="' . $tdata['editName'] . '"'
            . ' data-type="' . $tdata['type'] . '"'
            . ' id="' . $tdata['editId'] . '"' . $tdata['cssClass'] . $tdata['tagParams'] . '>';

        //Сформируем шаблонные переменные для выпадающего списка
        if ($this->any) {
            $outHtml .= '<option value="%">' . $this->anyPlaceholder . '</option>';
        }

        $selectedValue = (string)$value;

        if ($this->empty) {
            $selectedAttr = $this->isOptionSelected('', $selectedValue) ? ' selected=""' : '';
            $outHtml .= '<option value=""' . $selectedAttr . '>' . $this->emptyText . '</option>';
        }

        $optgroups = [];
        foreach ($this->getEvaluatedOptions() as $optionValue => $option) {

            $selected = $this->isOptionSelected($optionValue, $selectedValue);
            $optgroup = '';
            $cssClass = '';
            $disabled = false;
            $icon = '';
            $title = '';

            if (is_array($option)) {

                if (isset($option['Active'])) {
                    $active = (bool)$option['Active'];
                    if (!$active && !$selected) {
                        continue;
                    }
                }

                if (isset($option['optgroup'])) {
                    $optgroup = $option['optgroup'];
                }

                if (isset($option['cssClass'])) {
                    $cssClass = $option['cssClass'];
                }

                if (isset($option['disabled'])) {
                    $disabled = (bool)$option['disabled'];
                }

                if (isset($option['icon'])) {
                    $icon = $option['icon'];
                }

                $text = $option['Name'] ?? $option['text'] ?? $optionValue;

            } else {

                $text = $option;

            }

            $optionHtml = '<option value="' . htmlspecialchars($optionValue) . '"'
                . ($selected ? ' selected=""' : '')
                . ($cssClass !== '' ? ' class="' . $cssClass . '"' : '')
                . ($disabled ? ' disabled=""' : '')
                . ($icon !== '' ? ' data-icon="' . $icon . '"' : '')
                . ($title !== '' ? ' title="' . htmlspecialchars($title) . '"' : '')
                . '>'
                . htmlspecialchars($text)
                . '</option>';

            if ($optgroup === '') {
                $outHtml .= $optionHtml;
            } else {
                if (!isset($optgroups[$optgroup])) {
                    $optgroups[$optgroup] = '';
                }
                $optgroups[$optgroup] .= $optionHtml;
            }
        }

        // Приклеим все опции, которые были накоплены внутри optgroup к общему html
        foreach ($optgroups as $optgroup => $optgroupOptionsHtml) {
            $outHtml .= '<optgroup label="' . htmlspecialchars($optgroup) . '">'
                . $optgroupOptionsHtml
                . '</optgroup>';
        }

        if ($this->addNew) {
            $outHtml .= '<option value="+">+ ' . $this->addNewPlaceholder . '</option>';
        }

        $outHtml .= '</select>';


        if ($this->addNew) {

            //Добавим к выпадающему списку поле для ввода нового значения
            $editName = $this->editName;
            // Для случая Subtable, когда атрибут name принимает значение типа subtablename[2][FieldKey], нам нужно добавить
            // слово New внутрь вторых квадратных скобок
            if (substr($editName, -1) === ']') {
                $editName = substr($editName, 0, -1) . 'New]';
            } else {
                $editName .= 'New';
            }

            $editId = $this->editId . 'New';

            $inputParams = [
                'editCssClass' => 'display-none',
                'editWidth' => $this->editWidth,
                'editName' => $editName,
                'editId' => $editId,
                'placeholder' => $this->addNewPlaceholder
            ];
            if ($this->editCssClass !== null) {
                $inputParams['editCssClass'] = $this->editCssClass;
            }
            $outHtml .= (new FieldString('', $inputParams))->getHtmlInput('', $data);
        }

        return $outHtml;
    }

    public function allowSqlWhereValue($value)
    {
        return $value !== '%';
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);

        if ($this->addNew && $value === '+') {

            $valueText = $request[$this->key . 'New'];
            if ($this->upper) {
                $valueText = mb_strtoupper($valueText, 'UTF-8');
            }

            //Добавляем новое значение в базу, узнаем новое ID
            if ($this->addNewTable) {
                $addNewTable = $this->addNewTable;
            } else {
                $addNewTable = explode('|', $this->options, 1)[0];
            }
            $value = Db::insertUnique($valueText, $addNewTable);

            // После добавления нового значения в базу очистим кеширующее поле _cacheEvaluatedOptions, чтобы при записи
            // логов все значения снова подтянулись из базы, включая только что вставленное
            $this->evaluatedOptions = null;
        }

        return $value;

    }
}
