<?php
namespace Forward\Module\Traffic;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class Traffic extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('traffic');
        $this->_textObjectName = 'Traffic';
        $this->tableDefaultOrderBy = 'OrderID';
        $this->tableLayouts['Default'] = 'OrderID|Name|FileNumberFrom|FileNumberUntil|NextFileNumber|Type';

        $typeOptions = ['avia' => 'Avia', 'ocean' => 'Ocean', 'truck' => 'Truck'];

        $this->addFields(
            F::create('Name', F::TYPE_STRING),
            F::create('FileNumberFrom', F::TYPE_INT, ['name' => 'Start File Number']),
            F::create('FileNumberUntil', F::TYPE_INT, ['name' => 'End File Number']),
            F::create('NextFileNumber', F::TYPE_INT, ['name' => 'Next File Number']),
            F::create('Type', F::TYPE_SELECT, ['options' => $typeOptions]),
            CF::orderId()
        );
    }
}
