<?php
namespace Forward\Module\Shipment\CostSheet;

use Forward\Field\InvoiceFields;
use Forward\Module\Shipment\CostSheet\Subtables\Base\InvoicesAbstract;
use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoney;
use Light\Field\FieldMoneyWithCurrency;
use Light\Img;

class CostSheetFields
{
    public static function invoiceAmountWithCurrency(InvoicesAbstract $invoicesTable)
    {
        return F::create(
            'InvoiceAmountWithCurrency',
            F::TYPE_MONEY_WITH_CURRENCY,
            [
                'name' => 'Amount',
                'getHtmlFunction' => function ($value, $data, $outHtml, $forTable) use ($invoicesTable) {

                        if ($forTable === 'total') {
                            //В строке total помимо основной суммы нужно вывести разбивку по валютам

                            //Разложим основной total на составляющие
                            $totalCurrencyIso = FieldMoneyWithCurrency::getAsTouple($value)[0];

                            //С помощью объекта подтаблицы получим разбивку total по валютам
                            $amountPerCurrency = $invoicesTable->getAmountPerCurrency();

                            //Если при разбивке по валютам выяснили, что валюта была всего одна и она совпадает с тем, во что вылился основной total
                            //то нет смысла добавлять разбивку по валютам.
                            //В остальных случаях, добавляем разбивку по валютам!
                            if (count($amountPerCurrency) > 1 || !isset($amountPerCurrency[$totalCurrencyIso])) {

                                //Добавляем разбивку по валютам
                                $outHtml .= '<div>';

                                $amoutPerCurrencyHtml = [];
                                foreach ($amountPerCurrency as $currencyIso => $totalAmountInCurrency) {
                                    $amoutPerCurrencyHtml[] =  FieldMoney::beautify($totalAmountInCurrency, false, $currencyIso);
                                }
                                $outHtml .= implode('<br>', $amoutPerCurrencyHtml);

                                $outHtml .= '</div>';

                            }

                        } elseif (isset($data['AmountOnShipment']) && $data['Amount'] != $data['AmountOnShipment']) {
                            //Это не total строка

                            //Если сумма на шимпенте не соответствует общей сумме - выведем обе
                            //AmountOnShipment может отсутсвовать в массиве в случае Total строки

                            $fileAmountHtml = FieldMoney::beautify($data['AmountOnShipment'], false, $data['CurrencyISO']);

                            $outHtml = '<span class="gray" title="Full Invoice Amount">' . $outHtml . '</span><br>';
                            $outHtml .= '<span title="Amount on Shipments">'.$fileAmountHtml.'</span>';

                        }

                        return $outHtml;
                    }
            ]
        );
    }

    public static function invoiceComments()
    {
        return F::create(
            'Comments',
            F::TYPE_HTML,
            [
                'name' => 'Cm.',
                'title' => 'Comments',
                'tableAlign' => 'center',
                'getHtmlFunction' => function ($value) {
                        //Комментарии отображаются в виде иконки
                        if ($value != '') {
                            return '<span title="'.htmlspecialchars($value).'">'. Img::iconInfo() .'</span>';
                        }
                        return '';
                    }
            ]
        );
    }

    public static function vReinvoiced()
    {
        return F::createCheckbox('Reinvoiced', ['name' => 'Првст.', 'title' => 'Перевыставлен']);
    }

    /**
     * @return FieldAbstract
     */
    public static function vStatusIco()
    {
        return InvoiceFields::vStatusIco()->setParams(['name' => '', 'tableWidthHtml' => 20]);
    }

    /**
     * @return FieldAbstract
     */
    public static function cStatusIco()
    {
        return InvoiceFields::cStatusIco()->setParams(['name' => '', 'tableWidthHtml' => 20]);
    }
} 