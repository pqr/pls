<?php

namespace Light;

use Light\Field\FieldDate;

class Currency
{
    private static $rates = []; // Массив курсов валют [CurrencyISO][Дата] = array('CurrencyISO' => '...', 'RateToUSD' => '...', 'ValidDate' => '...')
    private static $currencies; // Основная информация по валютам из таблицы currency

    /**
     * Если дата не передана, возвращает текущий день
     * @param string $date
     * @return string
     */
    private static function getValidDate($date)
    {
        if (FieldDate::empty($date)) {
            return date('Y-m-d');
        }

        $date = FieldDate::parse($date);
        return $date ?: date('Y-m-d');
    }

    public static function getCurrencies(): array
    {
        if (!self::$currencies) {
            self::$currencies = Db::getAssoc("SELECT Name AS CurrencyKey, currency.* FROM currency ORDER BY OrderID");
        }

        return self::$currencies;
    }

    /**
     * Эта функция использует статический кеширующий массив self::$rates
     * Если в нём нет данных и запрос на текущий день (date('Y-m-d')),
     * то загружает из таблицы currencyrate_today
     * Если и там не найдено, то делает запрос к оригинальной таблице currencyrate
     * @param $date
     * @return array
     */
    private static function getRates($date): array
    {
        $date = self::getValidDate($date);
        if (!isset(self::$rates[$date])) {

            $ratesFromDb = null;
            if ($date === date('Y-m-d')) {
                // Если запрос на текущий день, то используем оптимизированную таблицу currencyrate_today
                $ratesFromDb = self::getRatesFromDbToday();
            }

            if (!$ratesFromDb) {
                // Если запрос был не на текущий день или на текущий день не удалось загрузить
                // то используем оригинальную таблицу currencyrate
                $ratesFromDb = self::getRatesFromDb($date);
            }

            self::$rates[$date] = [];

            // Запишем все загруженные курсы во внутренний кеширующий массив
            foreach ($ratesFromDb as $rate) {
                if ($rate['RateToRUB'] > 0) {
                    self::$rates[$date][$rate['CurrencyISO']] = new CurrencyRate(
                        $rate['CurrencyISO'],
                        $rate['RateToRUB'],
                        $rate['ValidDate']
                    );
                }
            }
        }

        return self::$rates[$date];
    }

    private static function getRatesFromDbToday(): array
    {
        return Db::getAll("SELECT CurrencyISO, RateToRUB, ValidDate FROM currencyrate_today");
    }

    /**
     * Всегда выполняет запрос к таблице currencyrate
     * @param string $date
     * @return array
     */
    private static function getRatesFromDb(string $date): array
    {
        $queryArr = [];

        foreach (self::getCurrencies() as $currencyIso => $_) {
            $queryArr[] = Db::parse(
                "(SELECT CurrencyISO, RateToRUB, ValidDate
                  FROM currencyrate
                  WHERE CurrencyISO = @s
                      AND ValidDate <= @s
                  ORDER BY ValidDate DESC LIMIT 0, 1)",
                $currencyIso,
                $date
            );
        }

        $query = implode(' UNION ', $queryArr);

