<?php
namespace Forward\ModuleBase\Invoices\Subtables;

use Forward\Application;
use Forward\Classes\Traits\FirstLetterTrait;
use Forward\Field\PaymentFields;
use Forward\Field\PaymentInvoiceFields;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;
use Light\User;

abstract class PaymentsAbstract extends Subtable
{
    use FirstLetterTrait;

    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $firstLetter = $this->getFirstLetter();
        $customerOrVendor = $this->getCustomerOrVendor();

        $this->setDbTable(strtolower($firstLetter . 'payment_' . $firstLetter . 'invoice'));
        $this->_title = $customerOrVendor . ' Payments';

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->allowTableLayouts = false;
        $this->_tablePrintTotalRecordsFound = false;
        $this->_tableMsgIfEmpty = 'No ' . $customerOrVendor . ' Payments yet';

        //Параметры для формирования ссылок на модули CPayment или VPayment
        $this->tableLinkModule = $firstLetter . 'Payment'; //имя модуля на который делать ссылку
        $this->tableLinkIdFieldKey = 'RealPaymentID'; //ключ в массиве $rowData по которому взять значение id=... для ссылки на CPayment или VPayment

        $this->folders = true;
        $this->folderBaseDir = $firstLetter . 'Payment';
        $this->folderNameFieldKey = 'RealPaymentID';

        $this->addFields(

            PaymentFields::name(),
            PaymentFields::paymentDate(),
            F::createMoneyWithCurrency('PaymentAmount', ['name' => "Total Payment\nAmount", 'viewCssClass' => 'gray']),
            F::createMoneyWithCurrency('ClearingAmountInInvoiceCurrency')->setName("Clearing\nAmount in Invoice\nCurrency"),
            PaymentInvoiceFields::rateToInvoiceCurrency(),
            F::createMoneyWithCurrency('ClearingAmountInPaymentCurrency')->setName("Clearing\nAmount in Payment\nCurrency")

        );

        $this->tableTotal = ['Name' => 'Total'];
    }

    protected function getExcelFilename()
    {
        return 'Payments for invoice ' . $this->_masterObj['Name'];
    }

    protected function getExcelWorksheetName()
    {
        return 'Payments';
    }

    protected function getDataQuery()
    {
        $firstLetter = $this->getFirstLetter();
        $firstLetterLower = strtolower($firstLetter);
        $masterCurrencyIso = $this->_masterObj['CurrencyISO'];
        $masterId = $this->_masterObj->getId();

        return "SELECT pi.ID
                      , p.Name
                      , CONCAT(p.CurrencyISO, ' ', p.Amount) AS PaymentAmount
                      , p.CurrencyISO AS PaymentCurrencyISO
                      , p.PaymentDate
                      , CONCAT('$masterCurrencyIso', ' ', pi.ClearingAmountInInvoiceCurrency) AS ClearingAmountInInvoiceCurrency
                      , pi.RateToInvoiceCurrency
                      , CONCAT(p.CurrencyISO, ' ', pi.ClearingAmountInPaymentCurrency) AS ClearingAmountInPaymentCurrency
                      , p.ID AS RealPaymentID
                      , '$masterCurrencyIso' AS InvoiceCurrencyISO
                  FROM {$firstLetterLower}payment_{$firstLetterLower}invoice pi, {$firstLetterLower}payment p
                  WHERE pi.{$firstLetter}PaymentID = p.ID
                      AND pi.{$firstLetter}InvoiceID = $masterId
                      AND pi.{$firstLetter}InvoiceID != 0
                  ORDER BY p.PaymentDate, p.Name";
    }

    protected function getTableRowLinkUrlParams(array $rowData, int $rowNum): array
    {
        $firstLetter = $this->getFirstLetter();
        return [$firstLetter . 'Invoices_findid' => $firstLetter . 'InvoiceID|' . $this->_masterObj->getId()];
    }
}
