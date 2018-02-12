<?php
namespace Forward\Module\Shipment\CostSheet\Subtables;

use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Forward\Module\Shipment\CostSheet\CostSheetFields;
use Forward\Module\Shipment\CostSheet\Subtables\Base\InvoicesAbstract;
use Forward\Module\Shipment\ShipmentObj;
use Light\Field\FieldAbstract as F;

class CInvoices extends InvoicesAbstract
{
    public function __construct(ShipmentObj $shipmentObj)
    {
        parent::__construct('CInvoices', $shipmentObj['Name']);

        $this->tableLinkModule = 'CInvoice';
        $this->folderBaseDir = 'CInvoice';

        $this->addField(CostSheetFields::cStatusIco());

        $this->addFields(
            GeneralFields::customerId(),
            F::create('Name', F::TYPE_STRING, ['name' => 'Inv No.', 'tableWidthHtml' => 155]),
            CostSheetFields::invoiceAmountWithCurrency($this),
            InvoiceFields::docDate(),
            CostSheetFields::invoiceComments()
        );

        $this->_tableTotalRowData = [
            'Name' => 'Total Income',
            'InvoiceAmountWithCurrency' => 'USD ' . $shipmentObj['CacheIncomeinUSD']
        ];

        //Сразу загрузим данные
        $this->_tableData = CommonDataProvider::getCInvoices($shipmentObj['Name']);
    }
} 
