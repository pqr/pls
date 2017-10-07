<?php
namespace Forward\Module\Shipment\CostSheet\Subtables;

use Forward\Module\Shipment\CostSheet\Subtables\Base\IncomeExpensesObjAbstract;

class ExpensesObj extends IncomeExpensesObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('csexpenses');
        parent::initStandartFields('VInvoice');
    }
}