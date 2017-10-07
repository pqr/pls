<?php
namespace Forward\Module\VInvoice\Subtables;

use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Light\ObjAbstract;
use Light\Field\FieldAbstract as F;

class LinesObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('vinvoiceline');

        $vat = [
            '-1.00' => 'No VAT',
            '0.00' => '0%',
            '12.00' => '12%',
            '18.00' => '18%',
        ];

        $this->addFields(

            GeneralFields::mawbNumber(),

            GeneralFields::fileNumber(true), //подсвечивает красным, если номера файла на самом деле нет в базе

            F::createString('Name', [
                'name' => 'Description',
                F::PARAM_AC_SOURCE => 'list_chargedescription',
                F::PARAM_AC_READONLY => true,
                'tableWidthHtml' => 204
            ]),

            InvoiceFields::uomid(),

            GeneralFields::quantity(),

            F::createMoney('Price'),

            F::createMoney('Amount'),

            F::createSelect('VATPercent', [
                'name' => 'VAT %',
                'options' => $vat,
                'empty' => false,
                'tableWidthHtml' => 59,
                'tableAlign' => 'center'
            ]),

            F::createMoney('VATAmount', ['name' => 'VAT Amount']),

            F::createMoney('FinalAmount', ['name' => 'Final Amount'])

        );
    }

    public function getDescriptionHtml()
    {
        return $this->getFieldHtml('MAWBNumber') . ' ' . $this->getFieldHtml('Name');
    }
}