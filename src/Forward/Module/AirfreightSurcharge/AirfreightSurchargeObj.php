<?php
namespace Forward\Module\AirfreightSurcharge;

use Forward\Field\GeneralFields;
use Forward\Field\RateFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class AirfreightSurchargeObj extends ObjAbstract
{
    public function __construct()
    {

        $this->addFields(
            F::createString(
                'Name',
                [F::PARAM_AC_SOURCE => 'list_chargedescription', F::PARAM_AC_READONLY => true]
            ),
            GeneralFields::vendorId(),
            GeneralFields::locationIata('IATA'),
            GeneralFields::locationName('LocationName'),
            GeneralFields::airlineAlphaCode(),
            GeneralFields::incoterms(),
            GeneralFields::incotermsLocationName(),
            CF::validFrom(),
            CF::validUntil(),
            CF::comments(),
            RateFields::baseAmount(),
            RateFields::minAmount(),
            RateFields::maxAmount(),
            RateFields::perKgAmount(),
            CF::currency()

        );

    }
}