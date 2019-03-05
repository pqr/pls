<?php

namespace Light;

use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldFloat;
use Light\Field\FieldMoneyWithCurrency;
use Light\Field\FieldOptionsAbstract;
use Light\Table\TableUserParamsRepository;
use Light\Traits\FieldsetAwareTrait;

class Table implements \JsonSerializable
{
    use FieldsetAwareTrait;

    /**
     * Выводить ли блок над таблицей с кол-вом найденных записей, пажинацией, кнопкой экспорта в Excel и проч?
     * По умолчанию false, т.к. для таблиц создаваемых напрямую из класса Table это наиболее удобное поведение.
     * Но для ModuleAbstract и Subtable и ReportAbstract этот флаг переопределён в true
     * @var bool
     */
    public $allowTablePrintHeadBlock = false;

    /**
     * Печатать ли сообщение "Total records found: xxx" (где xxx - результат вызова $this->getTableTotalRecordsFound())
     * @var bool
     */
    public $allowTablePrintTotalRecordsFound = true;

    /**
     * Если установить в true, то даже при пустых данных будет выведна структура таблицы и thead.
     * Если же false (по умолчанию), то при пустых данных будет выведено сообщение textTableEmpty (см. ниже)
     * @var bool
     */
    public $allowTablePrintTheadIfEmpty = false;

    /**
     * Прикреплять ли шапку таблицы к верху экрана с помощью position: sticky
     * @var bool
     */
    public $tableSticky = true;


    /**
     * @var bool Выводить ли total строку? (при наличии total данных)
     */
    public $allowTableTotal = true;

    /** @var string */
    public $textTableEmpty;

    /**
     * путь по которому таблица будет делать ajax запрос, чтобы перезагрузить себя с сервера
     * например при пажинации или при применении фильтров
     * @var string
     */
    public $tableReloadUrl;

    /**
     * модуль, на который автоматически делать ссылки при двойном клике по строке
     * (в тег tr будет добавлен атрибут data-linkid)
     * @var string
     */
    public $tableLinkModule;

    /**
     * Имена полей, которые должны стать ссылками на внешний модуль $this->tableLinkModule
     * @var array
     */
    public $tableLinkFieldKeys = ['ID', 'Name'];

    /**
     * ключевое поле поле, в котором хранится id для подстановки ссылки при использовании tableLinkModule и tableLinkFieldKeys
     * @var string
     */
    public $tableLinkIdFieldKey = 'ID';

    /**
     * дополнительные параметры, которые будут добавлены к ссылкам генерируемым по трём предыдущим свойствам
     * @var array
     */
    public $tableLinkUrlParams = [];

    /**
     * Делать ли ссылки на ключивых полях описанных выше с атрибутом target=_blank?
     * @var bool
     */
    public $tableLinkBlank = false;

    /**
     * Массив ключей полей, которые следует отображать в нужном порядке.
     * Если это поле null, будут выведены все поля.
     * @var array|null
     */
    public $tableFieldsShow;

    /** @var string Внутреннее имя таблицы, если оставить пустым будет приравнено к полному имени класса */
    private $tableName;

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

    /**
     * Двумерный массив данных. Задаётся с помощью setTableData()
     * @var array
     */
    protected $tableData = [];

    /**
     * Статические данные для total строки. Задаётся с помощью setTableDataTotal()
     * @var array
     */
    protected $tableDataTotal = [];

    /**
     * Параметры таблицы, которые хранятся в базе в user_tableparam
     * @var TableUserParamsRepository $tableUserParams
     */
    protected $tableUserParams;

    public function __construct($tableName = null)
    {
        if ($tableName) {
            $this->tableName = $tableName;
        } else {
            $tableClass = get_class($this);
            if (strpos($tableClass, 'class@anonymous') === 0) {
                $tableClass = 'Table' . uniqid();
            }
            $this->tableName = str_replace('\\', '_', $tableClass);
        }

        //Инициализируем внутреннюю переменную _obj хитрым способом -  возможно, на основе файла в этой же папке
        $this->_obj = $this->createObj();
        $this->tableUserParams = new TableUserParamsRepository($this->getTableNameForSettings(), User::getId());
    }

