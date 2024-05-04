<?php
namespace Forward\Field;

use Forward\Classes\Finance;
use Forward\Module\CPayment\CPayment;
use Light\Field\FieldAbstract as F;
use Light\Link;

class CustomerFields
{
    public static function openInvoicesAmounts()
    {
        return F::create(
            'OpenInvoicesAmounts',
            F::TYPE_MONEY_WITH_CURRENCY,
            [
                'name' => "Open Invoices",
                'nameTable' => "Open Invoices\nAmount",
                'modes' => F::MODE_READONLY,
                'tableWidthExcel' => 18,
                'zeroEmpty' => true,
                'groupconcat' => true,
                'groupconcatRemoveEmpty' => true,
                'tableOrderBy' => false,
                'sqlSelect' => "SELECT GROUP_CONCAT(CONCAT(CurrencyISO, ' ', OpenAmount) SEPARATOR '\\n') FROM customer_openinvoices WHERE CustomerID = customer.ID GROUP BY CustomerID",
                'sqlSelectRequires' => 'CreditAmount',
                'getHtmlFunction' => function ($value, $data, $outHtml, $forTable) {
                    if ($value && isset($data['ID'])) {
                        $currencyIso = explode(' ', $value)[0];
                        $outHtml = Finance::getCInvoiceLink($outHtml, $data['ID'], false, $currencyIso);
                    }
                    return $outHtml;
                },
                'viewCssClass' => function ($value, array $data, $forTable) {
                    if ($value && $forTable !== 'total' && isset($data['CreditAmount']) && strpos($data['CreditAmount'], ' ') !== false) {
                        [$currencyIso, $amount] = explode(' ', $value);
                        [$creditCurrencyIso, $creditAmount] = explode(' ', $data['CreditAmount']);
                        if ($amount > 0 && $creditCurrencyIso == $currencyIso) {
                            //Сравним сумму задолженности с кредитными условиями
                            if ($amount > $creditAmount) {
                                return 'bad outstanding';
                            } elseif ($amount >= 0.8 * $creditAmount) {
                                return 'warning outstanding';
                            }
                        }
                    }

                    return false;
                },
                'cssClassNegative' => 'good'
            ]
        );
    }

    public static function overdueInvoicesAmounts()
    {
        return F::create(
            'OverdueInvoicesAmounts',
            F::TYPE_MONEY_WITH_CURRENCY,
            [
                'name' => "Overdue Invoices",
                'nameTable' => "Overdue Invoices\nAmount",
                'modes' => F::MODE_READONLY,
                'tableWidthExcel' => 18,
                'zeroEmpty' => true,
                'groupconcat' => true,
                'tableOrderBy' => false,
                'viewCssClass' => 'bad outstanding',
                'sqlSelect' => "SELECT GROUP_CONCAT(CONCAT(CurrencyISO, ' ', OverdueAmount) SEPARATOR '\\n') FROM customer_openinvoices WHERE CustomerID = customer.ID GROUP BY CustomerID",
                'getHtmlFunction' => function ($value, $data, $outHtml, $forTable) {
                    if ($value && isset($data['ID'])) {
                        $currencyIso = explode(' ', $value)[0];
                        $outHtml = Finance::getCInvoiceLink($outHtml, $data['ID'], false, $currencyIso, true);
                    }
                    return $outHtml;
                }
            ]
        );
    }

    public static function openPaymentsAmounts()
    {
        return F::create(
            'OpenPaymentsAmounts',
            F::TYPE_MONEY_WITH_CURRENCY,
            [
                'name' => "Open Payments",
                'nameTable' => "Open Payments\nAmount",
                'modes' => F::MODE_READONLY,
                'zeroEmpty' => true,
                'groupconcat' => true,
                'tableOrderBy' => false,
                'sqlSelect' => "SELECT GROUP_CONCAT(CONCAT(CurrencyISO, ' ', OpenPaymentsAmount) SEPARATOR '\\n') FROM customer_openpayments WHERE CustomerID = customer.ID GROUP BY CustomerID",
                'getHtmlFunction' => function ($value, $data, $outHtml, $forTable) {
                    if ($value && isset($data['ID'])) {
                        $currencyIso = explode(' ', $value)[0];
                        $outHtml = Link::create(CPayment::class, $outHtml)
                            ->filters(['CustomerID' => $data['ID'], 'CurrencyISO' => $currencyIso])
                            ->title('View Customer Payments')
                            ->getHtml();
                    }
                    return $outHtml;
                },
                'cssClassPositive' => 'good',
                'cssClassNegative' => 'bad'
            ]
        );
    }
}
