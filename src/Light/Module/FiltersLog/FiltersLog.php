<?php

namespace Light\Module\FiltersLog;

use Light\Db;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\Js;
use Light\Request;
use Light\Response;
use Light\Table;
use Light\Traits\TableExcelExportable;
use Light\Traits\TableFilterable;
use Light\Traits\TablePaginatable;

class FiltersLog extends Table
{
    use TableFilterable, TableExcelExportable;

    const STATE_LIST = 'list';
    const STATE_EXCEL = 'excel';

    protected $_state;

    public function __construct()
    {
        parent::__construct('FiltersLog');

        Js::addLight('FiltersLog.js');

        $this->addFields(

            F::createSelect('TableID', [
                'name' => 'Table',
                'tableWidthExcel' => 15,
                'options' => "SELECT ID, TableName FROM dblog_table",
            ]),

            F::createSelect('FieldID', [
                'name' => 'Field',
                'tableWidthExcel' => 30,
                'options' => "SELECT ID, IF(FieldDescription = '', FieldKey, FieldDescription) FROM dblog_field",
            ]),

            F::createString('Value', ['groupconcat' => true])

        );

        $this->getFieldset()->addFieldsCreatedBy();

        $this->tableFieldsShow = [
            'CreatedDateTime',
            'CreatedBy',
            'TableID',
            'FieldID',
            'Value',
        ];
    }

    protected function initFilters()
    {
        $this->addFiltersDatesFromUntil('CreatedDateTime', ['name' => 'Дата создания']);

        $this->addFilters(

            CommonFields::filterCreatedBy(),

            CommonFields::filterTableId(),

            CommonFields::filterFieldId(),

        );

        $this->filtersLayout = [
            ['CreatedBy', 'TableID', 'FieldID'],
            ['CreatedDateTime'],
        ];

    }

    protected function isListExcelAction()
    {
        return $this->_state == self::STATE_EXCEL;
    }

    protected function tableLoadData()
    {
        //Сделаем некую оптимизацию: сначала вынем все ID записей, которые должны в итоге попасть в таблицу
        //После этого достанем все поля, указав в условии WHERE ID IN (...)
        //Это значительно ускорит запрос, т.к. сортировка сначала LIMIT будут производиться на лёгком наборе ID
        //При выборке остальных колонок объём данных будет уже меньше за счёт ограниченного набора WHERE ID IN (...)
        $queryIds = "SELECT ID FROM log_filter";

        $sqlWhere = $this->getFiltersSqlWhere();
        if ($sqlWhere) {
            $queryIds .= " WHERE $sqlWhere";
        }

        $queryIds .= " ORDER BY ID DESC";

        $limit = $this->isListExcelAction() ? $this->getTableExcelMaxRows() : 500;
        $queryIds .= " LIMIT $limit";

        //Получим все ID из базы
        $ids = Db::getCol($queryIds);

        if ($ids) {

            $idsString = implode(',', $ids);

            //Теперь сделаем второй запрос, в котором WHERE ID IN (...) - результат запишем в таблицу $this->tableData
            $query = "SELECT "
                . $this->getSqlSelect()
                . " FROM log_filter
                    WHERE ID IN ($idsString)
                    ORDER BY ID DESC";

            $this->tableData = Db::getAll($query);

        }
    }

    public function __invoke()
    {
        if (isset($_GET['xls'])) {
            $this->_state = self::STATE_EXCEL;
        } else {
            $this->_state = self::STATE_LIST;
        }

        $this->proceedFilters();
        $this->tableLoadData();

        if (Request::isXls()) {

            $this->sendExcel();

        } elseif (Request::isAjax()) {

            print $this->getHtmlTableForAjax();

        } else {

            Response::setPageHeadHtml('Filters Log');
            Response::setPageTitle('Filters Log');
            print $this->getFiltersHtml();
            print $this->getHtmlTable();
        }
    }

    protected function getExcelFilename()
    {
        return 'Filters Log';
    }

    protected function getExcelWorksheetName()
    {
        return 'Filters Log';
    }
}
