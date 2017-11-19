<?php

namespace Light;

use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoneyWithCurrency;
use Light\Traits\FieldsetAwareTrait;

abstract class ObjAbstract implements \ArrayAccess
{
    use FieldsetAwareTrait;

    private $dbTable;

    /** @var string имя поля в Fieldset, которое будет использоваться в автосгенерированном Obj для логов */
    private $nameFieldKey;

    /** @var bool Флаг, чтобы не вызывать лишний раз loadData */
    protected $dirtyData = true;

    protected $_id = 0;
    protected $_data = [];
    private $masterModuleForDbLog;
    protected $_masterIdForDbLog;

    /** @var Fieldset|F[] */
    protected $_fieldset;

    private $_descriptionFunction;

    protected $_lastChangedData = [];

    final public function getFieldset()
    {
        // Лениво инициализируем fieldset, если его ещё нет
        // обычно его нет на момент запуска конструктора, когда дочерние классы делают $this->addFields(...)
        if (!$this->_fieldset) {
            $this->_fieldset = new Fieldset();
        }

        return $this->_fieldset;
    }

    /** Метод для переопределения в классах потомках
     * @deprecated
     */
    protected function init()
    {
    }

    public function setDescriptionFunction($function)
    {
        $this->_descriptionFunction = $function;
    }

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->_data[$offset] ?? null;
    }

    public function loadDataForDescription()
    {
        // Используем массив из одного обязательного поля $this->nameFieldKey
        // Если класс потомок использует другие поля для вывода описания
        // этим другим полям нужно сделать sqlSelectMandatory => true
        $query = $this->getDataQuery([$this->getNameFieldKey()]);
        if ($this->_data = Db::getRow($query)) {
            return true;
        }

        return false;
    }

    protected function getDataQuery(array $fieldKeys)
    {
        $fieldset = $this->getFieldset();

        if (!$fieldKeys) {
            //Если не указан какой-то определённый массив полей, то загружаем все поля
            $fieldKeys = $fieldset->getKeys();
        }

        // Соберём массив только тех полей, которые реально нужно загружать - это поля с режимами VIEW и EDIT и LOG (изменения в которых проверяются при записи в лог)
        $correctKeys = [];
        $correctMode = F::MODE_VIEW | F::MODE_EDIT | F::MODE_LOG;
        foreach ($fieldKeys as $fieldKey) {
            $field = $fieldset->getField($fieldKey);
            if ($field && $field->hasMode($correctMode)) {
                $correctKeys[] = $fieldKey;
            }
        }

        $query = "SELECT " . $fieldset->getSqlSelect($correctKeys);
        $query .= " FROM " . Db::quote($this->getDbTable());
        $query .= " WHERE ID = " . (int)$this->_id;

        return $query;
    }

    public function getFieldData($fieldKey)
    {
        return $this->_data[$fieldKey];
    }

    public function getFieldHtml($fieldKey)
    {
        return $this->getField($fieldKey)->getHtml($this->_data[$fieldKey], $this->_data);
    }

    public function getFieldText($fieldKey)
    {
        return $this->getField($fieldKey)->getText($this->_data[$fieldKey], $this->_data);
    }

    public function getFieldExcel($fieldKey)
    {
        return $this->getField($fieldKey)->getExcel($this->_data[$fieldKey], $this->_data);
    }

    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param $id
     * @return ObjAbstract
     */
    public function setId($id)
    {
        $this->_id = (int)$id;
        if ($this->_id < 0) {
            $this->_id = 0;
        }
        //Устанавилваем флаг dirtyData в true - это сигнал к тому, что текущее состояние массива data
        //может не соответсвовать тому что в базе (и действительно, ведь мы поменял ID!)
        $this->dirtyData = true;
        return $this;
    }

    public function getMasterIdForDbLog()
    {
        return $this->_masterIdForDbLog;
    }

    public function setMasterIdForDbLog($masterId)
    {
        $this->_masterIdForDbLog = (int)$masterId;
        if ($this->_masterIdForDbLog < 0) {
            $this->_masterIdForDbLog = 0;
        }
        return $this;
    }

    public function getMasterModuleForDbLog()
    {
        if ($this->masterModuleForDbLog === null) {
            //masterModule можно определить из неймспейса: <ProjectNamespace>\Module\<ModuleNamespace>\...
            $namespaces = explode('\\', get_class($this));
            if (isset($namespaces[2], $namespaces[1]) && $namespaces[1] === 'Module') {

                $this->masterModuleForDbLog = $namespaces[2];

            } else {

                // Иногда из неймспейсов не удаётся определить модуль, к которому относится текущий объект.
                // Например, если бы работаем в рамках какого-то простого ModuleAbstract в котором нет отдельного класса Obj
                // и используется \Light\SimpleObj
                // В этом случае сделаем примитивную инициализацию из Request::getModule()
                // (это не работает при запуске из консоли Autolaunch скриптов, но обычно в Autolaunch используются
                // объекты описанные отдельными классами и там модуль вычисляется из нейсмпейсов)

                $this->masterModuleForDbLog = Request::getModule();

            }
        }

        return $this->masterModuleForDbLog;
    }

    public function setMasterModuleForDbLog($masterModule)
    {
        $this->masterModuleForDbLog = $masterModule;
        return $this;
    }

    public function getDbTable()
    {
        // Лениво инициализируем _dbTable, если имя таблицы не было явно задано в конструкторе
        // Обычно имя талицы не задаётся, т.к. его легко можно вычислить из имени класса
        if ($this->dbTable === null) {
            //Уберём имя неймспейса(ов) из класса
            $namespaces = explode('\\', get_class($this));
            $class = end($namespaces);
            if ($class !== 'Obj' && substr($class, -3) === 'Obj') {
                $class = substr($class, 0, -3);
                //Имя таблицы обычно совпадает с именем класса за вычетом суффикса Obj
                $this->dbTable = strtolower($class);
            }
        }
        return $this->dbTable;
    }

    public function setDbTable($dbTable)
    {
        $this->dbTable = $dbTable;
    }

    public function getNameFieldKey()
    {
        if ($this->nameFieldKey === null) {
            // Если параметр nameFieldKey не был проинициализирован явным образом, то попробуем использовать поле 'Name'
            // Если поля Name нет в текущем объекте, тогда будем использовать ID
            $this->nameFieldKey = $this->hasField('Name') ? 'Name' : 'ID';
        };
        return $this->nameFieldKey;
    }

    public function setNameFieldKey($fieldKey)
    {
        $this->nameFieldKey = $fieldKey;
    }

    /**
     * @param array $fieldsData
     * @param string $dbLogMsg
     * @return int кол-во обновлённых строк
     */
    public function updateDb(array $fieldsData, $dbLogMsg = '')
    {
        //$fieldsData = $this->_fieldset->getOnlyExistsFieldsData($fieldsData);
        if (!$fieldsData) {
            return 0;
        }

        if ($this->dirtyData) {
            $this->loadData();
        }
        $oldData = $this->_data;

        $fieldKeys = array_keys($fieldsData);

        //Подготовим данные для вставки в базу. Например, расчленим MoneyWithCurrency
        $fieldsData = $this->prepareFieldsDataForDb($fieldsData);

        $this->_lastChangedData = [];
        if ($affectedRows = Db::updateRow($fieldsData, $this->getDbTable(), $this->_id)) {
            //Если обновление реально произошло (что-то изменилось), запишем логи
            $this->loadData();
            $newData = $this->_data;
            $this->writeCompareLog($oldData, $newData, $fieldKeys, $dbLogMsg);

            //Обновим дату обновления
            $sqlUpdatedBy = [];
            //Добавим UpdatedDateTime и UpdatedBy, если такие поля зарегистрированы в $this->Fields
            if ($this->_fieldset->hasField('UpdatedDateTime')) {
                $sqlUpdatedBy['UpdatedDateTime'] = date('Y-m-d H:i:s');
            }

            if ($this->_fieldset->hasField('UpdatedBy')) {
                $sqlUpdatedBy['UpdatedBy'] = User::getId();
            }

            if ($sqlUpdatedBy) {
                Db::updateRow($sqlUpdatedBy, $this->getDbTable(), $this->_id);
            }
        }

        return $affectedRows;
    }

    /**
     * Этот метод вызывается внутри insertDb() и updateDb() - он подготавливает данные перед передачей в Db::insertRow()
     * или Db::updateRow(). Некоторые типы полей нужно предварительно переформатировать, например, MoneyWithCurrency может
     * прийти в качестве строки 'EUR 123.22' - нужно разделить на два поля для вставки в базу
     * @param array $fieldsData
     * @return array
     */
    private function prepareFieldsDataForDb(array $fieldsData)
    {
        foreach ($this->getFieldset() as $field) {
            if ($field->getType() == F::TYPE_MONEY_WITH_CURRENCY) {
                //Проверим, есть ли это поле в $fieldsData и есть ли внутри пробел?
                $fieldKey = $field->key;
                if (isset($fieldsData[$fieldKey])) {
                    $val = $fieldsData[$fieldKey];
                    if (is_string($val) && isset($val[3]) && $val[3] === ' ') {
                        //Это строка и в ней есть пробел на четвёртой позиции - значит надо разбить на два поля!
                        list($currencyIso, $amount) = FieldMoneyWithCurrency::getAsTouple($val);
                        //Запишем сумму непосредственно в поле $fieldKey
                        $fieldsData[$fieldKey] = $amount;
                        //Добавим поле с валютой, но для этого нужно сначала получить имя поля для валюты
                        /** @var FieldMoneyWithCurrency $field */
                        $currencyFieldKey = $field->getCurrencyIsoDbFieldKey();
                        $fieldsData[$currencyFieldKey] = $currencyIso;
                    }
                }
            }
        }

        return $fieldsData;
    }

    public function getLastChangedData()
    {
        return $this->_lastChangedData;
    }

    /**
     * Устанавилвает id этого объекта и загружает его из базы. Возвращает true в случае удачной загрузки
     * @param array $fieldKeys
     * @return bool
     */
    public function loadData(array $fieldKeys = [])
    {
        $query = $this->getDataQuery($fieldKeys);
        if ($this->_data = Db::getRow($query)) {
            //Для каждого поля описанного в $this->_fieldset дополнительно вызовем метод getCalculatedValue()
            //Сохраним результат обратно в массив $this->_data
            $this->calcData();
            //Сразу после загрузки данных из базы снимаем флаг dirtyData - признак свежих данных!
            $this->dirtyData = false;
            return true;
        }

        return false;
    }

    public function calcData()
    {
        // Для каждого поля описанного в $this->_fieldset дополнительно вызовем метод getCalculatedValue()
        // Сохраним результат обратно в массив $this->_data
        $this->_data = array_merge($this->_data, $this->getFieldset()->calcData($this->_data, false));
    }

    final public function getData()
    {
        return $this->_data;
    }

    final public static function getDataStatic($id)
    {
        $className = get_called_class();
        /** @var ObjAbstract $obj */
        $obj = new $className;
        $obj->setId($id);
        return $obj->loadData() ? $obj->getData() : null;
    }

    public function setData($data)
    {
        $this->_data = $data;
        $this->dirtyData = true;
        return $this;
    }

    public function mergeData(array $data)
    {
        $this->_data = array_merge($this->_data, $data);
        $this->dirtyData = true;
        return $this;
    }

    protected function writeCompareLog(array $oldData, array $newData, array $fieldKeys, $dbLogMsg)
    {
        $fieldset = $this->getFieldset();

        foreach ($fieldKeys as $fieldKey) {

            //Поле должно существовать в массиве новых данных
            if (!array_key_exists($fieldKey, $newData)) {
                continue;
            }

            $field = $fieldset[$fieldKey];
            if ($field === null) {
                //Поля почему-то нет в Fieldset данного объекта - невозможно сделать сравнение со старыми данными, пропускаем
                continue;
            }

            if (!$field->hasMode(F::MODE_LOG)) {
                //Если у поля отключён modes LOG - пропускаем
                continue;
            }

            $oldValue = $oldData[$field->key] ?? null;
            $newValue = $newData[$field->key];

            //Поле должно существовать и его значени должно отличаться от старого
            //Если нет массива $oldData (он пуст) - скорее всего это создание новой записи и нет смысла формировать old html
            $oldValueFormated = $oldData ? $field->getHtmlForLog($oldValue, $oldData) : '';
            $newValueFormated = $field->getHtmlForLog($newValue, $newData);

            if ($newValueFormated !== $oldValueFormated || $oldValue != $newValue) {

                $masterModule = (string)$this->getMasterModuleForDbLog();
                $masterId = (int)($this->_masterIdForDbLog ?: $this->_id);
                $tableName = (string)$this->getDbTable();
                $recordId = (int)$this->_id;
                $fieldDescription = (string)$field->getNameDbLog();
                $textData = [
                    'OldValue' => $oldValue,
                    'OldValueFormated' => $oldValueFormated,
                    'NewValue' => $newValue,
                    'NewValueFormated' => $newValueFormated,
                    'Text' => $dbLogMsg,
                    'ObjectDescription' => $this->getDescriptionHtml()
                ];

                DbLog::write($masterModule, $masterId, $tableName, $recordId, $field->key, $fieldDescription,
                    $textData);

                $this->_lastChangedData[$field->key] = [
                    'OldValue' => $oldValue,
                    'OldValueFormated' => $oldValueFormated,
                    'NewValue' => $newValue,
                    'NewValueFormated' => $newValueFormated,
                ];
            }
        }
    }

    public function __toString()
    {
        return strip_tags($this->getDescriptionHtml());
    }

    public function getDescriptionHtml()
    {
        $nameFieldKey = $this->getNameFieldKey();
        if (array_key_exists($nameFieldKey, $this->_data) && $this->hasField($nameFieldKey)) {
            $description = $this->getFieldHtml($nameFieldKey);
        } else {
            $description = 'Unknown';
        }

        if ($this->_descriptionFunction) {
            $function = $this->_descriptionFunction;
            $description = $function($this, $description);
        }

        return $description;
    }

    /**
     * @param array $fieldsData
     * @param string $dbLogMsg
     * @return int $id вставленного объекта
     */
    public function insertDb(array $fieldsData, $dbLogMsg = '')
    {
        $fieldKeys = array_keys($fieldsData);

        if (!isset($fieldsData['CreatedBy'])) {
            $fieldsData['CreatedBy'] = User::getId();
        }

        //Подготовим данные для вставки в базу. Например, расчленим MoneyWithCurrency
        $fieldsData = $this->prepareFieldsDataForDb($fieldsData);

        //Используем REPLACE, чтобы избежать ошибки Already Exists на сложных таблицах с уникальными полями
        $id = Db::insertRow($fieldsData, $this->getDbTable(), true);

        $this->setId($id);
        $this->loadData();

        //Запишем логи о вставке, в качестве oldData передаём пустой массив
        $newData = $this->_data;

        $msg = '<span class="good">Created</span>';
        if ($dbLogMsg) {
            $msg .= ' ' . $dbLogMsg;
        }
        $this->writeCompareLog([], $newData, $fieldKeys, $msg);

        return $id;
    }

    public function deleteDb($dbLogMsg = '')
    {
        //Безопасное удаление в "корзину" (данные сериализуются в JSON и сохраняются в таблицу deleted)
        Db::deleteRow($this->getDbTable(), 'ID', $this->_id);

        //запишем в базу логов
        $msg = $this->getDescriptionHtml() . ' <span class="bad">deleted</span>';
        if ($dbLogMsg) {
            $msg .= ' ' . $dbLogMsg;
        }
        $this->writeDbLogText($msg);
    }

    public function writeDbLogText($text, $fieldKey = '', $fieldDescription = '')
    {
        $masterModule = (string)$this->getMasterModuleForDbLog();
        $masterId = (int)($this->_masterIdForDbLog ?: $this->_id);
        $tableName = (string)$this->getDbTable();
        $recordId = (int)$this->_id;

        //Если передан fieldKey, но не передан fieldDescription, то вычислим последний
        if ($fieldKey && !$fieldDescription) {
            if ($this->hasField($fieldKey)) {
                $fieldDescription = $this->getField($fieldKey)->getNameDbLog();
            } else {
                $fieldDescription = $fieldKey;
            }
        }

        $fieldKey = (string)$fieldKey;
        $fieldDescription = (string)$fieldDescription;

        $textData = [
            'Text' => $text,
            'ObjectDescription' => $this->getDescriptionHtml()
        ];

        DbLog::write($masterModule, $masterId, $tableName, $recordId, $fieldKey, $fieldDescription, $textData);
    }

    /**
     * Возвращает массив с подготовленными для вставки в XlsWriter::replaceData($excelData)
     * @return array
     */
    public function getExcelData()
    {
        return $this->getFieldset()->getExcelData($this->_data);
    }

    public function getTplData($fieldMode)
    {
        return $this->getFieldset()->getTplData($this->_data, $fieldMode);
    }

    public function getOnlySubmitFieldsData(array $fieldsData)
    {
        $outData = [];
        foreach ($fieldsData as $fieldKey => $value) {
            if ((strpos($fieldKey, 'CurrencyISO') !== false && !$this->hasField($fieldKey))
                || ($this->hasField($fieldKey) && $this->getField($fieldKey)->hasMode(F::MODE_SUBMIT))
            ) {
                $outData[$fieldKey] = $value;
            }
        }

        return $outData;
    }

    final public function __call($method, $args)
    {
        $setOrGet = substr($method, 0, 3);
        $fieldKey = substr($method, 3);
        if ($setOrGet === 'set') {

            $this->_data[$fieldKey] = $args[0];
            $this->dirtyData = true;
            return $this;

        } elseif ($setOrGet === 'get') {

            return $this->_data[$fieldKey] ?? null;

        }

        $class = get_class($this);
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR);

        return null;
    }
}
