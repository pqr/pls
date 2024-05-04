<?php
namespace Forward\Classes;

use Light\Db;
use Light\Js;
use Light\Response;

/**
 * Вспомагательный класс, который инициализирует некоторые параметры JavaScript окружения.
 *
 * В модулях Shipment и Qutation при изменении клиента нужно чтобы автоматически менялось поле Our Company в соответсвие
 * с настройками в карточке клиента. Для этого в JS окружение предадим таблицу связей и подключим специальный JS файл
 *
 * Class CompanyChildFromCustomer
 * @package Forward\Classes
 */
class CompanyChild
{
    public static function initJsCompanyChildFromCustomer(): void
    {
        $customersCompanyId = Db::getAssoc("SELECT ID, CompanyID FROM customer WHERE CompanyID != 0");
        Response::addJsData(['CustomersCompanyID' => $customersCompanyId]);
        Js::add('CompanyChildFromCustomer.js');
    }

    public static function initJsCompanyChildFromVendor(): void
    {
        $vendorsCompanyId = Db::getAssoc("SELECT ID, CompanyID FROM vendor WHERE CompanyID != 0");
        Response::addJsData(['VendorsCompanyID' => $vendorsCompanyId]);
        Js::add('CompanyChildFromVendor.js');
    }
} 