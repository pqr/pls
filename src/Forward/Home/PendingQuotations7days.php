<?php
namespace Forward\Home;

use Forward\Application;
use Forward\Field\GeneralFields;
use Forward\Field\QuotationFields;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class PendingQuotations7days extends HomeBlockAbstract
{
    protected static $title = 'Pending Quotations';
    protected static $description = 'Котировки находящиеся в статусе Pending или Draft 2 или более дней с даты создания';
    protected static $informationFor = 'Информация для Quotation Responsible CS и Sales.';

    public function __construct(int $userId)
    {
        parent::__construct($userId);

        $this->tableLinkModule = 'Quotation';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(
            CF::id(),
            QuotationFields::name(),
            GeneralFields::customerId(),
            QuotationFields::status(),
            F::createInt('Days', ['name' => 'Days from Creation', 'tableAlign' => 'center'])
        );
    }

    protected function getDataQuery(int $userId)
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
