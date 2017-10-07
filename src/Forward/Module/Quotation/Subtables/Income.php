<?php
namespace Forward\Module\Quotation\Subtables;

use Light\ObjAbstract;

class Income extends IncomeExpensesAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('quotationincome');
    }
}