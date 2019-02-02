<?php

namespace Forward\Module\CPayment;

use Forward\Classes\BuAccess;
use Forward\Classes\CustomerAccess;
use Forward\Field\GeneralFields;
use Forward\Module\CPayment\Subtables\CInvoices;
use Forward\ModuleBase\Payments\PaymentModuleAbstract;
use Light\Db;
use Light\Field\FieldAbstract as F;

class CPayment extends PaymentModuleAbstract
{
    protected $textSaveBtn = 'Customer Payment';
    public $tableLayoutDefault = 'CompanyID|Name|CustomerID|PaymentDate|Amount|CurrencyISO|Comments';

    protected function registerSubtables()
    {
        $this->registerSubtable(CInvoices::class);
    }

    protected function initFilters()
    {
        $this->addFilters(

            F::createText('FilterName', [
                'name' => 'Ref. / Comments',
                'title' => 'Reference or Comments',
                'sqlWhere' => function ($value) {
                    return "(" . Db::getSqlWhereSeparatedByComa(
                            $value,
                            'Name',
                            0,
                            true,
                            false,
                            0
                        ) . " OR Comments LIKE '%{value}%')";
                }
            ]),

            GeneralFields::filterCompanyId(),

            GeneralFields::customerId(),

            GeneralFields::customerAccountId()

        );

        $this->addFiltersDatesFromUntil('PaymentDate', ['name' => 'Payment Date']);

        $this->filtersLayout = [
            [
                'CompanyID',
                'CustomerID',
                'CustomerAccountID',
                'FilterName',
            ],
            ['PaymentDate']

        ];
    }

    protected function getAccessSqlWhere(): string
    {
        return BuAccess::getSqlWhereCustomerId() . " AND " . CustomerAccess::getSqlWhere();
    }

    protected function hasWriteAccessAjaxChangeField(F $field): bool
    {
        if ($field->key === 'PrintedDate') {
            return true;
        }
        return parent::hasWriteAccessAjaxChangeField($field);
    }
}
