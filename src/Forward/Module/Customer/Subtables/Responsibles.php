<?php
namespace Forward\Module\Customer\Subtables;

use Forward\Classes\SubtablesBase\ResponsiblesSubtableAbstract;
use Light\ObjAbstract;

class Responsibles extends ResponsiblesSubtableAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('customer_responsible');
    }
}
