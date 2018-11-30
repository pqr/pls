<?php


namespace Forward\Module\DocsInventory\Subtables;


use Forward\Field\GeneralFields;
use Forward\Module\CInvoice\CInvoice;
use Forward\Module\CustomerAccount\CustomerAccount;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\InvStatus;
use Light\Link;
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

            F::createString('CInvoices', [
                'name' => 'Customer Invoice',
                'groupconcat' => true,
                'sqlSelect' => '',
                'tableAlign' => 'center',
                'tableWidthHtml' => 100,
                'tableWidthExcel' => 15,
                'modes' => F::MODE_READONLY,
                'getHtmlFunction' => function ($value, array $data, $outHtml) {
                    if (strpos($value, '|') === false) {
                        return $outHtml;
                    }

                    [$id, $name] = explode('|', $value);
                    return Link::create(CInvoice::class, htmlspecialchars($name))
                        ->view($id)
                        ->funcDialog()
                        ->getHtml();
                },
                'getExcelFunction' => function ($value, array $data, $out) {
                    if (strpos($value, '|') === false) {
                        return $out;
                    }

                    return explode('|', $value)[1];
                }

            ]),

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
                      , (SELECT GROUP_CONCAT(CONCAT(ci.ID,'|', ci.Name) ORDER BY ci.Name SEPARATOR '\\n')
                         FROM cinvoice ci
                         WHERE ci.Name != '' 
                            AND ci.Status NOT IN (" . InvStatus::getDeletedStatusesSql() . ")
                            AND EXISTS(SELECT 1 FROM cinvoice_file_amount cfa WHERE cfa.CInvoiceID = ci.ID AND cfa.FileNumber = s.Name)
                         ) AS CInvoices
                  FROM docsinventoryline AS dl
                      LEFT JOIN shipment s ON s.Name = dl.FileNumber
                      LEFT JOIN customeraccount ca ON ca.ID = s.CustomerAccountID
                  WHERE dl.DocsInventoryID = " . $this->_masterObj->getId() . " AND dl.DocsInventoryID != 0
                  ORDER BY dl.ID";
    }

}