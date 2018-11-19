<?php

namespace Light\SubtableBase;

use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldFloat;
use Light\Field\FieldMoney;
use Light\Img;
use Light\Misc;
use Light\Msg;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\Table;
use Light\Template;
use Light\Traits\TableExcelExportable;
use Light\Traits\TableLayoutable;
use Light\Traits\TableSortable;
use Light\Traits\TableWithFoldersTrait;

abstract class Subtable extends Table
{
    use TableLayoutable, TableWithFoldersTrait, TableExcelExportable;
    use TableSortable {
        getHtmlTableTheadThContent as getHtmlTableTheadThContentAlias;
    }

    const STATE_VIEW = 'view';
    const STATE_EXCEL = 'excel';
    const STATE_EDIT = 'edit';
    const STATE_SUBMIT = 'submit';

    protected $_state;

    /** @var string имя css-класса, которое будет добавлено к div контейнеру содержащему подтаблицу */
    protected $_cssClass;

    /** @var ObjAbstract */
    protected $_masterObj;

    /**
     * Название подтаблицы (для подзаголовков и пр.)
     * @var string
     */
    protected $_title = '';

    /**
     * Ассоциативный массив с данными по всем строкам в формате ID => [данные строки]
     * Он отличается от массива $this->_tableData тем, что ключи $this->_data - это ID элементов в базе
     * @var array
     */
    protected $_data;

    /**
     * Дополнительные данные, которые ещё не существую в базе, которые пользователь класса хочет добавить динамически
     * @var array
     */
    protected $_additionalData;

    /**
     * @var array поля из списка $groupconcatFieldKeys будут добавлены в мастер-модуль для режима List, причём
     * их значения будут вычисляться на лету с помощью GROUP_CONCAT подзапроса
     */
    public $groupconcatFieldKeys = [];
    /**
     * @var array поля из списка $_cacheFieldsKeys будут добавлены в мастер-модуль для режима List, их значения будут
     * браться из базы из полей с такими же ключами с префиксом Cache.
     * При сохранении подтаблицы эти поля в мастер-объекте в базе будут автоматически обновлены
     */
    protected $_cacheFieldsKeys = [];

    /**
     * @var bool если флаг установлен в true, то будут кешироваться только уникальные значения, например, номера контейнеров в счетах
     */
    protected $_cacheOnlyUniqueValues = false;
    /**
     * @var string префикс, который добавлять в параметр name у поля при выводе в режиме таблицы в мастер-модуле
     * Этот же префикс используется для полей из списка $groupconcatFieldKeys
     */
    protected $_cacheFieldsPrefix = '';

    //Сортировка по умолчанию
    protected $_orderBy = 'ID';

    //Включение ручной сортировки (требует поля OrderID в таблице БД)
    protected $_orderManual = false;

    //Добавление кнопки для копирования строки
    protected $_copyRow = false;

    // Настройки режима редактирования (Subtable::STATE_EDIT)
    protected $allowAdd = true;
    protected $allowPaste = true;
    protected $allowDelete = true;

    protected $textAddLink;

    //По умолчанию имя папки строится так же как <dbTableName>/<id>
    //Но для подтаблиц ссылающихся на объекты из других модулей как многие-ко-многим это не верно

    protected $_viewTableLayout;
    protected $_editTableLayout;
    protected $_printHeadBlock = true;

    /**
     * Обычно при двойном клике по строке мы проваливаемся куда-то внутрь объекта. Возможно, в этом объекте есть подтаблица
     * со связанными объектами их которых мы могли провалиться. Здесь можно указать имя этой подтаблицы и тогда ко ссылкам
     * (в том числе при двойном клике) будет добавлен параметр <имяподтаблицы>_findid=<$this->_masterObj->getId()>
     */
    protected $_tableLinkFindidSubtableName;

    /**
     * Массив с ключами полей, которые не должны участсовать в функции Paste
     * @var array
     */
    protected $_disablePasteFieldKeys = [];

    public function __construct(ObjAbstract $masterObj)
    {
        $this->_masterObj = $masterObj;

        parent::__construct(Misc::getClass($this));

        $this->tableReloadUrl = '?a=' . Request::getModule() . '&id=' . $masterObj->getId() . '&subtable=' . $this->getTableName();

        $this->_tablePrintTotalRecordsFound = false;
        $this->allowSort = false;
        $this->allowTableLayouts = false;
        $this->sticky = false;

        $this->textAddLink = Config::$lang === 'ru' ? 'Добавить строку' : 'Add Line';

        $masterModule = $this->getMasterModule();
        if ($masterModule) {
            $this->_obj->setMasterModuleForDbLog($masterModule);
        }
    }


    /**
     * @deprecated
     */
    protected function init()
    {
    }

    /**
     * Вычисляет модуль к которому относится эта подтаблица на основе неймспейсов
     */
    protected function getMasterModule()
    {
        //Массив неймспейсов будет выглять так: <ProjectNamespace>\Module\<ModuleNamespace>\Subtables\ClassName
        $namespaces = explode('\\', get_class($this));
        //Имя модуля таким образом находится в элементе с индексом 2
        if (isset($namespaces[2])) {
            return $namespaces[2];
        }

        return '';
    }

    /**
     * Метод пригождается при копировании в ModuleAbstract
     * @param ObjAbstract $masterObj
     */
    public function setMasterObj(ObjAbstract $masterObj)
    {
        $this->_masterObj = $masterObj;
    }

