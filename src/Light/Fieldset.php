<?php

namespace Light;

use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldSelect;

class Fieldset implements \Iterator, \Countable, \ArrayAccess, \JsonSerializable
{
    /**
     * @var F[]
     */
    private $_fields = [];

    /**
     * Проиндексированный массив с ключами полей из массива $_fields - нужен для Iterator и $_position
     * @var array
     */
    private $_fieldKeys = [];

    //Внутренняя переменная для реализации интерфейса Iterator
    private $_position = 0;

    /**
     * В качестве параметров конструктору можно передать массив - тогда этот массив будет рассмотрен как массив из полей
     * Второй варинат: передать поля через запятую, тогда они будут добавлены в этот Fieldset
     */
    public function __construct()
    {
        $argc = func_num_args();

        if ($argc != 0) {
            $argv = func_get_args();

            if ($argc == 1 && is_array($argv[0])) {
                $this->setFields($argv[0]);
            } else {
                $this->setFields($argv);
            }
        }
    }

    /**
     * Для имплементации \Iterator
     */
    public function rewind(): void
    {
        $this->_position = 0;
    }

    /**
     * Для имплементации \Iterator
     * @return F
     */
    public function current()
    {
        return $this->_fields[$this->_fieldKeys[$this->_position]];
    }

    /**
     * Для имплементации \Iterator
     * @return int
     */
    public function key()
    {
        return $this->_position;
    }

    /**
     * Для имплементации \Iterator
     */
    public function next(): void
    {
        ++$this->_position;
    }

    /**
     * Для имплементации \Iterator
     * @return bool
     */
    public function valid()
    {
        return isset($this->_fieldKeys[$this->_position]);
    }

    /**
     * Для имплемментации \Countable
     * @return int
     */
    public function count()
    {
        return count($this->_fieldKeys);
    }

    /**
     * Для имплементации \ArrayAccess
     * @param $fieldKey
     * @param $field
     */
    public function offsetSet($fieldKey, $field): void
    {
        //Если это поле уже есть в массивах $this->_fields и $this->_fieldKeys, то не будем его трогать в $this->_fieldKeys
        //чтобы сохранить позицию
        if (!isset($this->_fields[$fieldKey])) {
            //Этого поля ещё нет, добавляем его в конец $this->_fieldKeys
            $this->_fieldKeys[] = $fieldKey;
        }

        $this->_fields[$fieldKey] = $field;
    }

    /**
     * Для имплементации \ArrayAccess
     * @param string $fieldKey
     * @return bool
     */
    public function offsetExists($fieldKey)
    {
        return isset($this->_fields[$fieldKey]);
    }

    /**
     * Для имплементации \ArrayAccess
     * Функция умышленно не реализована, поэтому выводит ошибку
     * @param string $fieldKey
     */
    public function offsetUnset($fieldKey): void
    {
        if (isset($this->_fields[$fieldKey])) {
            unset($this->_fields[$fieldKey]);
            $index = array_search($fieldKey, $this->_fieldKeys);
            unset($this->_fieldKeys[$index]);
            $this->_fieldKeys = array_values($this->_fieldKeys);
        }
    }

    /**
     * Для имплементации \ArrayAccess
     * @param string $fieldKey
     * @return F|null
     */
    public function offsetGet($fieldKey): ?F
    {
        return $this->_fields[$fieldKey] ?? null;
    }

