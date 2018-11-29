<?php
namespace Forward\Module\CustomerAccount;

use Forward\Classes\Constant;
use Forward\Field\CreditFields;
use Forward\Field\GeneralFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class CustomerAccountObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('customeraccount');

        $this->addFields(

            CF::id(),

            GeneralFields::companyId(),

            F::createString('Name', ['name' => 'Account Name']),

            CF::active(),

            F::createString('ContractNo', ['name' => 'Contract No.']),

            F::createText('InvoiceAddress', ['name' => 'Billing Address']),

            GeneralFields::ratePercent(),
            CreditFields::creditPeriod(),

            CF::comments(),

            GeneralFields::buIds(),

            GeneralFields::shipmentRequestZip(),
            GeneralFields::shipmentRequestStreet(),
            GeneralFields::shipmentRequestCity(),
            GeneralFields::shipmentRequestCountry(),
            GeneralFields::shipmentRequestPhone(),

            F::createSelect('ActOnDateType', [
                'name' => 'Акт на дату',
                'empty' => true,
                'options' => [
                    Constant::ACTONDATETYPE_ATA => 'На дату прилёта',
                    Constant::ACTONDATETYPE_CPAYMENT => 'На дату оплаты счёта',
                ]
            ]),

            GeneralFields::docsAddress(),
            GeneralFields::deliveryMethod()

        );
    }
}
