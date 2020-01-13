<?php
namespace Forward\Module\Shipper;

use Forward\Application;
use Forward\Field\GeneralFields;
use Light\ModuleBase\ModuleAbstract;
use Light\Field\FieldAbstract as F;
use Light\User;

class Shipper extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('list_shipper');
        $this->textSaveBtn = 'Shipper';
        $this->continmethod = true;
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            F::createString('Name', ['name' => 'Shipper', 'upper' => true]),

            GeneralFields::shipmentRequestZip(),

            GeneralFields::shipmentRequestStreet(),

            GeneralFields::shipmentRequestCity(),

            GeneralFields::shipmentRequestCountry(),

            GeneralFields::shipmentRequestPhone()

        );

    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;
        $this->addFilterText('Name');
    }


}
