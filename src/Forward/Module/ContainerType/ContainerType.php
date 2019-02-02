<?php

namespace Forward\Module\ContainerType;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class ContainerType extends ModuleAbstract
{
    protected $textSaveBtn = 'Container Type';
    public $tableLayoutDefault = 'OrderID|Name|TEU';

    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(
            CF::id(),
            F::createString('Name'),
            F::createInt('TEU', ['editAlign' => 'left']),
            CF::orderId()
        );
    }
}
