<?php
namespace Forward\Module\AirfreightSurcharge;

use Forward\Application;
use Forward\Field\GeneralFields;
use Forward\Field\RateFields;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class AirfreightSurcharge extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->textSaveBtn = 'Airfreight Local';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
    }

    #[\Override]
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

    #[\Override]
    protected function validateUnique(array $dataFromRequest)
    {
    }
}
