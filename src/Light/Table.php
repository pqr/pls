<?php

namespace Light;

use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoneyWithCurrency;
use Light\Traits\FieldsetAwareTrait;

class Table
{
    use FieldsetAwareTrait;

    private $tableName;

    protected $_tableMsgIfEmpty;

    /** @var ObjAbstract */
    protected $_obj;

    /**
     * Системные поля, которые должны быть в начале таблицы, не выводятся в Excel
     * @var Fieldset|\Light\Field\FieldAbstract[]
     */
    private $fieldsetFirst;

    /**
     * Системные поля, которые должны быть в конце таблицы, не выводятся в Excel
     * @var Fieldset|\Light\Field\FieldAbstract[]
     */
    private $fieldsetLast;

    //Массив ключей полей, которые следует отображать в нужном порядке
    //Если это поле null, будут выведены все поля
    protected $_tableFieldsShow;

    protected $_tableData = [];
    protected $_tableTotalRowData;

    /** @var array $_tableTotalRowCalcFieldsKeys */
    protected $_tableTotalRowCalcFieldsKeys;

    /** @var string $_tableLinkModule модуль, на который автоматически делать ссылки при двойном клике по строке
     * с добавленим data-linkid из строки
     */
    protected $_tableLinkModule;

    /** Имена полей, которые должны стать ссылками на внешний модуль $this->_tableLinkModule */
    protected $_tableLinkFieldKeys = ['ID', 'Name'];

    /** @var string $_tableLinkIdFieldKey ключевое поле поле, в котором хранится id для подстановки ссылки при использовании _tableLinkModule и _tableLinkFieldKeys */
    protected $_tableLinkIdFieldKey = 'ID';

    public $nosticky = false;
    public $stickyOffset;
    public $nohover = false;

    /**
     * Печатать ли сообщение "Total records found: xxx" (где xxx - результат вызова $this->getTableTotalRecordsFound())
     * @var bool
     */
    protected $_tablePrintTotalRecordsFound = true;

    /**
     * Параметры таблицы, которые хранятся в базе в user_tableparam
     * Загружаются лениво при первом обращении с помощью метода $this->lazyLoadTableUserParams()
     * @var array
     */
    private $tableUserParams;

    public function __construct($tableName = null)
    {
        if ($tableName) {
            $this->tableName = $tableName;
        } else {
            $this->tableName = str_replace('\\', '_', get_class($this));
        }

        //Инициализируем внутреннюю переменную _obj хитрым способом -  возможно, на основе файла в этой же папке
        $this->_obj = $this->createObj();
    }

    protected function createObj()
    {
        //Если объект не задан явно, попробуем найти соответсвующий класс рядом с текущим файлом модуля
        $objClass = get_class($this) . 'Obj';
        $filepath = LIGHT_DIR_ROOT . '/src/' . str_replace('\\', '/', $objClass) . '.php';
        if (file_exists($filepath)) {
            //Файл найден, создаём объект именно этого класса
            return new $objClass();
        }

        //Отдельного файла для класса найдено не было, создадим на основе обычного Obj
        $obj = new SimpleObj();
        $obj->setDbTable(strtolower($this->getTableName()));
        return $obj;
    }

    public function getObj()
    {
        return $this->_obj;
    }

    protected function getTableFindIdFromRequest()
    {
        $key = $this->getTableName() . '_findid';
        // Проверяем REQUEST, а не GET, т.к. если происходит ajax обновление страницы с помощью js вызова tables.get('...').reload({'TableName_findid' => '...'})
        //в этом случае параметр приходит в POST
        return $_REQUEST[$key] ?? null;
    }

    /**
     * Регристрирует переданный массив $fields во внутренней переменной $this->fieldsFirst
     * @param Fieldset $fieldset
     */
    public function setFieldsetFirst(Fieldset $fieldset)
    {
        $this->fieldsetFirst = $fieldset;
    }

    /**
     * Регристрирует переданный массив $fields во внутренней переменной $this->fieldsLast
     * @param Fieldset $fieldset
     */
    public function setFieldsetLast(Fieldset $fieldset)
    {
        $this->fieldsetLast = $fieldset;
    }

