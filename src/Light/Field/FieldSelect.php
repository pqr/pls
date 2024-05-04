<?php

namespace Light\Field;

use Light\Config;
use Light\Css;
use Light\Db;
use Light\Js;
use Light\User;

/**
 * @method $this setAny(bool $value)
 * @method $this setAnyText(string $value)
 * @method $this setEmpty(bool $value)
 * @method $this setEmptyText(string $value)
 * @method $this setText(string $value)
 * @method $this setAddNew(bool $value)
 * @method $this setAddNewPlaceholder(string $value)
 * @method $this setSelect2(bool $value)
 * @method $this setSelect2ajax(bool $value)
 * @method $this setSelect2closeOnSelect(bool $value)
 * @method $this setChoices(bool $value)
 * @method $this setParentFieldKeys(string | array $value)
 * @method $this setParentFieldKeysMandatory(string | array $value)
 * @method $this setRelations(string | callable $value)
 * @method $this setMultiple(bool $value)
 * @method $this setMultipleSqlWhereOperator(string $value)
 * @method $this setMultipleViewSeparator(string $value)
 * @method $this setMultipleSqlWhereOnMultiple(bool $isOnMultiple)
 * @method $this setAvatar(bool $value)
 *
 * @method bool getAny()
 * @method string getAnyText()
 * @method bool getEmpty()
 * @method string getEmptyText()
 * @method bool getAddNew()
 * @method string getAddNewPlaceholder()
 * @method bool getSelect2()
 * @method bool getSelect2ajax()
 * @method bool getSelect2closeOnSelect()
 * @method string|array getParentFieldKeys()
 * @method string|array getParentFieldKeysMandatory()
 * @method string|callable getRaltions()
 * @method bool getMultiple()
 * @method string getMultipleSqlWhereOperator()
 * @method string getMultipleViewSeparator()
 * @method bool getMultipleSqlWhereOnMultiple()
 * @method bool getAvatar()
 */
class FieldSelect extends FieldOptionsAbstract
{
    public $any = false;
    public $anyText = '...any...';

    public $empty = true;
    public $emptyText = '';
    public $emptyCssClass = 'skipp';

    public $addNew = false;
    public $addNewTable;
    public $addNewPlaceholder = 'Enter new value';

    public $select2 = false;
    public $select2ajax = false;
    public $select2closeOnSelect = true;
    public $select2ajaxMin = 1;

    public $choices = false;

    public $multiple = false;


    /**
     * @var string каким образом выводить несколько значений в режиме view? Здесь можно указать запятую, например
     */
    public $multipleViewSeparator = '<br>';

    /**
     * @var string 'OR' или 'AND' - поле multiple, то какую логиу применять в фильтрах (в sqlWhere)?
     */
    public $multipleSqlWhereOperator = "OR";

    /**
     * @var bool Если установлено в true, то подразумевается что поле само по себе является multiple и содержит заначения
     * которые записаны в VARCHAR разделённые вертикальной чертой |
     */
    public $multipleSqlWhereOnMultiple = false;

    /**
     * @var string|array здесь может быть указано одно поле или несколько в массиве
     */
    public $parentFieldKeys;

    /**
     * Для child поля обязательно требуется какое-нибудь не пустое parent заначение
     * (работает только в select2ajax)
     * @var string|array
     */
    public $parentFieldKeysMandatory;

    /**
     * Строка запроса или метод, который должен возвращать простой массив содержащий ассоциативные массивы
     * Например: Db::getAll("SELECT CompanyID, CustomerAccountID, ID AS CustomerAccountContractID FROM customeraccountcontract")
     * В этом примере мы хотим сделать поле CustomerAccountContractID зависимым от CompanyID и CustomerAccountID
     * Db::getAll() вернёт нам все комбинации. Благодаря совпадению имён полей в parentFieldKeys и в самом запросе
     * на стороне JavaScript движок сможет подобрать нужные значения.
     * Если в качестве $relations используется метод, то он принимает два параметра: $options и $this ($field)
     * @var string|callable
     */
    public $relations;

    /**
     * Есть поставить true и поле само по себе является select2, то к каждой опции будет добавлена картинка аватарки
     * @var bool
     */
    public $avatar = false;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->default = '%';
        $this->editWidth = 'auto';

