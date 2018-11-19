<?php
namespace Light\Module\Currency;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class Currency extends ModuleAbstract
{

protected $textSaveBtn = 'Currency';
protected $tableLayoutDefault = 'OrderID|Name|Symbol|Description';

    public function __construct()
    {
        parent::__construct();

        $this->addFields(

            F::createString('Name', [
                'name' => 'ISO code (3 char)',
                'editMaxLength' => 3,
                'editWidth' => 'small',
                'upper' => true
            ]),

            F::createString('Symbol', [
                'editMaxLength' => 3,
                'editWidth' => 'small',
            ]),

            F::createString('Description'),

            CF::orderId()

        );
    }
}