    protected function adjustFieldsWidth()
    {
        //Пройдём по всему fieldset и добавим ширину полей на основе их css классов, если ширина не задана явно
        foreach ($this->getFieldset() as $field) {


            if ($field->tableWidthHtml === null) {

                /** @var $field FieldMoney */
                if (($this->_state == self::STATE_EDIT && $field->getType() === F::TYPE_MONEY_WITH_CURRENCY)
                    || ($this->_state == self::STATE_EDIT && $field->getType() === F::TYPE_MONEY && $field->currencyIso)
                ) {
                    //Для поля TYPE_MONEY_WITH_CURRENCY в режиме редактирования задаём особую ширину
                    //Это же касается для поля TYPE_MONEY в котором задана константая валюта
                    $field->tableWidthHtml = 134;

                } else {

                    //Пользоватлеь не задал ширину вручную, попределим её из css класса редактирования
                    switch ($field->editWidth) {
                        case 'default':
                            $field->tableWidthHtml = 306;
                            break;

                        case 'medium':
                            $field->tableWidthHtml = 126;
                            break;

                        case 'small':
                            $field->tableWidthHtml = 76;
                            break;

                        case 'mini':
                            $field->tableWidthHtml = 46;
                            break;
                    }

                }
            }
        }
    }

    protected function initCssJs()
    {
    }

    /**
     * Добавляет к изначальным данным некоторые дополнительные, которые будут выведены на экран
     * @param array $data
     * @return void
     */
    public function setAdditionalData($data)
    {
        $this->_additionalData = $data;
    }

    protected function isStateView()
    {
        return $this->_state == self::STATE_VIEW;
    }

    protected function isStateExcel()
    {
        return $this->_state == self::STATE_EXCEL;
    }

    protected function isStateEdit()
    {
        return $this->_state == self::STATE_EDIT;
    }

    protected function isStateSubmit()
    {
        return $this->_state == self::STATE_SUBMIT;
    }

    /**
     * Возвращает поля объявленные в этой подтаблице как кеширующие
     * К ключам полей добавляется префикс Cache<имя класса подтаблицы без слова Subtable>
     * К именам полей добавляет префикс $this->_cacheFieldsPrefix
     * @return array
     */
    public function getFieldsForMasterModuleList()
    {
        $fieldsForMasterModuleList = [];

        //Существует два массива с ключами полей, которые нужно добавить в мастер-модуль
        $arrays = ['Groupconcat' => $this->groupconcatFieldKeys, 'Cache' => $this->_cacheFieldsKeys];
        foreach ($arrays as $type => $fieldKeys) {
            /** @var array $fieldKeys */
            foreach ($fieldKeys as $fieldKey) {
                if ($this->hasField($fieldKey)) {

                    $field = $this->getField($fieldKey);
                    //Новый ключ будет начинаться либо с Cache, либо с Groupconcat (переменная $fieldsType)
                    $newKey = $this->getCacheFieldKey($type, $field->key);

                    /** @var $newField \Light\Field\FieldAbstract */
                    $newField = $field->getClone($newKey);

                    //Ко всем кеширующим полям в отображаемые имена добавим префикс, чтобы пользователю на экране было понятно, что эти данные из подтбалицы
                    $newField->addNamePrefix($this->_cacheFieldsPrefix);
                    //Кеширующие поля выводятся только в режиме таблицы
                    $newField->setModes(F::MODE_LIST);
                    //Обязательно нужно наложить параметр groupconcat
                    $newField->groupconcat = true;
                    $newField->groupconcatTruncate = true;
                    //Обязательно изменим ключ по которому будет сортировка в SQL запросе
                    //TODO: нужен новый параметр groupConcatOrderBy - если он определён, то использовать его
                    $newField->tableOrderBy = $newKey;

                    //Если кеширующее поле === null, то выведем красный прочерк
                    /** @var $originalGetHtmlFunction string */
                    $originalGetHtmlFunction = $newField->getHtmlFunction;
                    $newField->setParam(
                        'getHtmlFunction',
                        function ($value, $data, $outHtml, $forTable, $field, $forLog) use (
                            $originalGetHtmlFunction,
                            $newField
                        ) {
                            if ($value === null) {
                                $outHtml = '&mdash;';
                            } elseif ($originalGetHtmlFunction && is_callable($originalGetHtmlFunction)) {
                                $outHtml = $originalGetHtmlFunction($value, $data, $outHtml, $forTable, $newField,
                                    $forLog);
                            }

                            return $outHtml;

                        }
                    );

                    /** @var $originalViewCssClass string */
                    $originalViewCssClass = $newField->viewCssClass;
                    $newField->setParam(
                        'viewCssClass',
                        function ($value, $data, $forTable) use ($originalViewCssClass) {
                            if ($value === null) {
                                return 'bad';
                            } elseif ($originalViewCssClass && is_callable($originalViewCssClass)) {
                                return $originalViewCssClass($value, $data, $forTable);
                            }

                            return $originalViewCssClass;
                        }
                    );

                    //Для Groupconcat полей нужно обязательно добавить sqlSelect параметр!
                    if ($type === 'Groupconcat') {
                        $sqlSelect = "SELECT GROUP_CONCAT(";
                        if ($this->_cacheOnlyUniqueValues) {
                            $sqlSelect .= "DISTINCT ";
                        }
                        $fieldSqlSelect = $field->getSqlSelect();
                        if (!$fieldSqlSelect) {
                            $fieldSqlSelect = $field->key;
                        }
                        $sqlSelect .= $fieldSqlSelect . " ORDER BY ID SEPARATOR '\\n')";
                        $sqlSelect .= " FROM " . $this->getDbTable();
                        $sqlSelect .= " WHERE " . $this->getMasterFieldKey() . "
                                              = " . $this->_masterObj->getDbTable() . ".ID";

                        $newField->setParam('sqlSelect', $sqlSelect);
                    }

                    $fieldsForMasterModuleList[] = $newField;
                }
            }
        }

        return $fieldsForMasterModuleList;
    }