        $this->addNewPlaceholder = Config::$lang === 'ru' ? 'Добавить значение' : 'Enter new value';
        $this->anyText = Config::$lang === 'ru' ? '...любой...' : '...any...';
    }

    protected function getEditCssClass($value, array $data)
    {
        $cssClass = parent::getEditCssClass($value, $data);
        if ($this->select2) {
            $cssClass .= ' select-select2';

            // Для select2 полей нельзя делать input-auto - они скукоживаются
            $cssClass = str_replace('input-auto', 'input-default', $cssClass);

            if ($this->select2ajax) {
                $cssClass .= ' select2-ajax';
            }

            if ($this->avatar) {
                $cssClass .= ' select2-with-avatar';
            }

        } elseif ($this->choices) {

            $cssClass .= ' select-choices';

        }

        if ($this->addNew) {
            $cssClass .= ' select-addnew';
        }

        return $cssClass;
    }

    protected function getEditTagParams($value, array $data)
    {
        $editTagParams = (string)parent::getEditTagParams($value, $data);

        if ($this->select2) {

            if ($this->parentFieldKeys) {
                $parentFieldKeys = $this->parentFieldKeys;
                if (is_array($parentFieldKeys)) {
                    $parentFieldKeys = implode(',', $parentFieldKeys);
                }
                $editTagParams .= ' data-parantfieldkeys="' . htmlspecialchars($parentFieldKeys) . '"';
            }

            if ($this->multiple) {
                $editTagParams .= ' multiple size=1';
            }

            if ($this->select2ajax && $this->select2ajaxMin > 1) {
                $editTagParams .= ' data-minimum-input-length=' . $this->select2ajaxMin;
            }

            $editTagParams .= ' data-select2-closeOnSelect=' . ($this->select2closeOnSelect ? '1' : '0');

        } elseif ($this->choices) {

            if ($this->multiple) {
                $editTagParams .= ' multiple size=1';
            }
        }

        return trim($editTagParams);
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if (!$this->multiple) {
            $option = $this->getOption($value, self::MODE_VIEW);
            return $this->getOptionHtml($option, $value, $forTable);
        }

        $values = $this->parsePipeSeparatedValue($value);
        $htmls = [];
        foreach ($values as $singleValue) {

            $option = $this->getOption($singleValue);
            $htmls[] = $this->getOptionHtml($option, $singleValue, $forTable);
        }

        $outHtml = implode($this->multipleViewSeparator, $htmls);
        if (!$forTable && $outHtml && $this->uom) {
            $outHtml .= ' ' . $this->uom;
        }
        return $outHtml;
    }

    protected function getOptionHtml($option, $originalValue, $forTable)
    {
        if ($this->avatar && $originalValue && is_numeric($originalValue)) {
            if (!is_array($option)) {
                $option = ['text' => $option];
            }
            $option['img'] = User::avatarImgSrc($originalValue);
        }
        return parent::getOptionHtml($option, $originalValue, $forTable);
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        if (!$this->multiple) {
            $option = $this->getOption($value);
            return $this->getOptionText($option, $value);
        }

        $values = $this->parsePipeSeparatedValue($value);
        $lines = [];
        foreach ($values as $singleValue) {

            $option = $this->getOption($singleValue);
            $lines[] = $this->getOptionText($option, $singleValue);
        }

        $separator = str_replace('<br>', "\n", $this->multipleViewSeparator);
        return implode($separator, $lines);
    }


    private $cacheParsedSelectedValueArr;

    protected function isOptionSelected($optionValue, $selectedValue)
    {
        if (!$this->multiple) {
            return (string)$optionValue === $selectedValue;
        }

        if ($this->cacheParsedSelectedValueArr === null) {
            $parsedArr = $this->parsePipeSeparatedValue($selectedValue);
            $this->cacheParsedSelectedValueArr = array_flip($parsedArr);
        }
        return isset($this->cacheParsedSelectedValueArr[$optionValue]);
    }

    private function parsePipeSeparatedValue($value): array
    {
        return array_filter(explode('|', (string) $value));
    }

    protected function getHtmlInputTplData($value, array $data, $idPrefix)
    {
        $tdata = parent::getHtmlInputTplData($value, $data, $idPrefix);
        if ($this->multiple) {
            $tdata['editName'] .= '[]';
        }
        return $tdata;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        if ($this->select2) {
            // Это поле требует подключения библиотки select2 на стороне клиента
            Js::$select2 = true;
            Css::$select2 = true;
        } elseif ($this->choices) {
            // Это поле требует подключения библиотки Choices на стороне клиента
            Js::$choices = true;
            Css::$choices = true;
        }

        $tdata = $this->getHtmlInputTplData($value, $data, $idPrefix);

        $outHtml = '<select name="' . $tdata['editName'] . '"'
            . ' data-type="' . $tdata['type'] . '"'
            . ' id="' . $tdata['editId'] . '"' . $tdata['cssClass'] . $tdata['tagParams'] . '>';

        // Сформируем шаблонные переменные для выпадающего списка
        if ($this->any && !$this->multiple) {
            $outHtml .= '<option value="%">' . $this->anyText . '</option>';
        }

        $selectedValue = (string)$value;

        if ($this->empty && !$this->multiple) {
            $selectedAttr = $this->isOptionSelected('', $selectedValue) ? ' selected=""' : '';
            $outHtml .= '<option value=""' . $selectedAttr . ' class="' . $this->emptyCssClass . '">' . $this->emptyText . '</option>';
        }

        $optgroups = [];
        foreach ($this->getEditOptions($value) as $optionValue => $option) {

            $selected = $this->isOptionSelected($optionValue, $selectedValue);
            $optgroup = '';
            $cssClass = '';
            $disabled = false;
            $icon = '';
            $img = '';
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

                if (isset($option['img'])) {
                    $img = $option['img'];
                }

                $text = $option['Name'] ?? $option['text'] ?? $optionValue;

            } else {

                $text = $option;

            }

            if (!$img && $this->avatar && $optionValue && is_numeric($optionValue)) {
                $img = User::avatarImgSrc($optionValue);
            }

            $optionHtml = '<option value="' . htmlspecialchars($optionValue) . '"'
                . ($selected ? ' selected=""' : '')
                . ($cssClass !== '' ? ' class="' . $cssClass . '"' : '')
                . ($disabled ? ' disabled=""' : '')
                . ($icon !== '' ? ' data-icon="' . $icon . '"' : '')
                . ($img !== '' ? ' data-img="' . $img . '"' : '')
                . ($title !== '' ? ' title="' . htmlspecialchars($title) . '"' : '')
                . '>'
                . htmlspecialchars((string) $text)
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
            if (str_ends_with($editName, ']')) {
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
                'placeholder' => $this->addNewPlaceholder,
            ];
            if ($this->editCssClass !== null) {
                $inputParams['editCssClass'] = $this->editCssClass;
            }
            $outHtml .= (new FieldString('', $inputParams))->getHtmlInput('', $data);
        }

        return $outHtml;
    }


    protected function getEditOptions($value): array
    {
        if ($this->select2 && $this->select2ajax) {
            // Для поля с параметрами 'select2'=> true, 'select2ajax' => true
            // в качестве опций в режиме редактирования сначала выводим всего одну опцию - то что выбрано сейчас
            if ($value === '%' || $value === '') {
                // Если выбрано какое-то пустое значение по умолчанию, то вообще возвращаем пустой массив
                return [];
            }

            if ($this->multiple) {
                $ids = $this->parsePipeSeparatedValue($value);
            } else {
                // Если это не Multiple поле, то в начальный момент времени может быть выбрано только одно значение
                // именно его и загрузим из базы и вернём в качестве $editOptions
                $ids = [$value];
            }

            // С помощью уже имеющейся оптимизированной функции prefetchViewOptions загружаем из базы только то, что нужно
            $this->prefetchOptions($ids);

            $editOptions = [];
            foreach ($ids as $id) {
                $editOptions[$id] = $this->getOption($id);
            }
            return $editOptions;
        }

        return $this->getEvaluatedOptions();
    }

    public function allowSqlWhereValue($value)
    {
        if ($value === '%') {
            return false;
        }
        if ($this->multiple && $value === '') {
            return false;
        }

        return true;
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = parent::getDataFromRequestInner($request);

        if (is_array($value)) {

            // Это было multiple поле
            $value = '|' . implode('|', $value) . '|';

        } elseif ($this->addNew && $value === '+') {

            $valueText = $request[$this->key . 'New'];
            if ($this->upper) {
                $valueText = mb_strtoupper((string) $valueText, 'UTF-8');
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

        } elseif ($this->select2 && $this->multiple && $value === null) {

            // Если функция предка определила значение как NULL - значит его не было в POST запросе
            // Однако, если это Select2 и Multiple - значит пользователь стёр все варианты и реально нам нужно
            // не null значение а пустое значение
            $value = '';
        }

        return $value;

    }

    protected function convertToNullIfNeeded($value)
    {
        if ($this->select2 && $this->multiple) {
            return $value;
        }
        return parent::convertToNullIfNeeded($value);
    }

    protected function getSqlWhereInner($value): string
    {
        if ($this->multiple && str_contains($value, '|')) {
            $values = $this->parsePipeSeparatedValue($value);
            if (!$values) {
                return 'TRUE';
            }
            $sql = [];
            foreach ($values as $singleValue) {
                if ($this->multipleSqlWhereOnMultiple) {
                    // Это multiple фильтр который применяется на поле, которое само по себе является multiple
                    // т.е. значения в этом поле являются VARCHAR с разделителем вертикальной чертой
                    $sql[] = "{fieldKey} LIKE '%|" . Db::esc($singleValue) . "|%'";
                } else {
                    $sql[] = str_replace("{value}", Db::esc($singleValue), parent::getSqlWhereInner($singleValue));
                }
            }
            return implode(" " . $this->multipleSqlWhereOperator . " ", $sql);
        }

        return parent::getSqlWhereInner($value);
    }

    public function prefetchOptions(array $ids): void
    {
        parent::prefetchOptions($ids);
        if (is_array($this->prefetchedOptions)) {
            // Если удалось загрузить массив каких-то опций, т.е. options был не статический array или callback,
            // то дозаполним prefetchedOptions типовыми пустыми значениями
            $this->prefetchedOptions += $this->getStandartEmptyOptionsForView();
        }
    }

    protected function getStandartEmptyOptionsForView(): array
    {
        $emptyOption = ['text' => $this->emptyText, 'cssClass' => $this->emptyCssClass];
        return [
            '0' => $emptyOption,
            '' => $emptyOption,
            null => $emptyOption,
            '%' => ['text' => $this->anyText, 'cssClass' => $this->emptyCssClass],
        ];
    }
}
