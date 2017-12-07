<?php
namespace Light\ModuleBase;

use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleRegister;
use Light\Request;
use Light\Table;
use Light\Traits\TableExcelExportable;
use Light\Traits\TableFilterable;

class ModuleAbstractLog extends Table
{
    use TableExcelExportable, TableFilterable;

    private $_moduleId;
    private $_masterId;

    public function __construct($masterId)
    {
        parent::__construct(Request::getModule() . 'Log');

        $this->filtersNoSettings = true;

        $this->_moduleId = (int)Db::getOne(
            "SELECT ID FROM dblog_module WHERE ModuleName = '" . Db::esc(Request::getModule()) . "'"
        );
        $this->_masterId = (int)$masterId;

        $formatExcel = function ($value) {
            return html_entity_decode(strip_tags($value));
        };

        $this->addFields(

            CF::id(),
            F::createSelect(
                'TableID',
                [
                    'name' => 'Table',
                    'tableWidthExcel' => 25,
                    'options' => "SELECT ID, TableName FROM dblog_table"
                ]
            ),
            F::createInt(
                'RecordID',
                [
                    'name' => 'Record ID'
                ]
            ),
            F::createSelect(
                'FieldID',
                [
                    'name' => 'Field',
                    'tableWidthExcel' => 30,
                    'options' => "SELECT ID, FieldDescription FROM dblog_field"
                ]
            ),
            F::createHtml(
                'OldValueFormated',
                [
                    'name' => 'Old Value',
                    F::PARAM_TEXTTRUNCATE => F::TEXTTRUNCATE_PART,
                    'getExcelFunction' => $formatExcel
                ]
            ),
            F::createHtml(
                'NewValueFormated',
                [
                    'name' => 'New Value',
                    F::PARAM_TEXTTRUNCATE => F::TEXTTRUNCATE_PART,
                    'getExcelFunction' => $formatExcel
                ]
            ),
            F::createHtml(
                'Text',
                [
                    'name' => 'Message',
                    'tableWidthExcel' => 80,
                    'getExcelFunction' => $formatExcel
                ]
            ),
            F::createHtml(
                'ObjectDescription',
                [
                    'name' => 'Object Description',
                    'tableWidthExcel' => 80,
                    'getExcelFunction' => $formatExcel
                ]
            )

        );

        $this->getFieldset()->addFieldsCreatedBy();

        $this->_tableFieldsShow = [
            'CreatedDateTime',
            'CreatedBy',
            'TableID',
            'RecordID',
            'FieldID',
            'OldValueFormated',
            'NewValueFormated',
            'Text',
            'ObjectDescription'
        ];

        $this->proceedFilters();
        $this->tableLoadData();
    }

    protected function initFilters()
    {
        $fielIds = Db::getCol(
            "SELECT DISTINCT(dblog.FieldID)
             FROM dblog
             WHERE dblog.ModuleID=" . $this->_moduleId . "
                 AND dblog.MasterID = " . $this->_masterId
        );

        $this->addFilters(

            F::createSelect(
                'FieldID',
                [
                    'name' => 'Field',
                    'options' => "SELECT f.ID AS value, f.FieldDescription AS text, t.TableName as optgroup
                                      FROM dblog_field f, dblog_table t
                                      WHERE f.TableID = t.ID
                                          AND f.ID IN (" . (!$fielIds ? '0' : implode(',', $fielIds)) . ")
                                      ORDER BY t.TableName, f.FieldDescription"
                ]
            ),
            F::createString(
                'LogSearchText',
                [
                    'name' => 'Text',
                    'sqlWhere' => "ID in (SELECT dblogID FROM dblog_text WHERE Text LIKE '%{value}%'
                                               OR OldValueFormated LIKE '%{value}%'
                                               OR NewValueFormated LIKE '%{value}%')"
                ]
            ),
            F::createSelect(
                'CreatedBy',
                [
                    'name' => 'User',
                    'options' => "SELECT DISTINCT(dblog.CreatedBy) AS ID, user.Name FROM dblog
                                      LEFT JOIN user ON dblog.CreatedBy = user.ID
                                      WHERE dblog.ModuleID=" . $this->_moduleId . "
                                          AND dblog.MasterID = " . $this->_masterId . "
                                      ORDER BY user.Name",
                    'empty' => false
                ]
            )

        );
    }

    protected function getExcelFilename()
    {
        return ModuleRegister::getModuleName(Request::getModule()) . ' ' . $this->_masterId . ' Log';
    }

    protected function getExcelWorksheetName()
    {
        return 'Log';
    }

    private function tableLoadData()
    {
        //формируем query
        $query = "SELECT
                    ID,
                    CreatedDateTime,
                    CreatedBy,
                    TableID,
                    RecordID,
                    FieldID
                    FROM dblog
                 WHERE ModuleID= " . $this->_moduleId . "
                     AND MasterID=" . $this->_masterId;

        $filtersWhere = $this->getFiltersSqlWhere();
        if ($filtersWhere) {
            $query .= " AND " . $filtersWhere;
        }
        $query .= " ORDER BY ID DESC";

        $tableData = Db::getAll($query);
        if ($tableData) {
            //получение данных из dblog_text
            $ids = [];
            foreach ($tableData as $data) {
                $ids[] = $data['ID'];
            }
            $query = "SELECT dblogID, NewValueFormated, OldValueFormated, Text, ObjectDescription
                          FROM dblog_text
                          WHERE dblogID IN (" . implode(',', $ids) . ")
                          ORDER BY dblogID DESC";
            $txtData = Db::getAssoc($query);
            foreach ($tableData as &$rowData) {
                if (isset($txtData[$rowData['ID']])) {
                    $rowData += $txtData[$rowData['ID']];
                }
            }
            unset($rowData);
        }

        $this->setTableData($tableData);
    }

    protected function getTableReloadUrl()
    {
        return '?a=' . Request::getModule() . '&logid=' . $this->_masterId;
    }

    public function getTableExcelUrl()
    {
        return '?a=' . Request::getModule() . '&logid=' . $this->_masterId . '&xls=1';
    }

    public function getHtmlTableForAjax($printHeadBlock = true, $printIfEmpty = false)
    {
        $outHtml = parent::getHtmlTableForAjax($printHeadBlock, $printIfEmpty);
        $outHtml .= $this->getFiltersHiddenInput();
        return $outHtml;
    }
}
