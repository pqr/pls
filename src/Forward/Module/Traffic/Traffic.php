<?php

namespace Forward\Module\Traffic;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class Traffic extends ModuleAbstract
{
    protected $textSaveBtn = 'Traffic';
    public $tableLayoutDefault = 'OrderID|Name|FileNumberFrom|FileNumberUntil|NextFileNumber|Type';

    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('traffic');

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $typeOptions = ['avia' => 'Avia', 'ocean' => 'Ocean', 'truck' => 'Truck'];

        $this->addFields(
            F::createString('Name'),
            F::createInt('FileNumberFrom', ['name' => 'Start File Number']),
            F::createInt('FileNumberUntil', ['name' => 'End File Number']),
            F::createInt('NextFileNumber', ['name' => 'Next File Number']),
            F::createSelect('Type', ['options' => $typeOptions]),
            CF::orderId()
        );
    }
}
