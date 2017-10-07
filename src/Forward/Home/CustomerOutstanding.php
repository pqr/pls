<?php
namespace Forward\Home;

use Forward\Classes\Constant;
use Forward\Classes\Finance;
use Forward\Field\CustomerFields;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\Link;

class CustomerOutstanding extends HomeBlockAbstract
{
    private $userGroupId;

    protected function init()
    {
        $this->_title = 'Customer Outstanding';
        $this->_description = '';
        $this->_informationFor = 'Information for Customer Responsible CS / Sales.';
        $this->_tableLinkModule = 'Customer';
        $this->_tableExcelEnabled = false;
        $this->_printHeadBlock = false;

        $this->addFields(

            F::createString('Name', ['name' => 'Customer']),
            CustomerFields::openInvoicesAmounts(),
            CustomerFields::overdueInvoicesAmounts(),
            CustomerFields::openPaymentsAmounts()

        );
    }

    /**
     * Определяем обязательный абстрактный метод
     * @param int $userId
     * @return mixed|void
     */
    protected function getTableQuery($userId)
    {
        $query = "SELECT ID 
                    , CONCAT(CreditCurrencyISO, ' ', CreditAmount) AS CreditAmount,
                " . $this->getSqlSelect($this->getFieldset()->getKeys()) . "
                  FROM customer
                  WHERE (
                            EXISTS(SELECT CustomerID
                                   FROM customer_openinvoices
                                   WHERE CustomerID = customer.ID)
                            OR
                            EXISTS(SELECT CustomerID
                                   FROM customer_openpayments
                                   WHERE CustomerID = customer.ID)
                        )
                        ";

        //Если пользователь не принадлежит группе Finance, то ему нужно ограничить вывод только его клиентами
        $userGroupId = Db::getOne("SELECT GroupID FROM user WHERE ID = @i", $userId);
        if ($userGroupId != Constant::USERGROUP_FIN) {
            $query .= " AND EXISTS(SELECT CustomerID FROM customer_responsible WHERE UserID = " . $userId . " AND CustomerID = customer.ID)";
        }

        // Сохраним группу текущего пользователя на будущее для функции geCountForTitle()
        $this->userGroupId = $userGroupId;

        $query .= " ORDER BY Name";

        return $query;
    }

    protected function getCountForTitle()
    {
        // Отобразим число просроченных счетов по клиентам на этом Home
        $customerIds = array_column($this->_tableData, 'ID');

        $query = "SELECT count(*) 
                  FROM cinvoice 
                  WHERE Status IN (".implode(',',Finance::$cinvoiceOpenStatuses).") 
                      AND DueDate < CURDATE()";

        if ($this->userGroupId != Constant::USERGROUP_FIN) {
            $customerIds = array_column($this->_tableData, 'ID');
            if ($customerIds) {
                $query .= " AND CustomerID IN (" . implode(',', $customerIds) . ")";
            }
        }

        $overdueCount = Db::getOne($query);

        return Link::moduleList($overdueCount, ['Overdue' => 'Overdue'], null, 'CInvoice', 'Число просроченных счетов');

    }

}
