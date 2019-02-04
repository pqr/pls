<?php

namespace Light;

use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoneyWithCurrency;
use Light\SubtableBase\Subtable;
use Light\Traits\FieldsetAwareTrait;

abstract class ObjAbstract implements \ArrayAccess, \JsonSerializable
{
    use FieldsetAwareTrait;

    private $dbTable;

    /** @var string имя поля в Fieldset, которое будет использоваться в автосгенерированном Obj для логов */
    protected $nameFieldKey = 'Name';

    /** @var bool Флаг, чтобы не вызывать лишний раз loadData */
    protected $dirtyData = true;

    protected $_id = 0;
    protected $_data = [];

    private $justInserted = false;
    private $alreadyUpdated = false;
    // Массив с данными, которые были в объекте перед первым update/insert.
    // Все последующие update уже не меняют oldData, таким образом после череды update
    // можно сравнить текущее состояние data и oldData
    private $oldData = [];

    private $masterModuleForDbLog;
    protected $_masterIdForDbLog;

    /** @var Fieldset|F[] */
    protected $_fieldset;

    private $_descriptionFunction;

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

    protected function getDataQuery()
    {
        $fieldset = $this->getFieldset();
        $fieldKeys = $fieldset->getKeys();

        // Соберём массив только тех полей, которые реально нужно загружать - это поля с режимами VIEW и EDIT и LOG (изменения в которых проверяются при записи в лог)
        $correctKeys = [];
        $correctMode = F::MODE_VIEW | F::MODE_EDIT | F::MODE_LOG;
        foreach ($fieldKeys as $fieldKey) {
            if ($fieldset->hasField($fieldKey) && $fieldset->getField($fieldKey)->hasMode($correctMode)) {
                $correctKeys[] = $fieldKey;
            }
        }

        return "SELECT {$fieldset->getSqlSelect($correctKeys)}
                FROM " . Db::quote($this->getDbTable()) . "
                WHERE ID = " . (int)$this->_id;
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
    public function setId(int $id)
    {
        if ($id !== $this->_id) {
            // Если у объекта пеочему-то меняется id, то:

            // 1) Устанавилваем флаг dirtyData в true - это сигнал к тому, что текущее состояние массива data
            // может не соответсвовать тому что в базе (и действительно, ведь мы поменял ID!)
            $this->dirtyData = true;

            // 2) Очищаем oldData, т.к. мы по сути работаем уже с новым объектом
            $this->oldData = null;
        }

        $this->_id = $id;

        return $this;
    }

    public function getMasterIdForDbLog()
    {
        return $this->_masterIdForDbLog;
    }

    public function setMasterIdForDbLog(int $masterId)
    {
        $this->_masterIdForDbLog = $masterId;
        return $this;
    }

    public function getMasterModuleForDbLog()
    {
        if ($this->masterModuleForDbLog === null) {
            $this->masterModuleForDbLog = Misc::getModule($this);
        }

        return $this->masterModuleForDbLog;
    }

    public function setMasterModuleForDbLog(string $masterModule)
    {
        // Поскольку в переменной $masterModule может прийти fully qualified class name, приведём к короткому имени
        $this->masterModuleForDbLog = Misc::getModule($masterModule);
        return $this;
    }

    public function getDbTable()
    {
        // Лениво инициализируем dbTable, если имя таблицы не было явно задано в конструкторе
        // Обычно имя талицы не задаётся, т.к. его легко можно вычислить из имени класса
        if ($this->dbTable === null) {
            $className = Misc::getClass($this);
            if ($className !== 'Obj' && substr($className, -3) === 'Obj') {
                $className = substr($className, 0, -3);
                //Имя таблицы обычно совпадает с именем класса за вычетом суффикса Obj
                $this->dbTable = strtolower($className);
            }
        }
        return $this->dbTable;
    }

    public function setDbTable(string $dbTable)
    {
        $this->dbTable = $dbTable;
    }

    public function getNameFieldKey(): string
    {
        return $this->hasField($this->nameFieldKey) ? $this->nameFieldKey : 'ID';
    }

    public function setNameFieldKey(string $fieldKey)
    {
        $this->nameFieldKey = $fieldKey;
    }

    /**
     * @param array $fieldsData
     * @param string $dbLogMsg
     * @return int кол-во обновлённых строк
     * @throws \Exception
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

        if (!$this->justInserted && !$this->alreadyUpdated) {
            // Если этот объект ещё не мутировался ни разу, сохраним начальное состояние во врутреннем массиве
            $this->oldData = $this->_data;
        }

        // Отдельно сохраним текущее сосотояние в $oldData, чтобы записать изменения в dblog
        $oldData = $this->_data;

        $fieldKeys = array_keys($fieldsData);

        //Подготовим данные для вставки в базу. Например, расчленим MoneyWithCurrency
        $fieldsData = $this->prepareFieldsDataForDb($fieldsData);

        if ($affectedRows = Db::updateRow($fieldsData, $this->getDbTable(), $this->_id)) {
            //Если обновление реально произошло (что-то изменилось), запишем логи
            $this->loadData();
            $this->writeCompareLog($oldData, $fieldKeys, $dbLogMsg);

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

    /**
     * Возвращает true, если объект был только что создан (insertDb()) или в процессе updateDb() изменилось значение
     * этого поля
     * @param string $fieldKey
     * @return bool
     */
    public function isChanged(string $fieldKey): bool
    {
        if (!array_key_exists($fieldKey, $this->_data)) {
            // Если в текущем массиве данных нет этого поля, значит пользователь передал какой-то ошибочный fieldKey
            // просто вернём false, будто поле не менялось
            return false;
        }

        if (!$this->oldData || !array_key_exists($fieldKey, $this->oldData)) {
            // Если в старом массиве данных нет этого поля, скорее всего объект был только что создан,
            // считаем поле изменившимся
            return true;
        }

        $oldValue = $this->oldData[$fieldKey];
        $newValue = $this->_data[$fieldKey];

        return $oldValue !== $newValue;
    }

    /**
     * Возвращает true, если поле было изменено в процессе updateDb()
     * Но при вставке нового объекта через insertDb() этот метод всегда будет возвращать false
     * @param string $fieldKey
     * @return bool
     */
    public function isUpdated(string $fieldKey): bool
    {
        if ($this->justInserted) {
            false;
        }

        if (!array_key_exists($fieldKey, $this->_data)) {
            // Если в текущем массиве данных нет этого поля, значит пользователь передал какой-то ошибочный fieldKey
            // просто вернём false, будто поле не менялось
            return false;
        }

        if (!$this->oldData || !array_key_exists($fieldKey, $this->oldData)) {
            // Если в старом массиве данных нет этого поля, это странно, но вернём true, будто поле обновилось
            return true;
        }

        $oldValue = $this->oldData[$fieldKey];
        $newValue = $this->_data[$fieldKey];

        return $oldValue !== $newValue;
    }

    /**
     * Возвращает true, если поле изменило своё значение с пустого на заполненное
     * @param string $fieldKey
     * @return bool
     */
    public function isFilled(string $fieldKey): bool
    {
        if (!array_key_exists($fieldKey, $this->_data) || !$this->hasField($fieldKey)) {
            // Если в текущем массиве данных нет этого поля, значит пользователь передал какой-то ошибочный fieldKey
            // просто вернём false, будто поле не менялось
            return false;
        }

        $oldValue = $this->oldData[$fieldKey] ?? null;
        $newValue = $this->_data[$fieldKey];

        $field = $this->getField($fieldKey);

        return $field::isEmpty($oldValue) && !$field::isEmpty($newValue);
    }



    /**
     * Возвращает true, если поле изменило своё значение с заполненного на пустое
     * @param string $fieldKey
     * @return bool
     */
    public function isCleared(string $fieldKey): bool
    {
        if (!array_key_exists($fieldKey, $this->_data) || !$this->hasField($fieldKey)) {
            // Если в текущем массиве данных нет этого поля, значит пользователь передал какой-то ошибочный fieldKey
            // просто вернём false, будто поле не менялось
            return false;
        }

        $oldValue = $this->oldData[$fieldKey] ?? null;
        $newValue = $this->_data[$fieldKey];

        $field = $this->getField($fieldKey);

        return !$field::isEmpty($oldValue) && $field::isEmpty($newValue);
    }

    public function getOldFieldValue(string $fieldKey)
    {
        return $this->oldData[$fieldKey] ?? null;
    }

    /**
     * Устанавилвает id этого объекта и загружает его из базы. Возвращает true в случае удачной загрузки
     * @return bool
     * @throws \Exception
     */
    public function loadData()
    {
        $query = $this->getDataQuery();
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

    final public function getDataWithSubtables(array $subtables)
    {
        $data = $this->getData();
        $data['_subtables'] = [];
        /** @var Subtable $subtable */
        foreach ($subtables as $subtable) {
            $subtableName = Misc::getClass($subtable);
            $data['_subtables'][$subtableName] = $subtable->getSubtableData();
        }

        return $data;
    }

    final public static function getDataStatic(int $id)
    {
        if (!$id) {
            return null;
        }
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

    protected function writeCompareLog(array $oldData, array $fieldKeys, $dbLogMsg)
    {
        $fieldset = $this->getFieldset();
        $newData = $this->getData();

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
            $newValue = $newData[$field->key] ?? null;

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
            }
        }
    }

    public function __toString()
    {
        return strip_tags($this->getDescriptionHtml());
    }

    public function jsonSerialize()
    {
        return $this->_data;
    }

    public function getDescriptionHtml()
    {
        $nameFieldKey = $this->getNameFieldKey();
        if (array_key_exists($nameFieldKey, $this->_data) && $this->hasField($nameFieldKey)) {
            // Получаем HTML представление поля в режиме forLog
            $description = $this->getField($nameFieldKey)->getHtml($this->_data[$nameFieldKey], $this->_data, false,
                true);
        } else {
            $description = 'Unknown';
            if (isset($this->_data['ID'])) {
                $description .= " ID " . $this->_data['ID'];
            }
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
     * @throws \Exception
     */
    public function insertDb(array $fieldsData, $dbLogMsg = '')
    {

        // Устанавливаем $oldData в пустой массив, чтобы зафиксировать факт, что раньше этот объект был
        // полностью пустым и мы сейчас его создаём
        $this->justInserted = true;
        $this->oldData = [];

        $fieldKeys = array_keys($fieldsData);

        if (!isset($fieldsData['CreatedBy'])) {
            $fieldsData['CreatedBy'] = User::getId();
        }

        //Подготовим данные для вставки в базу. Например, расчленим MoneyWithCurrency
        $fieldsData = $this->prepareFieldsDataForDb($fieldsData);

        // Используем REPLACE, чтобы избежать ошибки Already Exists на сложных таблицах с уникальными полями
        $id = Db::insertRow($fieldsData, $this->getDbTable(), true);

        $this->setId($id);
        $this->loadData();

        $msg = '<span class="good">' . (Config::$lang === 'ru' ? 'Создан' : 'Created') . '</span>';
        if ($dbLogMsg) {
            $msg .= ' ' . $dbLogMsg;
        }

        //Запишем логи о вставке, в качестве oldData передаём пустой массив
        $this->writeCompareLog([], $fieldKeys, $msg);

        return $id;
    }

    public function deleteDb($dbLogMsg = '')
    {
        if (!$this->_data) {
            $this->loadData();
        }

        $masterModule = (string)$this->getMasterModuleForDbLog();
        $masterId = (int)($this->_masterIdForDbLog ?: $this->_id);
        $tableName = (string)$this->getDbTable();
        $recordId = (int)$this->_id;

        //Безопасное удаление в "корзину" (данные сериализуются в JSON и сохраняются в таблицу deleted)
        Db::deleteRow($tableName, $recordId, $masterModule, $masterId);

        // запишем в базу логов
        $msg = $this->getDescriptionHtml() . ' <span class="bad">' . (Config::$lang === 'ru' ? 'удалён' : 'deleted') . '</span>';
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

    /**
     * Оставляет только те данные в $fieldsData, поля у которых имеют F::MODE_SUBMIT
     * @param array $fieldsData
     * @return array
     */
    final public function filterDataByModeSubmit(array $fieldsData): array
    {
        foreach ($fieldsData as $fieldKey => $value) {
            if (!$this->hasFieldModeSubmit($fieldKey)) {
                unset($fieldsData[$fieldKey]);
            }
        }

        return $fieldsData;
    }

    final public function hasFieldModeSubmit(string $fieldKey): bool
    {
        if (!$this->hasField($fieldKey)) {
            // Это может быть поле с фуффикстом CurrencyISO, но нужно проверить по основному полю Amount
            // например: TruckingBuyingRateCurrencyISO и TruckingBuyingRateAmount
            if (strpos($fieldKey, 'CurrencyISO') !== false) {
                // Это часть составного поля MoneyWithCurrency, тогда всё зависит от прав доступа на основное поле
                $baseFieldKey = str_replace('CurrencyISO', '', $fieldKey);
                if (!$this->hasField($baseFieldKey)) {
                    $baseFieldKey .= 'Amount';
                }
                return $this->hasFieldModeSubmit($baseFieldKey);
            }

            // Если поля нет в fieldset, значит и доступа на сохранение (submit) нет
            return false;
        }

        return $this->getField($fieldKey)->hasMode(F::MODE_SUBMIT);
    }

    /**
     * Оставляет только те данные в $fieldsData, поля у которых имеют проходят проверку через editAccessFunction
     * @param array $fieldsData
     * @return array
     */
    final public function filterDataByEditAccessFunction(array $fieldsData): array
    {
        // Если вставляем ноую запись (id=0), то используем только что введённые пользователем данные в качестве $currentObjData
        // Если рарботаем с обновлением уже существующей записи, то используем текущие данные объекта до обновления $this->getData()
        $currentObjData = $this->getId() === 0 ? $fieldsData : $this->getData();

        foreach ($fieldsData as $fieldKey => $value) {
            if (!$this->checkFieldEditAccessFunction($fieldKey, $currentObjData)) {
                unset($fieldsData[$fieldKey]);
            }
        }

        return $fieldsData;
    }

    private function checkFieldEditAccessFunction(string $fieldKey, array $currentObjData): bool
    {
        if (!$this->hasField($fieldKey)) {
            // Пример: TruckingBuyingRateAmount и TruckingBuyingRateCurrencyISO
            if (strpos($fieldKey, 'CurrencyISO') !== false) {
                // Это часть составного поля MoneyWithCurrency, тогда всё зависит от прав доступа на основное поле
                $baseFieldKey = str_replace('CurrencyISO', '', $fieldKey);
                if (!$this->hasField($baseFieldKey)) {
                    $baseFieldKey .= 'Amount';
                }
                return $this->checkFieldEditAccessFunction($baseFieldKey, $currentObjData);
            }

            // Если поля нет в fieldset, значит editAccessFunction проверить не удастся
            return false;
        }

        return $this->getField($fieldKey)->checkEditAccessFunction($currentObjData);
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

    public function getLinkView(string $innerHtml = null): Link
    {
        if (!$innerHtml) {
            $innerHtml = $this->getDescriptionHtml();
        }
        $module = $this->getMasterModuleForDbLog();
        $id = (int)($this->_masterIdForDbLog ?: $this->_id);
        return Link::create($module, $innerHtml)->view($id);
    }

    public function getLinkViewDialog(): Link
    {
        return $this->getLinkView()->funcDialog();
    }

    /**
     * Метод для переопределния в классах потомках.
     * Запускается автоматически в ModuleAbstract после того как сохранён сам объект и все подтаблицы
     * Удобен для пересчёта различных кеширующих полей, когда подтаблицы уже сохранены.
     * При вызове этого метода мы по сути принимаем текущий объект корнем агрегата
     * @param array $oldData
     * @param Msg $msgBuff
     */
    public function afterSubmit(array $oldData, Msg $msgBuff)
    {

    }

    /**
     * Метод для переопределния в классах потомках.
     * Запускается автоматически в ModuleAbstract после того как удалён сам объект и все подтаблицы
     * Удобен для удаления различных кеширующих полей, когда подтаблицы уже очищены.
     * При вызове этого метода мы по сути принимаем текущий объект корнем агрегата
     * @param array $oldData
     * @param Msg $msgBuff
     */
    public function afterDelete(array $oldData, Msg $msgBuff)
    {

    }
}
