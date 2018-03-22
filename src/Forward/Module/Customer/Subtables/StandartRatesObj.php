<?php
namespace Forward\Module\Customer\Subtables;

use Forward\Field\GeneralFields;
use Forward\Field\QuotationFields;
use Forward\Field\RateFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class StandartRatesObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('customer_standartrates');
        $this->addFields(
            CF::id(),
            QuotationFields::name(),
            GeneralFields::responsibleSalesId(),
            GeneralFields::responsibleCsId(),
            GeneralFields::natureOfGoods(),
            GeneralFields::departureIata(),
            GeneralFields::departureLocationName(),
            GeneralFields::arrivalIata(),
            GeneralFields::arrivalLocationName(),
            CF::validFrom(),
            CF::validUntil(),
            QuotationFields::commentsInternal(),
            QuotationFields::commentsCustomer(),
            GeneralFields::airlineAlphaCode(),
            GeneralFields::transitTime(),
            CF::incoterms(),
            GeneralFields::incotermsLocation(),
            GeneralFields::vendorId(false, false),

            RateFields::minAmount('Selling'),
            RateFields::minAmount('Buying'),
            RateFields::amountN('Selling'),
            RateFields::amountN('Buying'),
            RateFields::amount45('Selling'),
            RateFields::amount45('Buying'),
            RateFields::amount100('Selling'),
            RateFields::amount100('Buying'),
            RateFields::amount200('Selling'),
            RateFields::amount200('Buying'),
            RateFields::amount300('Selling'),
            RateFields::amount300('Buying'),
            RateFields::amount500('Selling'),
            RateFields::amount500('Buying'),
            RateFields::amount750('Selling'),
            RateFields::amount750('Buying'),
            RateFields::amount1000('Selling'),
            RateFields::amount1000('Buying'),
            RateFields::amount2000('Selling'),
            RateFields::amount2000('Buying'),
            RateFields::fscAmount('Selling'),
            RateFields::fscAmount('Buying'),
            RateFields::sscAmount('Selling'),
            RateFields::sscAmount('Buying'),
            CF::currency('Selling', ['default' => 'USD']),
            CF::currency('Buying', ['default' => 'USD']),

            F::createInt('CustomerID')->setModes(0)->setSqlSelectMandatory(true)

        );
    }
}
