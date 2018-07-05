<?php

namespace Forward\Module\Incoterm;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class Incoterm extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('incoterm');

        $this->textSaveBtn = 'Incoterm';
        $this->tableDefaultOrderBy = 'OrderID';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            F::create('Name', F::TYPE_STRING, array('editMaxLength' => 3, 'upper' => true)),

            F::create('Description', F::TYPE_STRING),

            CF::orderId()

        );
    }
}
