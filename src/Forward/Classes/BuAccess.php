<?php
namespace Forward\Classes;

use Forward\Application;
use Light\Db;
use Light\User;

class BuAccess
{
    /**
     * Сравнивает BU от какого-то объекта (перевозка, котировка и т.п.) с BU к которым есть доступ у текущего пользователя
     * @param string $buids разделённые вертикальной чертой номера BU
     * @return bool
     */
    public static function hasAccess(string $buids)
    {
        if (!$buids) {
            // У объекта вообще не обозначены BU - по умолчанию даём доступ к таким объектам
            return true;
        }

        // Текущий объект каким-то образом относится к MOW и у пользователя есть роль BU: MOW
        // или
        // Текущий объект каким-то образом относится к LED и у пользователя есть роль BU: LED
        return strpos($buids, '|' . Constant::BU_MOW . '|') !== false && User::hasResponsibleRole(Application::ROLE_BUMOW)
            || strpos($buids, '|' . Constant::BU_LED . '|') !== false && User::hasResponsibleRole(Application::ROLE_BULED);
    }

    public static function hasAccessAll()
    {
        return User::hasResponsibleRole(Application::ROLE_BUMOW) && User::hasResponsibleRole(Application::ROLE_BULED);
    }

    public static function hasAccessNone()
    {
        return !User::hasResponsibleRole(Application::ROLE_BUMOW) && !User::hasResponsibleRole(Application::ROLE_BULED);
    }

    /**
     * Возвращает фрагмент SQL WHERE который проверяет, что поле BUIDs содержит допустимое для данного пользователя BU
     * Используется в модулях Customer, CustomerAccount и Shipment где в таблицах есть явное поле BUIDs
     * @return string
     */
    public static function getSqlWhereBu()
    {
        //Если у пользователя доступ ко всем BU - возвращаем TRUE, что обозначает "никаких SQL ограничений"
        if (self::hasAccessAll()) {
            return 'TRUE';
        }

        //Если у пользователя нет доступа ни к одному BU, возвращаем строку 'FALSE', которая в SQL запросе вернёт пустую выборку
        if (self::hasAccessNone()) {
            return 'FALSE';
        }

        // Сформируем правильное SQL условие ограничивающее выборку
        // Для начала добавим условие на выборку всех объектов у которых вообще не определён BU
        $sqlWhere[] = "BUIDs = ''";
        // Затем отдельные условия по MOW и LED
        if (User::hasResponsibleRole(Application::ROLE_BUMOW)) {
            $sqlWhere[] = "BUIDs LIKE '%|" . Constant::BU_MOW . "|%'";
        }
        if (User::hasResponsibleRole(Application::ROLE_BULED)) {
            $sqlWhere[] = "BUIDs LIKE '%|" . Constant::BU_LED . "|%'";
        }

        return "(" . implode(" OR ", $sqlWhere) . ")";
    }

    /**
     * Возвращает фрагмент SQL WHERE который проверят, что CustomerID входит в число допустимых для текущего пользователя
     * Используется в модулях CInvoice и CPayment, где в таблицах нет явного поля BUIDs, поэтому проверка происходит на
     * основе клиента (поля CustomerID)
     */
    public static function getSqlWhereCustomerId()
    {
        //Если у пользователя доступ ко всем BU - возвращаем TRUE, что обозначает "никаких SQL ограничений"
        if (BuAccess::hasAccessAll()) {
            return 'TRUE';
        }

        if (BuAccess::hasAccessNone()) {
            return 'CustomerID = 0';
        }

        //Узнаем к каким клиентам пользователь имеет доступ?
        $allowedCustomerIds = BuAccess::getCustomerIds();
        if (!$allowedCustomerIds) {
            // Нет ни одного клиента к которому у пользователя есть доступ! Вернём только те инвойсы и оплаты, которые
            // пока не привязаны ни к одному клиенту
            return 'CustomerID = 0';

        }

        return '(CustomerID = 0 OR CustomerID IN (' . implode(',', $allowedCustomerIds) . '))';
    }

    /**
     * Возвращает ID клиентов к которым у пользователя есть доступ
     * Этот метод вызывается напрямую при посмтроении выпающего списка CustomerID
     */
    public static function getCustomerIds()
    {
        return self::getIdsFromTable('customer');
    }

    /**
     * Возвращает ID аккаунтов к которым у пользователя есть доступ
     * Этот метод вызывается напрямую при посмтроении выпающего списка CustomerAccountID
     */
    public static function getCustomerAccountIds()
    {
        return self::getIdsFromTable('customeraccount');
    }

    /**
     * Возвращает ID записей из указанной таблицы у которых в поле BUIDs стоит один из BU к которому у текущего пользователя есть доступ
     * @param string $dbTable
     * @return array
     */
    private static function getIdsFromTable($dbTable)
    {
        $query = "SELECT ID 
                  FROM $dbTable
                  WHERE " . self::getSqlWhereBu();

        return Db::getCol($query);
    }
}
