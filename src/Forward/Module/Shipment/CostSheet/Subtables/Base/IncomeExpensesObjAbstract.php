<?php

namespace Forward\Module\Shipment\CostSheet\Subtables\Base;

use Forward\Field\GeneralFields;
use Forward\Module\Shipment\CostSheet\Subtables\CommonDataProvider;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoney;
use Light\Field\FieldMoneyWithCurrency;
use Light\Link;
use Light\ObjAbstract;

abstract class IncomeExpensesObjAbstract extends ObjAbstract
{
    protected $_fileNumber;

    /** @var IncomeExpensesAbstract */
    protected $_subtable;

    public function setFileNumber($fileNumber)
    {
        //Инжектим номер текущего файла - понадобится для формирования ссылок на номерах счетов
        $this->_fileNumber = $fileNumber;
    }

    public function setSubtable(IncomeExpensesAbstract $subtable)
    {
        $this->_subtable = $subtable;
    }

    protected function initStandartFields($invNumLinkModule)
    {
        $this->addFields(

            F::createString('Name', [
                    'name' => 'Description',
                    'editCssClass' => 'small',
                    'editWidth' => 'full',
                    'tableWidthHtml' => 'auto',
                    F::PARAM_AC_SOURCE => 'list_chargedescription',
                    F::PARAM_AC_READONLY => true
                ]
            ),

            GeneralFields::quantity(),

            F::createMoney('Price', [
                'name' => 'Unit Price',
                'editCssClass' => 'cs-price small',
                'tableWidthHtml' => 59
            ]),

            F::createMoneyWithCurrency('Amount', [
                'copy' => true,
                'default' => 'USD 0',
                'editCssClass' => 'cs-amount small',
                'getHtmlFunction' => function ($value, array $data, $outHtml, $forTable) {
                    if ($forTable === 'total' && $this->_subtable) {
                        //В строке total помимо основной суммы нужно вывести разбивку по валютам

                        //Разложим основной total на составляющие
                        $totalCurrencyIso = FieldMoneyWithCurrency::getAsTouple($value)[0];

                        //С помощью объекта подтаблицы получим разбивку total по валютам
                        $amountPerCurrency = $this->_subtable->getAmountPerCurrency();

                        //Если при разбивке по валютам выяснили, что валюта была всего одна и она совпадает с тем, во что вылился основной total
                        //то нет смысла добавлять разбивку по валютам.
                        //В остальных случаях, добавляем разбивку по валютам!
                        if (count($amountPerCurrency) > 1 || !isset($amountPerCurrency[$totalCurrencyIso])) {

                            //Добавляем разбивку по валютам
                            $outHtml .= '<div>';

                            $amoutPerCurrencyHtml = [];
                            foreach ($amountPerCurrency as $currencyIso => $totalAmountInCurrency) {
                                $amoutPerCurrencyHtml[] = FieldMoney::beautify(
                                    $totalAmountInCurrency,
                                    false,
                                    $currencyIso
                                );
                            }
                            $outHtml .= implode('<br>', $amoutPerCurrencyHtml);
                            $outHtml .= '</div>';

                        }

                    }

                    return $outHtml;
                },
                'viewCssClass' => function ($value, array $data, $forTable) use ($invNumLinkModule) {
                    if ($forTable !== 'total' && $data['InvNum'] && $this->_subtable) {

                        $amountForInvNum = (string)$this->_subtable->getSubAmountForInvNum($data['InvNum']);

                        //Не будем подсвечивать расхождения, если валюта строки отличается от валюты счёта
                        //поэтому нам нужно получить сумму счёта на данный файл + валюту счёта
                        $invDbTable = strtolower($invNumLinkModule); //получается 'cinvoice' или 'vinvoice'
                        $invAmountAndCurrencyIso = CommonDataProvider::getInvoiceAmountOnShipmentAndCurrency(
                            $invDbTable,
                            $this->_fileNumber,
                            $data['InvNum']
                        );

                        if ($invAmountAndCurrencyIso === null) {
                            return '';
                        }

                        list($invCurrencyIso, $invAmount) = $invAmountAndCurrencyIso;
                        $rowCurrencyIso = FieldMoneyWithCurrency::getAsTouple($data['Amount'])[0];
                        if ($rowCurrencyIso === $invCurrencyIso && $amountForInvNum != $invAmount) {
                            return 'bad';
                        }
                    }

                    return '';
                }
            ]),
            F::createString('InvNum', [
                'name' => 'Inv. No',
                'editCssClass' => 'cs-invoice small',
                'tableWidthHtml' => 54,
                'getHtmlFunction' =>
                    function ($value, array $data, $outHtml) use ($invNumLinkModule) {
                        if ($value) {
                            //Проверим, есть ли этот счёт реально в базе данных?
                            //Сделаем ссылку на него, либо подсветим красным
                            $invDbTable = strtolower($invNumLinkModule); //получается 'cinvocie' или 'vinvoice'
                            $invId = CommonDataProvider::getInvoiceId($invDbTable, $this->_fileNumber, $value);
                            if ($invId) {
                                //Счёт найден, делаем ссылку на просмотр счёта!
                                //В ссылке передаём GET параметр fromFileNumber - для подсветки строк в подтаблице
                                //Lines внутри счёта
                                $outHtml = $outHtml = Link::moduleView(
                                    $outHtml,
                                    $invId,
                                    ['fromFileNumber' => $this->_fileNumber],
                                    $invNumLinkModule
                                );
                            } else {
                                //Обернём в красный цвет
                                $outHtml = '<span class="bad" title="Invoice not found">' . $outHtml . "</span>";
                            }
                        }

                        return $outHtml;
                    }
            ])
        );
    }
}
