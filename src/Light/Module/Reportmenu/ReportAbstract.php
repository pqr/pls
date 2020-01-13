<?php

namespace Light\Module\Reportmenu;

use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Link;
use Light\Metric;
use Light\Misc;
use Light\ModuleBase\MassactionsTrait;
use Light\ModuleBase\MassactionUtils;
use Light\ModuleRegister;
use Light\Request;
use Light\Response;
use Light\Table;
use Light\Template;
use Light\Timer;
use Light\Traits\SubmoduleTrait;
use Light\Traits\TableExcelExportable;
use Light\Traits\TableFilterable;
use Light\Traits\TableSortable;
use Light\Translate;

abstract class ReportAbstract extends Table
{
    use SubmoduleTrait, TableExcelExportable, TableFilterable, TableSortable, MassactionsTrait;

    /** @var \Light\Fieldset */
    protected $_fieldset;

    public $tableLinkBlank = true;
    public $allowTablePrintHeadBlock = true;

    public function __construct()
    {
        parent::__construct();

        $this->tableReloadUrl = '?a=Reportmenu&class=' . Misc::getClass($this);
        $this->allowSort = false;
        $this->allowFiltersLayoutChange = false;
    }

    /**
     * Переопределяем метод класса Table, чтобы придать таблице уникальное имя, которое в том числе будет использоваться
     * в вёрстке и js коде для обновления таблицы через ajax
     * @return string
     */
    final public function getTableName()
    {
        return 'Report' . Misc::getClass($this);
    }

    public static function getIconFile()
    {
        $class = get_called_class();
        $class = substr($class, strrpos($class, '\\') + 1);
        return "Reportmenu/$class.png";
    }

    protected function getDataQuery()
    {
        // Теперь сделаем второй запрос, в котором WHERE ID IN (...) - результат запишем в таблицу $this->tableData
        $query = "SELECT ";
        $query .= $this->getFieldset()->getSqlSelect($this->getFieldset()->getKeys());
        $query .= " FROM " . Db::quote($this->getDbTable());

        $sqlWhere = $this->getFiltersSqlWhere();
        if ($sqlWhere) {
            $query .= " WHERE $sqlWhere";
        }

        $sqlOrderBy = $this->getSqlOrderBy();
        if ($sqlOrderBy) {
            $query .= " ORDER BY $sqlOrderBy";
        }

        return $query;
    }

