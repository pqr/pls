<?php

namespace Light\Module\Logviewer;

use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Request;
use Light\Response;
use Light\Table;
use Light\Timer;
use Light\Traits\TableExcelExportable;
use Light\Traits\TableFilterable;
use Light\Traits\TablePaginatable;

class Logviewer extends Table
{
    use TablePaginatable, TableFilterable, TableExcelExportable;

    const STATE_LIST = 'list';
    const STATE_EXCEL = 'excel';

    protected $_state;

    protected $_queryTime = 0;

    public $tableReloadUrl = "?a=Logviewer";
    public $tableFieldsShow = [
        'CreatedDateTime',
        'CreatedBy',
        'ModuleID',
        'MasterID',
        'TableID',
        'RecordID',
        'FieldID',
        'OldValueFormated',
        'NewValueFormated',
        'Text',
        'ObjectDescription',
    ];

    public function __construct()
    {
        parent::__construct('Logviewer');

        $formatExcel = function ($value) {
            return html_entity_decode(strip_tags($value));
        };

        $this->addFields(

            CF::id(),

            F::createSelect('ModuleID', [
                    'name' => 'Module',
                    'tableWidthExcel' => 15,
                    'options' => "SELECT ID, ModuleName FROM dblog_module"
                ]
            ),

            F::createInt('MasterID', [
                'name' => 'Master ID',
                'tableAlign' => 'left'
            ]),

            F::createSelect('TableID', [
                'name' => 'Table',
                'tableWidthExcel' => 15,
                'options' => "SELECT ID, TableName FROM dblog_table"
            ]),

            F::createInt('RecordID', [
                'name' => 'Record ID',
                'tableAlign' => 'left'
            ]),

            F::createSelect('FieldID', [
                'name' => 'Field',
                'tableWidthExcel' => 30,
                'options' => "SELECT ID, FieldDescription FROM dblog_field"
            ]),

            F::createHtml('OldValueFormated', [
                'name' => 'Old Value',
                F::PARAM_TEXTTRUNCATE => F::TEXTTRUNCATE_PART,
                'getExcelFunction' => $formatExcel,
                'sqlSelect' => ''
            ]),

            F::createHtml('NewValueFormated', [
                'name' => 'New Value',
                F::PARAM_TEXTTRUNCATE => F::TEXTTRUNCATE_PART,
                'getExcelFunction' => $formatExcel,
                'sqlSelect' => ''
            ]),

            F::createHtml('Text', [
                'name' => 'Message',
                'tableWidthExcel' => 80,
                'getExcelFunction' => $formatExcel,
                'sqlSelect' => ''
            ]),

            F::createHtml('ObjectDescription', [
                'name' => 'Object Description',
                'getExcelFunction' => $formatExcel,
                'sqlSelect' => ''
            ])

        );

        $this->getFieldset()->addFieldsCreatedBy();
    }

    protected function initFilters()
    {
        $this->addFilters(

            CommonFields::filterCreatedBy(),

            CommonFields::filterModuleId(),

            CommonFields::filterTableId(),

            CommonFields::filterFieldId(),

            F::createString('FilterName', [
                'name' => Config::$lang === 'ru' ? 'Текст' : 'Text',
                'sqlWhere' => "ID in (SELECT dblogID FROM dblog_text WHERE (Text LIKE '%{value}%'
                                            OR OldValueFormated LIKE '%{value}%'
                                            OR NewValueFormated LIKE '%{value}%'))"
            ]),

            CommonFields::filterMasterId(),

            CommonFields::filterRecordId()

        );

        $this->addFiltersDatesFromUntil('CreatedDateTime', ['name' => Config::$lang === 'ru' ? 'Дата' : 'Date']);

        $this->filtersLayout = [
            [
                'CreatedBy',
                'ModuleID',
                'TableID',
                'FieldID',
                'FilterName'
            ],
            [
                'CreatedDateTime',
                'MasterID',
                'RecordID'
            ]
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

        $queryIds = "SELECT ID";

        $dblogTable = 'dblog';
        $database = $this->getFilterValue('Database');
        if ($database == 'archive') {
            $archiveDb = ApplicationAbstract::getParam('ArchiveDatabase');
            if ($archiveDb) {
                $dblogTable = $archiveDb . '.dblog';
            }
        }
        $queryIds .= " FROM " . $dblogTable;

        $sqlWhere = $this->getFiltersSqlWhere();
        if ($sqlWhere) {
            $queryIds .= " WHERE $sqlWhere";
        }

        $queryIds .= " ORDER BY ID DESC";

        if ($this->isListExcelAction()) {
            $queryIds .= ' LIMIT ' . $this->getTableExcelMaxRows();
        } else {
            $page = $this->getTablePage();
            $rowsPerPage = $this->getTableRowsPerPage();
            $queryIds .= ' LIMIT ' . ($page - 1) * $rowsPerPage . ', ' . $rowsPerPage;
        }

        //Получим все ID из базы
        $timer = new Timer();
        $ids = Db::getCol($queryIds);
        $this->_queryTime += $timer->get();

        if ($ids) {

            //Сразу посчитаем общее количество строк, если это не режим экспорта в Excel
            if (!$this->isListExcelAction()) {
                $foundRows = 9999;
                $this->setTableTotalRecordsFound($foundRows);
            }

            $idsString = implode(',', $ids);

            //Теперь сделаем второй запрос, в котором WHERE ID IN (...) - результат запишем в таблицу $this->tableData
            $query = "SELECT ";

            $selectFieldKeys = $this->getFieldsetShowed()->getKeys();

            $query .= $this->getSqlSelect($selectFieldKeys);

            $query .= " FROM " . $dblogTable;
            $query .= " WHERE ID IN (" . $idsString . ")";
            $query .= " ORDER BY ID DESC";

            $timer = new Timer();
            $this->tableData = Db::getAll($query);
            $this->_queryTime += $timer->get();

            //Теперь сделаем третий запрос, который выбирает данные из dblog_text
            $query = "SELECT dblogID, NewValueFormated, OldValueFormated, Text, ObjectDescription
                      FROM dblog_text
                      WHERE dblogID IN (" . $idsString . ")
                      ORDER BY dblogID DESC";
            $txtData = Db::getAssoc($query);
            foreach ($this->tableData as &$rowData) {
                if (isset($txtData[$rowData['ID']])) {
                    $rowData += $txtData[$rowData['ID']];
                }
            }
            unset($rowData);
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

        if ($this->_state != self::STATE_EXCEL) {
            //Инициализируем пажинацию, только если это не экспорт в Excel
            $this->initPaginatable();
        }
        $this->initPaginatable();
        $this->tableLoadData();

        if (Request::isXls()) {

            $this->sendExcel();

        } elseif (Request::isAjax()) {

            print $this->getHtmlTableForAjax();

        } else {

            Response::setPageHeadHtml('Logs');
            Response::setPageTitle('Logs');
            print $this->getFiltersHtml();
            print $this->getHtmlTable();
        }
    }

    protected function getExcelFilename()
    {
        return 'Logs';
    }

    protected function getExcelWorksheetName()
    {
        return 'Logs';
    }
}
