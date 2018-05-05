<?php
namespace Forward\Module\OurCompany;

use Light\ModuleBase\ModuleAbstract;

class OurCompany extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->textSaveBtn = 'Company';
        $this->tableDefaultOrderBy = 'OrderID ASC';
        $this->tableLayouts['Default'] = 'OrderID|Name';

        $this->registerSubtable('Banks');
    }
}
