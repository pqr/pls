<?php
namespace Forward\Module\Shipment\CostSheet\Subtables;

use Forward\Module\Shipment\CostSheet\Subtables\Base\IncomeExpensesObjAbstract;

class IncomeObj extends IncomeExpensesObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('csincome');
        parent::initStandartFields('CInvoice');
    }
}