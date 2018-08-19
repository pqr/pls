<?php

namespace Light;

use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldOptionsAbstract;

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
    public function rewind()
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
    public function next()
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
    public function offsetSet($fieldKey, $field)
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
    public function offsetUnset($fieldKey)
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
     * @return \Light\Field\FieldAbstract
     */
    public function offsetGet($fieldKey)
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

    public function isEmpty()
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
                'options' => 'user'
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
     * @param \Light\Field\FieldAbstract $field
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

    public function createField($fieldKey, $type, array $params = [])
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
     * @param int $modes битовая маска FieldAbstract::MODE_* - будут возвращены поля с удовлетворяющим параметром Modes
     * @return Fieldset|F[]
     */
    public function filterByMode($modes)
    {
        $outFieldset = new self();
        foreach ($this->_fields as $field) {
            if ($field->hasMode($modes)) {
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
     * @param array $fieldsKeys
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
     * @param string $fieldKey
     * @return bool
     */
    public function hasField($fieldKey)
    {
        return isset($this[$fieldKey]);
    }

    /**
     * Меняет определённый параметр у всех полей
     * @param string $paramName
     * @param $paramValue
     * @return $this
     */
    public function setParam($paramName, $paramValue)
    {
        foreach ($this->_fields as $field) {
            $field->setParam($paramName, $paramValue);
        }

        return $this;
    }

    /**
     * Меняет набор параметров у всех полей
     * @param array $params
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
     * @param string $fieldKey
     * @return F
     */
    public function getField($fieldKey)
    {
        return $this[$fieldKey];
    }

    /**
     * Возвращает массив данных, в котором остаются только те поля, которые присутсвуют в этом Fieldset
     * @param array $fieldsData
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
     * @param array $rowData
     * @param bool $forTable
     * @return array
     */
    public function calcData(array $rowData, $forTable = false)
    {
        foreach ($this->_fields as $field) {
            $value = $rowData[$field->key] ?? null;
            $value = $field::parseFromDb($value);
            if ($field->calcFunction) {
                $value = $field->getCalculatedValue($value, $rowData, $forTable);
            }
            $rowData[$field->key] = $value;
        }

        return $rowData;
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

        //Эта функция должна возвращать данные по всем полям, которые были объявлены в Fieldset как MODE_EDIT (или MODE_MULTIPLEUPDATE) И MODE_SUBMIT
        foreach ($this->_fields as $field) {
            /** @var \Light\Field\FieldAbstract $field */
            $value = $field->getDataFromRequest($request);
            if ($value !== null) {
                if (is_array($value)) {
                    //Если возвращает несколько значений, запишем их все
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $outData = array_merge($outData, $value);
                } else {
                    if ($value === 'n/a' && $field->getType() === 'Money') {
                        $outData[$field->key] = null;
                    } else {
                        $outData[$field->key] = $value;
                    }
                }
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
     * @param array $fieldsShow
     * @return string
     */
    public function getSqlSelect(array $fieldsShow = null)
    {
        if ($fieldsShow === null) {
            $fieldsShow = $this->getKeys();
        }

        //Сначала добавим в массив $selectFieldsArr все обязательные поля
        $selectFieldsArr = $this->filterByParams(['sqlSelectMandatory' => true])->getKeys();

        //Пройдём по всему массиву, проверим зависимости, добавим зависимые поля
        foreach ($fieldsShow as $fieldKey) {
            $this->makeSelectFieldsArr($fieldKey, $selectFieldsArr);
        }

        //Массив полей для отображения сформирован, теперь сформируем SQL фрагменты
        $sqlArr = [];

        foreach ($selectFieldsArr as $fieldKey) {
            /** @var $field \Light\Field\FieldAbstract */
            $field = $this[$fieldKey];
            $sqlSelect = $field->getSqlSelect();
            if ($sqlSelect) {
                $sqlArr[$fieldKey] = '(' . $sqlSelect . ') AS ' . Db::quote($fieldKey);
            } elseif ($sqlSelect === null) {
                //Если параметр совсем не инициализирован, по умолчанию считаем, что нужно просто вывести поле
                //Альтернативный варинат: пользователь может указать sqlSelect = false, тогда поле не будет
                //включено в SQL запрос
                $sqlArr[$fieldKey] = Db::quote($fieldKey);
            }
        }

        if (!isset($sqlArr['ID'])) {
            //Обязательно добавляем поле ID В число полей для запроса
            $sqlArr['ID'] = 'ID';
        }

        return implode(', ', $sqlArr);
    }

    /**
     * Рекурсивная функция, собирает в массив $selectFieldsArr поле $fieldKey и все поля от которых зависит $fieldKey
     * @param string $fieldKey
     * @param array $selectFieldsArr
     */
    private function makeSelectFieldsArr($fieldKey, array &$selectFieldsArr)
    {
        //Если запрашиваемое поле существует в текущем Fieldset и ещё не добавлено в $currentFieldsShow, добавим его!
        //Проверка !isset($selectFieldsArr[$fieldKey]) избавляет от циклических ссылок
        if (isset($this[$fieldKey]) && !isset($selectFieldsArr[$fieldKey])) {
            $selectFieldsArr[$fieldKey] = $fieldKey;

            //Проверим от каких полей зависит данное поле $fieldKey
            /** @var $field \Light\Field\FieldAbstract */
            $field = $this[$fieldKey];
            $requires = $field->getSqlSelectRequires();
            if ($requires) {
                //Если параметр $sqlSelectRequires был задан одной строкой, превратим его в массив из этой строки
                $requires = (array)$requires;
                foreach ($requires as $reqFieldKey) {
                    $this->makeSelectFieldsArr($reqFieldKey, $selectFieldsArr);
                }
            }
        }
    }

    public function getSqlWhere(array $filtersValues)
    {
        $sqlWhere = [];
        foreach ($this->_fields as $field) {
            if (isset($filtersValues[$field->key])) {
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

        return '(' . implode(' AND ', $sqlWhere) . ')';
    }

    public function validateMandatory(array $dataFromRequest)
    {
        $errors = [];
        //Проверим заполненность остальных полей, помеченных как editMandatory
        foreach ($this->_fields as $field) {
            if (!$field->editMandatory || !($field->modes & F::MODE_SUBMIT)) {
                continue;
            }

            $type = $field->getType();

            /** @var $field \Light\Field\FieldAbstract */
            if (!isset($dataFromRequest[$field->key])
                || $dataFromRequest[$field->key] === ''
                || ($type === 'Int' && $dataFromRequest[$field->key] === 0)
                || ($type === 'Float' && $dataFromRequest[$field->key] === 0.0)
                || ($type === 'Money' && $dataFromRequest[$field->key] === 0.0)) {

                $msg = 'empty ' . $field->getName();

                // Стандартные сообщения для пользователя могут быть заданы внтури параметра editMandatory
                if (is_string($field->editMandatory)) {
                    $msg = $field->editMandatory;
                }

                $errors[] = [
                    'msg' => $msg,
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
            /** @var $field \Light\Field\FieldAbstract */
            $fields[$field->key] = ['type' => $field->getType()];
        }

        return $fields;
    }

    public function jsonSerialize()
    {
        return $this->_fields;
    }

    public function prefetchSelectOptionsView(array $data)
    {
        foreach ($this->_fields as $field) {
            if ($field->hasMode(F::MODE_VIEW) && $field instanceof FieldOptionsAbstract) {
                $fieldKey = $field->key;
                if (isset($data[$fieldKey])) {
                    $value = $data[$fieldKey];
                    $field->prefetchViewOptions([$value]);
                }
            }
        }
    }

    public function prefetchSelectOptionsViewTableData(array $tableData)
    {
        foreach ($this->_fields as $field) {
            if ($field->hasMode(F::MODE_VIEW) && $field instanceof FieldOptionsAbstract) {
                $fieldKey = $field->key;
                $columnValues = array_column($tableData, $fieldKey);
                if ($columnValues) {
                    $field->prefetchViewOptions($columnValues);
                }
            }
        }
    }
}
