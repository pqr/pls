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
    protected function init()
    {
        $this->_title = 'Customer Invoices';
        $this->_width = 550;
        $this->_description = 'Customer Invoices в статусах Open и Partially Cleared. Сортировка по клиенту.';
        $this->_informationFor = 'Информация для всех, у кого настроены права доступа в модуле Users::Home::Show Customer Invoices';
        $this->_tableLinkModule = 'CInvoice';
        $this->_tableLinkFieldKeys = ['CInvoiceName'];

        $this->addFields(

            InvoiceFields::cStatusIco(),

            GeneralFields::customerId(),

            InvoiceFields::cinvoiceName(),

            F::create('Amount', F::TYPE_MONEY),

            CF::currency(),

            InvoiceFields::docDate(),

            InvoiceFields::shippingDocument(),

            F::create('FileNumbers', F::TYPE_TEXT, ['name' => 'File Numbers', 'tableWidthExcel' => 15])

        );
    }

    protected function getTableQuery($userId)
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

    protected function tableLoadData($userId)
    {
        if (User::hasSpecialAccess(Application::SA_HOME_CINVOICE, $userId)) {
            parent::tableLoadData($userId);
        }
    }
}
