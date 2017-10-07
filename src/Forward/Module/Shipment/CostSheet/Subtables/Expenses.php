<?php
namespace Forward\Module\Shipment\CostSheet\Subtables;

use Forward\Module\Shipment\CostSheet\Subtables\Base\IncomeExpensesAbstract;
use Light\ObjAbstract;

class Expenses extends IncomeExpensesAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->_textAddLink = 'Add Expenses Row';
        $this->_tableTotalRowData = [
            'Name' => 'Total Expenses',
            'Amount' => 'USD ' . $this->_masterObj['CacheCSExpensesinUSD']
        ];

        $this->_obj = new ExpensesObj();
        //Инжектим номер текущего файла - понадобится для формирования ссылок на номерах счетов
        $this->_obj->setFileNumber($this->_masterObj['Name']);
        //Также, сохраним внутри объекта ссылку на подтаблицу - понадобится для метода 'getHtmlFunction' в поле Amount
        $this->_obj->setSubtable($this);
    }

    protected function getInvIdForRow(array $rowData)
    {
        if (!isset($rowData['InvNum']) || !$rowData['InvNum']) {
            return 0;
        }

        return CommonDataProvider::getInvoiceId(
            'vinvoice',
            $this->_masterObj['Name'],
            $rowData['InvNum']
        );
    }
}
