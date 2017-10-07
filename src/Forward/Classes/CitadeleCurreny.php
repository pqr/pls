<?php
namespace Forward\Classes;

use Light\Currency;
use Light\Db;

class CitadeleCurreny
{
    private static $ratesCache = [];

    private static function getRates($currencyIso, $date)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $cacheKey = $currencyIso . $date;

        if (!array_key_exists($cacheKey, self::$ratesCache)) {
            $rates = Db::getRow("SELECT ValidDate, BuyingForEUR, SellingForEUR, BuyingForUSD, SellingForUSD
                        FROM currencyrate_citadele
                        WHERE ValidDate <= @s
                            AND Name = @s
                        ORDER BY ValidDate DESC
                        LIMIT 1", $date, $currencyIso);

            if (!$rates) {

                //Если ничего не найдено, используем курсы ЦБРФ
                self::$ratesCache[$cacheKey] = [
                    'ValidDate' => $date,
                    'BuyingForEUR' => Currency::getRate('EUR', $currencyIso, $date),
                    'SellingForEUR' => Currency::getRate('EUR', $currencyIso, $date),
                    'BuyingForUSD' => Currency::getRate('USD', $currencyIso, $date),
                    'SellingForUSD' => Currency::getRate('USD', $currencyIso, $date)
                ];

            } else {

                //В базе нашлись реальные ставки, сохраним их в кеш
                self::$ratesCache[$cacheKey] = $rates;

                //Если реальная дата ValidDate не совпадает с запрошенной датой, то сохраним второе значение
                if ($rates['ValidDate'] != $date) {
                    self::$ratesCache[$currencyIso . $rates['ValidDate']] = $rates;
                }

            }
        }

        return self::$ratesCache[$cacheKey];
    }

    /**
     * Продаём какую-то валюту, получаем от банка доллары (обычно, когда получаем от клиентов какую-то странную валюту)
     * @param float $somethingAmount
     * @param string $somethingCurrency
     * @param string $date
     * @return float количество полученных от банка USD
     */
    public static function sellSomethingBuyUsd($somethingAmount, $somethingCurrency, $date = null)
    {
        //Исключительный случай: если я хочу продать EUR и получить доллары, то в базе нет отдельной строки с курсом EUR.
        //Зато есть строка с курсом USD (это потому что Citadele европейский банк, у них EUR основная валюта)
        if ($somethingCurrency == 'EUR') {
            //Получаем массив курсов для доллара в котором будут два поля с примерно такими курсами:
            //BuyingForEUR=1.135
            //SellingForEUR=1.107
            $rates = self::getRates('USD', $date);
            //Сумма в долларах должна быть больше чем сумма в EUR. Но не сильно больше, банк всегда конвернтирует как
            //ему выгоднее. В данном случае надо применить умножение на SellingForEUR=1.107
            return $somethingAmount * $rates['SellingForEUR'];
        }

        //Общий случай - мы продаём НЕ EUR.
        //Для наглядности предположим, что мы продаём GBP - он дороже доллара, поэтому в долларах от банка мы получим
        //большее кол-во зелёных купюр, чем проданное кол-во GBP
        //Загруженные курсы будут содержать следующие поля:
        //BuyingForUSD=0.64
        //SellingForUSD=0.66
        //Т.к. курс меньше 1 - на него надо делить! (чтобы получить большее кол-во купюр в USD).
        //Но при этом банк всегда используе выгодный для себя курс, поэтому делить будем на SellingForUSD=0.66

        //Второй пример для наглядности: мы продаём RUB. Рубль дешевый, кол-во полученных от банка зелёных купюр будет маленьким
        //Загруженные курсы будут содержать следующие поля:
        //BuyingForUSD=53
        //SellingForUSD=58
        //Будем делить на SellingForUSD=58 - т.е. подход совпадает с тем, что для GBP выше
        $rates = self::getRates($somethingCurrency, $date);
        return $somethingAmount / $rates['SellingForUSD'];
    }

    /**
     * Покупаем некую валюту за доллары (когда нужно заплатить подрядчику)
     * @param float $somethingAmount
     * @param string $somethingCurrency
     * @param string $date
     * @return float кол-во USD, которые нужно потратить, чтобы купить требуемую валюту
     */
    public static function buySomethingSellUsd($somethingAmount, $somethingCurrency, $date = null)
    {
        //Исключительный случай: если купить EUR, то в базе нет отдельной строки с курсом EUR.
        //Зато есть строка с курсом USD (это потому что Citadele европейский банк, у них EUR основная валюта)
        if ($somethingCurrency == 'EUR') {
            //Получаем массив курсов для доллара в котором будут два поля с примерно такими курсами:
            //BuyingForEUR=1.135
            //SellingForEUR=1.107
            $rates = self::getRates('USD', $date);
            //Число долларов, которое нужно потратить на покупку EUR валюты будет большим
            //Используем выгодный для банка курс BuyingForEUR=1.135
            return $somethingAmount * $rates['BuyingForEUR'];
        }

        //Общий случай - мы покупаем НЕ EUR.
        //Для наглядности предположим, что мы покупаем GBP - он дороже доллара, поэтому в долларах мы должны отдать
        //банку больше купюр, чем получим в GBP
        //Загруженные курсы будут содержать следующие поля:
        //BuyingForUSD=0.64
        //SellingForUSD=0.66
        //Чтобы узнать число требуемых долларов, нужно разделить на этот курс, причём на выгодный для банка
        //BuyingForUSD=0.64

        //Второй пример для наглядности: мы покупаем RUB. Рубль дешевый, кол-во требуемых зелёных купюр будет маленьким
        //Загруженные курсы будут содержать следующие поля:
        //BuyingForUSD=53
        //SellingForUSD=58
        //Будем делить на BuyingForUSD=53 - т.е. подход совпадает с тем, что для GBP выше
        $rates = self::getRates($somethingCurrency, $date);
        return $somethingAmount / $rates['BuyingForUSD'];
    }
}