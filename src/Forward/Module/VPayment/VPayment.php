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

    protected function registerSubtables()
    {
        $this->registerSubtable(VInvoices::class);
    }

    protected function initFilters()
    {
        $this->addFilters(

            GeneralFields::filterCompanyId(),

            GeneralFields::vendorId(),

            GeneralFields::vendorAccountId()

        );

        $this->addFilterName(
            F::TYPE_TEXT,
            [
                'name' => 'Ref. / Comments',
                'title' => 'Reference or Comments',
                'sqlWhere' => function ($value) {
                    return Db::getSqlWhereSeparatedByComa(
                            $value,
                            'Name',
                            0,
                            true,
                            false,
                            0
                        ) . " OR Comments LIKE '%" .
                        Db::esc($value) . "%'";
                }
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
            ['PaymentDate']
        ];
    }
}
