<?php
namespace Light\Module\Reportmenu;

use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Link;
use Light\Metric;
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

abstract class ReportAbstract extends Table
{
    use SubmoduleTrait, TableExcelExportable, TableFilterable, TableSortable;

    /** @var \Light\Fieldset */
    protected $_fieldset;

    public function __construct($tableName = null)
    {
        parent::__construct($tableName);
        $this->_tableSortingEnabled = false;
        $this->filtersNoSettings = true;
    }

    /**
     * Переопределяем метод класса Table, чтобы придать таблице уникальное имя, которое в том числе будет использоваться
     * в вёрстке и js коде для обновления таблицы через ajax
     * @return string
     */
    final public function getTableName()
    {
        return 'Report' . $this->getSubmoduleClass();
    }

    /**
     * Метод для переопределения в классах потоках. Здесь задаётся Fieldset
     */
    protected function init()
    {
    }

    public static function getIconFile()
    {
        $parts = explode("\\", get_called_class());
        return 'Reportmenu/' . end($parts) . '.png';
    }

    protected function getDataQuery()
    {
        //Теперь сделаем второй запрос, в котором WHERE ID IN (...) - результат запишем в таблицу $this->_tableData
        $query = "SELECT ";
        $query .= $this->getFieldset()->getSqlSelect($this->getFieldset()->getKeys());
        $query .= " FROM " . Db::quote($this->getDbTable());

        $sqlWhere = $this->getFiltersSqlWhere();
        if ($sqlWhere) {
            $query .= " WHERE " . $sqlWhere;
        }

        $sqlOrderBy = $this->getSqlOrderBy();
        if ($sqlOrderBy) {
            $query .= " ORDER BY " . $sqlOrderBy;
        }

        return $query;
    }

    protected function tableLoadData()
    {
        $query = $this->getDataQuery();
        if ($query) {
            $this->_tableData = Db::getAll($query);
        }
    }

    public function __invoke()
    {
        $timer = new Timer();

        $this->proceedFilters();
        $this->init();
        $this->initSortable();
        $this->tableLoadData();

        if (Request::isXls()) {

            //Запрос через кнопку Export to Excel
            $this->sendExcel();

        } elseif (Request::isAjax()) {

            //Режим обновления таблицы отчёта через ajax - например, новые фильтры
            print $this->getHtmlTableForAjax();

        } else {

            $this->initTitleHeadText();

            $tdata = [
                'filters' => $this->getFiltersHtml(),
                'table' => $this->getHtmlTable(),
                'description' => static::getDescription()
            ];

            Template::includeTpl('Submodules/Report.tpl.php', $tdata);
        }

        Metric::record('Reportmenu::' . $this->getSubmoduleClass(), $timer->getMs(), 'Filters: ' . json_encode($this->getFiltersValues()));
    }

    protected function initTitleHeadText()
    {
        //Распечатка полноценной страницы с отчётом
        $img = '<img src="i/' . static::getIconFile() . '" width="24">';
        $moduleName = ModuleRegister::getModuleName('Reportmenu');
        $reportName = self::getTitle();
        Response::setPageTitle("$moduleName - $reportName");
        Response::setPageHeadHtml(
            Link::module($moduleName) . " $img <strong>$reportName</strong>"
        );

    }

    protected function getExcelFilename()
    {
        return static::getTitle() . ' (' . date('Y-m-d') . ')';
    }

    protected function getExcelWorksheetName()
    {
        return 'Generated ' . date('Y-m-d');
    }

    protected function getTableReloadUrl()
    {
        return '?a=Reportmenu&class=' . $this->getSubmoduleClass();
    }

    public function getTableExcelUrl()
    {
        return '?a=Reportmenu&class=' . $this->getSubmoduleClass() . '&xls=1';
    }

    final protected function addMonthFields($type, array $params = [])
    {
        //Регистрируем поля по месяцам
        for ($m = 1; $m <= 12; $m++) {
            $fieldKey = 'Month' . $m;
            $defaultParams = [
                'name' => date('M', mktime(0, 0, 0, $m))
            ];
            $fieldParams = array_merge($defaultParams, $params);
            $this->addField(F::create($fieldKey, $type, $fieldParams));
            $this->_tableTotalRowCalcFieldsKeys[] = $fieldKey;
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
                'title' => FieldDate::beautify($weekStartDate) . '-' . FieldDate::beautify($weekEndDate)
            ];
            $fieldParams = array_merge($defaultParams, $params);
            $this->addField(F::create($fieldKey, $fieldType, $fieldParams));

            $this->_tableTotalRowCalcFieldsKeys[] = $fieldKey;
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
