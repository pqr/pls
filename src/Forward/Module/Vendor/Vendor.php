<?php
namespace Forward\Module\Vendor;

use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class Vendor extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->_folders = true;
        $this->_foldersNested = true;
        $this->_textObjectName = 'Vendor';
        $this->_tableLayouts['Default'] = 'Name|Address|CreditPeriod|CreditAmount|CreditCurrencyISO|Active';

        $this->registerSubtable('Responsibles');
        $this->registerSubtable('VendorAccounts');
        $this->registerSubtable('Contacts');
    }

    protected function initFilters()
    {
        $this->_filtersNoSettings = true;

        $vendorType = [
            'isAirline' => 'Airline',
            'isCarrier' => 'Carrier',
            'isPortExpeditor' => 'Port Expeditor',
            'isTrucker' => 'Trucker',
        ];

        $this->addFilterName(F::TYPE_STRING);

        $this->addFilters(

            F::createSelect('FilterType', [
                'name' => 'Type',
                'options' => $vendorType,
                'sqlWhere' => "`{value}` = 1"
            ]),

            F::createSelect('Responsible', [
                'name' => 'Responsible',
                'options' => "SELECT ID, Name FROM `user` WHERE Active AND EXISTS(SELECT UserID FROM vendor_responsible WHERE UserID = user.ID) ORDER BY Name",
                'sqlWhere' => function ($value) {
                    return ($value ? '' : 'NOT ')
                    . "EXISTS (SELECT VendorID FROM vendor_responsible WHERE VendorID = vendor.ID AND UserID = " . (int)$value . ")";
                }
            ])

        );
    }
}

