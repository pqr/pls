<?php
namespace Forward\Module\Location;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class Location extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('location');

        $this->_textObjectName = 'Airport';
        $this->_tableDefaultOrderBy = 'Name';
        $this->_tableLayouts['Default'] = 'Name|NameRu|IATA|CountryCode';

        $this->addFields(

            CF::id(),

            F::createString('Name'),

            F::createString('NameRu', 'Name (ru)'),

            F::createString('IATA', ['maxLength' => 3, 'editWidth' => 'small', 'submitToupper' => true]),

            F::createString('CountryCode', ['name' => 'Country Code', 'maxLength' => 2, 'editWidth' => 'small', 'submitToupper' => true])

        );
    }

    protected function initFilters()
    {
        $this->_filtersNoSettings = true;
        $this->addFilterName(F::TYPE_STRING, ['name' => 'Name / IATA', 'sqlWhere' => "Name LIKE '%{value}%' OR NameRu LIKE '%{value}%' OR IATA = '{value}'"]);
    }
}
