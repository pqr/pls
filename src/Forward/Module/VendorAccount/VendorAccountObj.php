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

            F::createString('Name', ['name' => 'Account Name', 'upper' => true]),

            F::createText('InvoiceAddress', ['name' => 'Invoice Address', 'upper' => true]),

            F::createString('OneCName', 'Название в 1С'),

            CF::active(),

            CF::comments(),

            F::createCheckbox('NotifyAccountingByEmail', [
                'name' => 'Уведомить бухгалтерию при получении входящего счёта',
                'ajax' => true,
            ])

        );
    }
}
