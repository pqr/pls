<?php
namespace Forward\Module\Shipment\CostSheet\Subtables;

use Forward\Module\Shipment\CostSheet\Subtables\Base\IncomeExpensesAbstract;
use Light\ObjAbstract;

class Income extends IncomeExpensesAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->textAddLink = 'Add Income Row';
        $this->tableDataTotal = [
            'Name' => 'Total Income',
            'Amount' => 'USD ' . $this->_masterObj['CacheCSIncomeinUSD']
        ];

        $this->_obj = new IncomeObj();
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
            'cinvoice',
            $this->_masterObj['Name'],
            $rowData['InvNum']
        );
    }
}
