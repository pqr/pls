<?php

namespace Forward\Module\BU;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class BU extends ModuleAbstract
{
    protected $textSaveBtn = 'Business Unit';
    protected $tableLayoutDefault = 'OrderID|Name';

    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            F::createString('Name', [
                'editMaxLength' => 3,
                'editWidth' => 'mini'
            ]),

            CF::orderId()

        );
    }
}
