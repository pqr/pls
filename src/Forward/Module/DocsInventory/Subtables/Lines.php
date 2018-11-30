<?php


namespace Forward\Module\DocsInventory\Subtables;


use Forward\Field\GeneralFields;
use Forward\Module\CustomerAccount\CustomerAccount;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

class Lines extends Subtable
{
    protected $tableLinkModule = 'Shipment';
    protected $tableLinkFieldKeys = ['FileNumber', 'MAWBNumber'];
    protected $tableLinkIdFieldKey = 'ShipmentID';
    protected $_title = 'Shipments';

    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('docsinventoryline');
        $this->setNameFieldKey('FileNumber');

        $this->addFields(

            GeneralFields::mawbNumber(),

            GeneralFields::fileNumber(true),

            GeneralFields::customerAccountId()
                ->setModes(F::MODE_READONLY)
                ->setSqlSelect("")
                ->setLinkModule(CustomerAccount::class)
                ->setLinkModuleDialog(true)
                ->setLinkModuleForTable(true),

            GeneralFields::docsAddress()
                ->setModes(F::MODE_READONLY)
                ->setSqlSelect(""),

            GeneralFields::deliveryMethod()
                ->setModes(F::MODE_READONLY)
                ->setSqlSelect(""),

            CommonFields::comments()
            ->setRows(2)

        );
    }

    protected function getDataQuery()
    {
        return "SELECT dl.*
                      , s.ID AS ShipmentID
                      , s.CustomerAccountID
                      , ca.DocsAddress
                      , ca.DeliveryMethod
                  FROM docsinventoryline AS dl
                      LEFT JOIN shipment s ON s.Name = dl.FileNumber
                      LEFT JOIN customeraccount ca ON ca.ID = s.CustomerAccountID
                  WHERE dl.DocsInventoryID = " . $this->_masterObj->getId() . " AND dl.DocsInventoryID != 0
                  ORDER BY dl.ID";
    }

}