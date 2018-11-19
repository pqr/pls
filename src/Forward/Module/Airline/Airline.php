<?php

namespace Forward\Module\Airline;

use Forward\Application;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class Airline extends ModuleAbstract
{
    protected $textSaveBtn = 'Airline';
    protected $continmethod = true;
    protected $tableLayoutDefault = 'AlphaCode|DigitalCode|Name';

    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            F::createString('Name', ['upper' => true]),

            //используем string, чтобы не стирались лидирующие нули
            F::createString('DigitalCode', [
                'name' => 'Digital Code',
                'tableAlign' => 'center',
                'editMaxLength' => 3,
                'editCssClass' => 'input-int',
                'editWidth' => 'mini'
            ]),

            F::createString('AlphaCode', [
                'name' => 'Alpha Code',
                'tableAlign' => 'center',
                'upper' => true,
                'editMaxLength' => 2,
                'editWidth' => 'mini',
                'editMandatory' => true
            ])

        );
    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;
        $this->addFilterName(F::TYPE_STRING, [
            'name' => 'Name / Code',
            'sqlWhere' => "Name LIKE '%{value}%' OR DigitalCode = '{value}' OR AlphaCode = '{value}'"
        ]);
    }

}
