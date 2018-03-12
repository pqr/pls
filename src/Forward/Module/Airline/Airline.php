<?php

namespace Forward\Module\Airline;

use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class Airline extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('airline');
        $this->_textObjectName = 'Airline';
        $this->continmethod = true;

        $this->addFields(

            F::createString('Name', ['submitToupper' => true]),

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
                'submitToupper' => true,
                'editMaxLength' => 2,
                'editWidth' => 'mini',
                'editMandatory' => true
            ])

        );

        $this->tableLayouts['Default'] = 'AlphaCode|DigitalCode|Name';
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
