<?php
namespace Forward\ModuleBase\Payments\Subtables;

use Light\Traits\FirstLetterTrait;
use Forward\Field\PaymentInvoiceFields;
use Light\ObjAbstract;

abstract class InvoicesObjAbstract extends ObjAbstract
{
    use FirstLetterTrait;

    public function __construct()
    {
        $firstLetter = $this->getFirstLetter();

        $this->setDbTable(strtolower($firstLetter . 'payment_' . $firstLetter . 'invoice'));
        $this->setNameFieldKey($firstLetter . 'InvoiceName');

        //Т.к. это объект подтаблицы, для правильной записи в dblog вручную укажем masterModule
        //$this->setMasterIdForDbLog($firstLetter . 'Payment');

        $this->addFields(
            PaymentInvoiceFields::invoiceIdAutocomplete($firstLetter . 'InvoiceID'),
            PaymentInvoiceFields::invoiceNameAutocomplete($firstLetter . 'InvoiceName'),
            PaymentInvoiceFields::clearingAmountInInvoiceCurrency(),
            PaymentInvoiceFields::rateToInvoiceCurrency(),
            PaymentInvoiceFields::clearingAmountInPaymentCurrency()
        );
    }
} 
