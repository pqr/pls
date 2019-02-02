<?php

namespace Light\Field;

use Light\A;
use Light\Css;
use Light\Db;
use Light\Js;
use Light\Response;

/**
 * @method $this setAny(bool $value)
 * @method $this setAnyPlaceholder(string $value)
 * @method $this setEmpty(bool $value)
 * @method $this setEmptyText(string $value)
 * @method $this setText(string $value)
 * @method $this setAddNew(bool $value)
 * @method $this setAddNewPlaceholder(string $value)
 * @method $this setSelect2(bool $value)
 * @method $this setSelect2ajax(bool $value)
 * @method $this setParentFieldKeys(string | array $value)
 * @method $this setParentFieldKeysMandatory(string | array $value)
 * @method $this setRelations(string | callable $value)
 * @method $this setMultiple(bool $value)
 * @method $this setMultipleSqlWhereOperator(string $value)
 * @method $this setMultipleViewSeparator(string $value)
 *
 * @method bool getAny()
 * @method string getAnyPlaceholder()
 * @method bool getEmpty()
 * @method string getEmptyText()
 * @method bool getAddNew()
 * @method string getAddNewPlaceholder()
 * @method bool getSelect2()
 * @method bool getSelect2ajax()
 * @method string|array getParentFieldKeys()
 * @method string|array getParentFieldKeysMandatory()
 * @method string|callable getRaltions()
 * @method bool getMultiple()
 * @method string getMultipleSqlWhereOperator()
 * @method string getMultipleViewSeparator()
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
    public $select2ajax = false;
    public $select2ajaxMin = 1;
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

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->default = '%';
        $this->editWidth = 'auto';
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
                $editTagParams = ' data-minimum-input-length=' . $this->select2ajaxMin;
            }
        }

        return trim($editTagParams);
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if (!$this->multiple) {
            $option = $this->getOption($value);
            return $this->getOptionHtml($option, $value, $forTable);
        }

        $values = $this->parsePipeSeparatedValue($value);
        $htmls = [];
        foreach ($values as $singleValue) {

            $option = $this->getOption($singleValue);
            $htmls[] = $this->getOptionHtml($option, $singleValue, $forTable);
        }

        return implode($this->multipleViewSeparator, $htmls);
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
        return array_filter(explode('|', $value));
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
        }

        $tdata = $this->getHtmlInputTplData($value, $data, $idPrefix);

        $outHtml = '<select name="' . $tdata['editName'] . '"'
            . ' data-type="' . $tdata['type'] . '"'
            . ' id="' . $tdata['editId'] . '"' . $tdata['cssClass'] . $tdata['tagParams'] . '>';

        // Сформируем шаблонные переменные для выпадающего списка
        if ($this->any && !$this->multiple) {
            $outHtml .= '<option value="%">' . $this->anyPlaceholder . '</option>';
        }

        $selectedValue = (string)$value;

        if ($this->empty && !$this->multiple) {
            $selectedAttr = $this->isOptionSelected('', $selectedValue) ? ' selected=""' : '';
            $outHtml .= '<option value=""' . $selectedAttr . '>' . $this->emptyText . '</option>';
        }

        $optgroups = [];
        foreach ($this->getEditOptions($value) as $optionValue => $option) {

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

    public function proceedSelect2ajax()
    {
        $options = $this->getEvaluatedOptions();

        if (isset($_GET['loadOptionIds'])) {
            // От клиента пришел запрос на загрузку одной конкретной опции
            // обычно это происходит, когда в фильтрах вызывается ранее сохранённый набор фильтров
            $ids = array_filter(explode(',', $_GET['loadOptionIds']));
            $returnOptions = [];
            foreach ($ids as $id) {
                if (isset($options[$id])) {
                    $returnOptions[$id] = $options[$id];
                }
            }
            Response::json([
                'results' => $this->getSelect2ajaxResultsArray($returnOptions)
            ]);
            return;
        }

        $parentFieldValuesFromAjax = $_GET['parentFieldValues'] ?? [];
        if ($parentFieldValuesFromAjax) {
            // Вместе с ajax запросом пришли какие-то значения parent полей
            // используем их, чтобы уменьшить массив валидных опций
            $options = $this->filterChildOptions($options, $parentFieldValuesFromAjax);
        }

        if (isset($_GET['updateCurrentChildValue'])) {
            // Это на самом деле не запрос на получение всех возможных опций
            // а запрос проверка - валидно ли текущее child значение в условиях новых parent значений?
            $currentChildValue = $_GET['updateCurrentChildValue'];
            $availableOptionsCount = count($options);

            if ($availableOptionsCount === 1
                && ($currentChildValue === '' || !isset($options[$currentChildValue]))
            ) {
                // Текущее значение в выпадающем списке на экране пользователя - пусто
                // либо текущее значение не присуствует среди допустимых значений
                // при этом допустимых значений всего ровно одно - используем его!
                // Если среди отфильтрованных $options осталось только одно возможное значение - именно его и нужно выбрать
                $theOnlyOption = reset($options);
                $theOnlyOptionValue = key($options);
                Response::json([
                    'action' => 'change',
                    'id' => $theOnlyOptionValue,
                    'text' => $this->getOptionText($theOnlyOption, $theOnlyOptionValue)
                ]);
                return;
            }

            // Если же текущее значение child выпадающего списка на экране пользователя не пусто, то нужно понять
            // является ли оно допустимым?
            if ($currentChildValue === '' || isset($options[$currentChildValue])) {
                // Да, текущее значение допустимо
                Response::json([
                    'action' => 'dontchange'
                ]);
                return;
            }

            // Текущее значение не допустимо, нужно отправить инструкцию на сборс на пустой вариант
            Response::json([
                'action' => 'reset'
            ]);
            return;
        }

        $searchTerm = $_GET['term'] ?? '';
        if ($searchTerm) {
            // Вместе с ajax запросом пришел параметр поиска по подстроке - отфильтруем по этой строке
            $options = $this->filterBySearchTerm($options, $searchTerm);
        }


        // Если дошли до этого места, значит мы работаем в режиме "показать все значения в выпадающем списке при раскрытии"
        // Соберём все полученные $options по optgroups
        $noOptgroup = [];
        $optgroups = [];
        $inactiveGroup = [];
        foreach ($options as $optionValue => $option) {

            if (is_array($option) && isset($option['Active']) && !$option['Active']) {
                // эта запись неактивна
                $inactiveGroup[$optionValue] = $option;
                continue;
            }

            if (is_array($option) && isset($option['optgroup']) && $option['optgroup'] !== '') {
                $optgroup = $option['optgroup'];
                $optgroups[$optgroup][$optionValue] = $option;
                continue;
            }

            $noOptgroup[$optionValue] = $option;
        }

        // Теперь добавляем всё в финальный массив $results
        // https://select2.org/data-sources/formats
        $results = [];
        if ($this->any && !$this->multiple) {
            $results[] = ['id' => '%', 'text' => $this->anyPlaceholder];
        }
        if ($this->empty && !$this->multiple) {
            $results[] = ['id' => '', 'text' => $this->emptyText];
        }

        // Сначала записываем все опции БЕЗ группы
        if ($noOptgroup) {
            $results = array_merge($results, $this->getSelect2ajaxResultsArray($noOptgroup));
        }

        // Затем перебираем все группы
        foreach ($optgroups as $optgroup => $options) {
            $results[] = [
                'text' => $optgroup,
                'children' => $this->getSelect2ajaxResultsArray($options)
            ];
        }

        // В самом конце добавляем группу Inactive
        if ($inactiveGroup) {
            $results[] = [
                'text' => 'Inactive',
                'children' => $this->getSelect2ajaxResultsArray($inactiveGroup)
            ];
        }

        Response::json(['results' => $results]);
    }

    /**
     * По полученному из ajax запроса массиву $parentFieldValuesFromAjax отфильтруем опции текущего FieldSelect
     * (который является child)
     *
     * @param array $options
     * @param array $parentFieldValuesFromAjax
     * @return array
     */
    private function filterChildOptions(array $options, array $parentFieldValuesFromAjax): array
    {
        $relations = $this->relations;
        if (!$relations) {
            // Для этого поля не заданы соответсвеия parent-child, возвращаем входящий массив как есть без фильтрации
            return $options;
        }

        // Проверим пришедший вместе с ajax запросом массив $parentFields - есть ли в нём не пустые значения?
        $parentFieldValuesFromAjax = array_filter($parentFieldValuesFromAjax, function ($parentValue) {
            $parentValue = (string)$parentValue;
            return $parentValue !== '' && $parentValue !== '%' && $parentValue !== '0' && $parentValue !== '||';
        });

        if ($this->parentFieldKeysMandatory) {
            $parentFieldKeysMandatory = is_string($this->parentFieldKeysMandatory)
                ? [$this->parentFieldKeysMandatory]
                : $this->parentFieldKeysMandatory;
            // Для данного поля определены некоторые parent поля, которые должны быть обязательно переданы
            // Проверим, все ли обязательные parent поля переданы с этим запросом?
            $fieldKeysDiff = array_diff($parentFieldKeysMandatory, array_keys($parentFieldValuesFromAjax));
            if ($fieldKeysDiff) {
                // Какие-то из обязательных полей указанных в $this->parentFieldKeysMandatory не переданы в запросе
                // тогда выведем пользователю пустой массив
                return [];
            }
        }

        if (!$parentFieldValuesFromAjax) {
            // Пришедшие от клиента по ajax значени parent полей все пустые, значит нет смысла фильтровать опции
            // текущиего child поля, возвращаем входящий массив как есть без фильтрации
            return $options;
        }

        // Расшифруем параметр $relations, записанный в настройках текщущего поля
        // - это может быть функция
        // - или sql запрос
        // - или готовый массив
        $relationsByCurrentFieldKey = null;
        if (is_callable($relations)) {
            // Пользователь определил метод, который возвращает массив связей - вызовем этот метод
            $relations = $relations($options, $parentFieldValuesFromAjax, $this);
        } elseif (is_string($relations)) {
            // Этот Relations в форме запроса - выполним запрос!
            $relations = Db::getAll($relations);
        }

        // Переформатируем массив relations так, чтобы в качестве ключей были значения относящиеся к текущему полю
        // Например, изначально массив $relations выглядел так:
        // $relations = [['CustomerAccountID' => 1, 'CustomerID' => 15], ['CustomerAccountID' => 1, 'CustomerID' => 16]]
        // После группировки по CustomerAccountID получим удобную для поиска структуру:
        // $relationsByCurrentFieldKey = [1 => [['CustomerID' => 15], ['CustomerID' => 16]]]
        $relationsByCurrentFieldKey = A::groupBy($relations, $this->key);

        $filteredOptions = [];

        foreach ($options as $optionValue => $option) {

            // Если заданы какие-то связи с родительскими полями, то нужно проверить текущий option - подходит ли он?
            if (!isset($relationsByCurrentFieldKey[$optionValue])) {
                // Текущее значение option вообще не отображено в массиве relations - значит для этого option
                // нет вообще удовлетворительной комбинации родительских полей
                continue;
            }

            // Проверим, удовлетворяет ли текущий optionValue массиву связям с родительскими полями?
            $relationsForThisChildValue = $relationsByCurrentFieldKey[$optionValue];
            if (!$this->isParentChildPass($relationsForThisChildValue, $parentFieldValuesFromAjax)) {
                // Текуща опция не проходит по условиям связи с родительскими полями
                continue;
            }

            $filteredOptions[$optionValue] = $option;
        }

        return $filteredOptions;

    }

    private function filterBySearchTerm(array $options, string $searchTerm): array
    {
        $out = [];
        foreach ($options as $optionValue => $option) {
            $optionText = $this->getOptionText($option, $optionValue);
            if (mb_stripos($optionText, $searchTerm) !== false) {
                $out[$optionValue] = $option;
            }
        }
        return $out;
    }

    /**
     * Проходит ли текущий элемент проверку по массиву связей parent-child?
     * @param array $relationsForThisChildValue
     * @param array $parentFieldValuesFromAjax
     * @return bool
     */
    private function isParentChildPass(array $relationsForThisChildValue, array $parentFieldValuesFromAjax): bool
    {
        // Вернём true, если хотя бы одна комбинация из массива $relations соответсвует значениям родительских полей
        // пришедших по ajax
        return A::some($relationsForThisChildValue, function ($relation) use ($parentFieldValuesFromAjax) {
            // Удовлетворяет ли текущая комбинация $relation загруженная из базы тем значениям $parentFieldValues,
            // которые пришли по ajax?
            foreach ($relation as $relationParentFieldKey => $relationParentFieldValue) {
                // Проверяем только те поля, которые реально пришли с ajax запросом
                if (isset($parentFieldValuesFromAjax[$relationParentFieldKey])) {
                    $valueFromAjax = $parentFieldValuesFromAjax[$relationParentFieldKey];
                    if (strpos($valueFromAjax, '|') !== false) {
                        $multipleValuesFromAjax = array_filter(explode('|', $valueFromAjax));
                        if (!in_array($relationParentFieldValue, $multipleValuesFromAjax)) {
                            // Текущая комбинация relations загруженная из базы провалила проверку, попробуем в цикле найти следующую
                            return false;
                        }
                    } elseif ($valueFromAjax != $relationParentFieldValue) {
                        // Текущая комбинация relations загруженная из базы провалила проверку, попробуем в цикле найти следующую
                        return false;
                    }
                }
            }

            // Проверку по всем родительcким полям прошли успешно!
            return true;
        });
    }

    private function getSelect2ajaxResultsArray(array $options)
    {
        $results = [];
        foreach ($options as $optionValue => $option) {
            $results[] = [
                'id' => $optionValue,
                'text' => $this->getOptionText($option, $optionValue)
            ];
        }
        return $results;
    }

    protected function getSqlWhereInner($value)
    {
        if ($this->multiple && strpos($value, '|') !== false) {
            $values = $this->parsePipeSeparatedValue($value);
            if (!$values) {
                return 'TRUE';
            }
            $sql = [];
            foreach ($values as $singleValue) {
                $sql[] = "{fieldKey} = " . Db::escString($singleValue);
            }
            return implode(" " . $this->multipleSqlWhereOperator . " ", $sql);
        }

        return parent::getSqlWhereInner($value);
    }

    public function prefetchOptions(array $ids)
    {
        parent::prefetchOptions($ids);
        if (is_array($this->prefetchedOptions)) {
            // Если удалось загрузить массив каких-то опций, т.е. options был не статический array или callback,
            // то дозаполним prefetchedOptions типовыми пустыми значениями
            $this->prefetchedOptions['0'] = $this->emptyText;
            $this->prefetchedOptions[''] = $this->emptyText;
            $this->prefetchedOptions[null] = $this->emptyText;
            $this->prefetchedOptions['%'] = $this->anyPlaceholder;
        }
    }


}
