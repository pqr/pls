<?php
namespace Forward\Module\CInvoice\Subtables;

use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class LinesObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('cinvoiceline');

        $this->addFields(

            GeneralFields::fileNumber(true), //true - подсветка красным, если empty(ShipmentID)

            F::createString('Name', [
                'name' => 'Description',
                F::PARAM_AC_SOURCE => 'list_chargedescription',
                F::PARAM_AC_READONLY => true,
                'tableWidthHtml' => 305,
            ]),

            InvoiceFields::uomid(),

            GeneralFields::quantity(),

            F::createMoney('Price', ['name' => 'Unit Price', 'total' => false]),

            F::createMoney('Amount', ['name' => 'Amount'])

        );
    }

    public function getDescriptionHtml()
    {
        return $this->_data['FileNumber'] . ' ' . htmlspecialchars($this->_data['Name']);
    }

}
