<?php

namespace Light\Module\Deleted;

use Light\Config;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\Ico;
use Light\Js;
use Light\ModuleBase\ModuleAbstract;
use Light\Response;
use Light\Session;
use Light\Template;

class Deleted extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        Js::addLight('Deleted.js');

        $this->allowAdd = false;
        $this->allowDelete = false;
        $this->allowEdit = false;
        $this->allowSort = false;
        $this->tableDefaultOrderBy = 'ID DESC';

        $this->addMassaction('Restore', 'Restore');
    }

    protected function initFilters()
    {
        $this->addFilters(
            CommonFields::filterCreatedBy(),

            CommonFields::filterModuleId(),

            CommonFields::filterTableId(),

            F::createString('FilterName', [
                'name' => Config::$lang === 'ru' ? 'Текст' : 'Text',
                'sqlWhere' => "ID in (SELECT deletedID FROM deleted_text WHERE JSON LIKE '%{value}%')",
            ]),

            CommonFields::filterMasterId(),

            CommonFields::filterRecordId(),

            F::createInt('TraceID', ['name' => 'Trace ID', 'editAlign' => 'left', 'editWidth' => 'medium'])
        );

        $this->addFiltersDatesFromUntil($this->getField('CreatedDateTime'));

        $this->filtersLayout = [
            [
                'CreatedBy',
                'ModuleID',
                'TableID',
                'FilterName',
            ],
            [
                'CreatedDateTime',
                'MasterID',
                'RecordID',
                'TraceID',
            ],
        ];

    }

    public function initViewSubmenu()
    {
        $textRestore = Config::$lang === 'ru' ? 'Восстановить' : 'Restore';
        $this->addSubmenuLinkViewFunc(Ico::i('fa-undo icon-green', 'fas') . " $textRestore", 'Restore')
            ->post();

        $textFindRelated = Config::$lang === 'ru' ? 'Найти удалённые одновременно' : 'Find deleted the same time';
        $this->addSubmenuLinkViewFunc(Ico::search() . " $textFindRelated", 'FindRelations');
    }

    public function viewGetFindRelations()
    {
        if ($this->_obj['TraceID']) {

            $filterValues = [
                'TraceID' => $this->_obj['TraceID'],
            ];

        } else {

            $from = new \DateTime($this->_obj['CreatedDateTime'] . ' - 5 seconds');
            $until = new \DateTime($this->_obj['CreatedDateTime'] . ' + 5 seconds');

            $filterValues = [
                'CreatedBy' => $this->_obj['CreatedBy'],
                'CreatedDateTimeFrom' => $from->format('Y-m-d H:i:s'),
                'CreatedDateTimeUntil' => $until->format('Y-m-d H:i:s'),
            ];

        }

        Response::redirect('Deleted', ['filterValues' => json_encode($filterValues)]);
    }

    public function viewPostRestore()
    {
        $this->restore($this->_obj->getId());
        Response::redirect('Deleted', ['id' => $this->_obj->getId()]);
    }

    public function massactionRestore(array $ids)
    {
        foreach ($ids as $id) {
            $this->restore($id);
        }
        Response::redirect('Deleted');
    }

    private function restore(int $deleteId)
    {
        $tableId = Db::getOne("SELECT TableID FROM deleted WHERE ID = $deleteId");
        if (!$tableId) {
            $msg = Config::$lang === 'ru' ? "Не удалось найти восстанавливаемую запись ID $deleteId" : "Failed to restore, because deleted record ID = $deleteId not exists any more";
            Session::flashError($msg);
            return 0;
        }
        $tableName = Db::getOne("SELECT TableName FROM dblog_table WHERE ID = $tableId");
        if (!$tableName) {
            $msg = Config::$lang === 'ru' ? "Не удалось найти имя таблицы по TableID $tableId" : "Failed to restore, because unknown Table ID $tableId";
            Session::flashError($msg);
            return 0;
        }
        $json = Db::getOne("SELECT `JSON` FROM deleted_text WHERE deletedID = $deleteId");
        if (!$json) {
            $msg = Config::$lang === 'ru' ? "Не удалось найти восстанавливаемые данные ID $deleteId" : "Failed to restore, unable to fetch data to restore ID $deleteId";
            Session::flashError($msg);
            return 0;
        }

        try {
            $data = json_decode($json, true);
            $insertId = Db::insertRow($data, $tableName);
            if (!$insertId) {
                $msg = Config::$lang === 'ru' ? "Не удалось восстановить ID $deleteId" : "Failed to restore ID $deleteId";
                Session::flashError($msg);
                return 0;
            }
        } catch (\Throwable $e) {
            Session::flashError($e->getMessage());
            return 0;
        }

        $deletedObj = new DeletedObj();
        $deletedObj->setId($deleteId);
        $deletedObj->updateDb(['Restored' => 1]);
        $msg = Config::$lang === 'ru' ? 'Восстановлено' : 'Restored';
        Session::flashInfo("$msg ID $insertId");

        return $insertId;
    }

    protected function getTableRowCssClass(array $rowDataCalculated, int $rowNum): string
    {
        if ($rowDataCalculated['Restored']) {
            return 'completed-tr';
        }
        return '';
    }
}