    private $_masterFieldKey;

    /**
     * @return string внешний ключ связывающий с мастер-объектом
     */
    protected function getMasterFieldKey()
    {
        if ($this->_masterFieldKey === null) {
            //<projectNamespace>\Module\<moduleNamespace>\Sutables\<subtableClass>
            $class = get_class($this);
            $class = explode('\\', $class);
            $masterModule = $class[2];
            $this->_masterFieldKey = $masterModule . 'ID';
        }

        return $this->_masterFieldKey;
    }

    protected function getMasterFieldValue()
    {
        return $this->_masterObj->getId();
    }

    /**
     * @desc возвращает запрос к базе на выборку данных
     * @return string $query
     */
    protected function getDataQuery()
    {
        $selectFieldKeys = $this->getFieldsetShowed()->getKeys();

        $query = "SELECT " . $this->getFieldset()->getSqlSelect($selectFieldKeys) . "
                      , " . Db::quote($this->getMasterFieldKey()) . "
                  FROM " . Db::quote($this->getDbTable()) . "
                  WHERE ";

        if ($this->_masterObj->getId()) {
            $query .= Db::quote($this->getMasterFieldKey()) . " = '" . Db::esc($this->getMasterFieldValue()) . "'";
        } else {
            $query .= "0";
        }

        $orderBy = [];
        if ($this->allowSort) {
            $orderBy[] = $this->getSqlOrderBy();
        }
        if ($this->_orderManual) {
            $orderBy[] = '`OrderID`';
        }
        if ($this->_orderBy != '') {
            $orderBy[] = $this->_orderBy;
        }
        if ($orderBy) {
            $query .= " ORDER BY " . implode(",", $orderBy);
        }

        return $query;
    }

    public function loadData()
    {
        $this->_data = [];

        $query = $this->getDataQuery();
        $data = Db::getAll($query);
        foreach ($data as $row) {
            $id = $row['ID'];
            $this->_data[$id] = $row;
        }

        //Скопируем все данные в таблицу
        //Массив $this->_data в качестве ключей имеет ID записей в базе
        //Массив $this->_tableData специально приводим к виду последовательно пронумерованного
        $this->_tableData = array_values($data);

        if ($this->_state == self::STATE_VIEW && $data && $this->folders) {
            //Загрузим специальный массив с закешированным состоянием непустых папок
            $notEmptyIds = array_filter(array_column($data, $this->folderNameFieldKey));
            if ($notEmptyIds) {
                $idsString = implode(',', $notEmptyIds);
                $this->loadCacheNotEmptyFolderIds($idsString);
            }
        }

        if (is_array($this->_additionalData)) {
            $this->_tableData = array_merge($this->_tableData, array_values($this->_additionalData));
        }
    }

    public function getDataCount()
    {
        if ($this->_data === null) {
            $this->loadData();
        }
        $count = count($this->_data);
        if (is_array($this->_additionalData)) {
            $count += count($this->_additionalData);
        }
        return $count;
    }

    public function setData(array $data)
    {
        $this->_data = $data;
    }

    public function getData()
    {
        if ($this->_data === null) {
            $this->loadData();
        }
        return $this->_data;
    }

    protected function getHtmlTableCell(array $rowDataCalculated, int $rowNum, F $field, array $fullLinkParams): string
    {
        if ($this->isStateEdit()
            && ($field->hasMode(F::MODE_EDIT) && $field->checkEditAccessFunction($rowDataCalculated)
                || $field->getType() === 'Color')
        ) {
            $value = $rowDataCalculated[$field->key] ?? '';
            return (string)$this->getHtmlInput($field, $rowNum, $value, $rowDataCalculated);
        }

        return parent::getHtmlTableCell($rowDataCalculated, $rowNum, $field, $fullLinkParams);
    }

    protected function getTableRowTagParams(array $rowDataCalculated, int $rowNum): string
    {
        if ($this->isStateEdit()) {
            //В режиме Edit добавим номер каждой строки в виде id для тега tr (убыстряет селекторы)
            return 'id="' . $this->getTableName() . '-subtable-tr-' . $rowNum . '"';
        }

        return '';
    }

