<?php
namespace Forward\Home;

use Forward\Field\GeneralFields;
use Forward\Field\QuotationFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;

class PendingQuotations7days extends HomeBlockAbstract
{
    protected function init()
    {
        $this->_title = 'Pending Quotations';
        $this->_description = 'Котировки находящиеся в статусе Pending или Draft 2 или более дней с даты создания';
        $this->_informationFor = 'Информация для Quotation Responsible CS и Sales.';
        $this->_tableLinkModule = 'Quotation';

        $this->addFields(
            CF::id(),
            QuotationFields::name(),
            GeneralFields::customerId(),
            QuotationFields::status(),
            F::createInt('Days', ['name' => 'Days from Creation', 'tableAlign' => 'center'])
        );
    }

    protected function getTableQuery($userId)
    {
        return "SELECT ID, Name, CustomerID, Status, DATEDIFF(NOW(), CreatedDateTime) AS Days
                  FROM quotation
                  WHERE (Status = 'pending' OR Status = 'draft')
                      AND (ResponsibleSalesID = " . (int)$userId . " OR ResponsibleCSID = " . (int)$userId . ")
                      AND DATEDIFF(NOW(), CreatedDateTime) >= 2
                      AND CreatedDateTime >= '2014-01-01'
                  ORDER BY CreatedDateTime";
    }
} 