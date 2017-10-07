<?php
namespace Light\Module\Currency;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class Currency extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->_textObjectName = 'Currency';
        $this->_tableDefaultOrderBy = 'OrderID ASC';
        $this->_tableLayouts['Default'] = 'OrderID|Name|Symbol|Description|DescriptionRu';

        $this->addFields(

            CF::id(),

            F::createString('Name', ['name' => 'ISO code (3 char)']),

            F::createString('Symbol'),

            F::createString('Description'),

            F::createString('DescriptionRu', ['name' => 'Description Ru']),

            CF::orderId()

        );
    }
}

