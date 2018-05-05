<?php
namespace Forward\Module\AirfreightSurcharge;

use Forward\Field\GeneralFields;
use Forward\Field\RateFields;
use Light\ModuleBase\ModuleAbstract;

class AirfreightSurcharge extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->textSaveBtn = 'Airfreight Local';
    }

    protected function initFilters()
    {
        $this->addFilters(
            RateFields::name(),
            GeneralFields::vendorId(),
            GeneralFields::locationIata('IATA'),
            GeneralFields::locationName('LocationName'),
            GeneralFields::airlineAlphaCode()
        );

        $this->addFiltersDatesFromUntil('Valid', ['name' => 'Validity', 'asInterval' => true]);

        $this->filtersLayout = [
            [
                'Name',
                'VendorID'
            ],
            [
                'Valid'
            ],
            [
                'AirlineAlphaCode',
                'LocationName' => GeneralFields::filterTemplateLocation(''),

            ]
        ];
    }

    protected function validateUnique(array $dataFromRequest)
    {
    }
}
