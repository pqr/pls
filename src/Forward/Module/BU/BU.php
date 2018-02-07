<?php

namespace Forward\Module\BU;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class BU extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('bu');
        $this->_textObjectName = 'Business Unit';
        $this->tableDefaultOrderBy = 'OrderID';

        $this->addFields(

            F::createString('Name', [
                'editMaxLength' => 3,
                'editWidth' => 'mini'
            ]),

            CF::orderId()

        );

        $this->tableLayouts['Default'] = 'OrderID|Name';
    }
}
