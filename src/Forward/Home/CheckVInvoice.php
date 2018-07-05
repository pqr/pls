<?php

namespace Forward\Home;

use Forward\Application;
use Forward\Classes\Finance;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class CheckVInvoice extends HomeBlockAbstract
{
    protected static $title = 'Vendor Invoices';
    protected static $description = 'Vendor Invoices в статусах On checking и Under dipsute.  Учитываются только USD/EUR счета. Сортировка по кол-ву оставшихся на проверку дней (Days Left).';
    protected static $informationFor = 'Информация для Vendor Invoice Responsible.';

    public function __construct(int $userId)
    {
        parent::__construct($userId);
        $this->width = 550;
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->tableLinkModule = 'VInvoice';

        $this->addFields(

            F::createString('Status', [
                'name' => 'St.',
                'getHtmlFunction' => 'Forward\\Classes\\Finance::getVInvoiceStatusIcon'
            ]),

            F::createString('Name', ['name' => 'Reference']),

            F::createMoney('Amount'),

            CF::currency(),

            F::createDate('DueDate', ['name' => 'Due Date']),

            F::createInt('DaysLeft', ['name' => 'Days Left', 'tableAlign' => 'center']),

            F::createText('MAWBNumbers', ['name' => 'MAWB Numbers']),

            F::createText('FileNumbers', ['name' => 'File Numbers'])

        );
    }

    protected function getDataQuery(int $userId)
    {
        return "SELECT ID, Name, Amount, CurrencyISO, DueDate, Status,
                        DATEDIFF(DueDate, CURDATE()) AS DaysLeft,

                        (SELECT GROUP_CONCAT(DISTINCT(FileNumber) ORDER BY ID SEPARATOR '\\n') FROM vinvoiceline WHERE VInvoiceID = vinvoice.ID GROUP BY VInvoiceID) AS FileNumbers,

                        (SELECT GROUP_CONCAT(DISTINCT(MAWBNumber) ORDER BY ID SEPARATOR '\\n') FROM vinvoiceline WHERE VInvoiceID = vinvoice.ID GROUP BY VInvoiceID) AS MAWBNumbers

                  FROM vinvoice
                  WHERE EXISTS(SELECT UserID
                                 FROM vinvoice_responsible vr
                                 WHERE vr.UserID = " . $userId . "
                                     AND vr.VInvoiceID = vinvoice.ID
                                     AND vinvoice.Status IN (" . Finance::INVOICESTATUS_OPEN . ", " . Finance::INVOICESTATUS_UNDERDISPUTE . "))
                  ORDER BY Status, DaysLeft, DueDate, Name";
    }

    protected function tableLoadData(int $userId)
    {
        if (User::hasSpecialAccess(Application::SA_HOME_VINVOICE, $userId)) {
            parent::tableLoadData($userId);
        }
    }

}
