<?php
namespace Forward\ModuleBase\Payments\Subtables;

use Forward\Classes\Finance;
use Forward\Classes\Traits\FirstLetterTrait;
use Forward\Field\InvoiceFields;
use Forward\Field\PaymentInvoiceFields;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\VInvoice\VInvoiceObj;
use Light\Db;
use Light\Link;
use Light\Msg;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

abstract class InvoicesAbstract extends Subtable
{
    use FirstLetterTrait;

    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $firstLetter = $this->getFirstLetter();
        $customerOrVendor = $this->getCustomerOrVendor();

        $this->setDbTable(strtolower($firstLetter . 'payment_' . $firstLetter . 'invoice'));
        $this->_title = $customerOrVendor . ' Invoices';
        $this->allowPaste = false;
        $this->allowExportToExcel = true;
        $this->allowTableLayouts = false;
        $this->_tablePrintTotalRecordsFound = false;
        $this->_tableMsgIfEmpty = 'No ' . $customerOrVendor . ' Invoices';

        //Параметры для формирования ссылок на модули CInvoice или VInvoice
        $this->tableLinkModule = $firstLetter . 'Invoice';
        $this->tableLinkIdFieldKey = 'RealInvoiceID';
        $this->tableLinkFieldKeys = [$firstLetter . 'InvoiceName'];

        $this->textAddLink = 'Add Invoice';

        $this->folders = true;
        $this->folderBaseDir = strtolower($firstLetter) . 'Invoice';
        $this->folderNameFieldKey = 'RealInvoiceID';

        $commonFields = $firstLetter . 'InvoiceName|InvoiceAmount|InvoiceOutstandingAmount|ClearingAmountInInvoiceCurrency|RateToInvoiceCurrency|ClearingAmountInPaymentCurrency';
        $this->_viewTableLayout = 'StatusIco|' . $commonFields;
        $this->_editTableLayout = 'StatusIco|' . $firstLetter . 'InvoiceID|' . $commonFields;

        $this->tableTotal = [$firstLetter . 'InvoiceName' => 'Total'];
        $this->_copyOnAddFieldKeys = ['RateToInvoiceCurrency'];

        if (isset($_POST['invoices'])) {
            $invIds = explode(',', $_POST['invoices']);
            $this->setAdditionalData($this->getInvoices($invIds));
        }

        //Добавим в Fieldset те поля, которые не сохраняются в базу данных, но участвуют в таблице
        $statusIcoField = $firstLetter === 'C' ? InvoiceFields::cStatusIco() : InvoiceFields::vStatusIco();
        $this->addField($statusIcoField->setParam('sqlSelect', ''));
        $this->addField(PaymentInvoiceFields::invoiceAmount()->setParam('sqlSelect', ''));
        $this->addField(PaymentInvoiceFields::invoiceOutstandingAmount()->setParam('sqlSelect', ''));

    }

    /**
     * Если пользователь выделил галочками несколько счетов в модуле CInvoice или VInvoice и нажал massaction
     * Create Payment, то он попадает в модуль Payment с параметром $_REQUEST['invoices']
     * Наличие этого параметра проверяется в методе init и, если он есть, то вызывается данный метод,
     * чтобы получить более подробную информацию по счетам
     * @param array $invIds
     * @return array
     */
    final protected function getInvoices(array $invIds)
    {
        //Необходимо в подтаблицу добавить информацию по переданным инвойсам
        $firstLetter = $this->getFirstLetter();
        $firstLetterLower = strtolower($firstLetter);
        $query = "SELECT
                      ID AS " . $firstLetter . "InvoiceID
                    , Name AS " . $firstLetter . "InvoiceName
                    , Status AS StatusIco
                    , Amount AS InvoiceAmount
                    , CacheOutstandingAmount AS InvoiceOutstandingAmount
                    , CacheOutstandingAmount AS ClearingAmountInInvoiceCurrency
                    , CurrencyISO AS InvoiceCurrencyISO
                    , CacheOutstandingAmount AS ClearingAmountInPaymentCurrency
                  FROM " . $firstLetterLower . "invoice
                  WHERE ID IN (@a)";

        return Db::getAll($query, $invIds);
    }

    protected function getExcelFilename()
    {
        return 'Payment ' . $this->_masterObj['Name'] . ' details';
    }

    protected function getExcelWorksheetName()
    {
        return 'Payment Details';
    }

    protected function getDataQuery()
    {
        $firstLetter = $this->getFirstLetter();
        $firstLetterLower = strtolower($firstLetter);
        $masterCurrencyIso = $this->_masterObj['CurrencyISO'];
        $masterId = $this->_masterObj->getId();

        return "SELECT pi.ID
                      , pi.{$firstLetter}InvoiceID
                      , pi.{$firstLetter}InvoiceName
                      , i.Amount AS InvoiceAmount
                      , i.CacheOutstandingAmount AS InvoiceOutstandingAmount
                      , pi.ClearingAmountInInvoiceCurrency
                      , i.CurrencyISO AS InvoiceCurrencyISO
                      , pi.RateToInvoiceCurrency
                      , pi.ClearingAmountInPaymentCurrency
                      , i.ID AS RealInvoiceID
                      , i.Status AS StatusIco
                      , '$masterCurrencyIso' AS PaymentCurrencyISO
                  FROM {$firstLetterLower}payment_{$firstLetterLower}invoice AS pi
                      LEFT JOIN " . $firstLetterLower . "invoice i ON pi.{$firstLetter}InvoiceID = i.ID
                  WHERE pi.{$firstLetter}PaymentID = $masterId
                      AND pi.{$firstLetter}PaymentID != 0
                  ORDER BY pi.ID";
    }

    /**
     * Эта функция вызывается для сбора данных пришедших из $_POST в момент сохранения
     * Переопределяем этот метод, чтобы жестко установить курс RateToInvoiceCurrency = 1, если валюта счёта совпадает
     * с валютой платёжки. В этом случае в пользовательском интерфейсе поле RateToInvoiceCurrency в подтаблице будет
     * заблокировано (disabeld) и данные по полю RateToInvoiceCurrency не будут переданы на сервер в POST запросе - подставим
     * туда единицу!
     * @param int $rowNum
     * @param array $dataFromRequest
     * @return array
     */
    protected function getRowDataFromPost($rowNum, array $dataFromRequest)
    {
        $rowDataFromRequest = parent::getRowDataFromPost($rowNum, $dataFromRequest);
        if (!isset($rowDataFromRequest['RateToInvoiceCurrency'])) {
            $rowDataFromRequest['RateToInvoiceCurrency'] = 1;
        }

        if (!isset($rowDataFromRequest['ClearingAmountInPaymentCurrency'])) {
            $rowDataFromRequest['ClearingAmountInPaymentCurrency'] = $rowDataFromRequest['ClearingAmountInInvoiceCurrency'];
        }

        return $rowDataFromRequest;
    }

    protected function getTableRowLinkUrlParams(array $rowData, int $rowNum): array
    {
        $firstLetter = $this->getFirstLetter();
        return [$firstLetter . 'Payments_findid' => 'RealPaymentID|' . $this->_masterObj->getId()];
    }
}
