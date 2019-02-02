<?php


namespace Forward\Module\DocsInventory;


use Forward\Module\DocsInventory\Subtables\Lines;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\InvStatus;
use Light\Misc;
use Light\ModuleBase\ModuleAbstract;

class DocsInventory extends ModuleAbstract
{
    protected $textSaveBtn = 'Опись док-ов';

    public function __construct()
    {
        parent::__construct();

        $this->setNameFieldKey('ID');

        $this->addFields(

            CommonFields::id(),
            F::createDate('SentDate', [
                'name' => 'Дата отправки в бухгалтерию',
                'default' => date('Y-m-d'),
            ]),
            CommonFields::comments()->setAjax(false)
        );

    }

    protected function registerSubtables()
    {
        $this->registerSubtable(Lines::class);
    }

    protected function initFilters()
    {
        $this->addFilters(
            F::createText('FileNumbers', [
                'name' => 'File Numbers',
                'sqlWhere' => function ($value) {
                    $query = "EXISTS (SELECT 1
                                  FROM docsinventoryline dil
                                  WHERE dil.DocsInventoryID = docsinventory.ID
                                      AND " . Db::getSqlWhereSeparatedByComa($value, 'dil.FileNumber', -1, false,
                            true) .
                        ")";

                    return $query;
                }
            ]),
            F::createText('MAWBNumbers', [
                'name' => 'MAWB Numbers',
                'sqlWhere' => function ($value) {
                    $value = str_replace([' ', '-'], '', $value);
                    $query = "EXISTS(SELECT 1
                             FROM docsinventoryline dil
                             WHERE dil.DocsInventoryID = docsinventory.ID
                                 AND " . Db::getSqlWhereSeparatedByComa(
                            $value,
                            "REPLACE(REPLACE(dil.MAWBNumber, ' ', ''), '-', '')",
                            11,
                            true
                        ) . ")";

                    return $query;
                }
            ])

        );
    }

    protected function proceedAddNew()
    {
        if (isset($_GET['fromShipmentIds'])) {
            $ids = Misc::idsFromComaSeparatedString($_GET['fromShipmentIds']);
            $this->loadFromShipments($ids);
        }

        parent::proceedAddNew();
    }

    private function loadFromShipments(array $shipmentIds)
    {
        $query = "SELECT s.ID AS ShipmentID
                      , s.Name AS FileNumber
                      , s.MAWBNumber
                      , s.CustomerAccountID
                      , ca.DocsAddress
                      , ca.DeliveryMethod
                      , (SELECT GROUP_CONCAT(CONCAT(ci.ID,'|', ci.Name) ORDER BY ci.Name SEPARATOR '\\n')
                         FROM cinvoice ci
                         WHERE ci.Name != '' 
                            AND ci.Status NOT IN (" . InvStatus::getDeletedStatusesSql() . ")
                            AND EXISTS(SELECT 1 FROM cinvoice_file_amount cfa WHERE cfa.CInvoiceID = ci.ID AND cfa.FileNumber = s.Name)
                         ) AS CInvoices
                  FROM shipment s
                      LEFT JOIN customeraccount ca ON ca.ID = s.CustomerAccountID
                  WHERE s.ID IN (@a)
                  ORDER BY s.ID";
        $linesData = Db::getAll($query, $shipmentIds);
        $lines = $this->getSubtable(Lines::class);
        $lines->setSubtableDataAdditional($linesData);
    }
}
