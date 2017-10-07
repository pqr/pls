<?php
namespace Forward\Classes\ShipmentCriteria\Rules\Base;

use Light\Db;

abstract class RuleBase
{

    /**
     * Большенство критериев - это простые запросы к таблице shipment
     * Но если понадобится какой-то очень хитрый критерий, этот метод можно переопределить
     */
    protected static function getQuery()
    {
        return "SELECT ID FROM shipment WHERE (" . static::getQueryWhere() . ")";
    }

    /**
     * Основной метод для переопределения в классах потоках
     */
    protected static function getQueryWhere()
    {
        return '';
    }

    /**
     * В случае если вызывается метод checkShipment, который проверяет одну конкретную перевозку,
     * то к запросу $this->getQuery() приклеивается условие AND ID = ... - проверка одной перевозки
     * @param int $shipmentId
     * @return string
     */
    protected static function getQueryForCheckShipment($shipmentId)
    {
        $query = static::getQuery();
        $query .= " AND ID = " . (int)$shipmentId;

        return $query;
    }

    /**
     * Вычисляет и возвращает массив ID шипметов подпадающих под данный критерий
     * Метод используется при обновлении критерия по всем шипментам
     * @return array
     */
    public static function getShipmentIds()
    {
        $query = static::getQuery();
        return Db::getCol($query);
    }

    /**
     * Проверяет, подпадает ли указанный шипмент под этот критерий
     * Метод используется при обновлении критерия по одному шипменту
     * @param int $shipmentId
     * @return bool
     */
    public static function checkShipment($shipmentId)
    {
        $query = static::getQueryForCheckShipment($shipmentId);
        //Если результат запроса будет не пустым массивом - то возвращаем true
        return (bool)Db::getCol($query);
    }
}