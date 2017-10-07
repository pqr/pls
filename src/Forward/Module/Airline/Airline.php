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

            F::create('Name', F::TYPE_STRING, ['submitToupper' => true]),

            F::create(
                'DigitalCode',
                F::TYPE_STRING, //используем string, чтобы не стирались лидирующие нули
                [
                    'name' => 'Digital Code',
                    'tableAlign' => 'center',
                    'editMaxLength' => 3,
                    'editCssClass' => 'input-int',
                    'editWidth' => 'mini'
                ]
            ),

            F::create(
                'AlphaCode',
                F::TYPE_STRING,
                [
                    'name' => 'Alpha Code',
                    'tableAlign' => 'center',
                    'submitToupper' => true,
                    'editMaxLength' => 2,
                    'editWidth' => 'mini',
                    'editMandatory' => true
                ]
            )

        );

        $this->_tableLayouts['Default'] = 'AlphaCode|DigitalCode|Name';
    }

    protected function initFilters()
    {
        $this->_filtersNoSettings = true;
        $this->addFilterName(F::TYPE_STRING, ['name' => 'Name / Code', 'sqlWhere' => "Name LIKE '%{value}%' OR DigitalCode = '{value}' OR AlphaCode = '{value}'"]);
    }

}
