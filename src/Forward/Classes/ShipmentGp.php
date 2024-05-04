<?php

namespace Forward\Classes;

use Forward\Application;
use Light\ApplicationAbstract;
use Light\Currency;
use Light\Db;

/**
 * Вспомагательный класс в котором собраны методы отвечающие за обновление GP, EGP и Deviation по шипментам
 * Class ShipmentGp
 * @package Forward\Classes
 */
class ShipmentGp
{
    /** @var array */
    private static $_buffer;

    /**
     * Вспомагательный метод, который принимает массив номеров файлов и последовательно вызывает метод  self::updateGpEgpDeviation()
     * @param array $fileNumbers
     */
    public static function updateGpEgpDeviationByFileNumbers(array $fileNumbers): void
    {
        foreach ($fileNumbers as $fileNumber) {
            self::updateGpEgpDeviation($fileNumber);
        }
    }

    /**
     * @param int $fileNumber
     */
    public static function updateGpEgpDeviation($fileNumber): void
    {
        //Если буфер ещё не инициалирован, значит это вообще первое обращение к ShipmentGp::updateGpEgpDeviation()
        //Одновременно с этим регистрируем обработчик ApplicationAbstract::addFlushOnTerminate()
        if (self::$_buffer === null) {
            self::$_buffer = [];
            ApplicationAbstract::onAfterSend([self::class, 'flush']);
        }

        self::$_buffer[$fileNumber] = true;
    }

    public static function flush($defaultConvertionDate = null): void
    {
        if (!self::$_buffer) {
            //Ничего в буфере нет
            return;
        }

        //Для начала получим все ID перевозок из базы на основе известных нам номеров файлов
        $fileNumbers = array_keys(self::$_buffer);

        //Ассоциативный массив FileNumber => ID
        $shipmentIdsPerFileNumber = Db::getAssoc(
            "SELECT Name, ID FROM shipment WHERE Name IN (@a)",
            $fileNumbers
        );

        if ($defaultConvertionDate === null) {
            $defaultConvertionDate = date('Y-m-d');
        }

        foreach ($fileNumbers as $fileNumber) {
            if (!isset($shipmentIdsPerFileNumber[$fileNumber])) {
                //Для данного номера файла почему-то не нашлось ID в базе - не будем его обновлять
                continue;
            }
            $shipmentId = $shipmentIdsPerFileNumber[$fileNumber];

            //Произведём непосредственную запись в базу данных
            self::flushFile($shipmentId, $fileNumber, $defaultConvertionDate);
        }

        //На всякий случай очистим буфер
        self::$_buffer = [];
    }

    private static function flushFile($shipmentId, $fileNumber, $defaultConvertionDate): void
    {
        $customerInvoices = self::loadCustomerInvoices($fileNumber);
        $vendorInvoices = self::loadVendorInvoices($fileNumber);
        $csIncomeLines = self::loadCsLines($shipmentId, 'income');
        $csExpensesLines = self::loadCsLines($shipmentId, 'expenses');

        if (self::isOneCurrency($customerInvoices, $vendorInvoices)) {
            $minDocDate = self::minDocDate($customerInvoices, $vendorInvoices);

            $actualIncome = self::getActualIncomeExpensesOneRate($customerInvoices, $minDocDate);
            $actualExpenses = self::getActualIncomeExpensesOneRate($vendorInvoices, $minDocDate);
        } else {
            $actualIncome = self::getActualIncomeExpenses($customerInvoices);
            $actualExpenses = self::getActualIncomeExpenses($vendorInvoices);
        }

        // Перед расчётом Estimated значений (которые переводятся в USD по некоему курсу)
        // проверим, возможно все Estimated расходы и все доходы в одной валюте?
        // Если так, то не будем использовать разные курсы покупки и продажи, будем использовать единый курс!
        if (self::isOneCurrency($csIncomeLines, $csExpensesLines)) {
            $minDocDate = self::minDocDate($customerInvoices, $vendorInvoices);

            $estimatedIncome = self::getEstimatedIncomeExpensesOneRate($csIncomeLines, $minDocDate);
            $estimatedExpenses = self::getEstimatedIncomeExpensesOneRate($csExpensesLines, $minDocDate);
        } else {
            $estimatedIncome = self::getEstimatedIncomeExpenses(
                $csIncomeLines,
                $customerInvoices,
                'income',
                $defaultConvertionDate
            );
            $estimatedExpenses = self::getEstimatedIncomeExpenses(
                $csExpensesLines,
                $vendorInvoices,
                'expenses',
                $defaultConvertionDate
            );
        }

        self::updateShipment(
            $shipmentId,
            $actualIncome,
            $actualExpenses,
            $estimatedIncome,
            $estimatedExpenses
        );
    }

