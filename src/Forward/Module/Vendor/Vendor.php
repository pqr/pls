<?php

namespace Forward\Module\Vendor;

use Forward\Application;
use Forward\Module\Vendor\Subtables\Contacts;
use Forward\Module\Vendor\Subtables\Responsibles;
use Forward\Module\Vendor\Subtables\VendorAccounts;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class Vendor extends ModuleAbstract
{
    public $folders = true;
    protected $textSaveBtn = 'Vendor';
    public $tableLayoutDefault = 'Name|Address|CreditPeriod|CreditAmount|CreditCurrencyISO|Active';

    public function __construct()
    {
        parent::__construct();
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
    }

    protected function initSubtables()
    {
        $this->addSubtable(Responsibles::class);
        $this->addSubtable(VendorAccounts::class);
        $this->addSubtable(Contacts::class);
    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;

        $vendorType = [
            'isAirline' => 'Airline',
            'isCarrier' => 'Carrier',
            'isPortExpeditor' => 'Port Expeditor',
            'isTrucker' => 'Trucker',
        ];

        $this->addFilterText('Name');

        $this->addFilters(

            F::createSelect('FilterType', [
                'name' => 'Type',
                'options' => $vendorType,
                'sqlWhere' => "`{value}` = 1"
            ]),

            F::createSelect('Responsible', [
                'name' => 'Responsible',
                'options' => "SELECT ID, Name FROM `user` WHERE Active AND EXISTS(SELECT UserID FROM vendor_responsible WHERE UserID = user.ID) ORDER BY Name",
                'sqlWhere' => fn($value) => ($value ? '' : 'NOT ')
                    . "EXISTS (SELECT VendorID FROM vendor_responsible WHERE VendorID = vendor.ID AND UserID = " . (int)$value . ")"
            ])

        );
    }
}

