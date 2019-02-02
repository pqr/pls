<?php

namespace Forward\Module\OurCompany;

use Forward\Application;
use Forward\Module\OurCompany\Subtables\Banks;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class OurCompany extends ModuleAbstract
{
    protected $textSaveBtn = 'Company';
    public $tableLayoutDefault = 'OrderID|Name';

    public function __construct()
    {
        parent::__construct();
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
    }

    protected function registerSubtables()
    {
        $this->registerSubtable(Banks::class);
    }
}