    private static function loadCustomerInvoices($fileNumber)
    {
        return Db::getAll(
            "SELECT cinvoice.ID
                , cinvoice.Name
                , cinvoice.CurrencyISO
                , cinvoice.DocDate
                , cinvoice.CacheLastPaymentDate
                , cfa.Amount AS AmountOnShipment
                , cfa.AmountinUSD AS AmountOnShipmentinUSD
             FROM cinvoice, cinvoice_file_amount cfa
             WHERE cfa.FileNumber = @s_fileNumber
                 AND cinvoice.Status != " . Finance::INVOICESTATUS_DELETED . "
                 AND cinvoice.Status != " . Finance::INVOICESTATUS_CREDITNOTED . "
                 AND cfa.CInvoiceID = cinvoice.ID",
            $fileNumber
        );
    }

    private static function loadVendorInvoices($fileNumber)
    {
        return Db::getAll(
            "SELECT vinvoice.ID
                , vinvoice.Name
                , vinvoice.CurrencyISO
                , vinvoice.DocDate
                , vinvoice.CacheLastPaymentDate
                , vfa.Amount AS AmountOnShipment
                , vfa.AmountinUSD AS AmountOnShipmentinUSD
             FROM vinvoice, vinvoice_file_amount vfa
             WHERE vfa.FileNumber = @s_fileNumber
                 AND vinvoice.Status != " . Finance::INVOICESTATUS_DELETED . "
                 AND vinvoice.Status != " . Finance::INVOICESTATUS_CREDITNOTED . "
                 AND vfa.VInvoiceID = vinvoice.ID",
            $fileNumber
        );
    }

    private static function loadCsLines($shipmentId, $incomeExpenses)
    {
        return Db::getAll(
            "SELECT Amount, CurrencyISO, InvNum
             FROM cs" . $incomeExpenses . "
             WHERE ShipmentID = " . $shipmentId
        );
    }

    private static function getActualIncomeExpensesOneRate(array $invoices, $minDocDate)
    {
        // Посчитаем доход/расход
        $total = 0;
        foreach ($invoices as $invoice) {
            $total += Currency::convertValue(
                $invoice['AmountOnShipment'],
                $invoice['CurrencyISO'],
                'USD',
                $minDocDate
            );
        }

        return $total;
    }

    private static function getActualIncomeExpenses(array $invoices)
    {
        //Посчитаем сумму по всем переданным счетам в указанной валюте
        return array_sum(array_column($invoices, 'AmountOnShipmentinUSD'));
    }

    private static function isOneCurrency(array $csIncomeLines, array $csExpensesLines): bool
    {
        if (!$csIncomeLines || !$csExpensesLines) {
            return false;
        }

        $currencies = array_merge(
            array_column($csExpensesLines, 'CurrencyISO'),
            array_column($csIncomeLines, 'CurrencyISO')
        );
        $uniqueCurrencies = array_unique($currencies);

        return count($uniqueCurrencies) <= 1;
    }

    private static function minDocDate(array $customerInvoices, array $vendorInvoices)
    {
        $docDates = array_merge(
            array_column($customerInvoices, 'DocDate'),
            array_column($vendorInvoices, 'DocDate')
        );

        $minDocDate = '';
        if ($docDates) {
            $minDocDate = min($docDates);
        }

        if (!$minDocDate || $minDocDate === '0000-00-00') {
            $minDocDate = date('Y-m-d');
        }

        return $minDocDate;
    }

    private static function getEstimatedIncomeExpensesOneRate(array $csLines, $minDocDate)
    {
        // Посчитаем доход/расход
        $total = 0;
        foreach ($csLines as $csLine) {
            $total += Currency::convertValue($csLine['Amount'], $csLine['CurrencyISO'], 'USD', $minDocDate);
        }

        return $total;
    }

    private static function getEstimatedIncomeExpenses(
        array $csLines,
        array $invoices,
        string $incomeOrExpenses,
        $defaultConvertionDate
    ) {
        //Посчитаем доход/расход
        $total = 0;
        foreach ($csLines as $csLine) {
            if ($csLine['CurrencyISO'] === 'USD') {
                //Перевод в какую-то другую валюту не требуется
                $csLineAmountInUsd = $csLine['Amount'];
            } else {
                $csLineAmountInUsd = self::convertCsLineAmountIntoUsd(
                    $csLine,
                    $invoices,
                    $incomeOrExpenses,
                    $defaultConvertionDate
                );
            }

            $total += $csLineAmountInUsd;
        }

        return $total;
    }