    protected function tableLoadData()
    {
        $query = $this->getDataQuery();
        if ($query) {

            if ($this->isRenderRowForAjax()) {
                $rowId = $this->getRowForAjaxId();
                if ($rowId) {
                    // Оптимизация на случай загрузки одной строки средствами ajax - используется при ajax изменении полей
                    $query = "SELECT * FROM ($query) as t WHERE ID = $rowId";
                }
            }

            $this->tableData = Db::getAll($query);
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function __invoke()
    {
        if ($this->isRenderRowForAjax()) {
            // Пользователь поменял какое-то поле с помощью changeField в режиме таблицы и теперь нужно перезагрузить всю строку
            // Но, возможно, для этого отчёта запрещено перезагружать отдельные строки (т.к. в отчёте нет соответствия строки модели)
            if (!$this->allowTableReloadRow) {
                // Нет разрешениея перезагружать отдельные строик - вернём 403, тогда на клиент будет прогинорировано
                Response::abort(403);
                return;
            }
            $this->proceedListRenderRowForAjax();
            return;
        }

        $timer = new Timer();

        $this->proceedFilters();
        $this->initSortable();
        $this->initMassactions();
        if ($this->massactions) {
            $this->addCheckboxCol();
        }
        $this->tableLoadData();

        if (Request::isXls()) {

            // Запрос через кнопку Export to Excel
            $this->sendExcel();

        } elseif (Request::isAjax()) {

            //Режим обновления таблицы отчёта через ajax - например, новые фильтры
            print $this->getHtmlTableForAjax();

        } else {

            $this->initTitleHeadText();

            $tdata = [
                'filters' => $this->getFiltersHtml(),
                'table' => $this->getHtmlTable(),
                'description' => static::getDescription(),
            ];

            Template::includeTpl('Submodules/Report.tpl.php', $tdata);
        }

        Metric::record('Reportmenu/' . Misc::getClass($this), $timer->getMs(),
            'Filters: ' . json_encode($this->getFiltersValues()));
    }

    protected function isRenderRowForAjax(): bool
    {
        return isset($_GET['renderRowForAjax'])
            && $_GET['renderRowForAjax'] === $this->getTableName()
            && Request::isAjax();
    }

    protected function getRowForAjaxId(): int
    {
        return (int)($_GET['rowId'] ?? 0);
    }

    protected function proceedListRenderRowForAjax()
    {
        $this->proceedFilters();
        $this->initSortable();
        $this->initMassactions();
        if ($this->massactions) {
            $this->addCheckboxCol();
        }
        $this->tableLoadRowForAjax();

        if (!$this->tableData) {
            Response::abort(404);
            return;
        }

        // Рендерим одну (единственно загруженную) строку
        print $this->getHtmlTableTrForAjax();
    }

    protected function tableLoadRowForAjax()
    {
        $rowId = $this->getRowForAjaxId();
        if (!$rowId) {
            return;
        }

        // Придётся вызвать стандартный метод tableLoadData() на случай если в конкретном отчёте он был проностью
        // написан заново. Однако, если пользователь не переопределял tableLoadData(),
        // то внутри будет некая оптимизация sql запроса
        $this->tableLoadData();

        // Из загруженных данных найдём ту строку, что нам нужна
        $foundRow = null;
        foreach ($this->tableData as $row) {
            if (!empty($row['ID']) && $row['ID'] == $rowId) {
                $foundRow = $row;
                break;
            }
        }

        if (!$foundRow) {
            // Не нашли строку с подходящим id
            return;
        }

        // Меняем массив $this->tableData, чтобы в нём была ровно одна найденная строка
        $this->tableData = [$foundRow];
    }

    public function getHtmlTableForAjax()
    {
        $outHtml = parent::getHtmlTableForAjax();

        if ($this->massactions && $this->getTableDataCount()) {
            $outHtml .= MassactionUtils::getHtmlSelectMassactions($this->massactions);
        }

        return $outHtml;
    }

    protected function initTitleHeadText()
    {
        //Распечатка полноценной страницы с отчётом
        $img = '<img src="i/' . static::getIconFile() . '" width="24">';
        $module = 'Reportmenu';
        $moduleName = ModuleRegister::getModuleName($module);
        $reportName = self::getTitle();
        $link = Link::create($module, $moduleName);
        Response::setPageTitle("$moduleName - $reportName");
        Response::setPageHeadHtml("$link $img <strong>$reportName</strong>");

    }

    protected function getExcelFilename()
    {
        return static::getTitle() . ' (' . date('Y-m-d') . ')';
    }

    protected function getExcelWorksheetName()
    {
        return 'Generated ' . date('Y-m-d');
    }

    final protected function addMonthFields($type, array $params = [])
    {
        //Регистрируем поля по месяцам
        for ($m = 1; $m <= 12; $m++) {
            $fieldKey = "Month$m";
            $name = Config::$lang === 'ru' ? Translate::monthRu($m) : date('M', mktime(0, 0, 0, $m));
            $params['name'] = $name;
            $this->addField(F::create($fieldKey, $type, $params + ['total' => true]));
        }
    }

    protected function addWeekFields($fieldType, $year, array $params = [])
    {
        //Регистрируем поля по неделям
        for ($w = 1; $w <= 52; $w++) {

            $fieldKey = 'Week' . $w;

            $weekStartDate = $this->getWeekStartDate($w, $year);
            $weekEndDate = $this->getWeekEndDate($w, $year);
            $defaultParams = [
                'name' => $w,
                'title' => FieldDate::beautify($weekStartDate) . '-' . FieldDate::beautify($weekEndDate),
                'total' => true,
            ];
            $fieldParams = array_merge($defaultParams, $params);
            $this->addField(F::create($fieldKey, $fieldType, $fieldParams));
        }
    }

    protected function getWeekStartDate($weekNum, $year)
    {
        $wTxt = $weekNum < 10 ? '0' . $weekNum : $weekNum;
        return date('Y-m-d', strtotime($year . 'W' . $wTxt . '1'));
    }

    protected function getWeekEndDate($weekNum, $year)
    {
        $wTxt = $weekNum < 10 ? '0' . $weekNum : $weekNum;
        return date('Y-m-d', strtotime($year . 'W' . $wTxt . '7'));
    }
}