    /**
     * @return Fieldset|F[]
     */
    public function getFieldsetFirst()
    {
        if (!$this->fieldsetFirst) {
            // Лениво инициализируем при первом обращении
            $this->fieldsetFirst = new Fieldset();
        }
        return $this->fieldsetFirst;
    }

    /**
     * @return Fieldset|F[]
     */
    public function getFieldsetLast()
    {
        if (!$this->fieldsetLast) {
            // Лениво инициализируем при первом обращении
            $this->fieldsetLast = new Fieldset();
        }
        return $this->fieldsetLast;
    }

    /**
     * @desc проверяет переданную строку на соответствие реальному набору полей. Заносит существующие в FieldsShow
     * @param string $fieldsKeysString ключи полей разделённые знаком |
     */
    public function setTableFieldsShow($fieldsKeysString)
    {
        if ($fieldsKeysString === null) {
            $this->_tableFieldsShow = null;
        } else {
            //Пробежимся по списку, проверим реальность этих полей, занесём правильные поля в $this->FieldsShow
            $this->_tableFieldsShow = explode('|', $fieldsKeysString);
        }
    }

    public function getTableFieldsShow()
    {
        return $this->_tableFieldsShow;
    }

    /**
     * Возвращает филдсет с полями, которые реально отображаются
     * @return Fieldset
     */
    public function getFieldsetShowed()
    {
        /** @var $outFieldset Fieldset */
        $outFieldset = $this->getFieldset()->filterByMode(F::MODE_LIST);
        //Если _fieldsShow установлен в null - это сигнал к тому, что нужно выводить все поля!
        if ($this->_tableFieldsShow !== null) {
            $outFieldset = $outFieldset->filterByKeys($this->_tableFieldsShow);
        }

        return $outFieldset;
    }

    /**
     * Проверяет, отображется ли поле в режиме таблицы?
     * Поле должно иметь mode F::MODE_LIST и быть среди отображаемых в текущих настроках Table layout settings (массив $this->_tableFieldsShow)
     * @param string $fieldKey
     * @return bool
     */
    public function isFieldShowed($fieldKey)
    {
        $field = $this->getField($fieldKey);
        if (!$field) {
            //Такого поля вообще нет
            return false;
        }

        if (!$field->hasMode(F::MODE_LIST)) {
            //Это поле не предназначено для отображения в режиме таблицы, оно не может быть отображемым в таблице
            return false;
        }

        /** @noinspection IfReturnReturnSimplificationInspection */
        if ($this->_tableFieldsShow !== null && !in_array($fieldKey, $this->_tableFieldsShow)) {
            //Если задан конкретный массив поле для отображения, но при этом требуемого нам поля нет в этом массиве
            //значит поле не отображатеся в режиме таблицы
            return false;
        }

        //Прошли все проверки
        return true;
    }

    public function setTableData(array $data)
    {
        $this->_tableData = $data;
    }

    public function setTableTotalRowData(array $totalRowData = null)
    {
        $this->_tableTotalRowData = $totalRowData;
    }

