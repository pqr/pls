<?php

namespace Light\ModuleBase;

use Light\Config;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\ModuleRegister;
use Light\Request;
use Light\Table;
use Light\Traits\TableExcelExportable;
use Light\Traits\TableFilterable;

class ModuleAbstractLog extends Table
{
    use TableExcelExportable, TableFilterable;

    protected $_moduleId;
    protected $_masterId;

    public $tableFieldsShow = [
        'CreatedDateTime',
        'CreatedBy',
        'TableID',
        'RecordID',
        'FieldID',
        'OldValueFormated',
        'NewValueFormated',
        'Text',
        'ObjectDescription',
    ];

    public function __construct($masterId)
    {
        parent::__construct(Request::getModule() . 'Log');

        $this->tableReloadUrl = Link::create(Request::getModule())->log($masterId)->getHref();
        $this->allowFiltersLayoutChange = false;

        $this->_moduleId = (int)Db::getOne(
            "SELECT ID FROM dblog_module WHERE ModuleName = " . Db::escString(Request::getModule())
        );
        $this->_masterId = (int)$masterId;

        $formatExcel = fn($value) => html_entity_decode(strip_tags((string) $value));

        $this->addFields(

            CF::id(),
            F::createSelect('TableID', [
                'name' => 'Table',
                'tableWidthExcel' => 25,
                'options' => "SELECT ID, TableName FROM dblog_table",
            ]),
            F::createInt('RecordID', [
                'name' => 'Record ID',
            ]),
            F::createSelect('FieldID', [
                'name' => 'Field',
                'tableWidthExcel' => 30,
                'options' => "SELECT ID, FieldDescription FROM dblog_field",
            ]),
            F::createHtml('OldValueFormated', [
                'name' => 'Old Value',
                F::PARAM_TEXTTRUNCATE => F::TEXTTRUNCATE_PART,
                'getExcelFunction' => $formatExcel,
            ]),
            F::createHtml('NewValueFormated', [
                'name' => 'New Value',
                F::PARAM_TEXTTRUNCATE => F::TEXTTRUNCATE_PART,
                'getExcelFunction' => $formatExcel,
            ]),
            F::createHtml('Text', [
                'name' => 'Message',
                'tableWidthExcel' => 80,
                'getExcelFunction' => $formatExcel,
            ]),
            F::createHtml('ObjectDescription', [
                'name' => 'Object Description',
                'tableWidthExcel' => 80,
                'getExcelFunction' => $formatExcel,
            ])

        );

        $this->getFieldset()->addFieldsCreatedBy();

        $this->proceedFilters();
        $this->tableLoadData();
    }

    protected function initFilters()
    {
        $tableAndField = Db::getAll(
            "SELECT DISTINCT dblog.FieldID, dblog.TableID
             FROM dblog
             WHERE dblog.ModuleID=" . $this->_moduleId . "
                 AND dblog.MasterID = " . $this->_masterId
        );

        $fieldIds = array_column($tableAndField, 'FieldID');
        $tableIds = array_column($tableAndField, 'TableID');

        $this->addFilters(

            CommonFields::filterCreatedBy()
                ->setOptions("SELECT DISTINCT(dblog.CreatedBy) AS ID, user.Name
                              FROM dblog
                                  LEFT JOIN user ON dblog.CreatedBy = user.ID
                              WHERE dblog.ModuleID=" . $this->_moduleId . "
                                  AND dblog.MasterID = " . $this->_masterId . "
                              ORDER BY user.Name"),

            CommonFields::filterTableId()
                ->setOptions("SELECT t.ID, t.TableName
                              FROM dblog_table t
                              WHERE t.ID IN (" . (!$tableIds ? '0' : implode(',', $tableIds)) . ")
                              ORDER BY t.TableName"),

            CommonFields::filterFieldId()
                ->setOptions("SELECT f.ID AS value, f.FieldDescription AS text, t.TableName AS optgroup
                              FROM dblog_field f, dblog_table t
                              WHERE f.TableID = t.ID
                                  AND f.ID IN (" . (!$fieldIds ? '0' : implode(',', $fieldIds)) . ")
                              ORDER BY t.TableName, f.FieldDescription"),

            F::createString('FilterName', [
                'name' => Config::$lang === 'ru' ? 'Текст' : 'Text',
                'editWidth' => 'default',
                'sqlWhere' => "ID in (SELECT dblogID FROM dblog_text WHERE Text LIKE '%{value}%'
                                               OR OldValueFormated LIKE '%{value}%'
                                               OR NewValueFormated LIKE '%{value}%')",
            ]),

            CommonFields::filterRecordId()

        );

        $this->addFiltersDatesFromUntil('CreatedDateTime', ['name' => Config::$lang === 'ru' ? 'Дата' : 'Date']);

        $this->filtersLayout = [
            [
                'CreatedBy',
                'TableID',
                'FieldID',
                'FilterName',
            ],
            [
                'CreatedDateTime',
                'RecordID',
            ],
        ];
    }

    protected function getExcelFilename()
    {
        return ModuleRegister::getModuleName(Request::getModule()) . ' ' . $this->_masterId . ' Log';
    }

    protected function getExcelWorksheetName()
    {
        return 'Log';
    }

    protected function tableLoadData()
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
                 WHERE ModuleID={$this->_moduleId}
                     AND MasterID={$this->_masterId}
                     AND " . $this->getFiltersSqlWhere()
            . " ORDER BY ID DESC";

        $tableData = Db::getAll($query);
        if ($tableData) {
            // получение данных из dblog_text
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

    public function getHtmlTableForAjax()
    {
        $outHtml = parent::getHtmlTableForAjax();
        $outHtml .= $this->getFiltersHiddenInput();
        return $outHtml;
    }
}
