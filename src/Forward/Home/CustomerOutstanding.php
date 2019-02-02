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
    protected static $title = 'Customer Outstanding';
    protected static $description = '';
    protected static $informationFor = 'Information for Customer Responsible CS / Sales.';


    private $userGroupId;

    public function __construct(int $userId)
    {
        parent::__construct($userId);
        
        $this->tableLinkModule = 'Customer';
        $this->allowExportToExcel = false;
        $this->allowTablePrintHeadBlock = false;

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
    protected function getDataQuery(int $userId)
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
        $query = "SELECT count(*) 
                  FROM cinvoice 
                  WHERE Status IN (" . implode(',', Finance::$cinvoiceOpenStatuses) . ") 
                      AND DueDate < CURDATE()";

        if ($this->userGroupId != Constant::USERGROUP_FIN) {
            $customerIds = array_column($this->tableData, 'ID');
            if ($customerIds) {
                $query .= " AND CustomerID IN (" . implode(',', $customerIds) . ")";
            }
        }

        $overdueCount = Db::getOne($query);

        return Link::create('CInvoice', $overdueCount)
            ->filters(['Overdue' => 'Overdue'])
            ->title('Число просроченных счетов')
            ->getHtml();

    }

}