    private static function convertCsLineAmountIntoUsd(
        array $csLine,
        array $invoices,
        string $incomeOrExpenses,
        $defaultConvertionDate
    ) {
        //Переведём сумму строки в Cost-sheet $csLine['Amount'] из валюты $csLine['CurrencyISO']
        //в требуемую валюту $currencyIso
        $convertDate = $defaultConvertionDate;
        if ($csLine['InvNum']) {
            //Попробуем определить курс пересчёта из массива счетов $invoices
            //Счета уже преесчитаны в USD в кеширующих таблицах cinvoice_file_amount и vinvoice_file_amount
            //Требуемая валюта для подсчёта Total это тоже всегда USD
            //Таким образом первод валюты текущей строки (это может быть любая валюта, например EUR)
            //в требуемую валюту USD с помощью счёта возможен только в том случае, если
            //валюта текущей строки совпадает с валютой счёта к которой она привязана

            //Алгоритм:
            //1 Найти в массиве $customerInvoices счёт соответсвующий текущй строке $csIncomeLine по номру счёта (InvNum = Name)
            //2 Попробуем вычислить курс на основе его полей AmountOnShipment, AmountOnShipmentinUSD
            $foundInvoice = false;
            foreach ($invoices as $invoice) {
                if ($invoice["Name"] == $csLine['InvNum']) {
                    $foundInvoice = $invoice;
                    break;
                }
            }

            if ($foundInvoice) {
                $invAmount = (float)$foundInvoice['AmountOnShipment'];
                $invAmountInUsd = (float)$foundInvoice['AmountOnShipmentinUSD'];
                if ($invAmount && $invAmountInUsd) {
                    //Найден счёт по совпадению номера, попробуем определить курс
                    //Мы можем использовать курс внутри счёта только в том случае, если валюта счёта совпадает с валютой строки
                    if ($foundInvoice['CurrencyISO'] == $csLine['CurrencyISO']) {
                        //Повезло!
                        //Требуемая валюта - это USD - используем для расчёта курса показатели $invAmount и $invAmountInUsd
                        $rate = $invAmountInUsd / $invAmount;

                        //Наконец, используем полученный курс, чтобы пересчитать сумму текущей строки
                        return $csLine['Amount'] * $rate;
                    }
                }

                //Если доошли до этого места, значит определить курс пересчёта по сумме счёта не удалось.
                //Будем использовать справочник курсов Citadele на дату оплаты или на дату счёта
                if ($foundInvoice['CacheLastPaymentDate'] !== '0000-00-00' && $foundInvoice['CacheLastPaymentDate'] !== null) {
                    $convertDate = $foundInvoice['CacheLastPaymentDate'];
                } elseif ($foundInvoice['DocDate'] != '0000-00-00') {
                    $convertDate = $foundInvoice['DocDate'];
                }
            }
        }

        return Currency::convertValue((float)$csLine['Amount'], $csLine['CurrencyISO'], 'USD', $convertDate);

        //Если дошли до этого места, значит continue выше не сработал - используем простой пересчёт по справочнику валют
//        if ($incomeOrExpenses === 'income') {
//            //Доходная часть. Мы получили от клиента какую-то странную валюту и теперь продаём её банку, чтобы
//            //получились доллары
//            return CitadeleCurreny::sellSomethingBuyUsd($csLine['Amount'], $csLine['CurrencyISO'], $convertDate);
//        } else {
//            //Расходная часть. Мы заплатили подрядчику в какой-то странной валюте, которую нам нужно было купить
//            //за доллары. Узнаем кол-во требуемых долларов
//            return CitadeleCurreny::buySomethingSellUsd($csLine['Amount'], $csLine['CurrencyISO'], $convertDate);
//        }
    }

    private static function updateShipment(
        $shipmentId,
        $actualIncome,
        $actualExpenses,
        $estimatedIncome,
        $estimatedExpenses
    ): void {
        $gp = $actualIncome - $actualExpenses;
        $egp = $estimatedIncome - $estimatedExpenses;
        $deviation = $gp - $egp;

        $fieldsData = [
            'CacheIncomeinUSD' => $actualIncome,
            'CacheExpensesinUSD' => $actualExpenses,
            'CacheCSIncomeinUSD' => $estimatedIncome,
            'CacheCSExpensesinUSD' => $estimatedExpenses,
            'CacheGPinUSD' => $gp,
            'CacheEGPinUSD' => $egp,
            'CacheGPDeviationinUSD' => $deviation
        ];
        Db::updateRow($fieldsData, 'shipment', $shipmentId);
    }
}
