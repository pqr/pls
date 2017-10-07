<?php
namespace Forward;

use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Forward\Classes\ShipmentGp;
use Forward\Home\CActNotReceived;
use Forward\Home\CheckATA;
use Forward\Home\CheckATD;
use Forward\Home\CheckVInvoice;
use Forward\Home\CInvoice;
use Forward\Home\CustomerOutstanding;
use Forward\Home\MakeInvoice;
use Forward\Home\PendingQuotations7days;
use Forward\Home\ShipmentsWithoutCs;
use Forward\Home\ToBeClosed;
use Forward\Home\VendorOutstanding;
use Light\ApplicationAbstract;
use Light\HomeBase\HomeBlockAbstract;
use Light\ModuleRegister;

class Application extends ApplicationAbstract
{
    const SA_VINVOICE_ALLOW_STATUS_ACCEPT = 'VInvoice1';
    const SA_VINVOICE_ALLOW_STATUS_CLEAR = 'VInvoice2';
    const SA_VINVOICE_ALLOW_ALL_COMPANYID = 'VInvoice3';
    const SA_SHIPMENT_DELETE = 'Shipment1';
    const SA_SHIPMENT_CHANGECLOSED = 'Shipment2';
    const SA_HOME_VOUTSTANDING = 'Home1';
    const SA_HOME_CINVOICE = 'Home2';
    const SA_HOME_VINVOICE = 'Home3';
    const SA_HOME_CHECKATD = 'SA_HOME_CHECKATD';
    const SA_HOME_CHECKATA = 'SA_HOME_CHECKATA';

    const ROLE_CS = 'CS';
    const ROLE_SALES = 'Sales';
    const ROLE_ACCOUNTING = 'Accounting';
    const ROLE_BUMOW = 'MOW';
    const ROLE_BULED = 'LED';

    public static $flushShipmentGp  = false;

    // Флаг для запуска ShipmentCriteria::flush()
    public static $flushShipmentCriteria = false;

    protected function init()
    {
    }

    protected function registerModules()
    {
        ModuleRegister::addModule('Shipment', 'Shipments', '');
        ModuleRegister::addModule('ShipmentAccounting', 'Накладные (бухгалтерия)', '');
        ModuleRegister::addModule('Help', 'Help', '');

        ModuleRegister::addModule('Quotation', 'Quotations', 'Rates');
        ModuleRegister::addModule('DefaultAddons', 'Default Addons', 'Rates');
        ModuleRegister::addModule('AirfreightRate', 'Airfreight Rates', 'Rates');
        ModuleRegister::addModule('AirfreightSurcharge', 'Airfreight Locals', 'Rates');

        ModuleRegister::addModule('SearchAirfreightRateDialog', 'Search Airfreight Rates', 'Rates',
            ModuleRegister::MODULE_HIDDEN); //скрытый модуль, который обрабатывает запрос к диалогу поиска покупных тарифов

        ModuleRegister::addModule('Customer', 'Customers', 'Customer');
        ModuleRegister::addModule('CustomerAccount', 'Customer Accounts', 'Customer');
        ModuleRegister::addModule('CInvoice', 'Customer Invoices', 'Customer');
        ModuleRegister::addModule('CPayment', 'Customer Payments', 'Customer');
        ModuleRegister::addModule('ImportCPaymentFrom1CLog', 'История оплат из 1С', 'Customer');

        ModuleRegister::addModule('Vendor', 'Vendors', 'Vendor');
        ModuleRegister::addModule('VendorAccount', 'Vendor Accounts', 'Vendor');
        ModuleRegister::addModule('VInvoice', 'Vendor Invoices', 'Vendor');
        ModuleRegister::addModule('VPayment', 'Vendor Payments', 'Vendor');

        ModuleRegister::addModule('Airline', 'Airlines', 'Lists');
        ModuleRegister::addModule('OceanCarrier', 'Ocean Carriers', 'Lists');
        ModuleRegister::addModule('Shipper', 'Shippers', 'Lists');
        ModuleRegister::addModule('OurCompany', 'Our Company', 'Lists');
        ModuleRegister::addModule('ContainerType', 'Container Types', 'Lists');
        ModuleRegister::addModule('Incoterm', 'Incoterms', 'Lists');
        ModuleRegister::addModule('Uom', 'UOMs', 'Lists');
        ModuleRegister::addModule('ItemList', 'Item List', 'Lists');
        ModuleRegister::addModule('Traffic', 'Traffics', 'Lists');
        ModuleRegister::addModule('Location', 'Airports / Locations', 'Lists');
        ModuleRegister::addModule('Port', 'Ocean Ports', 'Lists');
        ModuleRegister::addModule('EmailNoticeSettings', 'E-mail Notice Settings', 'Lists');
        ModuleRegister::addModule('CurrencyRateCitadele', 'Curr. Rates Citadele', 'Currency');
    }