    public function setTableTotalRowCalcFieldsKeys(array $totalRowCalcFieldsKeys = null)
    {
        $this->_tableTotalRowCalcFieldsKeys = $totalRowCalcFieldsKeys;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($name)
    {
        $this->tableName = $name;
    }

    /**
     * Возвращает имя таблицы, которое будет использовано для записи настроек пользователя в user_tableparam и user_tablelayout
     * Проблема возникает с подтаблицами, которые могут называться одинаково в разных модулях, из-за чего настройки постоянно перезаписывают друг друга
     * Этот метод будет переопределён в классе Subtable
     * @return string
     */
    protected function getTableNameForSettings()
    {
        return $this->getTableName();
    }

    /**
     * Метод для переопределения в классах потомках. Эти параметры будут вставлены в data-reloadurlparams
     * и будут использованы при ajax обновлении таблицы
     * @return string
     */
    protected function getTableReloadUrl()
    {
        return '?a=' . Request::getModule();
    }

    /**
     * Метод для переопределения в классах потомках. Эти параметры будут вставлены в data-linkurlparams
     * и будут использованы при генерации ссылок в полях $this->_tableLinkFieldKeys и при двойном клике на строке
     * Чтобы ссылки и двойной клик сработали, дополнительно нужно определить $this->_tableLinkModule
     * @return array
     */
    protected function getTableLinkUrlParams(): array
    {
        return [];
    }

    /**
     * @desc возвращате полный html код таблицы
     * @param bool $printHeadBlock
     * @param bool $printIfEmpty
     * @return string
     */
    public function getHtmlTable($printHeadBlock = true, $printIfEmpty = false)
    {
        $linkmodule =
            $this->_tableLinkModule ? ' data-linkmodule="' . htmlspecialchars($this->_tableLinkModule) . '"' : '';
        $reloadurl = $this->getTableReloadUrl();
        $reloadurl = $reloadurl ? ' data-reloadurl="' . htmlspecialchars($reloadurl) . '"' : '';

        return
            '<div class="table-div" id="' . $this->getTableName() .
            '-table-div" data-tablename="' . $this->getTableName() . '"' . $linkmodule . $reloadurl . '>' .
            $this->getHtmlTableForAjax($printHeadBlock, $printIfEmpty) . '</div>';
    }

    /**
     * Возвращает Fieldset из всех отображаемых полей включая _fieldsetFirst и _fieldsetLast
     * @return array
     */
    private function getAllFields(): array
    {
        $allFields = [];

        if ($this->fieldsetFirst) {
            foreach ($this->fieldsetFirst as $field) {
                $allFields[] = $field;
            }
        }

        $fieldset = $this->getFieldset();
        $shownFieldKeys = $this->getTableFieldsShow() ?? $fieldset->getKeys();
        foreach ($shownFieldKeys as $fieldKey) {
            $field = $fieldset[$fieldKey];
            if ($field && $field->modes & F::MODE_LIST) {
                $allFields[] = $field;
            }
        }

        if ($this->fieldsetLast) {
            foreach ($this->fieldsetLast as $field) {
                $allFields[] = $field;
            }
        }

        return $allFields;
    }

    public function getHtmlTableForAjax($printHeadBlock = true, $printIfEmpty = false)
    {
        //Если таблица пуста, возвращаем сообщение No data available
        if (!$printIfEmpty && $this->getTableDataCount() === 0) {
            return $this->_tableMsgIfEmpty ?? (Config::$lang === 'ru' ? 'Нет данных' : 'No data available');
        }

        $allFields = $this->getAllFields();

        //Сначала блок <p>...</p> над таблицей с различными ссылками: пажинация, экспорт в Excel и т.п.
        $htmlHeadBlock = $printHeadBlock ? $this->getHtmlTableHeadBlock() : '';
        $outHtml = $htmlHeadBlock;


        if ($printHeadBlock) {
            $outHtml .= '<div style="display: inline-block;">';
        }

        //Отркоем тег <table>, внутри будет много data-* атрибутов
        $outHtml .= $this->getHtmlTableOpenTableTag();

        //Выводим <thead>...</thead>
        $outHtml .= $this->getHtmlTableThead($allFields);

        //Генерируем <tbody>...</tbody>
        $outHtml .= $this->getHtmlTableTbody($allFields);

        $outHtml .= '</table>';

        if ($printHeadBlock) {
            $outHtml .= '</div>';
        }

        if ($this->getTableDataCount() > 30) {
            //Если в таблице более 30 строк, то повторим блок <p>...</p> со сслыками пажинации, экспорт в Excel и т.п.
            $outHtml .= $htmlHeadBlock;
        }

        return $outHtml;
    }

    protected function getHtmlTableTotalRecordsFound()
    {
        $msg = Config::$lang === 'ru' ? 'Найдено' : 'Total rows';
        return '<span class="table-totalrows">' . $msg . ': ' . $this->getTableTotalRecordsFound() . '</span>';
    }

    protected function getHtmlTableHeadBlock()
    {
        $outHtml = '<p class="table-headblock nobr">';


        if ($this->_tablePrintTotalRecordsFound) {
            $outHtml .= $this->getHtmlTableTotalRecordsFound();
        }

        if (method_exists($this, 'getTablePagesHtml')) {
            /** @var $this \Light\Traits\TablePaginatable */
            $paginationHtml = $this->getTablePagesHtml();
            $paginationHtml .= $this->getTableHtmlShowAll();
            $outHtml .= $paginationHtml;
        }
        /** @var $this \Light\Traits\TableExcelExportable */
        if (method_exists($this, 'isExcelEnabled') && $this->isExcelEnabled()) {
            $excelLinkHtml = '<span class="table-excel"><a href="' . $this->getTableExcelUrl() . '">' . Img::iconExcel() . ' Export to Excel</a></span>';
            $outHtml .= $excelLinkHtml;
        }
        /** @var $this \Light\Traits\TableSortable */
        if (method_exists($this, 'isSortingEnabled') && $this->isSortingEnabled()) {
            $sortBlockHtml = $this->getHtmlTableHeadBlockSortable();
            $outHtml .= $sortBlockHtml;
        }

        /** @var $this \Light\Traits\TableLayoutable */
        if (method_exists($this, 'isLayoutEnabled') && $this->isLayoutEnabled()) {
            $layoutsLinkHtml = $this->getHtmlTableHeadLayoutSettingsLink();
            $outHtml .= $layoutsLinkHtml;
        }

        if (method_exists($this,
                'getAdditionalHtmlTableHeadBlock') && $additionalHtml = $this->getAdditionalHtmlTableHeadBlock()) {
            $outHtml .= $additionalHtml;
        }

        /** @var $this \Light\Table */
        $outHtml .= '</p>';

        return $outHtml;
    }

    private function getHtmlTableOpenTableTag()
    {
        $dataTableName = ' data-tablename="' . $this->getTableName() . '"';
        $class = ' class="table' . ($this->nosticky ? ' nosticky' : '') . '"';
        $stickyOffset = $this->stickyOffset !== null ? ' data-stickyoffset="' . $this->stickyOffset . '"' : '';
        $dataLinkUrlParams = http_build_query($this->getTableLinkUrlParams());
        if ($dataLinkUrlParams) {
            $dataLinkUrlParams = ' data-linkurlparams="' . htmlspecialchars($dataLinkUrlParams) . '"';
        }

        return '<table' . $dataTableName . $class . $dataLinkUrlParams . $stickyOffset . '>';
    }

    /**
     * Возвращает массив из строк, которые должны быть помещены в thead. Обычно это одна строка
     * @param array $allFields
     * @return array
     */
    private function getHtmlTableThead(array $allFields)
    {
        $outHtml = '<thead><tr>';
        /** @var FieldAbstract $field */
        foreach ($allFields as $field) {
            $title = $field->getTitle();
            $title = $title ? ' title="' . htmlspecialchars($title) . '"' : '';

            $width = $field->getTableWidthHtml();
            $width = $width ? ' width="' . htmlspecialchars($width) . '"' : '';
            $fieldKey = ' data-fieldkey="' . htmlspecialchars($field->key) . '"';
            $cssClass = !$field->showInTable ? ' class="display-none"' : '';

            $outHtml .= '<th' . $title . $width . $fieldKey . $cssClass . '>' . $this->getHtmlTableTheadThContent($field) . '</th>';
        }
        $outHtml .= '</tr></thead>';

        return $outHtml;
    }

    /**
     * @param F $field
     * @return string
     */
    protected function getHtmlTableTheadThContent(F $field)
    {
        return str_replace("\n", '<br>', $field->getNameTable());
    }

    /**
     * Метод переопределяется в ModuleAbstract, т.к. там данные не находятся в массиве $this->_tableData,
     * а лениво подгружаются из результатов mysqli запроса
     * @param int $rowNum
     * @return array
     */
    protected function getTableRowData($rowNum)
    {
        return $this->_tableData[$rowNum] ?? [];
    }

    private function getHtmlTableTbody(array $allFields)
    {
        //Если есть total строка, то нам понадобится вспомагательный массив с calculated данными по каждой строке
        //А если total строки нет, то этот массив и не нужен (переменную $calculatedDataCache сделаем null)
        $tableDataCount = $this->getTableDataCount();

        if (is_array($this->_tableTotalRowCalcFieldsKeys)) {
            $calcTotal = true;
            $calculatedDataCache = [];
            $calcFieldKeysHash = array_flip($this->_tableTotalRowCalcFieldsKeys);
        } else {
            $calcTotal = false;
            $calculatedDataCache = null;
            $calcFieldKeysHash = null;
        }

        $tableLinkUrlParams = $this->getTableLinkUrlParams();

        $outHtml = '<tbody>';
        for ($rowNum = 0; $rowNum < $tableDataCount; $rowNum++) {

            //Достаём данные из массива $this->_tableData
            $rowData = $this->getTableRowData($rowNum);

            //Вызовем все методы calcData определённые внутри полей
            foreach ($allFields as $field) {
                $fieldKey = $field->key;
                $value = $rowData[$fieldKey] ?? null;
                if ($field->calcFunction) {
                    $value = $field->getCalculatedValue($value, $rowData, true); //$forTable = true
                }
                $rowData[$fieldKey] = $value;

                if ($calcTotal && isset($calcFieldKeysHash[$fieldKey])) {
                    $calculatedDataCache[$rowNum][$fieldKey] = $rowData[$fieldKey];
                }
            }

            $outHtml .= $this->getHtmlTableTr($rowData, $rowNum, $allFields, $tableLinkUrlParams);

        }

        //Если есть total строка
        if (is_array($this->_tableTotalRowData) || is_array($this->_tableTotalRowCalcFieldsKeys)) {
            $outHtml .= $this->getHtmlTableTrTotal($allFields, $calculatedDataCache);
        }


        $outHtml .= '</tbody>';

        return $outHtml;
    }

    /**
     * Метод для переопределения в классах потомках
     * @param array $rowData
     * @param int $rowNum
     * @return string
     */
    protected function getTableRowCssClass(array $rowData, int $rowNum): string
    {
        return '';
    }

    /**
     * Метод для переоределения в классах потомках
     * Возвращает url-параметры для строки. Они будут вставлены в аттрибут data-urlparams в теге tr,
     * Эти url-параметры в итоге будут использованы при двойном клике на строке (см. Table.js)
     * и при клике на одну из ссылок $this->_listLinkFieldKeys
     * @param array $rowData
     * @param int $rowNum
     * @return array
     */
    protected function getTableRowLinkUrlParams(array $rowData, int $rowNum): array
    {
        return [];
    }

    /**
     * @param array $rowData
     * @param int $rowNum
     * @return string
     */
    protected function getTableRowTagParams(array $rowData, int $rowNum): string
    {
        return '';
    }

    private $tableLinkFieldKeysHash;

    /**
     * Метод переопределяется в Subtable, когда в режиме редактирования нужно выводить поля в режиме редактирования
     * @param array $rowData
     * @param int $rowNum
     * @param F $field
     * @param array $fullLinkParams
     * @return string
     */
    protected function getHtmlTableCell(array $rowData, int $rowNum, F $field, array $fullLinkParams): string
    {
        $value = $rowData[$field->key] ?? null;
        $outHtml = $field->getHtml($value, $rowData, true); //forTable = ture
        // Обернём значение ячейки в ссылку на модуль _tableLinkModule, если это поле среди _tableLinkFieldKeys
        if ($this->_tableLinkModule) {

            if ($this->tableLinkFieldKeysHash === null) {
                //Оптимизация: кеширующая переменная хэш с ключами полей, которые являются ссылками, чтобы
                //делать более быструю проверку isset вместо in_array
                if ($this->_tableLinkFieldKeys) {
                    $this->tableLinkFieldKeysHash = array_flip($this->_tableLinkFieldKeys);
                } else {
                    $this->tableLinkFieldKeysHash = [];
                }
            }

            if (isset($this->tableLinkFieldKeysHash[$field->key], $rowData[$this->_tableLinkIdFieldKey])) {
                $outHtml = Link::moduleView(
                    $outHtml,
                    $rowData[$this->_tableLinkIdFieldKey],
                    $fullLinkParams,
                    $this->_tableLinkModule
                );
            }
        }

        return $outHtml;
    }

    protected function getHtmlTableTr(array $rowData, int $rowNum, array $allFields, array $tableLinkUrlParams)
    {
        $cssClass = $this->getTableRowCssClass($rowData, $rowNum);

        if ($this->isFindId($rowData)) {
            $cssClass .= ' findid';
        }

        if (isset($rowData['Color']) && $rowData['Color'] != 0) {
            //Если существует поле с зарезервированным именем Color, то внесём этот цвет в класс строки
            $cssClass .= ' color' . $rowData['Color'];
        }

        $cssClass = $cssClass ? ' class="' . $cssClass . '"' : '';

        $dataLinkId = isset($rowData[$this->_tableLinkIdFieldKey]) ? ' data-linkid="' . $rowData[$this->_tableLinkIdFieldKey] . '"' : '';

        $rowLinkUrlParams = $this->getTableRowLinkUrlParams($rowData, $rowNum);
        $dataLinkUrlParams = $rowLinkUrlParams ? ' data-linkurlparams="' . htmlspecialchars(http_build_query($rowLinkUrlParams)) . '"' : '';

        $fullLinkParams = $tableLinkUrlParams + $rowLinkUrlParams;

        $tagParams = $this->getTableRowTagParams($rowData, $rowNum);
        $tagParams = $tagParams ? ' ' . $tagParams : '';

        $outHtml = "<tr{$cssClass}{$dataLinkId}{$dataLinkUrlParams}{$tagParams}>";

        foreach ($allFields as $field) {

            /** @var \Light\Field\FieldAbstract $field */
            $fieldKey = $field->key;
            $value = $rowData[$fieldKey];
            if ($field->groupconcat && strpos($value, "\n") !== false) {
                //Если было объединение нескольких значений, то накладывать css class на всю ячейку TD не стоит
                //В этом случае каждое значение должно быть подсвечено отдельно
                $tdCssClass = '';
            } else {
                $tdCssClass = $field->getViewCssClass($value, $rowData, true);
            }
            if ($field->tableAlign) {
                $tdCssClass .= ' ta' . $field->tableAlign[0]; //получается 'tac' (если center) или 'tar' (если right)
            }
            if (!$field->showInTable) {
                $tdCssClass .= ' display-none';
            }
            $tdCssClass = $tdCssClass ? ' class="' . $tdCssClass . '"' : '';

            $tdTitle = $field->getViewTitle($value, $rowData, true);
            $tdTitle = $tdTitle ? ' title="' . htmlspecialchars($tdTitle) . '"' : '';

            $tdTagParams = $field->getViewTagParams($value, $rowData, true);
            $tdTagParams = $tdTagParams ? ' ' . $tdTagParams : '';

            $tdHtml = $this->getHtmlTableCell($rowData, $rowNum, $field, $fullLinkParams);

            // Если в css классе для td присутсвует хитрый класс "overflow300", то его нужно оттуда убрать
            // т.к. этот класс портит рендеринг td. Придётся добавить отдельный div поверх контента ячейки
            if (strpos($tdCssClass, 'overflow300') !== false) {
                $tdCssClass = str_replace(' overflow300', '', $tdCssClass);
                $tdHtml = '<div class="overflow300">' . $tdHtml . '</div>';
            }

            $outHtml .= "<td{$tdCssClass}{$tdTitle}{$tdTagParams}>{$tdHtml}</td>";

        }

        $outHtml .= '</tr>';

        return $outHtml;
    }

    protected function getHtmlTableTrTotal(array $allFields, array $calculatedDataCache = null)
    {
        $totalRowData = is_array($this->_tableTotalRowData) ? $this->_tableTotalRowData : [];

        //Если были переданые поля для подсчёта Total динамически - сделаем это!
        //Подсчёт total происходит до вызова calcData и getTableRowDataHtmlFormatedStandard
        if ($calculatedDataCache && is_array($this->_tableTotalRowCalcFieldsKeys)) {
            foreach ($this->_tableTotalRowCalcFieldsKeys as $fieldKey) {
                //Посчитаем эту total ячейку только в том случае, если пользователь не определил её сам ранее
                if (array_key_exists($fieldKey, $calculatedDataCache[0]) && !array_key_exists($fieldKey,
                        $totalRowData)) {
                    $totalRowData[$fieldKey] = $this->tableCalcFieldTotal($fieldKey, $calculatedDataCache);
                }
            }
        }

        $outHtml = '<tr class="total">';

        foreach ($allFields as $field) {

            /** @var \Light\Field\FieldAbstract $field */
            $fieldKey = $field->key;

            $totalValue = $totalRowData[$fieldKey] ?? null;
            if ($field->groupconcat && strpos($totalValue, "\n") !== false) {
                //Если было объединение нескольких значений, то накладывать css class на всю ячейку TD не стоит
                //В этом случае каждое значение должно быть подсвечено отдельно
                $tdCssClass = '';
            } else {
                $tdCssClass = $field->getViewCssClass($totalValue, $totalRowData, 'total');
            }
            if ($field->tableAlign) {
                $tdCssClass .= ' ta' . $field->tableAlign[0]; //получается 'tac' (если center) или 'tar' (если right)
            }
            if (!$field->showInTable) {
                $tdCssClass .= ' display-none';
            }
            $tdCssClass = $tdCssClass ? ' class="' . $tdCssClass . '"' : '';

            // Формируем html представление только для тех ячеек, у которых реально определено значнеие в массиве $totalRowData
            // иначе можем получить total строку из большого кол-ва значений по умолчанию (нули, No, и т.п)
            if (array_key_exists($fieldKey, $totalRowData)) {
                $totalValue = $totalRowData[$fieldKey];
                if ($field->getType() === F::TYPE_MONEY_WITH_CURRENCY && strpos($totalValue, "\n") !== false) {
                    // Текущая колонка имеет тип TYPE_MONEY_WITH_CURRENCY, а total значение содержит перевод строки
                    // значит в таблице были разные валюты и мы в итоге получаем разбивку по валютам
                    // нужно применить groupconcat подход
                    $fieldWithGroupconcat = $field->getClone($field->key, ['groupconcat' => true]);
                    $tdHtml = $fieldWithGroupconcat->getHtml($totalValue, $totalRowData, 'total');
                } else {
                    $tdHtml = $field->getHtml($totalValue, $totalRowData, 'total');
                }
            } else {
                $tdHtml = '';
            }

            $outHtml .= "<td{$tdCssClass}>{$tdHtml}</td>";

        }

        $outHtml .= '</tr>';

        return $outHtml;
    }

    private $findId;

    protected function isFindId(array $rowData): bool
    {
        // Инициализируем кэш, если он ещё не был инициализирован
        if (!is_array($this->findId)) {

            $this->findId = [];

            //Получим значение из GET параметра
            $findIdFromRequest = $this->getTableFindIdFromRequest();
            if ($findIdFromRequest) {
                $findIdFields = explode('+', $findIdFromRequest);
                foreach ($findIdFields as $part) {

                    if (strpos($part, '|') !== false) {
                        list($key, $val) = explode('|', $part);
                        $this->findId[$key] = $val;
                    } else {
                        $this->findId['ID'] = $part;
                    }

                }

            }

        }

        if (!$this->findId) {
            return false;
        }

        foreach ($this->findId as $key => $val) {
            if (!isset($rowData[$key]) || $rowData[$key] != $val) {
                return false;
            }
        }

        return true;
    }

    /**
     * Калькулирует и возвращает значение сумму всех значений поля $fieldKey в массиве $this->_data
     * Тут может быть нюанс: если поле типа MONEY_WITH_CURRENCY, то считать total нужно только в том случае, если все
     * значения имеют одинаковую валюту.
     * Также при типе MONEY_WITH_CURRENCY меняется тип возвращаемого значения - это уже не просто число, но строка
     * @param string $fieldKey
     * @param array $tableData
     * @return float|string $total
     */
    public function tableCalcFieldTotal(string $fieldKey, array $tableData)
    {
        $amounts = array_column($tableData, $fieldKey);

        $isMoneyWithCurrency = $this->getField($fieldKey)->getType() == F::TYPE_MONEY_WITH_CURRENCY;
        if ($isMoneyWithCurrency) {
            $amountPerCurrency = [];
            foreach ($amounts as $amountWithCurrency) {
                [$currencyIso, $amount] = FieldMoneyWithCurrency::getAsTouple($amountWithCurrency);
                if ($amount && $currencyIso) {
                    if (!isset($amountPerCurrency[$currencyIso])) {
                        $amountPerCurrency[$currencyIso] = 0;
                    }
                    $amountPerCurrency[$currencyIso] += $amount;
                }
            }

            ksort($amountPerCurrency);

            $resultAmountsWithCurrency = [];
            foreach ($amountPerCurrency as $currencyIso => $totalAmount) {
                $resultAmountsWithCurrency[] = "$currencyIso $totalAmount";
            }
            // Возвращаем как groupconcat поле
            return implode("\n", $resultAmountsWithCurrency);
        }

        return array_sum($amounts);
    }

    protected function getTableTotalRecordsFound()
    {
        return $this->getTableDataCount();
    }

    public function getTableData()
    {
        return $this->_tableData;
    }

    public function getTableCalculatedData()
    {
        $calculatedData = [];
        $fieldsetShowed = $this->getFieldsetShowed();
        $rowsCount = $this->getTableDataCount();
        $tableData = $this->getTableData();
        for ($rowNum = 0; $rowNum < $rowsCount; $rowNum++) {

            $rowData = $tableData[$rowNum] ?? [];
            $calculatedRowData = $fieldsetShowed->calcData($rowData, true);
            //Скалькулированные значениея строки положим обратно в _tableData - пригодится для подсчёта total
            $calculatedData[] = $calculatedRowData;
        }

        return $calculatedData;
    }

    public function getTableDataCount()
    {
        return count($this->_tableData);
    }

    public static function writeDbUserTableParam(string $tableName, $paramName, $value)
    {
        $query = "INSERT INTO user_tableparam (
                      CreatedBy,
                      TableName,
                      " . Db::quote($paramName) . "

                  ) VALUES (
                         " . User::getId() . "
                      , '" . Db::esc($tableName) . "'
                      , '" . Db::esc($value) . "'

                  ) ON DUPLICATE KEY UPDATE " . Db::quote($paramName) . " = VALUES(" . $paramName . ")";

        Db::query($query);
    }

