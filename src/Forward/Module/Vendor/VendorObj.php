<?php
namespace Forward\Module\Vendor;

use Forward\Field\GeneralFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class VendorObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('vendor');

        $this->addFields(

            CF::id(),

            F::create('Name', F::TYPE_STRING, ['name' => 'Vendor Name', 'submitToupper' => true]),

            CF::active(),

            CF::address()->setParam('submitToupper', true),

            F::create('Contacts', F::TYPE_TEXT, ['clickable' => true]),

            GeneralFields::companyId(),

            F::createMoney('CreditAmount'),

            CF::currency('Credit'),

            F::create('CreditPeriod', F::TYPE_INT, ['name' => 'Credit Period', 'uom' => 'days']),

            F::create('isAirline', F::TYPE_CHECKBOX, ['name' => 'Airline']),

            F::create('isCarrier', F::TYPE_CHECKBOX, ['name' => 'Ocean Carrier']),

            F::create('isPortExpeditor', F::TYPE_CHECKBOX, ['name' => 'Port Expeditor']),

            F::create('isTrucker', F::TYPE_CHECKBOX, ['name' => 'Trucker']),

            CF::comments()

        );
    }
}
