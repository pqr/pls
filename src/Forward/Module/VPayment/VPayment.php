<?php

namespace Forward\Module\VPayment;

use Forward\Field\GeneralFields;
use Forward\ModuleBase\Payments\PaymentModuleAbstract;
use Light\Db;
use Light\Field\FieldAbstract as F;

class VPayment extends PaymentModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->_textObjectName = 'Vendor Payment';
        $this->_tableLayouts['Default'] =
            'CompanyID|Name|VendorID|PaymentDate|Amount|CurrencyISO|Comments';

        $this->registerSubtable('VInvoices');
    }

    protected function initFilters()
    {
        $this->addFilters(

            GeneralFields::companyId(),

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

        $this->_filtersDefaultTemplateArray = [
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