    public function setFields(array $fieldsArr)
    {
        $this->_fields = [];
        foreach ($fieldsArr as $field) {
            $this->addField($field);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return count($this) === 0;
    }

    /**
     * Добавляет в массив Fields поля UpdatedBy, UpdatedDateTime со стандратными параметрами
     * Очень распространённая операция для многих модулей, поэтому функциональность вынесена в движок
     * @return \Light\Fieldset
     */
    public function addFieldsUpdatedBy()
    {
        //Специально выставляем Read Only, чтобы поле автоматически не превращалось в Input в режиме Edit
        $this->addField(
            F::createDateTime('UpdatedDateTime', [
                'name' => Config::$lang === 'ru' ? 'Дата и время обновления' : 'Updated Date, Time',
                'modes' => F::MODE_READONLY,
            ])
        );

        $this->addField(
            F::createSelect('UpdatedBy', [
                'name' => Config::$lang === 'ru' ? 'Автор обновления' : 'Updated By',
                'modes' => F::MODE_READONLY,
                'nobr' => true,
                'tableWidthExcel' => 18,
                'options' => 'user',
            ])
        );

        return $this;
    }

    /**
     * Добавляет в массив Fields поля CreatedBy, CreatedDateTime со стандратными параметрами
     * Очень распространённая операция для многих модулей, поэтому функциональность вынесена в движок
     * @return \Light\Fieldset
     */
    public function addFieldsCreatedBy()
    {
        //Специально выставляем Read Only, чтобы поле автоматически не превращалось в Input в режиме Edit
        $this->addFields(
            CommonFields::createdBy(),
            CommonFields::createdDateTime()
        );

        return $this;
    }

    /**
     * @param bool $clone
     * @return FieldSet
     */
    public function addField(F $field, $clone = false)
    {
        if ($clone) {
            $field = clone $field;
        }

        $this[$field->key] = $field;

        return $this;
    }

    public function createField($fieldKey, $type, array $params = []): void
    {
        $this->addField(F::create($fieldKey, $type, $params));
    }

    /**
     * Удобный метод - принимает переменное число параметров объектов Field
     * @param F[] $fields
     * @return Fieldset
     */
    public function addFields(...$fields)
    {
        if ((is_array($fields[0]) || $fields[0] instanceof Fieldset) && count($fields) === 1) {
            //Был передан всего один параметр и это массив или готовый Fieldset - используем его для обхода foreach
            $fields = $fields[0];
        }

        foreach ($fields as $field) {
            $this->addField($field);
        }

        return $this;
    }

    /**
     * Возвращает Fieldset из полей, у которых параметр 'modes' удовлетворяет переданному
     * Поля при этом не клонинуются, используются ссылки на поля текущего Fieldset
     * @param int $testMode битовая маска FieldAbstract::MODE_* - будут возвращены поля с удовлетворяющим параметром Modes
     * @return Fieldset|F[]
     */
    public function filterByMode(int $testMode, array $data = [])
    {
        $outFieldset = new self();
        foreach ($this->_fields as $field) {
            if ($field->hasMode($testMode)) {
                $outFieldset->addField($field);
            }
        }

        return $outFieldset;
    }

    /**
     * Возвращает Fieldset из полей, у которых параметры совпадают с переданными
     * @param array $filterParams массив из параметров key => val, все их которых должны удовлетворить
     * @return Fieldset|F[]
     */
    public function filterByParams($filterParams)
    {
        $outFieldset = new self();
        foreach ($this->_fields as $field) {
            //Проверим все параметры на соответствие образцу
            $allParamsSuccess = true;
            foreach ($filterParams as $key => $val) {
                if ($field->{$key} != $val) {
                    //Нашли параметр, который не соответствует
                    $allParamsSuccess = false;
                    break;
                }
            }

            if ($allParamsSuccess) {
                $outFieldset->addField($field);
            }
        }

        return $outFieldset;
    }

    /**
     * Возвращает Fieldset, который содержит поля только с указанными ключами
     * @return Fieldset|F[]
     */
    public function filterByKeys(array $fieldsKeys)
    {
        $outFieldset = new self();
        foreach ($fieldsKeys as $fieldKey) {
            if ($field = $this[$fieldKey]) {
                $outFieldset->addField($field);
            }
        }

        return $outFieldset;
    }

    /**
     * Проверяет, существует ли поле с таким key (в массиве $this->_fields)
     * @return bool
     */
    public function hasField(string $fieldKey)
    {
        return isset($this[$fieldKey]);
    }

    /**
     * Меняет определённый параметр у всех полей
     * @param $paramValue
     * @return $this
     */
    public function setParam(string $paramName, $paramValue)
    {
        foreach ($this->_fields as $field) {
            $field->setParam($paramName, $paramValue);
        }

        return $this;
    }

    /**
     * Меняет набор параметров у всех полей
     * @return $this
     */
    public function setParams(array $params)
    {
        foreach ($this->_fields as $field) {
            $field->setParams($params);
        }

        return $this;
    }

    /**
     * Функция должна использоваться там, где невозможно разименовать объект как массив
     * Например: $table->getFieldsetFirst()->getField(...)
     * @return F
     */
    public function getField(string $fieldKey)
    {
        return $this[$fieldKey];
    }

    /**
     * Возвращает массив данных, в котором остаются только те поля, которые присутсвуют в этом Fieldset
     * @return array $outData
     */
    public function getOnlyExistsFieldsData(array $fieldsData)
    {
        $outData = [];
        foreach ($fieldsData as $fieldKey => $value) {
            //Пропускаем поля, которых вообще нет в данном Fieldset
            if ($this->hasField($fieldKey)) {
                $outData[$fieldKey] = $value;
            }
        }

        return $outData;
    }

    /**
     * Для каждого поля выполняет метод $field->getValue($data[$field->key]) и результат кладёт в выходной массив
     * @param bool $forTable
     * @return array
     */
    public function calcData(array $rowData, $forTable = false)
    {
        foreach ($this->_fields as $field) {
            $value = $rowData[$field->key] ?? null;
            $value = $this->parseFieldValueFromDb($field, $value);
            $value = $field->getCalculatedValue($value, $rowData, $forTable);
            $rowData[$field->key] = $value;
        }

        return $rowData;
    }

    private function parseFieldValueFromDb(F $field, $value)
    {
        if ($field->groupconcat && str_contains($value, "\n")) {
            $lines = explode("\n", $value);
            //Если число линий больше 1 - будем рекурсивно обрабатывать каждую линию
            if (count($lines) > 1) {
                //Обработаем каждую строку через метод $this->getCalculatedValueOneLine() и соберём все строки в текст
                //через перевод строки
                foreach ($lines as &$lineValue) {
                    $lineValue = $field::parseFromDb($lineValue);
                }
                return implode("\n", $lines);
            }
        }

        return $field::parseFromDb($value);
    }

    /**
     * Возвращает массив с подготовленными для шаблона переменными
     * @param array $data
     * @param int $fieldMode
     * @return array
     */
    public function getTplData($data, $fieldMode)
    {
        // Разложим default данные в отсутсвующие поля $data
        foreach ($this->_fields as $field) {
            if (!array_key_exists($field->key, $data)) {
                $data[$field->key] = $field->default;
            }
        }

        return ['_fieldset' => $this, '_data' => $data, '_mode' => $fieldMode];
    }

    /**
     * Возвращает массив с подготовленными для вставки в XlsWriter::replaceData($excelData)
     * @param array $data
     * @return array
     */
    public function getExcelData($data)
    {
        $excelData = [];
        foreach ($this->_fields as $field) {
            $key = $field->key;
            $value = array_key_exists($key, $data) ? $data[$key] : $field->default;
            $excelData[$field->key] = $field->getExcel($value);
        }

        return $excelData;
    }

    /**
     * Возвращает массив полей с данными из Request преобразованных в формат для вставки в SQL
     * @param array
     * @return array
     */
    public function getDataFromRequest(array $request)
    {
        $outData = [];

        // Эта функция должна возвращать данные по всем полям, которые были объявлены в Fieldset как MODE_EDIT (или MODE_MULTIPLEUPDATE) И MODE_SUBMIT
        foreach ($this->_fields as $field) {
            /** @var F $field */
            $value = $field->getDataFromRequest($request);
            if ($value !== null) {
                if (is_array($value)) {
                    // Если возвращает несколько значений, запишем их все
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $outData = array_merge($outData, $value);
                } elseif ($value === 'n/a' && $field->getType() === 'Money') {
                    $outData[$field->key] = null;
                } else {
                    $outData[$field->key] = $value;
                }
            } elseif ($field->nullable && array_key_exists($field->key, $request)) {
                // Если из FieldAbstract::getDataFromRequest() вернулось значение null, тут можем быть несколько вариантов
                // 1) это поле вообще не приншо в $request (тогда его не нужно добавлять в $outData)
                // 2) это поле пришло в $request и пришло какое-то пустое значение и поле имеет флаг nullable (нужно добавлять в $outData)
                $outData[$field->key] = null;
            }
        }

        return $outData;
    }

    /**
     * Возвращает массив ключей полей
     * @return array
     */
    public function getKeys()
    {
        return $this->_fieldKeys;
    }

    public function getFieldsArr()
    {
        return $this->_fields;
    }

    /**
     * Возвращает строку для SELECT запроса состояющую из полей в массиве $fieldsShow и зависимых от них полей
     * Некоторые поля могут быть вычисляемыми или состоять из подзапроса (FieldAbstract параметр 'sqlSelect')
     * @return string
     */
    public function getSqlSelect(array $fieldsShow = null, string $table = null)
    {
        if ($fieldsShow === null) {
            $fieldsShow = $this->getKeys();
        }

        //Сначала добавим в массив $selectFieldsArr все обязательные поля
        $selectFieldKeys = $this->filterByParams(['sqlSelectMandatory' => true])->getKeys();
        $selectFieldsArr = array_combine($selectFieldKeys, $selectFieldKeys);

        //Пройдём по всему массиву, проверим зависимости, добавим зависимые поля
        foreach ($fieldsShow as $fieldKey) {
            $this->makeSelectFieldsArr($fieldKey, $selectFieldsArr);
        }

        //Массив полей для отображения сформирован, теперь сформируем SQL фрагменты
        $sqlArr = [];

        foreach ($selectFieldsArr as $fieldKey) {

            if (!$fieldKey) {
                continue;
            }

            // Возможно, это поле нужно загружать из базы как-то по особенному, если задан параметр sqlSelect?
            $sqlSelect = null;
            if ($this->hasField($fieldKey)) {

                /** @var F $field */
                $field = $this[$fieldKey];
                $sqlSelect = $field->getEvalueatedSqlSelect($table);

            } else {

                $sqlSelect = Db::quote($fieldKey);
                if ($table) {
                    $sqlSelect = Db::quote($table) . '.' . $sqlSelect;
                }

            }

            if ($sqlSelect) {
                $sqlArr[$fieldKey] = $sqlSelect;
            }

        }

        if (!isset($sqlArr['ID'])) {
            // Обязательно добавляем поле ID В число полей для запроса
            $sqlSelect = 'ID';
            if ($table) {
                $sqlSelect = Db::quote($table) . '.' . $sqlSelect;
            }
            $sqlArr['ID'] = $sqlSelect;
        }

        return implode(', ', $sqlArr);
    }

    /**
     * Рекурсивная функция, собирает в массив $selectFieldsArr поле $fieldKey и все поля от которых зависит $fieldKey
     */
    private function makeSelectFieldsArr(string $fieldKey, array &$selectFieldsArr): void
    {
        if (!$fieldKey) {
            return;
        }

        // Если запрашиваемое поле существует в текущем Fieldset и ещё не добавлено в $currentFieldsShow, добавим его!
        // Проверка !isset($selectFieldsArr[$fieldKey]) избавляет от циклических ссылок
        if (!isset($selectFieldsArr[$fieldKey])) {

            // Сразу добавляем ключ в результирующий массив.
            // Это поле на самом деле может и не существовать в Fieldset, но оно очевидно должно существовать
            // в базе данных в запрашиваемой таблице
            $selectFieldsArr[$fieldKey] = $fieldKey;

            if (isset($this[$fieldKey])) {
                // Если это реальное поле существующее в Fieldset,
                // то проверим от каких полей зависит данное поле $fieldKey
                /** @var F $field */
                $field = $this[$fieldKey];
                $requires = $field->getSqlSelectRequires();
                if ($requires) {
                    //Если параметр $sqlSelectRequires был задан одной строкой, превратим его в массив из этой строки
                    $requires = (array)$requires;
                    foreach ($requires as $reqFieldKey) {
                        if ($reqFieldKey) {
                            $this->makeSelectFieldsArr($reqFieldKey, $selectFieldsArr);
                        }
                    }
                }
            }
        }
    }

    public function getSqlWhere(array $filtersValues)
    {
        $sqlWhere = [];
        foreach ($this->_fields as $field) {
            if (array_key_exists($field->key, $filtersValues)) {
                $value = $filtersValues[$field->key];

                $sql = $field->getEvaluatedSqlWhere($value, $filtersValues);
                if ($sql) {
                    $sqlWhere[] = $sql;
                }
            }
        }

        if (!$sqlWhere) {
            return '1';
        }

        return '((' . implode(') AND (', $sqlWhere) . '))';
    }

    public function validateMandatory(array $dataFromRequest, array $currentObjData = [])
    {
        $errors = [];
        // Проверим заполненность остальных полей, помеченных как editMandatory
        foreach ($this->_fields as $field) {
            if (!$field->hasMode(F::MODE_SUBMIT)) {
                continue;
            }

            if (!$field->checkEditAccessFunction($currentObjData ?: $dataFromRequest)) {
                continue;
            }

            $errMsg = $field->validate($dataFromRequest, $currentObjData);
            if ($errMsg !== null) {
                $errors[] = [
                    'msg' => $errMsg,
                    'fieldKey' => $field->key,
                ];
            }
        }

        return $errors;
    }

    /**
     * Используется для добавления информации о полях формы в JavaScript окружение по средствам data атрибута, так:
     * <form name="SomeFormName" data-fields="<?= htmlspecialchars(json_encode($fields)) ?>">
     * затем в JavaScript коде можно работать с этой формой так:
     * var form = Light.forms.get('SomeFormName');
     * var someFieldValue = form.getFieldValue('fieldKey');
     * @return array
     */
    public function getFieldsForFormJs()
    {
        $fields = [];
        foreach ($this->_fields as $field) {
            /** @var F $field */
            $fields[$field->key] = ['type' => $field->getType()];
        }

        return $fields;
    }

    public function jsonSerialize()
    {
        return $this->_fields;
    }

    public function proceedSelect2ajax(string $fieldKey): ?array
    {
        if (str_ends_with($fieldKey, '[]')) {
            $fieldKey = substr($fieldKey, 0, -2);
        }

        $field = $this->getField($fieldKey);
        if (!$field || !($field instanceof FieldSelect)) {
            return null;
        }

        $select2ajaxController = new Select2ajaxController();
        return $select2ajaxController->proceed($field, Request::$psr7);
    }
}
