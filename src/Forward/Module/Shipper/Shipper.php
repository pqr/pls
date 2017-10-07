<?php
namespace Forward\Module\Shipper;

use Forward\Field\GeneralFields;
use Light\ModuleBase\ModuleAbstract;
use Light\Field\FieldAbstract as F;

class Shipper extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('list_shipper');
        $this->_textObjectName = 'Shipper';
        $this->continmethod = true;

        $this->addFields(

            F::createString('Name', ['name' => 'Shipper', 'submitToUpper' => true]),

            GeneralFields::shipmentRequestZip(),

            GeneralFields::shipmentRequestStreet(),

            GeneralFields::shipmentRequestCity(),

            GeneralFields::shipmentRequestCountry(),

            GeneralFields::shipmentRequestPhone()

        );

    }

    protected function initFilters()
    {
        $this->_filtersNoSettings = true;
        $this->addFilterName();
    }


}