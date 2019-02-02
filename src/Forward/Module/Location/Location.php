<?php

namespace Forward\Module\Location;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class Location extends ModuleAbstract
{

    protected $textSaveBtn = 'Airport';
    public $tableDefaultOrderBy = 'Name';
    public $tableLayoutDefault = 'Name|NameRu|IATA|CountryCode';

    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            CF::id(),

            F::createString('Name'),

            F::createString('NameRu', 'Name (ru)'),

            F::createString('IATA', ['maxLength' => 3, 'editWidth' => 'small', 'upper' => true]),

            F::createString('CountryCode',
                ['name' => 'Country Code', 'maxLength' => 2, 'editWidth' => 'small', 'upper' => true])

        );
    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;
        $this->addFilterName(F::TYPE_STRING, [
            'name' => 'Name / IATA',
            'sqlWhere' => "Name LIKE '%{value}%' OR NameRu LIKE '%{value}%' OR IATA = '{value}'"
        ]);
    }
}
