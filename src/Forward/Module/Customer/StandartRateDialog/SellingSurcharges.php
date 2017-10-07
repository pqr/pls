<?php
namespace Forward\Module\Customer\StandartRateDialog;

use Light\ObjAbstract;

class SellingSurcharges extends SurchargesAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('customer_standratrates_sellingsurcharges');
    }
}