<?php

namespace Forward;

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
use Light\Db;
use Light\HomeBase\HomeBlockAbstract;
use Light\ModuleRegister;
use Light\Response;

class Application extends ApplicationAbstract
{
    public const SA_VINVOICE_ALLOW_STATUS_ACCEPT = 'VInvoice1';
    public const SA_VINVOICE_ALLOW_STATUS_CLEAR = 'VInvoice2';
    public const SA_VINVOICE_ALLOW_ALL_COMPANYID = 'VInvoice3';
    public const SA_SHIPMENT_DELETE = 'Shipment1';
    public const SA_SHIPMENT_CHANGECLOSED = 'Shipment2';
    public const SA_SHIPMENT_SAVE_WITHOUT_MANDATORY = 'SA_SHIPMENT_SAVE_WITHOUT_MANDATORY';
    public const SA_HOME_VOUTSTANDING = 'Home1';
    public const SA_HOME_CINVOICE = 'Home2';
    public const SA_HOME_VINVOICE = 'Home3';
    public const SA_HOME_CHECKATD = 'SA_HOME_CHECKATD';
    public const SA_HOME_CHECKATA = 'SA_HOME_CHECKATA';

    public const SA_UNBLOCK = 'SA_UNBLOCK';

    public const ROLE_CS = 'CS';
    public const ROLE_SALES = 'Sales';
    public const ROLE_ACCOUNTING = 'Accounting';
    public const ROLE_BUMOW = 'MOW';
    public const ROLE_BULED = 'LED';
    public const ROLE_ALLOWEXCEL = 'Excel';

    #[\Override]
    protected function init()
    {
        if ((string)Application::getParam('blocked') === '1'
            && (!isset($_GET['next']) || $_GET['next'] !== 'a=Block&ps=Unblock')
            && !isset($_POST['username'])
            && (!isset($_GET['ps']) || $_GET['ps'] !== 'Unblock')
        ) {
            Response::redirectUrl('http://yandex.ru');
        }

        Db::query("SET time_zone='+3:00'");
        Db::query("SET group_concat_max_len=65535");
        Db::query("SET sql_mode = 'ALLOW_INVALID_DATES'");
    }

    #[\Override]
    protected function registerModules()
    {
        ModuleRegister::addModule('Shipment', 'Shipments', '');
        ModuleRegister::addModule('ShipmentAccounting', 'Накладные (бухгалтерия)', '');
        ModuleRegister::addModule('DocsInventory', 'Описи переданных док-ов', '');
        ModuleRegister::addModule('Help', 'Help', '');

        ModuleRegister::addModule('Quotation', 'Quotations', 'Rates');
        ModuleRegister::addModule('DefaultAddons', 'Default Addons', 'Rates');

        ModuleRegister::addModule('Customer', 'Customers', 'Customer');
        ModuleRegister::addModule('CustomerAccount', 'Customer Accounts', 'Customer');
        ModuleRegister::addModule('CInvoice', 'Customer Invoices', 'Customer');
        ModuleRegister::addModule('CPayment', 'Customer Payments', 'Customer');

        ModuleRegister::addModule('Vendor', 'Vendors', 'Vendor');
        ModuleRegister::addModule('VendorAccount', 'Vendor Accounts', 'Vendor');
        ModuleRegister::addModule('VInvoice', 'Vendor Invoices', 'Vendor');
        ModuleRegister::addModule('VPayment', 'Vendor Payments', 'Vendor');

        ModuleRegister::addModule('Airline', 'Airlines', 'Lists');
        ModuleRegister::addModule('OceanCarrier', 'Ocean Carriers', 'Lists');
        ModuleRegister::addModule('Shipper', 'Shippers', 'Lists');
        ModuleRegister::addModule('OurCompany', 'Our Company', 'Lists');
        ModuleRegister::addModule('ContainerType', 'Container Types', 'Lists');
        ModuleRegister::addModule('Uom', 'UOMs', 'Lists');
        ModuleRegister::addModule('ItemList', 'Item List', 'Lists');
        ModuleRegister::addModule('Traffic', 'Traffics', 'Lists');
        ModuleRegister::addModule('Location', 'Airports / Locations', 'Lists');
        ModuleRegister::addModule('Port', 'Ocean Ports', 'Lists');
        ModuleRegister::addModule('EmailNoticeSettings', 'E-mail Notice Settings', 'Lists');
        ModuleRegister::addModule('Block', 'Block Base', 'System');
    }

    #[\Override]
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

    #[\Override]
    public function getUserRolesList()
    {
        return [
            self::ROLE_CS => 'Customer Service',
            self::ROLE_SALES => 'Sales',
            self::ROLE_ACCOUNTING => 'Accounting',
            self::ROLE_BUMOW => 'BU: MOW',
            self::ROLE_BULED => 'BU: LED',
            self::ROLE_ALLOWEXCEL => 'Allow Export to Excel',
        ];
    }

    #[\Override]
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
            ['Shipment', self::SA_SHIPMENT_SAVE_WITHOUT_MANDATORY, 'Allow save without mandatory fields'],
            ['VInvoice', self::SA_VINVOICE_ALLOW_STATUS_ACCEPT, 'Allow status Acceepted'],
            ['VInvoice', self::SA_VINVOICE_ALLOW_STATUS_CLEAR, 'Allow status Cleared'],
            ['VInvoice', self::SA_VINVOICE_ALLOW_ALL_COMPANYID, 'Any company - Yes; Only PLS - No.'],
            ['Block', self::SA_UNBLOCK, 'Allow Unblock'],
        ];
    }
}