    public function hasTotalRow(array $dataTotal): bool
    {
        if ($dataTotal) {
            // Если есть подсчитанные на стороне сервера данные $dataTotal, то total строку обязательно нужно выводить
            return true;
        }

        // Ещё один случай, когда нужно выводить строку total: это в режиме Edit, если есть хотя бы одно поле со свойством total
        if ($this->isStateEdit()) {
            /** @var FieldAbstract $field */
            foreach ($this->getFieldset() as $field) {
                if ($field->total) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getHtmlTableTrTotal(array $allFields, array $dataCalculated)
    {
        $outHtml = parent::getHtmlTableTrTotal($allFields, $dataCalculated);
        return str_replace('tr class="total"', 'tr class="total" id="' . $this->getTableName() . '-subtable-tr-total"',
            $outHtml);
    }

    /**
     * Возвращает JS код функции для добавления строк в таблицу
     * @return array
     */
    private function getJsFieldsArray()
    {
        $jsFields = [];
        //Добавим соответсвующую ячейку содержащую кнопку на удаление строки
        if ($this->allowDelete) {
            $field = $this->getFieldsetFirst()['Del'];
            $jsFields['Del'] = [
                'calcTotal' => false,
                'disablePaste' => true,
                'tableAlign' => 'center',
                'html' => $this->getHtmlDel(),
                'type' => $field->getType(),
            ];
        }

        if ($this->_copyRow) {
            $field = $this->getFieldsetFirst()['Copy'];
            $jsFields['Copy'] = [
                'calcTotal' => false,
                'disablePaste' => true,
                'tableAlign' => 'center',
                'html' => $this->getHtmlCopy(),
                'type' => $field->getType(),
            ];
        }

        if ($this->_orderManual) {
            $field = $this->getFieldsetFirst()['OrderID'];
            $jsFields['Sort'] = [
                'calcTotal' => false,
                'disablePaste' => true,
                'tableAlign' => 'center',
                'html' => $this->getJsFieldInput($field),
                'type' => $field->getType(),
            ];
        }

        //Добавляем ячейки в цикле
        $editFields = explode('|', $this->_editTableLayout);
        foreach ($editFields as $fieldKey) {
            if ($this->hasField($fieldKey)) {
                $field = $this->getField($fieldKey);
                $jsField = ['type' => $field->getType(), 'tableAlign' => $field->tableAlign];

                if ($field->hasMode(F::MODE_EDIT)) {

                    //Добавим флаг copyOnAdd, чтобы значение поля в новой строке копировалось из предыдущей
                    $jsField['copy'] = $field->copy;

                    //Поля, которые редактируемые могут быть вставлены с помощью функции Paste
                    $jsField['disablePaste'] = in_array($fieldKey, $this->_disablePasteFieldKeys);

                    //Сам html код для вывода поля
                    $jsField['html'] = $this->getJsFieldInput($field);
                } else {
                    //Если поле не для режима редактирования, нужно вывести пустую ячейку при добавлении новой строки в JS
                    $jsField['html'] = $field->getHtml($field->default);

                    //Не редактируемые поля, не могут быть вставлены с помощью функции Paste
                    $jsField['disablePaste'] = true;

                    //Для полей, которые отображаются не как поля для ввода, а как просто Html установим спец флаг
                    //Иногда по этим полям нужно считать total - в этом случае Subtable.js должен знать, что поле
                    //не является полем для ввода, чтобы брать чистый html из ячейки
                    $jsField['mode'] = 'view';
                }

                //Добавим флаг, считать-ли total по этому полю автоматически?
                $jsField['total'] = $field->total;

                //Число знаков после запятой до скольки округлять при пересчёте Total
                if ($field instanceof FieldFloat) {
                    $jsField['precision'] = $field->precision;
                }

                $jsFields[$fieldKey] = $jsField;
            }
        }

        return $jsFields;
    }

    /**
     * Возвращает HTML код input элемента, который будет вставлен при добавлении новой строки
     * @param F $field
     * @return string
     */
    protected function getJsFieldInput($field)
    {
        //HTML код поля в режиме редактирования, который будет вставлен в JS скрипт добавления новой строки
        //В качестве rowNum используется 0
        return $this->getHtmlInput($field, '{NewRowNum}', $field->default, []);
    }

    protected function getFieldEditName($fieldKey, $rowNum)
    {
        return $this->getTableName() . 'Subtable[' . $rowNum . '][' . $fieldKey . ']';
    }

    protected function getFieldEditId($fieldKey, $rowNum)
    {
        return $this->getTableName() . '-' . $rowNum . '-' . $fieldKey;
    }

    protected function getHtmlInput(F $field, $rowNum, $value, array $rowData)
    {
        //Изменям имя поля с учётом номера строки в subtable
        $newField = clone $field;
        $newField->editName = $this->getFieldEditName($field->key, $rowNum);
        $newField->editId = $this->getFieldEditId($field->key, $rowNum);
        return $newField->getHtmlInput($value, $rowData);
    }

    /**
     * Инициализирует поля в объекте $this->Table, удобна для переопределения в классе потомке
     * @return void
     */
    protected function initTableFieldsAdditional()
    {
        //Общие параметры для всех полей типов
        $commonParams = [
            'modes' => F::MODE_READONLY,
            'tableWidthHtml' => 20,
            'tableOrderBy' => '',
            'tableAlign' => 'center'
        ];

        if ($this->allowDelete && $this->isStateEdit()) {
            $params = $commonParams;
            $params['name'] = 'Del';
            $params['getHtmlFunction'] = function ($value, $data, $outHtml, $forTable) {

                if ($forTable !== "total") {
                    $outHtml = $this->getHtmlDel();
                }

                return $outHtml;
            };
            $this->getFieldsetFirst()->addField(F::createHtml('Del', $params));
        }

        if ($this->_copyRow && $this->isStateEdit()) {
            $params = $commonParams;
            $params['name'] = 'Copy';
            $params['getHtmlFunction'] = function ($value, $data, $outHtml, $forTable) {

                if ($forTable !== "total") {
                    $outHtml = $this->getHtmlCopy();
                }

                return $outHtml;
            };
            $this->getFieldsetFirst()->addField(F::createHtml('Copy', $params));
        }

        if ($this->_orderManual && ($this->isStateEdit() || $this->isStateSubmit())) {
            $params = [
                'name' => 'Sort',
                'tableWidthHtml' => 20,
                'tableOrderBy' => '',
                'tableAlign' => 'center',
                'inputType' => 'hidden',
                'default' => '',
                'getHtmlInputFunction' => function ($value, $data, $outHtml, $forTable, $field) {

                    if ($forTable !== "total") {
                        $outHtml = $this->getHtmlSortable() . $outHtml;
                    }

                    return $outHtml;
                },
            ];
            $this->getFieldsetFirst()->addField(F::createInt('OrderID', $params));
        }

        if ($this->folders && $this->isStateView()) {
            $this->getFieldsetFirst()->addField($this->fieldFolder());
        }
    }

    private function getHtmlDel()
    {
        return '<a href="#" title="Remove row" class="subtable-del-link">—</a>';
    }

    private function getHtmlSortable()
    {
        return '<span class="subtable-sort-link">' . Img::icon('fa-bars') . '</span>';
    }

    private function getHtmlCopy()
    {
        return '<a href="#" class="subtable-copy-link">' . Img::icon('fa-copy') . '</a>';
    }

    /**
     * Возвращает все видимые в указанном режиме поля разделённые |
     * @param string $mode MODE_VIEW | MODE_EDIT
     * @return string
     */
    protected function getAllFieldsLayout($mode)
    {
        return implode('|', $this->getFieldset()->filterByMode($mode)->getKeys());
    }

    protected function initViewLayout()
    {
        //Если ссылка Table Layout Settings не выводится, фиксируем выводимые поля
        if (!$this->allowTableLayouts) {
            //В режиме редактирования необходимо зафиксировать набор полей, вне зависимости от текущих настроек пользователя Table Layout Settins
            if (!$this->_viewTableLayout) {
                //Если в конструкторе этот набор полей не был определён, выведем в таблицу все возможные поля
                //Для начала получим имена всех полей, которые могут выводиться в таблицу
                $this->_viewTableLayout = $this->getAllFieldsLayout(F::MODE_VIEW);
            }

            $this->setTableFieldsShow($this->_viewTableLayout);
        }
    }

    public function setViewLayout($fieldsKeysString)
    {
        $this->_viewTableLayout = $fieldsKeysString;
    }


    public function setEditLayout($fieldsKeysString)
    {
        $this->_editTableLayout = $fieldsKeysString;
    }

    protected function initEditLayout()
    {
        //В режиме редактирования необходимо зафиксировать набор полей, вне зависимости от текущих настроек пользователя Table Layout Settins
        if (!$this->_editTableLayout) {
            //Если в конструкторе этот набор полей не был определён, выведем в таблицу все возможные поля
            //Для начала получим имена всех полей, которые могут выводиться в таблицу
            $this->_editTableLayout = $this->getAllFieldsLayout(F::MODE_VIEW);
        }

        $this->setTableFieldsShow($this->_editTableLayout);
    }

    public function getHtmlView()
    {
        $this->_state = self::STATE_VIEW;

        $this->initCssJs();
        $this->initTableFieldsAdditional();
        $this->initLayoutable();
        $this->initSortable();
        $this->initViewLayout();

        // Автоматически подгоняет ширину полей в зависимости от типа
        $this->adjustFieldsWidth();

        $this->loadData();

        return '<div class="subtable-div" data-subtablename="' . htmlspecialchars($this->getTableName()) . '">'
            . $this->getHtmlTable($this->_printHeadBlock)
            . '</div>';
    }

    protected function getTableAjaxChangeFieldUrl(): string
    {
        return "?a=" . Request::getModule()
            . "&id=" . $this->_masterObj->getId()
            . "&subtable=" . htmlspecialchars($this->getTableName())
            . "&func=ChangeField&subid=";
    }

    final public function proceedExcel()
    {
        $this->_state = self::STATE_EXCEL;
        $this->initLayoutable();
        $this->initSortable();
        $this->initViewLayout();
        $this->loadData();
        $this->sendExcel();
    }

    public function getHtmlEditData()
    {
        $this->_state = self::STATE_EDIT;

        //В режиме Edit принудительно отключаем переход по двойному клику на строке
        $this->tableLinkModule = null;

        $this->initCssJs();
        $this->initTableFieldsAdditional();
        $this->initLayoutable();
        $this->initSortable();
        $this->initEditLayout();

        //Автоматически подгоняет ширину полей в зависимости от типа
        $this->adjustFieldsWidth();

        $this->loadData();

        //Сформируем массив из ID строк, его нужно записать в input hidden name="<subtablename>[ids]"
        $ids = [];
        if (is_array($this->_data)) {
            $ids = array_keys($this->_data);
        }

        //Если есть дополнительный массив с данными $this->_additionalData, нужно добавить соответсвующее кол-во нулей в $rowsIds
        if (is_array($this->_additionalData)) {
            $max = count($this->_additionalData);
            for ($i = 0; $i < $max; $i++) {
                $ids[] = '0';
            }
        }

        $ids = implode(',', $ids);

        //В режиме редактирования разложим default данные в отсутсвующие поля $data
        foreach ($this->_tableData as &$rowData) {
            foreach ($this->getFieldset() as $field) {
                if (!array_key_exists($field->key, $rowData)) {
                    $rowData[$field->key] = $field->default;
                }
            }
        }
        //Уберём временную переменную - это переменная-ссылка, обязательно нужно убрать!
        unset($rowData);

        $tdata = [
            'name' => $this->getTableName(),
            'content' => $this->getHtmlTable(false, true),
            'ids' => $ids,
            'allowAdd' => $this->allowAdd,
            'allowDelete' => $this->allowDelete,
            'allowPaste' => $this->allowPaste,
            'textAddLink' => $this->textAddLink,
            'fieldsJson' => json_encode($this->getJsFieldsArray()),
            'orderManual' => $this->_orderManual
        ];

        return $tdata;
    }

    public function getHtmlEdit()
    {
        $tdata = $this->getHtmlEditData();
        return Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/module/subtableedit.tpl.php', $tdata);
    }

    /**
     * Метод proceedSumbit в Subtable делаем final
     * Если нужны какие-то дополнительные действия после сохранения, нужно переопределить afterSubmit
     * @param Msg $msgBuff
     * @param array|null $dataFromRequest
     * @return int
     * @throws \Exception
     */
    public function proceedSubmit(Msg $msgBuff, array $dataFromRequest = null)
    {
        $this->_state = self::STATE_SUBMIT;

        $dataFromRequest = $dataFromRequest ?? $_POST[$this->getTableName() . 'Subtable'] ?? null;

        if ($dataFromRequest === null) {
            //Таблица вообще не пришла в запросе - нечего сохранять
            return 0;
        }

        /*
        Обновление элементов в базе основывается на двух скрытых полях:

        1. $_POST[$this->getTableName().'Subtable']['ids'] - содержит ID всех элементов через запятую в порядке следования строк
        в HTML таблице. Если разделить эту строку через запятые, получим массив <строка в таблице> => <ID в базе>
        Новые строки, которые добавляются в HTML таблицу через JS будут отображены в конце с нулевым ID

        2. $_POST[$this->getTableName().'Subtable']['delrows'] - сожержит номера строк через запятую, которые были удалены
        (если используется визуальное удаление строки на экране) или это непосредственно массив чекбоксов (если для
        удаления строк используются чекбоксы)

        */

        // При ручной сортировке скопируем поле OrderID из FieldsetFirst для сохранения сортировки в базу
        if ($this->_orderManual) {
            $this->initTableFieldsAdditional();
            $field = $this->getFieldsetFirst()['OrderID'];
            $this->addField($field);
        }

        $this->loadData();
        $oldData = $this->_data;

        if (!$this->_obj->getMasterIdForDbLog()) {
            $this->_obj->setMasterIdForDbLog($this->_masterObj->getId());
        }

        // Удаляем некоторые строки из базы, если разрешено удаление
        if ($this->allowDelete) {
            foreach ($this->getRowsToBeDeleted($dataFromRequest) as $rowNum) {
                // Узнаем ID записи лежащей в данной строке
                $id = $this->getRowIdFromPost($rowNum, $dataFromRequest);
                if ($id && isset($this->_data[$id])) {
                    // Если ID существует среди текущих данных, удалим объект
                    $this->proceedDeleteRow($id);
                    // Удалим непосредсвенно из массива Data
                    unset($this->_data[$id]);
                }
            }
        }

        // Пройдём в цикле по всем данным из запроса и сделаем вставки или обновления
        $affectedRows = 0;
        $notDeletedDataFromReqeust = $this->getNotDeletedDataFromReqeust($dataFromRequest);
        foreach ($notDeletedDataFromReqeust as $notDeletedData) {
            $id = (int)$notDeletedData['ID'];
            $rowDataFromRequest = $notDeletedData['fieldsData'];
            $affectedRows += $this->proceedSubmitRow($id, $rowDataFromRequest);
        }

        //Загружаем сохранённые данные
        $this->loadData();

        //Если определена мастер-таблица в которой нужно обновить некоторые кешируюище поля и определны эти поля - обновим кеш!
        if ($this->_cacheFieldsKeys) {
            $this->updateCache();
        }

        if ($this->folders) {
            foreach ($this->_data as $rowData) {
                $this->createFolder($rowData);
            }
        }

        $this->afterSubmit($notDeletedDataFromReqeust, $affectedRows, $oldData, $msgBuff);

        return $affectedRows;
    }

    /**
     * @param int $id - может быть 0, если это новая строка
     * @param $rowDataFromRequest
     * @return int
     * @throws \Exception
     */
    protected function proceedSubmitRow(int $id, array $rowDataFromRequest)
    {
        $obj = clone $this->_obj;

        if ($id) {
            $obj->setId($id);
            $obj->loadData();
        }

        // Оставим в fieldsData только те поля, которые имеют MODE_SUBMIT
        $fieldsData = $obj->filterDataByModeSubmit($rowDataFromRequest);
        // Проверим editAccessFunction в каождом поле
        $fieldsData = $obj->filterDataByEditAccessFunction($fieldsData);

        // Добавим foreign key на родительский объект
        $fieldsData[$this->getMasterFieldKey()] = $this->getMasterFieldValue();

        if ($obj->getId()) {
            // Если объект загрузился из базы успешно, значит обновим
            return $obj->updateDb($fieldsData, 'Updated in subtable');
        }

        // Если же в базе не было такого объекта, то вставим новый
        $obj->insertDb($fieldsData, 'in subtable');

        // Возвращаем affected rows
        return 1;
    }

    /**
     * Метод для переопределения в классах потоках, когда нужно сделать какие-то дополнительные действия над строками
     * подтаблицы после сохранения в базу
     * @param array $dataFromPost
     * @param int $affectedRows
     * @param array $oldData
     * @param Msg $msgBuff
     */
    protected function afterSubmit(
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff
    ) {
    }

    //Кеширующая переменная
    private $_delRows;

    /**
     * @param array $dataFromRequest
     * @return array
     */
    protected function getRowsToBeDeleted(array $dataFromRequest)
    {
        if ($this->_delRows === null) {
            $this->_delRows = [];
            //сожержит номера строк через запятую, которые были удалены
            if (isset($dataFromRequest['delrows']) && $dataFromRequest['delrows'] !== '') {
                $delRowsIds = explode(',', $dataFromRequest['delrows']);
                $this->_delRows = array_combine($delRowsIds, $delRowsIds);
            }
        }

        return $this->_delRows;
    }

    private function isRowDeleted($rowNum, array $dataFromRequest)
    {
        $delRows = $this->getRowsToBeDeleted($dataFromRequest);
        return isset($delRows[$rowNum]);
    }

    //Кеширующая переменная
    private $_rowsIds;

    /**
     * Метод используется при сохранении данных (из proceedSubmit) - возвращает массив всех ID элементов записанных
     * в скрытом поле ids
     * @param $dataFromRequest
     * @return array
     */
    final protected function getRowsIdsFromPost(array $dataFromRequest)
    {
        if ($this->_rowsIds === null) {
            /* специальное скрытое поле содержит ID всех элементов через запятую в порядке следования строк
            в HTML таблице. Если разделить эту строку через запятые, получим массив <строка в таблице> => <ID в базе>
            Новые строки, которые добавляются в HTML таблицу через JS будут отображены в конце с нулевым ID */
            $ids = $dataFromRequest['ids'];
            if ($ids !== '') {
                $this->_rowsIds = explode(',', $ids);
            } else {
                $this->_rowsIds = [];
            }
        }
        return $this->_rowsIds;
    }

    protected function getRowIdFromPost($rowNum, array $dataFromRequest)
    {
        $rowsIds = $this->getRowsIdsFromPost($dataFromRequest);
        return $rowsIds[$rowNum] ?? null;
    }

    protected function proceedDeleteRow(int $id)
    {
        $obj = clone $this->_obj;
        $obj->setId($id);
        if ($obj->loadData()) {
            $obj->deleteDb('from subtable');
        }
    }

    public function deleteAll()
    {
        $this->loadData();
        foreach ($this->_data as $id => $row) {
            $this->proceedDeleteRow($id);
        }
        $this->_data = [];
    }

    public function getNotDeletedDataFromReqeust(array $dataFromRequest = null)
    {
        $dataFromRequest = $dataFromRequest ?? $_POST[$this->getTableName() . 'Subtable'] ?? null;
        if (!$dataFromRequest) {
            return [];
        }

        $notDeletedData = [];
        //Возвращает набор данных из $_REQUEST - может пригодится для внешних классов
        foreach ($this->getRowsIdsFromPost($dataFromRequest) as $rowNum => $id) {
            //Удалённые строки пропускаем
            if ($this->allowDelete && $this->isRowDeleted($rowNum, $dataFromRequest)) {
                continue;
            }

            //rowNum не используется непосредсвенно в классе subtable, но может использоваться во внешнем коде
            //который вызвает этот публичный метод getDataFromRequest()
            //Например в модуле ModuleAbstract в методе checkCriticalErrors может производиться проверка подтаблицы
            $notDeletedData[] = [
                'ID' => $id,
                'fieldsData' => $this->getRowDataFromPost($rowNum, $dataFromRequest),
                'rowNum' => $rowNum
            ];
        }

        return $notDeletedData;
    }

    protected function getRowDataFromPost($rowNum, array $dataFromRequest)
    {
        $rowDataFromPost = $dataFromRequest[$rowNum] ?? [];
        return $this->_obj->getDataFromRequest($rowDataFromPost);
    }

    /**
     * Поля из массива $this->_cacheFieldsKeys - по ним собираются все данные и выполняет UPDATE запрос
     * на таблицу $this->_masterObj->getDbTable(), для каждого поля, добавляя префикс Cache<Имя класса без Subtable>
     */
    public function updateCache()
    {
        if ($this->_data === null) {
            $this->loadData();
        }

        //Соберём данные в ассоциативный массив fieldKey => <текстовые данные через перевод строки>
        $cacheData = [];
        //Для каждого поля пробегаемся по всем данным
        foreach ($this->_cacheFieldsKeys as $fieldKey) {

            //Имя поля в мастер-таблице имеет префикс 'Cache' . $this->getTableName()
            $cacheFieldKey = $this->getCacheFieldKey('Cache', $fieldKey);

            if (count($this->_data)) {
                $values = [];
                foreach ($this->_data as $row) {
                    $values[] = $row[$fieldKey] ?? '';
                }

                //Если установлен специальный флаг, нужно записать только уникальные значения
                if ($this->_cacheOnlyUniqueValues) {
                    $values = array_unique($values);
                }

                //Объединяем все значения через перевод строки и записываем в выходной массив
                $cacheData[$cacheFieldKey] = implode("\n", $values);
            } else {
                $cacheData[$cacheFieldKey] = null;
            }
        }

        if (count($cacheData)) {
            $masterTable = $this->_masterObj->getDbTable();
            $masterId = $this->_masterObj->getId();
            //С помощью плейсхолдера @u формируем update запрос на основе массива $cacheData (имя поля => значение)
            $query = "UPDATE $masterTable SET @u WHERE ID = @i";
            Db::query($query, $cacheData, $masterId);
            if (Db::getAffectedRows()) {
                // Если при обновлении кеширующих полей в таблице мастер-объекта что-то изменилось
                // загрузим ещё раз и сам мастер-объект
                $this->_masterObj->loadData();
            }
        }
    }

    protected function getCacheFieldKey($prefix, $fieldKey)
    {
        return $prefix . $this->getTableName() . $fieldKey;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function proceedAjaxReload()
    {
        $this->_state = self::STATE_VIEW;

        $this->initTableFieldsAdditional();
        $this->initLayoutable();
        $this->initSortable();
        $this->initViewLayout();

        //Автоматически подгоняет ширину полей в зависимости от типа
        $this->adjustFieldsWidth();

        $this->loadData();

        print $this->getHtmlTableForAjax($this->_printHeadBlock);
        ApplicationAbstract::terminate();
    }

    /**
     * Изменение поля через ajax
     * @return int affectedRows
     * @throws \Exception
     */
    public function proceedChangeField(): int
    {
        $fieldKey = $_REQUEST['fieldKey'];
        $field = $this->_obj->getFieldset()->getField($fieldKey);
        if (!$field) {
            Response::abort(400, "Field $fieldKey not found");
            return 0;
        }

        $sudid = $_REQUEST['subid'];
        $this->_obj->setId($sudid);
        if (!$this->_obj->loadData()) {
            Response::abort(404, 'Subtable object not found (id = ' . $sudid . ')');
            return 0;
        }

        if (Request::isGet()) {

            // Это GET запрос на получение поля ввода (input или textarea)
            $value = $this->_obj[$fieldKey];
            $data = $this->_obj->getData();
            // Выводим в ajax ответ HTML код для поля ввода
            print $this->_obj->getField($fieldKey)->getHtmlInput($value, $data);
            return 0;

        }

        // Это POST запрос на изменение поля
        if (!$field->hasMode(F::MODE_SUBMIT, $this->_obj->getData())) {
            Response::abort(403, "Field $fieldKey not editable");
        }

        $value = $_REQUEST['newValue'];
        $data = [$fieldKey => $value];
        $normilizedValue = $field->getDataFromRequest($data);
        if ($normilizedValue === null && !$field->nullable) {
            Response::abort(400, "Invalid value $value");
        }

        $this->_obj->setMasterIdForDbLog($this->_masterObj->getId());
        $affectedRows = $this->_obj->updateDb([$fieldKey => $normilizedValue]);

        // Если определена мастер-таблица в которой нужно обновить некоторые кешируюище поля и определны эти поля - обновим кеш!
        if ($this->_cacheFieldsKeys && in_array($fieldKey, $this->_cacheFieldsKeys)) {
            $this->updateCache();
        }

        // Возвращаем новое HTML значение для вставки в вёрстку
        print $this->_obj->getFieldHtml($fieldKey);

        return $affectedRows;
    }

    protected function getExcelFilename()
    {
        return $this->_title ?: 'Subtable';
    }

    protected function getExcelWorksheetName()
    {
        return $this->_title ?: 'Subtable';
    }

    /**
     * Т.к. во многих модулях имена подтаблиц совпадают, следует усложнить метод генерации уникального имени для настроек,
     * которые хранятся в user_tableparam и в user_tablelayout. Добавим к имени таблицы имя мастер-модуля (для уникальности).
     * @return string
     */
    protected function getTableNameForSettings()
    {
        return $this->getMasterModule() . parent::getTableNameForSettings();
    }

    protected function getTableRowLinkUrlParams(array $rowDataCalculated, int $rowNum): array
    {
        if ($this->_tableLinkFindidSubtableName) {
            return [$this->_tableLinkFindidSubtableName . '_findid' => $this->_masterObj->getId()];
        }

        return [];
    }

    protected function getHtmlTableTheadThContent(F $field)
    {
        $html = $this->getHtmlTableTheadThContentAlias($field);

        if ($this->_state === self::STATE_EDIT && $field->getChangeAll()) {
            if ($field->getType() == F::TYPE_STRING || $field->getType() == F::TYPE_TEXT) {
                $html .= '<br><a href="#" class="small inner change-all-string" data-fieldkey="' . $field->key . '">Изменить все</a>';
            } else {
                $html .= '<br><a href="#" class="small inner change-all" data-fieldkey="' . $field->key . '">Изменить все</a>';
                $select = $field->getClone($field->key . 'ChangeAll')->setParam('editCssClass', 'display-none');
                $html .= $select->getHtmlInput('');
            }
        }

        if ($field->getType() == F::TYPE_TEXT && $field->markdown && $this->_state == self::STATE_EDIT) {
            $html .= '<div class="note-markdown">Данное поле поддерживает язык разметки <a href="http://klavogonki.ru/wiki/Markdown" target="_blank">Markdown</a></div>';
        }

        return $html;
    }
}
