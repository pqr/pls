<?php

namespace Forward\Module\VPayment;

use Forward\Field\GeneralFields;
use Forward\Module\VPayment\Subtables\VInvoices;
use Forward\ModuleBase\Payments\PaymentModuleAbstract;
use Light\Db;
use Light\Field\FieldAbstract as F;

class VPayment extends PaymentModuleAbstract
{
    protected $textSaveBtn = 'Vendor Payment';
    public $tableLayoutDefault = 'CompanyID|Name|VendorID|PaymentDate|Amount|CurrencyISO|Comments';

    protected function initSubtables()
    {
        $this->addSubtable(VInvoices::class);
    }

    protected function initFilters()
    {
        $this->addFilters(

            GeneralFields::filterCompanyId(),

            GeneralFields::vendorId(),

            GeneralFields::vendorAccountId()

        );

        $this->addFilterText(
            ['Name', 'Comments'],
            [
                'name' => 'Ref. / Comments',
                'title' => 'Reference or Comments',
            ]
        );

        $this->addFiltersDatesFromUntil('PaymentDate', ['name' => 'Payment Date']);

        $this->filtersLayout = [
            [

                'CompanyID',
                'VendorID',
                'VendorAccountID',
                'FilterName',
            ],
            ['PaymentDate'],
        ];
    }
}
