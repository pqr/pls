<?php


namespace Forward\Classes;


use Light\Db;
use Light\User;

class CustomerAccess
{
    public static function getSqlWhere(string $fieldKey = "CustomerID"): string
    {
        // Найдём список клиентов к которым пользователю запрещён доступ
        $noAccessCustomerIds = self::getNoAccessCustomerIds();

        if (!$noAccessCustomerIds) {
            // Нет таких клиентов к которым у пользователя запрещён доступ
            return "TRUE";
        }

        return "$fieldKey NOT IN (" . implode(',', $noAccessCustomerIds) . ")";
    }

    public static function getSqlWhereCustomerAccount()
    {
        $noAccessCustomerAccountIds = self::getNoAccessCustomerAccountIds();
        if ($noAccessCustomerAccountIds) {
            return "ID NOT IN (" . implode(',', $noAccessCustomerAccountIds) . ")";
        }

        return "TRUE";
    }

    public static function getNoAccessCustomerIds(): array
    {
        static $noAccessCustomerIds;
        if ($noAccessCustomerIds === null) {
            $noAccessCustomerIds = Db::getCol(
                "SELECT ID
            FROM customer
            WHERE OnlyForResponsibles = 1
                AND NOT EXISTS(SELECT 1
                               FROM customer_responsible cr 
                               WHERE cr.CustomerID = customer.ID
                                   AND cr.UserID = " . User::getId() . ")"
            );
        }

        return $noAccessCustomerIds;
    }

    public static function getNoAccessCustomerAccountIds()
    {
        static $noAccessCustomerAccountIds;
        if ($noAccessCustomerAccountIds === null) {

            $noAccessCustomerAccountIds = [];
            $noAccessCustomerIds = self::getNoAccessCustomerIds();
            if ($noAccessCustomerIds) {
                $noAccessCustomerAccountIds = Db::getCol(
                    "SELECT CustomerAccountID
                    FROM customer_customeraccount
                    WHERE CustomerID IN (@a)"
                    , $noAccessCustomerIds
                );
            }
        }

        return $noAccessCustomerAccountIds;
    }
}
