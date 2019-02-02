<?php
namespace Forward\Module\CInvoice\Massactions;

use Forward\Classes\Finance;
use Forward\Module\CPayment\CPaymentObj;
use Forward\Module\CPayment\Subtables\CInvoicesObj;
use Light\A;
use Light\Db;
use Light\Field\FieldMoney;
use Light\Msg;
use Light\Response;
use Light\Template;

class CreateCPayments
{
    public static function check(array $cinvoiceIds):array
    {
        $errors = [];

        $res = Db::query("SELECT ID FROM cinvoice WHERE Status != " . Finance::INVOICESTATUS_OPEN . " AND Status != " . Finance::INVOICESTATUS_PARTCLEARED . " AND ID IN (@a) LIMIT 1",
            $cinvoiceIds);
        if ($res->num_rows > 0) {
            $errors[] = 'Некоторые счета находятся в статусе отличном от Open';
        }

        $res = Db::query("SELECT CompanyID FROM cinvoice WHERE ID IN (@a) GROUP BY CompanyID", $cinvoiceIds);
        if ($res->num_rows !== 1) {
            $errors[] = 'Cчета относятся к разным компаниям Our Company';
        }

        return $errors;
    }

    public static function showPaymentDateDialog(array $cinvoiceIds)
    {
        $body = '<form action="?a=CInvoice&ps=CreateCPayments" method="post">'
            . Template::csrfHidden()
            . '<input type="hidden" name="ids" value="' . implode(',', $cinvoiceIds) . '">'
            . '<label>Payment Date: <input type="text" name="PaymentDate" class="input-date" value="' . date('d.m.Y') . '"></label>'
            . '</form>';

        Response::showDialog($body, 'Create Customer Payments');
    }

    public static function create(array $cinvoiceIds, string $paymentDate)
    {
        $msgBuff = new Msg();
        $createdCPaymentNames = [];

        $cinvoices = Db::getAll("SELECT cinvoice.*, CONCAT(CustomerID, '-', CurrencyISO) AS UniqueKey FROM cinvoice WHERE ID IN (@a)", $cinvoiceIds);
        // Группируем по связке клиент+валюта
        $cinvoicesPerCustomerAndCurrency = A::groupBy($cinvoices, 'UniqueKey');

        $companyId = $cinvoices[0]['CompanyID'];

        $cpaymentObjBase = new CPaymentObj();
        $lineObjBase = new CInvoicesObj();

        $dbLogMsg = 'Create Customer Payment from Customer Invoices';

        $totalAmountPerCurrency = [];

        // Создаём по одной оплате на счёт
        foreach ($cinvoicesPerCustomerAndCurrency as $customerAndCurrency => $cinvoicesForCustomer) {

            [$customerId, $currencyIso] = explode('-', $customerAndCurrency);

            $amount = array_sum(array_column($cinvoicesForCustomer, 'CacheOutstandingAmount'));
            $customerAccountIds = array_unique(array_column($cinvoicesForCustomer, 'CustomerAccountID'));
            if (count($customerAccountIds) === 1) {
                // У выбранных счетов одинаковый Customer Account - именно его и будем использовать
                $customerAccountId = reset($customerAccountIds);
            } else {
                // У выбранных счетов разные Customer Account - создадим оплату с пустым Customer Account
                $customerAccountId = 0;
            }

            $cpaymentObj = clone $cpaymentObjBase;
            $cpaymentObj->insertDb([
                'CompanyID' => $companyId,
                'CustomerID' => $customerId,
                'CustomerAccountID' => $customerAccountId,
                'Amount' => $amount,
                'CurrencyISO' => $currencyIso,
                'PaymentDate' => $paymentDate
            ], $dbLogMsg);

            foreach ($cinvoicesForCustomer as $cinvoice) {
                $lineObj = clone $lineObjBase;
                $lineObj->setMasterIdForDbLog($cpaymentObj->getId());
                $lineObj->insertDb([
                    'CPaymentID' => $cpaymentObj->getId(),
                    'CInvoiceID' => $cinvoice['ID'],
                    'CInvoiceName' => $cinvoice['Name'],
                    'ClearingAmountInInvoiceCurrency' => $cinvoice['CacheOutstandingAmount'],
                    'RateToInvoiceCurrency' => 1,
                    'ClearingAmountInPaymentCurrency' => $cinvoice['CacheOutstandingAmount']
                ], $dbLogMsg);
            }

            $cpaymentObj->updateCacheAfterSubmit([], $msgBuff, true, null, $dbLogMsg);

            $createdCPaymentNames[] = $cpaymentObj['Name'];

            if (!isset($totalAmountPerCurrency[$currencyIso])) {
                $totalAmountPerCurrency[$currencyIso] = 0;
            }

            $totalAmountPerCurrency[$currencyIso] += $cpaymentObj['Amount'];
        }

        $msg = 'Было создано <strong>' . count($createdCPaymentNames) . '</strong> оплат на сумму ';
        $msgAmounts = [];
        foreach($totalAmountPerCurrency as $currencyIso => $amount) {
            $msgAmounts[] = FieldMoney::beautify($amount, false, $currencyIso);
        }
        $msg .= implode(', ', $msgAmounts);

        $msgBuff->addSuccess($msg);
        $msgBuff->putIntoSessionFlash();

        Response::redirectWithPost('CPayment', null, [
            'filterValues' => json_encode([
                'FilterName' => implode("\n", $createdCPaymentNames),
                'PaymentDateFrom' => $paymentDate,
                'PaymentDateUntil' => $paymentDate
            ])
        ]);
    }
}
