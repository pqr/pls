<?php
namespace Forward\Classes\SubtablesBase;

use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

/**
 * Обрабатывает и выводит блоки:
 * Блок Customer Accounts в модуле Customers
 * Блок Customers в модуле Customer Accounts
 * Блок Vendor Accounts в модуле Vendors
 * Блок Vendors в модуле Vendor Accounts
 */
class Many2ManyAbstract extends Subtable
{
    protected $_masterText; //Тест для записи в логи, например 'Customer'

    protected $_slaveField; //Ключевое поле в таблице связей по которому определяется связанный объект, например
    protected $_slaveTable; //Таблица второго объекта для записи в логи, например 'customeraccount'
    protected $_slaveText; //Тест для записи в логи, например 'Customer Account'
    protected $_slaveModule; //Модуль второго объекта, нужне для записи в логи, например CustomerAccount

    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->tableDefaultOrderBy = 'Name';

        $this->allowExportToExcel = false;
        $this->allowTableLayouts = false;
        $this->allowTablePrintHeadBlock = false;
        $this->allowPaste = false;
    }

    /**
     * Переопределяем запрос на выборку, чтобы включить сортировку по полю Name в _slaveTable
     * @return string
     */
    #[\Override]
    protected function getDataQuery()
    {
        $query = "SELECT " . $this->getDbTable() . ".*
                  FROM " . $this->getDbTable() . "
                      JOIN " . $this->_slaveTable . " ON " . $this->_slaveTable . ".ID = " . $this->getDbTable() . "." .
            $this->_slaveField . "
                  WHERE " . $this->getMasterFieldKey() . "=" . $this->_masterObj->getId() . " AND " .
            $this->getMasterFieldKey() . " != 0
                 ORDER BY " . $this->_slaveTable . ".Name";

        return $query;
    }
}
