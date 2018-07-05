<?php

namespace Forward\Home;

use Forward\Application;
use Forward\Classes\Finance;
use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class CInvoice extends HomeBlockAbstract
{
    protected static $title = 'Customer Invoices';
    protected static $description = 'Customer Invoices в статусах Open и Partially Cleared. Сортировка по клиенту.';
    protected static $informationFor = 'Информация для всех, у кого настроены права доступа в модуле Users::Home::Show Customer Invoices';

    public function __construct(int $userId)
    {
        parent::__construct($userId);

        $this->width = 550;
        $this->tableLinkModule = 'CInvoice';
        $this->tableLinkFieldKeys = ['CInvoiceName'];
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            InvoiceFields::cStatusIco(),

            GeneralFields::customerId(),

            InvoiceFields::cinvoiceName(),

            F::createMoney('Amount'),

            CF::currency(),

            InvoiceFields::docDate(),

            InvoiceFields::shippingDocument(),

            F::createText('FileNumbers', [
                'name' => 'File Numbers',
                'tableWidthExcel' => 15
            ])

        );
    }

    protected function getDataQuery(int $userId)
    {
        return "SELECT ID
                  , ID AS RealCInvoiceID
                  , Name AS CInvoiceName
                  , Status AS StatusIco
                  , CustomerID
                  , Amount
                  , CurrencyISO
                  , DocDate
                  , ShippingDocument
                  , (SELECT GROUP_CONCAT(DISTINCT(FileNumber) ORDER BY ID SEPARATOR '\\n')
                     FROM cinvoiceline WHERE CInvoiceID = cinvoice.ID GROUP BY CInvoiceID) AS FileNumbers
                  FROM cinvoice
                  WHERE Status IN (" . implode(',', Finance::$cinvoiceOpenStatuses) . ")
                  ORDER BY CustomerID, Status, DocDate";
    }

    protected function tableLoadData(int $userId)
    {
        if (User::hasSpecialAccess(Application::SA_HOME_CINVOICE, $userId)) {
            parent::tableLoadData($userId);
        }
    }
}
