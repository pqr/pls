<?php
namespace Forward\Module\Incoterm;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class Incoterm extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('incoterm');

        $this->_textObjectName = 'Incoterm';
        $this->tableDefaultOrderBy = 'OrderID';

        $this->addFields(

            F::create('Name', F::TYPE_STRING, array('editMaxLength' => 3, 'submitToupper' => true)),

            F::create('Description', F::TYPE_STRING),

            CF::orderId()

        );
    }
}
