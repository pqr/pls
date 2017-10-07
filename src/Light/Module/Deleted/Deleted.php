<?php
namespace Light\Module\Deleted;

use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Img;
use Light\Js;
use Light\ModuleBase\ModuleAbstract;
use Light\Response;
use Light\Session;

class Deleted extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        Js::addLight('Deleted.js');

        $this->_allowAdd = false;
        $this->_allowDelete = false;
        $this->_allowEdit = false;
        $this->_tableSortingEnabled = false;
        $this->_tableDefaultOrderBy = 'ID DESC';

        $this->addMassaction('Restore', 'Restore');
    }

    protected function initFilters()
    {
        $this->addFilters(
            F::createSelect('TableName', [
                'name' => 'Table Name',
                'options' => "SELECT DISTINCT(TableName) FROM deleted ORDER BY TableName"
            ]),
            F::createSelect('CreatedBy', ['name' => 'Deleted By', 'options' => 'user']),
            F::createInt('RecordID', ['name' => 'Record ID', 'editAlign' => 'left']),
            F::createString('Query', ['name' => 'SQL Query']),
            F::createString('JSON', ['name' => 'JSON Data', 'title' => 'Usage: "ID": "12345"'])
        );
        $this->addFiltersDatesFromUntil($this->getField('CreatedDateTime'));
    }

    public function initViewSubmenu()
    {
        $this->addSubmenuItemViewFuncPost('Restore', Img::icon('fa-undo icon-green'), 'Restore');
        $this->addSubmenuItemViewFunc('Find Relations', Img::iconSearch(), 'FindRelations');
    }

    public function viewGetFindRelations()
    {
        $from = new \DateTime($this->_obj['CreatedDateTime'] . ' - 5 seconds');
        $until = new \DateTime($this->_obj['CreatedDateTime'] . ' + 5 seconds');

        $filterValues = [
            'CreatedBy' => $this->_obj['CreatedBy'],
            'CreatedDateTimeFrom' => $from->format('Y-m-d H:i:s'),
            'CreatedDateTimeUntil' => $until->format('Y-m-d H:i:s'),
        ];

        Response::redirect('Deleted', ['filterValues' => json_encode($filterValues)]);
    }

    public function viewPostRestore()
    {
        $this->restore($this->_obj->getData(), $this->_obj->getId());
        Response::redirect('Deleted', ['id' => $this->_obj->getId()]);
    }

    public function massactionRestore(array $ids)
    {
        $data = Db::getAssoc("SELECT ID, JSON, TableName FROM deleted WHERE ID IN (@a)", $ids);

        foreach ($data as $deleteId => $rowData) {
            $this->restore($rowData, $deleteId);
        }
        Response::redirect('Deleted');
    }

    private function restore($data, $deleteId)
    {
        $dataRow = json_decode($data['JSON'], true);
        $insertId = Db::insertRow($dataRow, $data['TableName']);

        if ($insertId) {
            $deletedObj = new DeletedObj();
            $deletedObj->setId($deleteId);
            $deletedObj->updateDb(['Restored' => 1]);
            Session::flashInfo('Restored as ' . $insertId);
        }

        return $insertId;
    }

    protected function getTableRowCssClass(array $rowData, int $rowNum):string
    {
        if ($rowData['Restored']) {
            return 'completed-tr';
        }
        return '';
    }

    /**
     * Переопределяем метод Deleted, чтобы отключить пажинацию и ускорить поиск/загрузку
     */
    protected function tableLoadData()
    {
        if (isset($_GET['excelIds']) && $this->isStateListExcel()) {

            $idsString = implode(',', array_map('intval', explode(',', $_GET['excelIds'])));
            $sqlOrderBy = [];
            $sortField = $this->getSortField();
            if ($sortField) {
                $sqlOrderBy[] = $sortField->tableOrderBy . ' ' . $this->getSortOrder();
            }

            if ($this->_tableDefaultOrderBy) {
                $sqlOrderBy[] = $this->_tableDefaultOrderBy;
            }

            $sqlOrderBy = implode(', ', $sqlOrderBy);

        } else {

            //Сделаем некую оптимизацию: сначала вынем все ID записей, которые должны в итоге попасть в таблицу
            //После этого достанем все поля, указав в условии WHERE ID IN (...)
            //Это значительно ускорит запрос, т.к. сортировка сначала LIMIT будут производиться на лёгком наборе ID
            //При выборке остальных колонок объём данных будет уже меньше за счёт ограниченного набора WHERE ID IN (...)

            $queryIds = "SELECT ID";

            //Проверим поле по которому идёт сортировка. Если оно вычисляемое, его тоже придётся добавить в этот запрос
            //Причём у этого вычисляемого поля могут быть зависимости, поэтому используем $this->getFieldset()->getSqlSelect()
            $sortField = $this->getSortField();
            if ($sortField && $sortField->getSqlSelect()) {
                $queryIds .= ', ' . $this->getFieldset()->getSqlSelect([$sortField->key]);
            }

            $queryIds .= " FROM " . Db::quote($this->getDbTable());

            $sqlWhere = $this->getFiltersSqlWhere();
            if ($sqlWhere) {
                $queryIds .= " WHERE " . $sqlWhere;
            }

            //Cформируем условие сортировки.
            //Оно может состоять из сортировки указанной в trait TableSortable и из _tableDefaultOrderBy
            //Сначала соберём всё в массив, потом из него склеим строку
            $sqlOrderBy = [];
            if ($sortField) {
                $sqlOrderBy[] = $sortField->tableOrderBy . ' ' . $this->getSortOrder();
            }

            if ($this->_tableDefaultOrderBy) {
                $sqlOrderBy[] = $this->_tableDefaultOrderBy;
            }

            $sqlOrderBy = implode(', ', $sqlOrderBy);

            if ($sqlOrderBy) {
                $queryIds .= " ORDER BY " . $sqlOrderBy;
            }

            if ($this->isStateListExcel()) {
                $queryIds .= ' LIMIT ' . $this->getTableExcelMaxRows();
            } elseif ($this->_tablePaginationEnabled) {
                $page = $this->getTablePage();
                $rowsPerPage = $this->getTableRowsPerPage();
                $queryIds .= ' LIMIT ' . ($page - 1) * $rowsPerPage . ', ' . $rowsPerPage;
            }

            //Получим все ID из базы
            $res = Db::query($queryIds);
            if ($res->num_rows) {
                $idsString = '0';
                while ($row = $res->fetch_row()) {
                    $idsString .= ',' . $row[0];
                }
            } else {
                $idsString = false;
            }
            $res->free();
        }

        if ($idsString) {


            //Сразу посчитаем общее количество строк, если это не режим экспорта в Excel
            $this->setTableTotalRecordsFound(1000);

            //Теперь сделаем второй запрос, в котором WHERE ID IN (...) - результат запишем в таблицу $this->_tableData
            $query = "SELECT ";

            $selectFieldKeys = $this->getFieldsetShowed()->getKeys();
            $query .= $this->getFieldset()->getSqlSelect($selectFieldKeys);

            $query .= " FROM " . Db::quote($this->getDbTable());
            $query .= " WHERE ID IN (" . $idsString . ")";

            if ($sqlOrderBy) {
                $query .= " ORDER BY " . $sqlOrderBy;
            }

            $this->_tableQueryRes = Db::query($query);
        }
    }


}