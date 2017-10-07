<?php
namespace Forward\Module\VendorAccount;

use Forward\Field\GeneralFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class VendorAccountObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('vendoraccount');

        $this->addFields(

            CF::id(),

            GeneralFields::companyId(),

            F::createString('Name', ['name' => 'Account Name', 'submitToupper' => true]),

            F::createText('InvoiceAddress', ['name' => 'Invoice Address', 'submitToupper' => true]),

            F::createString('OneCName', 'Название в 1С'),

            CF::active(),

            CF::comments()

        );
    }
}
