<?php

namespace Forward\Module\CPayment;

use Forward\Classes\BuAccess;
use Forward\Classes\CustomerAccess;
use Forward\Field\GeneralFields;
use Forward\Module\Customer\CustomerObj;
use Forward\ModuleBase\Payments\PaymentModuleAbstract;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class CPayment extends PaymentModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->textSaveBtn = 'Customer Payment';
        $this->tableLayouts['Default'] =
            'CompanyID|Name|CustomerID|PaymentDate|Amount|CurrencyISO|Comments';

        $this->registerSubtable('CInvoices');
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

            GeneralFields::companyId(),

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

    protected function afterChangeField($fieldKey, array $oldData)
    {
        //Если была кликнуна галочка "Overpay Cleared", то нужно пересчитать сумму открытых оплат по клиенту
        if ($this->_obj['CustomerID']) {
            $customerObj = new CustomerObj();
            $customerObj->setId($this->_obj['CustomerID']);
            $customerObj->loadData();
            $customerObj->updateCacheOpenPaymentsAmount();
        }
    }
}
