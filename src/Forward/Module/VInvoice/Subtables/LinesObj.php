<?php
namespace Forward\Module\VInvoice\Subtables;

use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Light\Field\CommonFields as CF;
use Light\ObjAbstract;
use Light\Field\FieldAbstract as F;

class LinesObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('vinvoiceline');

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

            CF::vatPercent(),

            CF::vatAmount(),

            F::createMoney('FinalAmount', ['name' => 'Final Amount'])

        );
    }

    public function getDescriptionHtml()
    {
        return $this->getFieldHtml('MAWBNumber') . ' ' . $this->getFieldHtml('Name');
    }
}