    private function lazyLoadTableUserParams()
    {
        if ($this->tableUserParams === null) {
            $this->tableUserParams = Db::getRow(
                "SELECT * 
                 FROM user_tableparam
                 WHERE TableName = @s AND CreatedBy = @i"
                , $this->getTableNameForSettings()
                , User::getId()
            );
        }
    }

    /**
     * Сохраняет переданный параметр/парамтры в таблицу user_tableparam
     * @param string $name
     * @param string $value
     */
    protected function setTableUserParam($name, $value)
    {
        $this->lazyLoadTableUserParams();
        Table::writeDbUserTableParam($this->getTableNameForSettings(), $name, $value);
        $this->tableUserParams[$name] = $value;
    }

    protected function getTableUserParam($name)
    {
        $this->lazyLoadTableUserParams();
        return $this->tableUserParams[$name] ?? null;
    }

    /**
     * @return Fieldset|F[]
     */
    public function getFieldset()
    {
        return $this->_obj->getFieldset();
    }

    public function getDbTable()
    {
        return $this->_obj->getDbTable();
    }

    public function setDbTable($dbTable)
    {
        $this->_obj->setDbTable($dbTable);
    }

    public function setNameFieldKey($nameFieldKey)
    {
        $this->_obj->setNameFieldKey($nameFieldKey);
    }
}
