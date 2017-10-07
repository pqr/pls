<?php
namespace Forward\Module\VPayment;

use Forward\ModuleBase\Payments\PaymentObjAbstract;
use Forward\Field\GeneralFields;
use Forward\Field\PaymentFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;

class VPaymentObj extends PaymentObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('vpayment');
        $this->addFields(

            PaymentFields::name(),

            GeneralFields::companyId(),

            GeneralFields::vendorId(true, false),

            GeneralFields::vendorAccountId(true),

            F::createMoney('Amount'),
            PaymentFields::cacheOverpayAmount(),
            PaymentFields::overpayCleared(),
            CF::currency(),

            PaymentFields::paymentDate(),

            CF::comments()

        );
    }
}
