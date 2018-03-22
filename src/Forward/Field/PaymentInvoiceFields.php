<?php
namespace Forward\Field;

use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoney;

/**
 * Описание полея для подтаблиц внутри модулей оплат (CPayment и VPayment)
 * а также для подтаблиц с оплатами в моудлях CInvoice и VInvoice
 */
class PaymentInvoiceFields
{
    private static function getInvoiceAutocompleteParams()
    {
        return [
            F::PARAM_AC_ONSELECTJSFUNCTION => 'Payment.onInvoiceSelect',
            F::PARAM_AC_RESULTFORMATTERJSFUNCTION => 'Payment.acInvoiceResultFormatter',
        ];
    }

    /**
     * Универсальный метод - подходит для формирования CInvoiceID и VInvoiceID в подтаблицах с инвойсами
     * @param $fieldKey
     * @return F
     */
    public static function invoiceIdAutocomplete($fieldKey)
    {
        $params = self::getInvoiceAutocompleteParams();
        $params[F::PARAM_AC_SOURCEJSFUNCTION] = 'Payment.acInvoiceByIdSourceFunction';
        return InvoiceFields::invoiceId($fieldKey, $params);
    }

    public static function invoiceNameAutocomplete($fieldKey)
    {
        $params = self::getInvoiceAutocompleteParams();
        $params[F::PARAM_AC_SOURCEJSFUNCTION] = 'Payment.acInvoiceByNameSourceFunction';
        return InvoiceFields::invoiceName($fieldKey, $params);
    }

    public static function invoiceAmount()
    {
        return F::create(
            'InvoiceAmount',
            F::TYPE_MONEY,
            [
                'modes' => F::MODE_READONLY,
                'name' => 'Invoice Amount',
                'getHtmlFunction' => function ($value, array $data, $outHtml) {
                    if ($value) {
                        $outHtml = '<span class="nobr">' . $outHtml . ' ' . $data['InvoiceCurrencyISO'] . '</span>';
                    }

                    return $outHtml;
                }
            ]
        );
    }

    /**
     * @return FieldMoney
     */
    public static function invoiceOutstandingAmount()
    {
        return InvoiceFields::cacheOutstandingAmount()->getClone(
            'InvoiceOutstandingAmount',
            [
                'getHtmlFunction' => function ($value, array $data, $outHtml) {
                    if ($value != '0.00' && isset($data['InvoiceCurrencyISO'])) {
                        $outHtml = '<span class="nobr">' . $outHtml . ' ' . $data['InvoiceCurrencyISO'] . '</span>';
                    }

                    return $outHtml;
                }
            ]
        );
    }

    public static function clearingAmountInInvoiceCurrency()
    {
        return F::create(
            'ClearingAmountInInvoiceCurrency',
            F::TYPE_MONEY,
            [
                'name' => "Clearing\nAmount in Invoice\nCurrency",
                'getHtmlFunction' => function ($value, array $data, $outHtml) {
                    if ($value && isset($data['InvoiceCurrencyISO'])) {
                        $outHtml = '<span class="nobr">' . $outHtml . ' ' . $data['InvoiceCurrencyISO'] . '</span>';
                    }

                    return $outHtml;
                },
                'getHtmlInputFunction' => function ($value, $data, $outHtml) {
                    return '<span class="nobr">' . $outHtml . ' <span class="invoice-currency-span">' . ($data['InvoiceCurrencyISO'] ?? '&emsp;&emsp;') . '</span></span>';
                }
            ]
        );
    }

    public static function rateToInvoiceCurrency()
    {
        return F::create(
            'RateToInvoiceCurrency',
            F::TYPE_FLOAT,
            [
                'name' => "Exch Rate to\nInv. Currency",
                'title' => 'Exchange Rate to Invoice Currency',
                'tableWidthHtml' => 84,
                'tableWidthExcel' => 12,
                'default' => 1
            ]
        );
    }

    public static function clearingAmountInPaymentCurrency()
    {
        return F::createMoney('ClearingAmountInPaymentCurrency', [
            'name' => "Clearing\nAmount in Payment\nCurrency",
            'getHtmlFunction' => function ($value, array $data, $outHtml) {
                if ($value && isset($data['PaymentCurrencyISO'])) {
                    $outHtml = '<span class="nobr">' . $outHtml . ' ' . $data['PaymentCurrencyISO'] . '</span>';
                }

                return $outHtml;
            },
            'getHtmlInputFunction' => function ($value, $data, $outHtml) {
                return '<span class="nobr">' . $outHtml . ' <span class="payment-currency-span">' . ($data['PaymentCurrencyISO'] ?? '') . '</span></span>';
            }
        ]);
    }
}
