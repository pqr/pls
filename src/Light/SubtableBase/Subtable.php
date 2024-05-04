<?php

namespace Light\SubtableBase;

use Light\ApplicationAbstract;
use Light\ChangeDetector\ChangeDetector;
use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldColor;
use Light\Field\FieldFloat;
use Light\Field\FieldMoney;
use Light\Field\FieldMoneyWithCurrency;
use Light\Field\FieldText;
use Light\FilesFolder;
use Light\Ico;
use Light\Misc;
use Light\Msg;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\SimpleObj;
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

    public const STATE_VIEW = 'view';
    public const STATE_EXCEL = 'excel';
    public const STATE_EDIT = 'edit';
    public const STATE_SUBMIT = 'submit';

    // Выставим некоторые настроки базового класса Table в значения удобные для подтаблиц
    // Для подтаблиц специально не делаем закреплённый заголок (position: sticky)
    public $tableSticky = false;
    public $allowTablePrintHeadBlock = true;
    public $allowTablePrintTotalRecordsFound = false;

    /**
     * Разрешить вручную перетягивать строки в режиме Edit, меняя их порядок в таблице. В базе данных должно быть поле OrderID
     * @var bool
     */
    public $allowOrderManual = false;

    /**
     * Разрешить добавлять новые строки в режиме редактирования
     * @var bool
     */
    public $allowAdd = true;

    /**
     * Разрешить копировать строки (появляется специальная иконка копирования)
     * @var bool
     */
    public $allowCopy = false;

    /**
     * Разрешить вставлять данные через диалоговое окно (копирование-вставка tab separated данных, например, из Excel)
     * @var bool
     */
    public $allowPaste = true;

    /**
     * Разрешить удаление строк из подтаблицы
     * @var bool
     */
    public $allowDelete = true;

    /**
     * Текст ссылки для добавления новой строки
     * @var string
     */
    public $textAddLink;

    /**
     * Имена полей для режима просмотра разделённые вертикальной чертой
     * @var string
     */
    public $subtableLayoutView;

    /**
     * Имена полей для режима редактирования разделённые вертикальной чертой
     * @var string
     */
    public $subtableLayoutEdit;

    /**
     * Массив с ключами полей, которые не должны участсовать в функции Paste
     * @var array
     */
    public $subtablePasteSkippFieldKeys = [];

    /**
     * Название подтаблицы выводимое на экран в h2
     * @var string
     */
    public $textSubtableTitle = '';

    /**
     * Ассоциативный массив с данными по всем строкам в формате ID => [данные строки]
     * Он отличается от массива $this->tableData тем, что ключи $this->subtableData - это ID элементов в базе
     * @var array
     */
    protected $subtableData;

    /**
     * Заполняется в момент второй загрузки данных (в момент второго вызова loadData() - сохраняется копия оригинальных данных)
     * @var array
     */
    protected $subtableOldData;

    /**
     * Дополнительные данные, которые ещё не существую в базе, которые пользователь класса хочет добавить динамически
     * @var array
     */
    protected $subtableDataAdditional;

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

    protected $_state;

    /** @var ObjAbstract */
    protected $_masterObj;

    public function __construct(ObjAbstract $masterObj)
    {
        $this->_masterObj = $masterObj;

        parent::__construct(Misc::getClass($this));

        $this->tableReloadUrl = '?a=' . Request::getModule() . '&id=' . $masterObj->getId() . '&subtable=' . $this->getTableName();
        $this->allowTableLayouts = false;
        $this->allowSort = false;

        // Для подтаблиц по умолчанию делаем сортировку по возрастанию ID
        // (в TableSortable trait этот параметр строит как 'ID DESC', но для подтаблиц удобнее 'ID ASC')
        if (!$this->tableDefaultOrderBy) {
            $this->tableDefaultOrderBy = 'ID';
        }

        if (!$this->textAddLink) {
            $this->textAddLink = Config::$lang === 'ru' ? 'Добавить строку' : 'Add Line';
        }

        $masterModule = $this->getMasterModule();
        if ($masterModule) {
            $this->_obj->setMasterModuleForDbLog($masterModule);
        }

        if ($this->_obj instanceof SimpleObj) {
            $masterFieldKey = $this->getMasterFieldKey();
            $this->_obj->setMasterFieldKey($masterFieldKey);
        }
    }

    /**
     * Вычисляет модуль к которому относится эта подтаблица на основе неймспейсов
     */
    protected function getMasterModule()
    {
        return Misc::getModule($this);
    }

    /**
     * Метод пригождается при копировании в ModuleAbstract
     * @param ObjAbstract $masterObj
     */
    public function setMasterObj(ObjAbstract $masterObj): void
    {
        $this->_masterObj = $masterObj;
    }

    public function getMasterObj(): ObjAbstract
    {
        return $this->_masterObj;
    }

    protected function adjustFieldsWidth()
    {
        //Пройдём по всему fieldset и добавим ширину полей на основе их css классов, если ширина не задана явно
        foreach ($this->getFieldset() as $field) {


            if ($field->tableWidthHtml === null) {

                if (($this->_state == self::STATE_EDIT && $field instanceof FieldMoneyWithCurrency)
                    || ($this->_state == self::STATE_EDIT && $field instanceof FieldMoney && $field->currencyIso)
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
    public function setSubtableDataAdditional($data): void
    {
        $this->subtableDataAdditional = $data;
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
        foreach ($arrays as $groupconcatOrCache => $fieldKeys) {
            /** @var array $fieldKeys */
            foreach ($fieldKeys as $fieldKey) {
                if ($this->hasField($fieldKey)) {

                    $field = $this->getField($fieldKey);
                    //Новый ключ будет начинаться либо с Cache, либо с Groupconcat (переменная $fieldsType)
                    $newKey = $this->getCacheFieldKey($groupconcatOrCache, $field->key, F::MODE_LIST);

                    if (!$newKey) {
                        continue;
                    }

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
                    if ($groupconcatOrCache === 'Groupconcat') {
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
    protected function getMasterFieldKey(): string
    {
        return $this->getMasterModule() . 'ID';
    }

    protected function getMasterFieldValue(): int
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
        if ($this->allowOrderManual) {
            $orderBy[] = '`OrderID`';
        }
        $sqlOrderBy = $this->getSqlOrderBy();
        if ($sqlOrderBy !== 'NULL') {
            $orderBy[] = $sqlOrderBy;
        }

        if ($orderBy) {
            $query .= " ORDER BY " . implode(",", $orderBy);
        }

        return $query;
    }

    public function loadData(): void
    {
        if ($this->subtableData !== null && $this->subtableOldData === null) {
            // Данные ранее уже загружались но только один один раз (т.к. массив oldData ещё пуст)
            // В этом случае наполним массив oldData начальными данными
            $this->subtableOldData = $this->subtableData;
        }

        $this->subtableData = [];

        if (!$this->_masterObj->getId()) {
            // Если мастер объект сам по себе ещё не сохранён в базе, у нас нет masterId, по которому можно было бы
            // загрузить данные подтаблицы
            return;
        }

        $query = $this->getDataQuery();
        $data = Db::getAll($query);
        foreach ($data as $row) {
            $id = $row['ID'];
            $this->subtableData[$id] = $row;
        }
    }

    public function initFolderStatuses(): void
    {
        if ($this->subtableData && $this->folders && $this->folderModule) {
            // Загрузим специальный массив с закешированным состоянием непустых папок
            $folderIds = array_filter(array_column($this->subtableData, $this->folderIdFieldKey));
            if ($folderIds) {
                FilesFolder::loadFoldersStatus($this->folderModule, $folderIds);
            }
        }
    }

    /**
     * Копирует данные из внутреннего массива $this->subtableData в $this->tableData
     * при этом ключи в $this->tableData идут по порядку от 0
     * Также в конец $this->tableData добавляются данные из массива $this->subtableDataAdditional
     *
     * Этот метод вызывается только в тех случаях, когда реально нужно отобразить подтаблицу (в режиме Vie/Edit/Excel)
     */
    public function copySubtableDataToTableData(): void
    {
        if ($this->subtableData) {
            $this->tableData = array_values($this->subtableData);
        } else {
            $this->tableData = [];
        }

        if (is_array($this->subtableDataAdditional)) {
            $this->tableData = array_merge($this->tableData, array_values($this->subtableDataAdditional));
        }
    }

    public function getSubtableDataCount()
    {
        if ($this->subtableData === null) {
            $this->loadData();
        }
        $count = count($this->subtableData);
        if (is_array($this->subtableDataAdditional)) {
            $count += count($this->subtableDataAdditional);
        }
        return $count;
    }

    public function setSubtableData(array $data): void
    {
        $this->subtableData = $data;
    }

    public function getSubtableData()
    {
        if ($this->subtableData === null) {
            $this->loadData();
        }
        return $this->subtableData;
    }

    public function hasSubtableOldData(): bool
    {
        return $this->subtableOldData !== null;
    }

    public function getSubtableOldData(): array
    {
        return $this->subtableOldData ?? [];
    }

    protected function getHtmlTableCell(array $rowDataCalculated, int $rowNum, F $field, array $fullLinkParams): string
    {
        if ($this->isStateEdit()
            && (
                ($field->hasMode(F::MODE_EDIT) && $field->checkEditAccessFunction($rowDataCalculated))
                || $field instanceof FieldColor
            )
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
        if (!$this->allowTableTotal) {
            return false;
        }

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

        if ($this->allowCopy) {
            $field = $this->getFieldsetFirst()['Copy'];
            $jsFields['Copy'] = [
                'calcTotal' => false,
                'disablePaste' => true,
                'tableAlign' => 'center',
                'html' => $this->getHtmlCopy(),
                'type' => $field->getType(),
            ];
        }

        if ($this->allowOrderManual) {
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
        $editFields = explode('|', $this->subtableLayoutEdit);
        foreach ($editFields as $fieldKey) {
            if ($this->hasField($fieldKey)) {
                $field = $this->getField($fieldKey);
                $jsField = ['type' => $field->getType(), 'tableAlign' => $field->tableAlign];

                if ($field->hasMode(F::MODE_EDIT)) {

                    //Добавим флаг copyOnAdd, чтобы значение поля в новой строке копировалось из предыдущей
                    $jsField['copy'] = $field->copy;

                    //Поля, которые редактируемые могут быть вставлены с помощью функции Paste
                    $jsField['disablePaste'] = in_array($fieldKey, $this->subtablePasteSkippFieldKeys);

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
            'tableAlign' => 'center',
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

        if ($this->allowCopy && $this->isStateEdit()) {
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

        if ($this->allowOrderManual && ($this->isStateEdit() || $this->isStateSubmit())) {
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
        return '<span class="subtable-sort-link">' . Ico::i('fa-bars') . '</span>';
    }

    private function getHtmlCopy()
    {
        return '<a href="#" class="subtable-copy-link">' . Ico::copy() . '</a>';
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
            if (!$this->subtableLayoutView) {
                //Если в конструкторе этот набор полей не был определён, выведем в таблицу все возможные поля
                //Для начала получим имена всех полей, которые могут выводиться в таблицу
                $this->subtableLayoutView = $this->getAllFieldsLayout(F::MODE_VIEW);
            }

            $this->setTableFieldsShow($this->subtableLayoutView);
        }
    }

    public function setViewLayout($fieldsKeysString): void
    {
        $this->subtableLayoutView = $fieldsKeysString;
    }

    public function setEditLayout($fieldsKeysString): void
    {
        $this->subtableLayoutEdit = $fieldsKeysString;
    }

    protected function initEditLayout()
    {
        // В режиме редактирования необходимо зафиксировать набор полей,
        // вне зависимости от текущих настроек пользователя Table Layout Settins
        if (!$this->subtableLayoutEdit) {
            // Если в конструкторе этот набор полей не был определён, выведем в таблицу все возможные поля
            // Для начала получим имена всех полей, которые могут выводиться в таблицу
            $this->subtableLayoutEdit = $this->getAllFieldsLayout(F::MODE_VIEW);
        }

        $this->setTableFieldsShow($this->subtableLayoutEdit);
    }

    public function getHtmlView($adjustFieldsWidth = true)
    {
        $this->_state = self::STATE_VIEW;

        $this->initCssJs();
        $this->initTableFieldsAdditional();
        $this->initLayoutable();
        $this->initSortable();
        $this->initViewLayout();

        if ($adjustFieldsWidth) {
            // Автоматически подгоняет ширину полей в зависимости от типа
            $this->adjustFieldsWidth();
        }

        $this->loadData();

        $this->initFolderStatuses();
        $this->copySubtableDataToTableData();

        return '<div class="subtable-div" data-subtablename="' . htmlspecialchars($this->getTableName()) . '">'
            . $this->getHtmlTable()
            . '</div>';
    }

    protected function getTableAjaxChangeFieldUrl(): string
    {
        return "?a=" . Request::getModule()
            . "&id=" . $this->_masterObj->getId()
            . "&subtable=" . htmlspecialchars($this->getTableName())
            . "&func=ChangeField&subid=";
    }

    final public function proceedExcel(): void
    {
        $this->_state = self::STATE_EXCEL;
        $this->initLayoutable();
        $this->initSortable();
        $this->initViewLayout();
        $this->loadData();
        $this->copySubtableDataToTableData();
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

        $this->initFolderStatuses();
        $this->copySubtableDataToTableData();

        //Сформируем массив из ID строк, его нужно записать в input hidden name="<subtablename>[ids]"
        $ids = [];
        if (is_array($this->subtableData)) {
            $ids = array_keys($this->subtableData);
        }

        //Если есть дополнительный массив с данными $this->_additionalData, нужно добавить соответсвующее кол-во нулей в $rowsIds
        if (is_array($this->subtableDataAdditional)) {
            $max = count($this->subtableDataAdditional);
            for ($i = 0; $i < $max; $i++) {
                $ids[] = '0';
            }
        }

        $ids = implode(',', $ids);

        // В режиме редактирования разложим default данные в отсутсвующие поля $data
        foreach ($this->tableData as &$rowData) {
            foreach ($this->getFieldset() as $field) {
                if (!array_key_exists($field->key, $rowData)) {
                    $rowData[$field->key] = $field->default;
                }
            }
        }

        // Уберём временную переменную - это переменная-ссылка, обязательно нужно убрать!
        unset($rowData);

        // В режиме редактирования принудительно выставляем флаг, который позволит вывести каркас таблицы
        // (thead) дажее если данных нет
        $this->allowTablePrintTheadIfEmpty = true;
        $this->allowTablePrintHeadBlock = false;

        $tdata = [
            'name' => $this->getTableName(),
            'content' => $this->getHtmlTable(),
            'ids' => $ids,
            'allowAdd' => $this->allowAdd,
            'allowDelete' => $this->allowDelete,
            'allowPaste' => $this->allowPaste,
            'textAddLink' => $this->textAddLink,
            'fieldsJson' => json_encode($this->getJsFieldsArray()),
            'orderManual' => $this->allowOrderManual,
        ];

        return $tdata;
    }

    public function getHtmlEdit()
    {
        $tdata = $this->getHtmlEditData();
        return Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/module/subtableedit.tpl.php', $tdata);
    }

    /**
     * Метод proceedSubmit в Subtable делаем final
     * Если нужны какие-то дополнительные действия после сохранения, нужно переопределить afterSubmit
     * @param Msg $msgBuff
     * @param array|null $dataFromRequest
     * @return int
     * @throws \Exception
     */
    public function proceedSubmit(Msg $msgBuff, array $dataFromRequest = null)
    {
        $this->_state = self::STATE_SUBMIT;

        $dataFromRequest ??= $_POST[$this->getTableName() . 'Subtable'] ?? null;

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
        if ($this->allowOrderManual) {
            $this->initTableFieldsAdditional();
            $field = $this->getFieldsetFirst()['OrderID'];
            $this->addField($field);
        }

        $this->loadData();
        $this->copySubtableDataToTableData();

        $oldData = $this->subtableData;

        if (!$this->_obj->getMasterIdForDbLog()) {
            $this->_obj->setMasterIdForDbLog($this->_masterObj->getId());
        }

        // Удаляем некоторые строки из базы, если разрешено удаление
        if ($this->allowDelete) {
            foreach ($this->getRowsToBeDeleted($dataFromRequest) as $rowNum) {
                // Узнаем ID записи лежащей в данной строке
                $id = $this->getRowIdFromPost($rowNum, $dataFromRequest);
                if ($id && isset($this->subtableData[$id])) {
                    // Если ID существует среди текущих данных, удалим объект
                    $this->proceedDeleteRow($id);
                    // Удалим непосредсвенно из массива Data
                    unset($this->subtableData[$id]);
                }
            }
        }

        // Пройдём в цикле по всем данным из запроса и сделаем вставки или обновления
        $affectedRows = 0;
        $notDeletedDataFromReqeust = $this->getNotDeletedDataFromReqeust($dataFromRequest);
        foreach ($notDeletedDataFromReqeust as $notDeletedData) {
            $id = (int)$notDeletedData['ID'];
            $rowDataFromRequest = $notDeletedData['fieldsData'];
            $affectedRows += $this->proceedSubmitRow($id, $rowDataFromRequest, $msgBuff);
        }

        //Загружаем сохранённые данные
        $this->loadData();

        //Если определена мастер-таблица в которой нужно обновить некоторые кешируюище поля и определны эти поля - обновим кеш!
        if ($this->_cacheFieldsKeys) {
            $this->updateCache();
        }

        if ($this->folders) {
            foreach ($this->subtableData as $rowData) {
                $this->createFolder($rowData);
            }
        }

        $this->afterSubmit($notDeletedDataFromReqeust, $affectedRows, $oldData, $msgBuff);

        return $affectedRows;
    }

    /**
     * @param int $id - может быть 0, если это новая строка
     * @param array $rowDataFromRequest
     * @param Msg $msgBuff
     * @return int
     * @throws \Exception
     */
    protected function proceedSubmitRow(int $id, array $rowDataFromRequest, Msg $msgBuff)
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
            $affectedRows = $obj->updateDb($fieldsData, 'Updated in subtable');
        } else {
            // Если же в базе не было такого объекта, то вставим новый
            $obj->insertDb($fieldsData, 'in subtable');
            $affectedRows = 1;
        }

        $obj->afterSubmit($msgBuff);

        // Возвращаем affected rows
        return $affectedRows;
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
                $delRowsIds = array_values(array_map('intval', explode(',', $dataFromRequest['delrows'])));
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
            if ($dataFromRequest['ids'] === '') {
                $this->_rowsIds = [];
            } else {
                $this->_rowsIds = array_values(array_map('intval', explode(',', $dataFromRequest['ids'])));
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
            $obj->afterDelete(new Msg());
        }
    }

    public function deleteAll(): void
    {
        $this->loadData();
        foreach ($this->subtableData as $id => $row) {
            $this->proceedDeleteRow($id);
        }
        $this->subtableData = [];
    }

    public function getDataFromPost(): ?array
    {
        return $_POST[$this->getTableName() . 'Subtable'] ?? null;
    }

    public function getNotDeletedDataFromReqeust(array $dataFromRequest = null)
    {
        $dataFromRequest ??= $this->getDataFromPost();
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
                'rowNum' => $rowNum,
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
    public function updateCache(): void
    {
        if ($this->subtableData === null) {
            $this->loadData();
        }

        //Соберём данные в ассоциативный массив fieldKey => <текстовые данные через перевод строки>
        $cacheData = [];
        //Для каждого поля пробегаемся по всем данным
        foreach ($this->_cacheFieldsKeys as $fieldKey) {

            //Имя поля в мастер-таблице имеет префикс 'Cache' . $this->getTableName()
            $cacheFieldKey = $this->getCacheFieldKey('Cache', $fieldKey, F::MODE_SUBMIT);

            if (!$cacheFieldKey) {
                continue;
            }

            if (count($this->subtableData)) {
                $values = [];
                foreach ($this->subtableData as $row) {
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

    protected function getCacheFieldKey($prefix, $fieldKey, $fieldMode = null)
    {
        return $prefix . $this->getTableName() . $fieldKey;
    }

    public function getTextSubtableTitle()
    {
        return $this->textSubtableTitle;
    }

    public function proceedAjaxReload(): void
    {
        $this->_state = self::STATE_VIEW;

        $this->initTableFieldsAdditional();
        $this->initLayoutable();
        $this->initSortable();
        $this->initViewLayout();

        //Автоматически подгоняет ширину полей в зависимости от типа
        $this->adjustFieldsWidth();

        if (isset($_GET['renderRowForAjax']) && $_GET['renderRowForAjax'] === $this->getTableName()) {
            // Рендерим одну строку при изменении какого-то поля по ajax
            $this->proceedRenderRowForAjax();
            return;
        }

        $this->loadData();
        $this->initFolderStatuses();
        $this->copySubtableDataToTableData();

        print $this->getHtmlTableForAjax();
        ApplicationAbstract::terminate();
    }

    public function proceedRenderRowForAjax(): void
    {
        $id = (int)$_GET['rowId'];
        if (!$id) {
            Response::abort(400);
            return;
        }

        $this->subtableData = [];

        $query = $this->getDataQuery();
        $data = Db::getAll($query);
        $foundRow = null;
        foreach ($data as $row) {
            if ($row['ID'] == $id) {
                $foundRow = $row;
                break;
            }
        }
        if (!$foundRow) {
            Response::abort(404);
            return;
        }

        $this->subtableData[$id] = $foundRow;
        $this->initFolderStatuses();
        $this->copySubtableDataToTableData();

        print $this->getHtmlTableTrForAjax();
    }

    /**
     * Изменение поля через ajax
     * @param Msg $msgBuff
     * @return int affectedRows
     * @throws \Exception
     */
    public function proceedChangeField(Msg $msgBuff): int
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
        if (!$field->hasMode(F::MODE_SUBMIT)) {
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

        $this->_obj->afterSubmit($msgBuff);

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
        return $this->textSubtableTitle ?: 'Subtable';
    }

    protected function getExcelWorksheetName()
    {
        return $this->textSubtableTitle ?: 'Subtable';
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

    protected function getHtmlTableTheadThContent(F $field)
    {
        $html = $this->getHtmlTableTheadThContentAlias($field);

        if ($this->_state === self::STATE_EDIT && $field->getChangeAll()) {
            $text = Config::$lang === 'ru' ? 'Изменить все' : 'Change all';
            $fieldType = $field->getType();
            if ($fieldType === F::TYPE_STRING || $fieldType === F::TYPE_TEXT) {
                $html .= '<br><a href="#" class="change-all-string" data-fieldkey="' . $field->key . '">' . $text . '</a>';
            } else {
                $html .= '<br><a href="#" class="change-all" data-fieldkey="' . $field->key . '">' . $text . '</a>';
                $select = $field->getClone($field->key . 'ChangeAll')
                    ->setPlaceholder($text)
                    ->setEditCssClass('small display-none');

                $html .= $select->getHtmlInput('');
            }
        }

        if ($field instanceof FieldText && $field->markdown && $this->_state == self::STATE_EDIT) {
            $html .= '<div class="note-markdown">Данное поле поддерживает язык разметки <a href="http://klavogonki.ru/wiki/Markdown" target="_blank">Markdown</a></div>';
        }

        return $html;
    }

    /**
     * Возвращает true, если объект был только что создан (insertDb()) или в процессе updateDb() изменилось значение
     * этого поля
     * @param int $id
     * @param string $fieldKey
     * @return bool
     */
    public function isChanged(int $id, string $fieldKey): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        $newRowData = $this->subtableData[$id] ?? [];
        $oldRowData = $this->subtableOldData[$id] ?? [];
        return ChangeDetector::isChanged($fieldKey, $newRowData, $oldRowData);
    }

    public function isChangedTo(int $id, string $fieldKey, $expectedValue): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        $newRowData = $this->subtableData[$id] ?? [];
        $oldRowData = $this->subtableOldData[$id] ?? [];
        return ChangeDetector::isChangedTo($fieldKey, $newRowData, $oldRowData, $expectedValue);
    }

    public function isChangedFrom(int $id, string $fieldKey, $expectedOldValue): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        $newRowData = $this->subtableData[$id] ?? [];
        $oldRowData = $this->subtableOldData[$id] ?? [];
        return ChangeDetector::isChangedFrom($fieldKey, $newRowData, $oldRowData, $expectedOldValue);
    }

    public function isChangedToInSomeRow(string $fieldKey, $expectedValue): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        foreach ($this->subtableData as $id => $newRowData) {
            $oldRowData = $this->subtableOldData[$id] ?? [];
            $isChanged = ChangeDetector::isChangedTo($fieldKey, $newRowData, $oldRowData, $expectedValue);
            if ($isChanged) {
                return true;
            }
        }

        return false;
    }

    public function isChangedFromInSomeRow(string $fieldKey, $expectedOldValue): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        foreach ($this->subtableData as $id => $newRowData) {
            $oldRowData = $this->subtableOldData[$id] ?? [];
            $isChanged = ChangeDetector::isChangedFrom($fieldKey, $newRowData, $oldRowData, $expectedOldValue);
            if ($isChanged) {
                return true;
            }
        }

        return false;
    }

    public function isJustInserted(int $id): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        return isset($this->subtableData[$id]) && !isset($this->subtableOldData[$id]);
    }

    /**
     * Возвращает true, если поле было изменено в процессе updateDb()
     * Но при вставке нового объекта через insertDb() этот метод всегда будет возвращать false
     * @param int $id
     * @param string $fieldKey
     * @return bool
     */
    public function isUpdated(int $id, string $fieldKey): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        if ($this->isJustInserted($id)) {
            return false;
        }

        return $this->isChanged($id, $fieldKey);
    }

    /**
     * Возвращает true, если поле изменило своё значение с пустого на заполненное
     * @param int $id
     * @param string $fieldKey
     * @return bool
     */
    public function isFilled(int $id, string $fieldKey): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        if (!$this->hasField($fieldKey)) {
            // Пользователь ошибся в имени поля
            return false;
        }

        // Методу isFilled требуется объект самого поля, чтобы применить функцию $field::empty(...)
        $field = $this->getField($fieldKey);

        $newRowData = $this->subtableData[$id] ?? [];
        $oldRowData = $this->subtableOldData[$id] ?? [];

        return ChangeDetector::isFilled($fieldKey, $newRowData, $oldRowData, $field);
    }

    /**
     * Возвращает true, если поле изменило своё значение с заполненного на пустое
     * @param int $id
     * @param string $fieldKey
     * @return bool
     */
    public function isCleared(int $id, string $fieldKey): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        if (!$this->hasField($fieldKey)) {
            // Пользователь ошибся в имени поля
            return false;
        }

        // Методу isFilled требуется объект самого поля, чтобы применить функцию $field::empty(...)
        $field = $this->getField($fieldKey);

        $newRowData = $this->subtableData[$id] ?? [];
        $oldRowData = $this->subtableOldData[$id] ?? [];

        return ChangeDetector::isCleared($fieldKey, $newRowData, $oldRowData, $field);
    }

    /**
     * @param int $id
     * @param string $fieldKey
     * @return bool
     */
    public function isFieldEmpty(int $id, string $fieldKey): bool
    {
        if (!$this->hasField($fieldKey)) {
            return true;
        }

        $rowData = $this->subtableData[$id] ?? [];
        if (!array_key_exists($fieldKey, $this->subtableData)) {
            return false;
        }

        $field = $this->getField($fieldKey);
        return $field::empty($rowData[$fieldKey]);
    }

    public function getFieldValue(int $id, string $fieldKey)
    {
        return $this->subtableData[$id][$fieldKey] ?? null;
    }

    public function getOldFieldValue(int $id, string $fieldKey)
    {
        return $this->subtableOldData[$id][$fieldKey] ?? null;
    }

    public function hasInsertedRows(): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        $ids = array_keys($this->subtableData);
        $oldIds = array_keys($this->subtableOldData);

        // Если есть какие-то id, которых не было раньше - значит были вставлены новые строки
        return (bool)array_diff($ids, $oldIds);
    }

    public function hasDeletedRows(): bool
    {
        if ($this->subtableOldData === null) {
            // Подтаблица загружалась всего один раз - значит никаких изменений и не было
            // (не было submit)
            return false;
        }

        $ids = array_keys($this->subtableData);
        $oldIds = array_keys($this->subtableOldData);

        // Если среди старых строк есть какие-то id, которых нет сейчас - значит некоторые строки были удалены
        return (bool)array_diff($oldIds, $ids);
    }
}
