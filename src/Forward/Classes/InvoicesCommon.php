<?php
namespace Forward\Classes;

use Light\Db;

class InvoicesCommon
{
    /**
     * Метод вызывается при создании счёта из Cost-sheet'а - заполняет поля CompanyID и CurrencyISO на основе отмеченных
     * галочками линий Cost-sheet'а из которых создаётся этот счёт
     * @param array $csLineIds
     * @param string $cstable
     * @return array
     */
    public static function getCurrencyFromCsLines(array $csLineIds, $cstable)
    {
        //Определим валюту счёта и компанию
        //Сначала попробуем определить по первой отмеченной галочке
        if ($csLineIds) {
            $query = "SELECT CurrencyISO
                      FROM @n
                      WHERE ID IN (@a)
                      LIMIT 1";

            $firstCslineData = Db::getRow($query, $cstable, $csLineIds);
            if ($firstCslineData) {
                return $firstCslineData;
            }
        }

        //Отмеченных галочек не было, используем тип Cost-sheet'а
        return ['CurrencyISO' => 'USD'];
    }
} 