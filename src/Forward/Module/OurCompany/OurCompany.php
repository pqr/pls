<?php
namespace Forward\Module\OurCompany;

use Light\ModuleBase\ModuleAbstract;

class OurCompany extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->_textObjectName = 'Company';
        $this->_tableDefaultOrderBy = 'OrderID ASC';
        $this->_tableLayouts['Default'] = 'OrderID|Name';

        $this->registerSubtable('Banks');
    }
}