    protected function createObj()
    {
        // Если объект не задан явно, попробуем найти соответсвующий класс рядом с текущим файлом модуля
        $tableClass = get_class($this);
        if (strpos($tableClass, 'class@anonymous') !== 0) {
            $objClass = $tableClass . 'Obj';
            $filepath = LIGHT_DIR_ROOT . '/src/' . str_replace('\\', '/', $objClass) . '.php';
            if (file_exists($filepath)) {
                //Файл найден, создаём объект именно этого класса
                return new $objClass();
            }
        }

        // Отдельного файла для класса найдено не было, создадим на основе обычного Obj
        $obj = new SimpleObj();
        $obj->setDbTable(strtolower($this->getTableName()));
        $obj->setMasterModuleForDbLog(Misc::getModule($this));
        return $obj;
    }

    /**
     * @return ObjAbstract|SimpleObj
     */
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
            $this->tableFieldsShow = null;
        } else {
            //Пробежимся по списку, проверим реальность этих полей, занесём правильные поля в $this->FieldsShow
            $this->tableFieldsShow = explode('|', $fieldsKeysString);
        }

        $this->fieldsetShowedCache = null;
    }

    public function getTableFieldsShow()
    {
        return $this->tableFieldsShow;
    }

    private $fieldsetShowedCache;

    /**
     * Возвращает филдсет с полями, которые реально отображаются
     * @return Fieldset
     */
    public function getFieldsetShowed()
    {
        if ($this->fieldsetShowedCache === null) {
            /** @var $outFieldset Fieldset */
            $this->fieldsetShowedCache = $this->getFieldset()->filterByMode(F::MODE_LIST);
            // Если _fieldsShow установлен в null - это сигнал к тому, что нужно выводить все поля!
            if ($this->tableFieldsShow !== null) {
                $this->fieldsetShowedCache = $this->fieldsetShowedCache->filterByKeys($this->tableFieldsShow);
            }
        }

        return $this->fieldsetShowedCache;
    }

    public function resetFieldsetShowedCache(): void
    {
        $this->fieldsetShowedCache = null;
    }

    protected function addCheckboxCol($class = 'cb')
    {
        $checkboxField = F::createHtml('CheckBox', [
                'tableWidthHtml' => 25,
                'tableOrderBy' => '',
                'tableAlign' => 'center',
                'nameTable' => '<input type="checkbox" class="cb-all">',
                'getHtmlFunction' => function ($value, array $data) use ($class) {
                    if (isset($data['ID'])) {
                        return '<input type="checkbox" class="' . $class . '" value="' . $data['ID'] . '">';
                    }

                    return '';
                },
            ]
        );
        $this->getFieldsetFirst()->addField($checkboxField);
        return $checkboxField;
    }

    /**
     * Проверяет, отображется ли поле в режиме таблицы?
     * Поле должно иметь mode F::MODE_LIST и быть среди отображаемых в текущих настроках Table layout settings (массив $this->tableFieldsShow)
     * @param string $fieldKey
     * @return bool
     */
    public function isFieldShowed(string $fieldKey): bool
    {
        return $this->getFieldsetShowed()->hasField($fieldKey);
    }

    public function setTableData(array $data)
    {
        $this->tableData = $data;
    }

    public function setTableDataTotal(array $dataTotal)
    {
        $this->tableDataTotal = $dataTotal;
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
     * @desc возвращате полный html код таблицы
     * @return string
     */
    public function getHtmlTable()
    {
        return $this->getHtmlTableDivWrapper()
            . $this->getHtmlTableForAjax()
            . '</div>';
    }

    protected function getHtmlTableDivWrapper()
    {
        $tableName = $this->getTableName();

        $id = ' id="' . $tableName . '-table-div"';
        $dataTablename = ' data-tablename="' . $tableName . '"';
        $linkmodule = '';
        if ($this->tableLinkModule) {
            $linkmodule = ' data-linkmodule="' . htmlspecialchars(Misc::getModule($this->tableLinkModule)) . '"';
        }

        $reloadurl = '';
        if ($this->tableReloadUrl) {
            $reloadurl = ' data-reloadurl="' . htmlspecialchars(Misc::getModule($this->tableReloadUrl)) . '"';
        }

        $ajaxChangeFieldUrl = $this->getTableAjaxChangeFieldUrl();
        if ($ajaxChangeFieldUrl) {
            $ajaxChangeFieldUrl = ' data-ajaxchangefieldurl="' . $ajaxChangeFieldUrl . '"';
        }

        return '<div class="table-div"' . $id . $dataTablename . $linkmodule . $reloadurl . $ajaxChangeFieldUrl . '>';
    }

    /**
     * Возвращает Fieldset из всех отображаемых полей включая _fieldsetFirst и _fieldsetLast
     * @return array
     */
    protected function getAllFields(): array
    {
        $allFields = [];

        if ($this->fieldsetFirst) {
            foreach ($this->fieldsetFirst as $field) {
                $allFields[] = $field;
            }
        }

        $allFields = array_merge($allFields, $this->getFieldsetShowed()->getFieldsArr());

        if ($this->fieldsetLast) {
            foreach ($this->fieldsetLast as $field) {
                $allFields[] = $field;
            }
        }

        return $allFields;
    }

    public function getHtmlTableForAjax()
    {
        //Если таблица пуста, возвращаем сообщение No data available
        if (!$this->allowTablePrintTheadIfEmpty && $this->getTableDataCount() === 0) {
            return $this->textTableEmpty ?? (Config::$lang === 'ru' ? 'Нет данных' : 'No data available');
        }

        $allFields = $this->getAllFields();
        $this->prefetchSelectOptions($allFields);

        //Сначала блок <p>...</p> над таблицей с различными ссылками: пажинация, экспорт в Excel и т.п.
        $htmlHeadBlock = '';
        if ($this->allowTablePrintHeadBlock) {
            $htmlHeadBlock = $this->getHtmlTableHeadBlock();
        }
        $outHtml = $htmlHeadBlock;


        if ($this->allowTablePrintHeadBlock) {
            $outHtml .= '<div style="display: inline-block;">';
        }

        //Отркоем тег <table>, внутри будет много data-* атрибутов
        $outHtml .= $this->getHtmlTableOpenTableTag($allFields);

        //Выводим <thead>...</thead>
        $outHtml .= $this->getHtmlTableThead($allFields);

        //Генерируем <tbody>...</tbody>
        $outHtml .= $this->getHtmlTableTbody($allFields);

        $outHtml .= '</table>';

        if ($this->allowTablePrintHeadBlock) {
            $outHtml .= '</div>';
        }

        if ($this->getTableDataCount() > 30) {
            //Если в таблице более 30 строк, то повторим блок <p>...</p> со сслыками пажинации, экспорт в Excel и т.п.
            $outHtml .= $htmlHeadBlock;
        }

        return $outHtml;
    }

    protected function prefetchSelectOptions(array $allFields)
    {
        foreach ($allFields as $field) {
            if ($field instanceof FieldOptionsAbstract && !is_array($field->options)) {
                $fieldKey = $field->key;
                $columnValues = array_column($this->tableData, $fieldKey);
                if ($columnValues) {
                    $field->prefetchOptions($columnValues);
                }
            }
        }
    }

    protected function getHtmlTableTotalRecordsFound()
    {
        $msg = Config::$lang === 'ru' ? 'Найдено' : 'Total rows';
        return '<span class="table-totalrows">' . $msg . ': ' . $this->getTableTotalRecordsFound() . '</span>';
    }

    protected function getHtmlTableHeadBlock()
    {
        $outHtml = '';

        if (method_exists($this, 'getShowAllWarning')) {
            // Покажем предупреждение о не оптимальном режиме Show All (если у пользователя он включен - проверка внутри)
            /** @var $this \Light\Traits\TablePaginatable */
            $outHtml .= $this->getShowAllWarning();
        }

        $outHtml .= '<p class="table-headblock nobr">';


        if ($this->allowTablePrintTotalRecordsFound) {
            $outHtml .= $this->getHtmlTableTotalRecordsFound();
        }

        if (method_exists($this, 'getTablePagesHtml')) {
            /** @var $this \Light\Traits\TablePaginatable */
            $outHtml .= $this->getTablePagesHtml()
                . $this->getTableHtmlShowAll();
        }

        /** @var $this \Light\Traits\TableExcelExportable */
        if (property_exists($this, 'allowExportToExcel') && $this->allowExportToExcel) {
            /** @var $this \Light\Traits\TableExcelExportable */
            $exportToExcelText = Config::$lang === 'ru' ? 'Экспорт в Excel' : 'Export to Excel';
            $outHtml .= '<span class="table-excel"><a href="' . $this->tableReloadUrl . '&xls=1">'
                . Img::iconExcel()
                . ' ' . $exportToExcelText
                . '</a></span>';
        }

        /** @var $this \Light\Traits\TableSortable */
        if (property_exists($this, 'allowSort') && $this->allowSort) {
            /** @var $this \Light\Traits\TableSortable */
            $outHtml .= $this->getHtmlTableHeadBlockSortable();
        }

        /** @var $this \Light\Traits\TableLayoutable */
        if (property_exists($this, 'allowTableLayouts') && $this->allowTableLayouts) {
            /** @var $this \Light\Traits\TableLayoutable */
            $outHtml .= $this->getHtmlTableHeadLayoutSettingsLink();
        }

        // Вывовем специальный метод, который может быть переопределён в классах потмоках для формирования
        // дополнительных кнопок или сообщений над таблицей (там где пажинация)
        $outHtml .= $this->getHtmlTableHeadBlockAdditional();

        /** @var $this \Light\Table */
        $outHtml .= '</p>';

        return $outHtml;
    }

    /**
     * Метод для переопределения в классах потомках, если нужно вывести что-то дополнительное рядом с пажинацией
     */
    protected function getHtmlTableHeadBlockAdditional(): string
    {
        return '';
    }

    private function getHtmlTableOpenTableTag(array $allFields)
    {
        $dataTableName = ' data-tablename="' . $this->getTableName() . '"';

        $class = 'table';
        if ($this->tableSticky) {
            $class .= ' sticky';
        }

        $dataLinkUrlParams = '';
        if ($this->tableLinkUrlParams) {
            $dataLinkUrlParams = http_build_query($this->tableLinkUrlParams);
            $dataLinkUrlParams = ' data-linkurlparams="' . htmlspecialchars($dataLinkUrlParams) . '"';
        }

        $widthAttr = '';

        /** @var $this \Light\Traits\TableLayoutable */
        if (property_exists($this, 'allowTableLayouts') && $this->allowTableLayouts) {
            $fieldsWidth = $this->tableUserParams->loadFieldsWidth();
            if (is_array($fieldsWidth)) {
                // Проверим, есть ли хотя бы для одного из отображаемых полей настрока пользователя с не нулевой шириной?
                $hasWidthSettings = A::some($allFields, function ($field) use ($fieldsWidth) {
                    return isset($fieldsWidth[$field->key]) && $fieldsWidth[$field->key];
                });
                if ($hasWidthSettings) {
                    // Пользователь задал какие-то настройки хотя бы для одного поля - значит всю таблицу переводим в режим layout:fixed
                    $class .= ' layoutfixed';
                    // Посчитаем суммарную ширину всех полей, чтобы явно добавить width="..." в тег table
                    // без этого не заработает layout:fixed
                    $totalWidth = 0;
                    /** @var FieldAbstract $field */
                    foreach ($allFields as $field) {
                        // Если у поля не была задана пользовательская настройка ширины и нет настройки ширины из параметров поля
                        // то примем 150px, чтобы зарезервировать хоть какое-то место под это поле в общей ширине таблицы
                        $fieldWidth = (int)($field->tableWidthHtml ?: 150);
                        $totalWidth += $fieldWidth;
                    }
                    $widthAttr = ' width="' . $totalWidth . '"';
                }
            }
        }

        $class = ' class="' . $class . '"';

        return '<table' . $dataTableName . $class . $dataLinkUrlParams . $widthAttr . '>';
    }

    /**
     * Возвращает массив из строк, которые должны быть помещены в thead. Обычно это одна строка
     * @param array $allFields
     * @return string
     */
    protected function getHtmlTableThead(array $allFields)
    {
        $outHtml = '<thead><tr>';
        /** @var FieldAbstract $field */
        foreach ($allFields as $field) {
            $title = $field->getTitle();
            $title = $title ? ' title="' . htmlspecialchars($title) . '"' : '';

            $width = $field->getTableWidthHtml();
            $width = $width ? ' width="' . htmlspecialchars($width) . '"' : '';
            $fieldKey = ' data-fieldkey="' . htmlspecialchars($field->key) . '"';
            $dataTotal = $field->total ? ' data-total="true"' : '';

            $cssClass = '';
            if ($field->tableAlign) {
                $cssClass = 'ta' . $field->tableAlign[0]; //получается 'tac' (если center) или 'tar' (если right)
                $cssClass = " class=$cssClass";
            }

            $outHtml .= '<th' . $title . $width . $fieldKey . $dataTotal . $cssClass . '>'
                . $this->getHtmlTableTheadThContent($field)
                . '</th>';
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
     * Метод переопределяется в ModuleAbstract, т.к. там данные не находятся в массиве $this->tableData,
     * а лениво подгружаются из результатов mysqli запроса
     * @param int $rowNum
     * @return array
     */
    protected function getTableRowData($rowNum)
    {
        return $this->tableData[$rowNum] ?? [];
    }

    private function getHtmlTableTbody(array $allFields)
    {
        $tableDataCount = $this->getTableDataCount();

        $dataCalculated = [];

        $outHtml = '<tbody>';
        for ($rowNum = 0; $rowNum < $tableDataCount; $rowNum++) {

            // Достаём данные из массива $this->tableData
            // (метод getTableRowData() может быть переопределён в потомке, поэтому не можем читать напрямую из $this->tableData
            $rowData = $this->getTableRowData($rowNum);
            $rowDataCalculated = $rowData;
            // Вызовем все методы calcData определённые внутри полей
            /** @var FieldAbstract $field */
            foreach ($allFields as $field) {
                $fieldKey = $field->key;
                $value = $rowData[$fieldKey] ?? null;
                if ($field->calcFunction) {
                    $value = $field->getCalculatedValue($value, $rowData, true); //$forTable = true
                }
                $rowDataCalculated[$fieldKey] = $value;
            }
            $dataCalculated[$rowNum] = $rowDataCalculated;

            $outHtml .= $this->getHtmlTableTr($rowDataCalculated, $rowNum, $allFields, $this->tableLinkUrlParams);

        }

        // Добавляем total строку (метод может венуть пустую строку, если для данной таблицы подсчёт total не нужен
        $outHtml .= $this->getHtmlTableTrTotal($allFields, $dataCalculated);

        $outHtml .= '</tbody>';

        return $outHtml;
    }

    /**
     * Метод для переопределения в классах потомках.
     * Он вызывается на всех строках, кроме последней total строки, поэтому $rowNum указан как int
     * (для последней total строки агрумент $rowNum был бы 'total', но этот метод не вызывается для total строки)
     * @param array $rowDataCalculated
     * @param int $rowNum
     * @return string
     */
    protected function getTableRowCssClass(array $rowDataCalculated, int $rowNum): string
    {
        $out = '';
        if (isset($rowDataCalculated['IsSeparator'])) {
            $out = 'separator';
        }

        if (isset($rowDataCalculated['IsTotal'])) {
            $out .= ' total';
        }

        return trim($out);
    }

    /**
     * Метод для переоределения в классах потомках
     * Возвращает url-параметры для строки. Они будут вставлены в аттрибут data-urlparams в теге tr,
     * Эти url-параметры в итоге будут использованы при двойном клике на строке (см. Table.js)
     * и при клике на одну из ссылок $this->_listLinkFieldKeys
     * @param array $rowDataCalculated
     * @param int $rowNum
     * @return array
     */
    protected function getTableRowLinkUrlParams(array $rowDataCalculated, int $rowNum): array
    {
        return [];
    }

    /**
     * @param array $rowDataCalculated
     * @param int $rowNum
     * @return string
     */
    protected function getTableRowTagParams(array $rowDataCalculated, int $rowNum): string
    {
        return '';
    }

    private $tableLinkFieldKeysHash;

    /**
     * Метод переопределяется в Subtable, когда в режиме редактирования нужно выводить поля в режиме редактирования
     * @param array $rowDataCalculated
     * @param int $rowNum
     * @param F $field
     * @param array $fullLinkParams
     * @return string
     */
    protected function getHtmlTableCell(array $rowDataCalculated, int $rowNum, F $field, array $fullLinkParams): string
    {
        $value = $rowDataCalculated[$field->key] ?? null;
        $outHtml = $field->getHtml($value, $rowDataCalculated, true); //forTable = ture
        // Обернём значение ячейки в ссылку на модуль tableLinkModule, если это поле среди tableLinkFieldKeys
        if ($this->tableLinkModule) {

            if ($this->tableLinkFieldKeysHash === null) {
                //Оптимизация: кеширующая переменная хэш с ключами полей, которые являются ссылками, чтобы
                //делать более быструю проверку isset вместо in_array
                if ($this->tableLinkFieldKeys) {
                    $this->tableLinkFieldKeysHash = array_flip($this->tableLinkFieldKeys);
                } else {
                    $this->tableLinkFieldKeysHash = [];
                }
            }

            if (isset($this->tableLinkFieldKeysHash[$field->key], $rowDataCalculated[$this->tableLinkIdFieldKey])) {
                $id = $rowDataCalculated[$this->tableLinkIdFieldKey];
                $outHtml = Link::create($this->tableLinkModule, $outHtml)
                    ->view($id)
                    ->params($fullLinkParams)
                    ->blank($this->tableLinkBlank)
                    ->getHtml();
            }
        }

        return $outHtml;
    }

    protected function getHtmlTableTr(
        array $rowDataCalculated,
        int $rowNum,
        array $allFields,
        array $tableLinkUrlParams
    )
    {
        $cssClass = $this->getTableRowCssClass($rowDataCalculated, $rowNum);

        if ($this->isFindId($rowDataCalculated)) {
            $cssClass .= ' findid';
        }

        if (isset($rowDataCalculated['Color']) && $rowDataCalculated['Color'] != 0) {
            //Если существует поле с зарезервированным именем Color, то внесём этот цвет в класс строки
            $cssClass .= ' color' . $rowDataCalculated['Color'];
        }

        $cssClass = $cssClass ? ' class="' . $cssClass . '"' : '';

        $dataLinkId = isset($rowDataCalculated[$this->tableLinkIdFieldKey]) ? ' data-linkid="' . $rowDataCalculated[$this->tableLinkIdFieldKey] . '"' : '';

        $rowLinkUrlParams = $this->getTableRowLinkUrlParams($rowDataCalculated, $rowNum);
        $dataLinkUrlParams = $rowLinkUrlParams ? ' data-linkurlparams="' . htmlspecialchars(http_build_query($rowLinkUrlParams)) . '"' : '';

        $fullLinkParams = $tableLinkUrlParams + $rowLinkUrlParams;

        $tagParams = $this->getTableRowTagParams($rowDataCalculated, $rowNum);
        $tagParams = $tagParams ? ' ' . $tagParams : '';

        $outHtml = "<tr{$cssClass}{$dataLinkId}{$dataLinkUrlParams}{$tagParams}>";

        foreach ($allFields as $field) {

            $tdHtml = $this->getHtmlTableCell($rowDataCalculated, $rowNum, $field, $fullLinkParams);

            /** @var \Light\Field\FieldAbstract $field */
            $fieldKey = $field->key;
            $value = $rowDataCalculated[$fieldKey];
            if ($field->groupconcat && strpos($value, "\n") !== false) {
                //Если было объединение нескольких значений, то накладывать css class на всю ячейку TD не стоит
                //В этом случае каждое значение должно быть подсвечено отдельно
                $tdCssClass = '';
            } else {
                // Четрвёртым параметром $excludeOverflow300 = true задаём поведение, чтобы класс overflow300
                // не добавлялся ко всей ячейке td - он портит вывод.
                $tdCssClass = $field->getViewCssClass($value, $rowDataCalculated, true, true);

                // Добавим этот класс внутрь содержимого, обернув содержимое дополнительным div
                if ($field->viewMaxWidth300) {
                    $tdHtml = "<div class=\"overflow300\">$tdHtml</div>";
                }
            }

            if ($field->tableAlign) {
                $tdCssClass .= ' ta' . $field->tableAlign[0]; //получается 'tac' (если center) или 'tar' (если right)
            }

            $tdCssClass = $tdCssClass ? ' class="' . $tdCssClass . '"' : '';

            $tdTitle = $field->getViewTitle($value, $rowDataCalculated, true);
            $tdTitle = $tdTitle ? ' title="' . htmlspecialchars($tdTitle) . '"' : '';

            $tdTagParams = $field->getViewTagParams($value, $rowDataCalculated, true);
            $tdTagParams = $tdTagParams ? ' ' . $tdTagParams : '';

            $outHtml .= "<td{$tdCssClass}{$tdTitle}{$tdTagParams}>{$tdHtml}</td>";

        }

        $outHtml .= '</tr>';

        return $outHtml;
    }

    /**
     * Возвращает значения для total строки таблицы
     * @param array $dataCalculated
     * @param array|null $fields опционально можно передать массив полей, по которым делать подсчёт
     * этот массив передаётся явно при рендеринге в html для оптимизации
     * @return array
     */
    public function getTableDataTotal(array $dataCalculated, array $fields = null): array
    {
        // Начальное значение для строки total
        $dataTotal = $this->tableDataTotal ?: [];

        // Уберём из $dataCalculated строки, отмеченные флагом IsSeparator или IsTotal, чтобы не задваивались данные
        foreach ($dataCalculated as $i => $rowDataCalculated) {
            if (isset($rowDataCalculated['IsSeparator']) || isset($rowDataCalculated['IsTotal'])) {
                unset($dataCalculated[$i]);
            }
        }

        if (!$dataCalculated) {
            // Нет подсчитанных данных на основе строк таблицы (таблица пуста?)
            return $dataTotal;
        }

        // Проходим по полям описанным в настройках для автоматичекого подсчёта
        if ($fields === null) {
            $fields = $this->getFieldsetShowed()->getFieldsArr();
        }
        foreach ($fields as $field) {
            if (!$field->total) {
                // У этого поля не стоит флаг total, значит его не нужно подсчитывать в столбик
                continue;
            }
            $fieldKey = $field->key;
            if (array_key_exists($fieldKey, $this->tableDataTotal)) {
                // По этому полю Total значение уже задано пользователем вручную
                continue;
            }
            // Посчитаем используя функцию tableCalcFieldTotal(), которую можно переопределить в классах потомках
            $dataTotal[$fieldKey] = $this->tableCalcFieldTotal($field, $dataCalculated);
        }

        return $dataTotal;
    }

    /**
     * Функция проверяет, нужно ли выводить total строку?
     * Этот метод переопределяется в Subtable, где в режиме Edit строка total выводится обязательно,
     * если есть хотя бы одно поле со свойством total
     * @param array $dataTotal
     * @return bool
     */
    public function hasTotalRow(array $dataTotal): bool
    {
        if (!$this->allowTableTotal) {
            return false;
        }
        // По умолчанию логика такая: если есть данные $dataTotal (которые собраны на основе колонок или заданы вручную)
        // то total строку выводить нужно!
        return (bool)$dataTotal;
    }

    protected function getHtmlTableTrTotal(array $allFields, array $dataCalculated)
    {
        $dataTotal = $this->getTableDataTotal($dataCalculated, $allFields);
        if (!$this->hasTotalRow($dataTotal)) {
            return '';
        }

        $outHtml = '<tr class="total">';

        foreach ($allFields as $field) {

            /** @var \Light\Field\FieldAbstract $field */
            $fieldKey = $field->key;

            $totalValue = $dataTotal[$fieldKey] ?? null;
            if ($field->groupconcat && strpos($totalValue, "\n") !== false) {
                //Если было объединение нескольких значений, то накладывать css class на всю ячейку TD не стоит
                //В этом случае каждое значение должно быть подсвечено отдельно
                $tdCssClass = '';
            } else {
                $tdCssClass = $field->getViewCssClass($totalValue, $dataTotal, 'total');
            }
            if ($field->tableAlign) {
                $tdCssClass .= ' ta' . $field->tableAlign[0]; //получается 'tac' (если center) или 'tar' (если right)
            }

            $tdCssClass = $tdCssClass ? ' class="' . $tdCssClass . '"' : '';

            // Формируем html представление только для тех ячеек, у которых реально определено значнеие в массиве $totalRowData
            // иначе можем получить total строку из большого кол-ва значений по умолчанию (нули, No, и т.п)
            if (array_key_exists($fieldKey, $dataTotal)) {
                $totalValue = $dataTotal[$fieldKey];
                if ($field->getType() === F::TYPE_MONEY_WITH_CURRENCY && strpos($totalValue, "\n") !== false) {
                    // Текущая колонка имеет тип TYPE_MONEY_WITH_CURRENCY, а total значение содержит перевод строки
                    // значит в таблице были разные валюты и мы в итоге получаем разбивку по валютам
                    // нужно применить groupconcat подход
                    $fieldWithGroupconcat = $field->getClone($field->key, ['groupconcat' => true]);
                    $tdHtml = $fieldWithGroupconcat->getHtml($totalValue, $dataTotal, 'total');
                } else {
                    $tdHtml = $field->getHtml($totalValue, $dataTotal, 'total');
                }
            } else {
                $tdHtml = '';
            }

            $outHtml .= "<td{$tdCssClass}>{$tdHtml}</td>";

        }

        $outHtml .= '</tr>';

        return $outHtml;
    }

    private $findId = null;

    protected function isFindId(array $rowDataCalculated): bool
    {
        // Инициализируем кэш, если он ещё не был инициализирован
        if ($this->findId === null) {

            $this->findId = [];

            //Получим значение из GET параметра
            $findIdFromRequest = $this->getTableFindIdFromRequest();
            if ($findIdFromRequest) {
                $findIdFields = explode('+', $findIdFromRequest);
                foreach ($findIdFields as $part) {

                    if (strpos($part, '|') !== false) {
                        [$key, $val] = explode('|', $part);
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
            if (!isset($rowDataCalculated[$key]) || $rowDataCalculated[$key] != $val) {
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
     * @param FieldAbstract $field
     * @param array $dataCalculated
     * @return float|string $total
     */
    public function tableCalcFieldTotal(FieldAbstract $field, array $dataCalculated)
    {
        $amounts = array_column($dataCalculated, $field->key);
        $isMoneyWithCurrency = $field->getType() == F::TYPE_MONEY_WITH_CURRENCY;
        if ($isMoneyWithCurrency) {
            $groupconcat = $field->groupconcat;
            $amountPerCurrency = [];
            foreach ($amounts as $cellText) {
                if ($groupconcat) {
                    $linesText = explode("\n", $cellText);
                } else {
                    $linesText = [$cellText];
                }
                foreach ($linesText as $lineText) {
                    [$currencyIso, $amount] = FieldMoneyWithCurrency::getAsTouple($lineText);
                    if ($amount && $currencyIso) {
                        if (!isset($amountPerCurrency[$currencyIso])) {
                            $amountPerCurrency[$currencyIso] = 0;
                        }
                        $amountPerCurrency[$currencyIso] += round($amount, 2);
                    }
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

        $precision = 2;
        if ($field instanceof FieldFloat) {
            $precision = $field->getPrecision();
        }

        $totalAmount = 0;
        foreach ($amounts as $amount) {
            $totalAmount += round($amount, $precision);
        }

        return $totalAmount;
    }

    protected function getTableTotalRecordsFound()
    {
        return $this->getTableDataCount();
    }

    public function getTableData()
    {
        return $this->tableData;
    }

    public function getTableDataCalculated()
    {
        $dataCalculated = [];
        $fieldsetShowed = $this->getFieldsetShowed();
        $tableData = $this->getTableData();
        foreach ($tableData as $rowData) {
            $dataCalculated[] = $fieldsetShowed->calcData($rowData, true);
        }

        return $dataCalculated;
    }

    public function getTableDataCount()
    {
        return count($this->tableData);
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

    public function jsonSerialize()
    {
        return $this->tableData;
    }

    protected function getTableAjaxChangeFieldUrl(): string
    {
        if (!$this->tableLinkModule) {
            return '';
        }
        $linkModule = Misc::getModule($this->tableLinkModule);
        return "?a=$linkModule&func=ChangeField&id=";
    }
}
