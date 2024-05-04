<?php
namespace Forward\Module\VInvoice\Subtables;

use Forward\Application;
use Light\Db;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;
use Light\User;

class Lines extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->allowTableLayouts = false;

        $this->textTableEmpty = 'No lines info';

        $this->tableLinkModule = 'Shipment';
        $this->tableLinkFieldKeys = ['FileNumber', 'MAWBNumber'];
        $this->tableLinkIdFieldKey = 'ShipmentID';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->folders = true;
        $this->folderModule = 'Shipment';
        $this->folderIdFieldKey = 'FileNumber';

        $this->allowTablePrintTotalRecordsFound = true;
        $this->tableDataTotal = ['Name' => 'Total'];

        $this->_cacheFieldsKeys = ['MAWBNumber', 'FileNumber'];
        $this->_cacheOnlyUniqueValues = true;
    }

    #[\Override]
    protected function getExcelFilename()
    {
        return 'Vendor Invoice ' . $this->_masterObj['Name'] . ' details';
    }

    #[\Override]
    protected function getExcelWorksheetName()
    {
        return 'Vendor Invoice Details';
    }

    #[\Override]
    protected function getDataQuery()
    {
        $query = "SELECT vil.*
                      , s.ID AS ShipmentID
                  FROM vinvoiceline AS vil
                      LEFT JOIN shipment s ON s.Name = vil.FileNumber
                  WHERE vil.VInvoiceID = " . $this->_masterObj->getId() . " AND vil.VInvoiceID != 0
                  ORDER BY vil.ID";

        return $query;
    }

    #[\Override]
    public function getHtmlEdit()
    {
        //Добавим строки в подтаблицу, если в GET запросе есть ключевой параметр fromCostSheet - значит попали сюда
        //из Cost-sheet'а нажатием кнопки Register Vendor Invoice
        if (isset($_GET['fromCostSheet'])) {
            $shipmentId = (int)$_GET['fromCostSheet'];
            $csLineIds = explode(',', (string) $_GET['csLineIds']);
            $this->loadFromCostSheet($csLineIds, $shipmentId);
        }

        return parent::getHtmlEdit();
    }

    private function loadFromCostSheet(array $csLineIds, $shipmentId): void
    {
        $linesData = [];
        if ($csLineIds) {
            //Если массив с ID линий был не пуст, попробуем загрузить их из базы
            $query = "SELECT s.MAWBNumber
                        , s.Name AS FileNumber
                        , cs.Name
                        , cs.Price
                        , cs.Quantity
                        , cs.Amount
                        , cs.Amount AS FinalAmount
                        , cs.CurrencyISO
                      FROM csexpenses cs, shipment s
                      WHERE s.ID = cs.ShipmentID
                          AND cs.ID IN (@a)
                      ORDER BY cs.ID";
            $linesData = Db::getAll($query, $csLineIds);
        }

        if (!$linesData) {
            //Если всё-таки не удалось загрузить информацию по линиям (возможно, исходный массив $lineIds был пуст),
            //то добавим одну пустую строку с контейнером и номером файла
            $linesData[] = Db::getRow(
                "SELECT MAWBNumber, Name AS FileNumber, 1 AS Quantity
                 FROM shipment
                 WHERE ID = @i",
                $shipmentId
            );
        }

        $this->setSubtableDataAdditional($linesData);
    }
}