    public function getHomeBlocks()
    {
        return [
            [ShipmentsWithoutCs::class, HomeBlockAbstract::CSSCLASS_GREEN2],
            [CustomerOutstanding::class, HomeBlockAbstract::CSSCLASS_GREEN],
            [PendingQuotations7days::class, HomeBlockAbstract::CSSCLASS_PURPLE2],
            [VendorOutstanding::class, HomeBlockAbstract::CSSCLASS_ORANGE2],
            [CheckVInvoice::class, HomeBlockAbstract::CSSCLASS_ORANGE],
            [CActNotReceived::class, HomeBlockAbstract::CSSCLASS_PURPLE],
            [CInvoice::class, HomeBlockAbstract::CSSCLASS_BLUE],
            [CheckATD::class, HomeBlockAbstract::CSSCLASS_BLUE],
            [CheckATA::class, HomeBlockAbstract::CSSCLASS_BLUE2],
            [MakeInvoice::class, HomeBlockAbstract::CSSCLASS_GREEN2],
            [ToBeClosed::class, HomeBlockAbstract::CSSCLASS_PURPLE2],
        ];
    }

    public function getUserRolesList()
    {
        return [
            self::ROLE_CS => 'Customer Service',
            self::ROLE_SALES => 'Sales',
            self::ROLE_ACCOUNTING => 'Accounting',
            self::ROLE_BUMOW => 'BU: MOW',
            self::ROLE_BULED => 'BU: LED',
        ];
    }

    public function getSpecialAccessList()
    {
        return [
            ['Home', self::SA_HOME_VOUTSTANDING, 'Show Vendor Outstanding'],
            ['Home', self::SA_HOME_CINVOICE, 'Show Customer Invoices'],
            ['Home', self::SA_HOME_VINVOICE, 'Show Vendor Invoices'],
            ['Home', self::SA_HOME_CHECKATD, 'Show "Check ATD"'],
            ['Home', self::SA_HOME_CHECKATA, 'Show "Check ATA"'],
            ['Shipment', self::SA_SHIPMENT_DELETE, 'Allow delete'],
            ['Shipment', self::SA_SHIPMENT_CHANGECLOSED, 'Allow change after close'],
            ['VInvoice', self::SA_VINVOICE_ALLOW_STATUS_ACCEPT, 'Allow status Acceepted'],
            ['VInvoice', self::SA_VINVOICE_ALLOW_STATUS_CLEAR, 'Allow status Cleared'],
            ['VInvoice', self::SA_VINVOICE_ALLOW_ALL_COMPANYID, 'Any company - Yes; Only PLS - No.'],
        ];
    }

    protected function flushCaches()
    {
        parent::flushCaches(); // TODO: Change the autogenerated stub

        if (self::$flushShipmentGp) {
            // Обновляет задолженонсть клиента и сумму открытых оплат
            ShipmentGp::flush();
        }

        if (self::$flushShipmentCriteria) {
            ShipmentCriteria::flush();
        }
    }
}
