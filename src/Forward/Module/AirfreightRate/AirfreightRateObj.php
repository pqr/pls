<?php
namespace Forward\Module\AirfreightRate;

use Forward\Field\GeneralFields;
use Forward\Field\RateFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class AirfreightRateObj extends ObjAbstract
{
    public function __construct()
    {
        $this->addFields(

            GeneralFields::vendorId(),
            GeneralFields::departureIata(),
            GeneralFields::departureLocationName(),
            GeneralFields::arrivalIata(),
            GeneralFields::arrivalLocationName(),
            GeneralFields::airlineAlphaCode(),
            GeneralFields::transitTime(),
            F::create('Name', F::TYPE_STRING, ['name' => 'Contract No']),
            CF::validFrom(),
            CF::validUntil(),
            CF::comments(),
            RateFields::minAmount(),
            RateFields::amountN(),
            RateFields::amount45(),
            RateFields::amount100(),
            RateFields::amount200(),
            RateFields::amount300(),
            RateFields::amount500(),
            RateFields::amount750(),
            RateFields::amount1000(),
            RateFields::amount2000(),
            RateFields::fscAmount(),
            RateFields::sscAmount(),
            CF::currency()

        );
    }
}