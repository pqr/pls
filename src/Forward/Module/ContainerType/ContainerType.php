<?php
namespace Forward\Module\ContainerType;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class ContainerType extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('containertype');

        $this->_textObjectName = 'Container Type';
        $this->_tableDefaultOrderBy = 'OrderID';
        $this->_tableLayouts['Default'] = 'OrderID|Name|TEU';

        $this->addFields(

            CF::id(),

            F::create('Name', F::TYPE_STRING),

            F::create('TEU', F::TYPE_INT, ['editAlign' => 'left']),

            CF::orderId()

        );
    }
}