        return Db::getAll($query);
    }

    /**
     * Возвращает отношение курсов валют на указанную дату, подгружает из базы при необходимости
     * @param string $from
     * @param string $to
     * @param null $date дата на которую загрузить курсы из базы
     * @param array|null $rates можно передать собственный массив ставок - массивк инстансов класса CurrencyRate
     * в этом случае параметр $date не используется
     * @return float
     */
    public static function getRate(string $from, string $to = 'USD', $date = null, array $rates = null): float
    {
        if ($from === $to) {
            return 1.0;
        }

        if (!$rates) {
            $rates = self::getRates($date);
        }

        //Если хотя бы одна из валют рубли, то используем и RateToRUB
        if ($from === 'RUB') {
            //Пользователь хочет перевести рубли в какую-то другую валюту
            //Например, хочет перевести рубли в доллары. В этом случае в toCurrencyIso.RateToRUB будет что-то типа 35.1234
            //А отношение RUB / USD будет соответственно 1 / 35.1234
            if (!isset($rates[$to])) {
                return 1.0;
            }
            /** @var CurrencyRate $rate */
            $rate = $rates[$to];
            if ($rate->rateToRub == 0) {
                return 1.0;
            }
            return 1 / $rate->rateToRub;
        }

        if ($to === 'RUB') {
            //Пользователь хочет перевести какую-то валюту в рубли
            //Например, хочет перевести доллары в рубли. В этом случае в fromCurrencyIso.RateToRUB будет что-то типа 35.1234
            //Его и возвращаем
            if (!isset($rates[$from])) {
                return 1;
            }
            /** @var CurrencyRate $rate */
            $rate = $rates[$from];
            return $rate->rateToRub;
        }

        // Если перевод никак не связан с рублями
        // Исходную валюту переводим в рубли, затем результат переводим в валюту назначения. Например, мы хотим перевести
        // фунты в евро. 1 фунт это примерно 1.5 евро - т.е. результат getRate('GBP', 'EUR') === 1.5
        // Сначала делаем getRate('GBP', 'RUB') === 100
        // Затем делаем getRate('RUB', 'EUR') === 1/70
        // Перемножаем 100 * (1/70) ~= 1.5!
        return self::getRate($from, 'RUB', $date, $rates) * self::getRate('RUB', $to, $date, $rates);
    }

    public static function convertValue(
        float $value,
        string $from,
        string $to = 'USD',
        $date = null,
        array $rates = null
    ): float {
        return $value * self::getRate($from, $to, $date, $rates);
    }

    /**
     * Возвращает реальную дату курса, который удовлетворяет переданным параметрам $currencyIso, $date
     * Например, если запросить курс за воскресенье ($date), а реально в базе есть курс только за предшествующую пятницу,
     * то эта функция вернёт дату пятницы
     * @param string $currencyIso
     * @param string $date
     * @return string
     */
    public static function getRealDate(string $currencyIso, $date = null): ?string
    {
        $rates = self::getRates($date);
        if (!isset($rates[$currencyIso])) {
            return null;
        }
        /** @var CurrencyRate $rate */
        $rate = $rates[$currencyIso];
        return $rate->validDate;
    }

    public static function getSymbol(string $currencyIso): string
    {
        return self::getCurrencies()[$currencyIso]['Symbol'] ?? '';
    }

    /**
     * Возвращает числовой код валюты, который нужен в оснвном для 1С
     * @param string $currencyIso
     * @return string
     */
    public static function getCurrencyCode(string $currencyIso): string
    {
        return self::getCurrencies()[$currencyIso]['Code'] ?? '';
    }

    public static function getCurrencyByCode(int $code): string
    {
        foreach (self::getCurrencies() as $currencyIso => $currencyInfo) {
            if ((int)$currencyInfo['Code'] === $code) {
                return $currencyIso;
            }
        }
        return '';
    }

    public static function putJsRates(): void
    {
        Response::addJsData([
            'CurrencyRates' => self::getRates(date('Y-m-d')),
        ]);
    }

    public static function updateCurrencyRateTodayAfterSend(): void
    {
        static $updateRegistered = false;
        if ($updateRegistered) {
            return;
        }
        ApplicationAbstract::onAfterSend(function () {
            $today = date('Y-m-d');
            // Сбрасываешь статически php-кэширующий массив, чтобы выполнились
            // реальные запросы к таблице currencyrate
            $rates = self::getRatesFromDb($today);
            $onDuplicateUpdateFieldKeys = ['RateToRub', 'ValidDate'];
            Db::insertRows($rates, 'currencyrate_today', false, false, $onDuplicateUpdateFieldKeys);
        });
        $updateRegistered = true;
    }
}
