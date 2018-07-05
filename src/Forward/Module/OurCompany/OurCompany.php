<?php
namespace Forward\Module\OurCompany;

use Forward\Application;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class OurCompany extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
        $this->textSaveBtn = 'Company';
        $this->tableDefaultOrderBy = 'OrderID ASC';
        $this->tableLayouts['Default'] = 'OrderID|Name';

        $this->registerSubtable('Banks');
    }
}
