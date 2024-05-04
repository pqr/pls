<?php
namespace Forward\Module\Shipment\CostSheet\Subtables;

use Light\Db;

/**
 * В этом классе описаны методы загружающие из базы информацию по счетам относящимся к данной перевозке
 * Методы вынесены в отдельный статический класс и после первого вызова сохраняют значение полученное из базы в статических
 * переменных (типа кеширование) - это пригождается, т.к. информация о строках и счетах нужна в разные моменты в разных
 * местах Cost-sheet'а.
 *
 * Например: информация о счетах нужна в следующих случаях:
 * 1) При рендринде подтаблиц со счетами (самое очевидное)
 * 2) При проверке, еслить такой счёт для формирования ссылки в колонке InvNum в строках Cost-sheet'а, или для окраски в красный)
 * 3) Для атрибута строк Cost-sheet'а data-invid чтобы работала JavaScript подсветка строк при наведении мыши
 */
class CommonDataProvider
{
    private static $_invoices;
    private static $_invoicesPerInvNumCache;

    /**
     * Этот метод вызвыается из всех методов подряд, но загружать реальные данные из базы он будет всегод один раз
     * @param string $dbTable 'cinvoice' или 'vinvoice'
     * @param int $fileNumber
     */
    private static function load($dbTable, $fileNumber): void
    {
        //Проверим, были ли данные уже загружены из базы?
        if (isset(self::$_invoices[$dbTable][$fileNumber])) {
            //Данные уже были загружены!
            return;
        }

        $firstLetter = strtoupper($dbTable[0]);

        $query = "SELECT i.*
                    , i.Status AS StatusIco
                    , CONCAT(i.CurrencyISO, ' ', i.Amount) AS InvoiceAmountWithCurrency
                    , fa.Amount AS AmountOnShipment
                  FROM $dbTable AS i
                      JOIN {$dbTable}_file_amount AS fa ON fa.{$firstLetter}InvoiceID = i.ID
                      AND fa.FileNumber = @i
                  GROUP BY i.ID
                  ORDER BY i.DocDate, i.ID";

        $invocies = Db::getAll($query, $fileNumber);

        self::$_invoices[$dbTable][$fileNumber] = $invocies;

        //Теперь пройдём по всем внешнийм счетам разложим их по компаниям в кеширующую переменную $_invoicesPerInvNumCache
        /** @noinspection ForeachSourceInspection */
        foreach (self::$_invoices[$dbTable][$fileNumber] as $invData) {
            $invNum = $invData['Name'];
            //Добавляем префикс 'InvNum' к ключу массива, чтобы если будут числовые номера счетов с лидирующими нулями - эти нули не должны стереться
            self::$_invoicesPerInvNumCache[$dbTable][$fileNumber]['InvNum' . $invNum] = $invData;
        }
    }

    private static function getInvoices($dbTable, $fileNumber)
    {
        self::load($dbTable, $fileNumber);
        return self::$_invoices[$dbTable][$fileNumber];
    }


    public static function getCInvoices($fileNumber)
    {
        return self::getInvoices('cinvoice', $fileNumber);
    }

    public static function getVInvoices($fileNumber)
    {
        return self::getInvoices('vinvoice', $fileNumber);
    }

    private static function getInvoice($dbTable, $fileNumber, $invNum)
    {
        self::load($dbTable, $fileNumber);

        if (!isset(self::$_invoicesPerInvNumCache[$dbTable][$fileNumber]['InvNum' . $invNum])) {
            return null;
        }

        return self::$_invoicesPerInvNumCache[$dbTable][$fileNumber]['InvNum' . $invNum];
    }

    public static function getInvoiceId($dbTable, $fileNumber, $invNum)
    {
        $inv = self::getInvoice($dbTable, $fileNumber, $invNum);
        if (!$inv) {
            //Счёт не найден
            return 0;
        }

        return $inv['ID'];
    }

    public static function getInvoiceAmountOnShipmentAndCurrency($dbTable, $fileNumber, $invNum)
    {
        $inv = self::getInvoice($dbTable, $fileNumber, $invNum);
        if (!$inv) {
            //Счёт не найден
            return null;
        }

        return [$inv['CurrencyISO'], $inv['AmountOnShipment']];
    }
}