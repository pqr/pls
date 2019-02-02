<?php
namespace Forward\Module\Shipment\CostSheet\Subtables;

use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Forward\Module\Shipment\CostSheet\CostSheetFields;
use Forward\Module\Shipment\CostSheet\Subtables\Base\InvoicesAbstract;
use Forward\Module\Shipment\ShipmentObj;

class VInvoices extends InvoicesAbstract
{
    public function __construct(ShipmentObj $shipmentObj)
    {
        parent::__construct('VInvoices', $shipmentObj['Name']);

        $this->tableLinkModule = 'VInvoice';
        $this->folderBaseDir = 'VInvoice';

        $this->addField(CostSheetFields::vStatusIco());

        $this->addFields(
            GeneralFields::vendorId(),
            InvoiceFields::vName()->setParam('tableWidthHtml', 90),
            CostSheetFields::invoiceAmountWithCurrency($this),
            InvoiceFields::docDate(),
            InvoiceFields::dueDate(),
            CostSheetFields::invoiceComments()
        );

        $this->tableDataTotal = [
            'Name' => 'Total Income',
            'InvoiceAmountWithCurrency' => 'USD ' . $shipmentObj['CacheExpensesinUSD']
        ];

        //Сразу загрузим данные
        $this->tableData = CommonDataProvider::getVInvoices($shipmentObj['Name']);
    }

} 
