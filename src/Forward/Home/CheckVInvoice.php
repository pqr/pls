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
    protected function init()
    {
        $this->_title = 'Vendor Invoices';
        $this->_description = 'Vendor Invoices в статусах On checking и Under dipsute.  Учитываются только USD/EUR счета. Сортировка по кол-ву оставшихся на проверку дней (Days Left).';
        $this->_width = 550;
        $this->_informationFor = 'Информация для Vendor Invoice Responsible.';
        $this->_tableLinkModule = 'VInvoice';

        $this->addFields(

            F::create('Status', F::TYPE_STRING, ['name' => 'St.', 'getHtmlFunction' => 'Forward\\Classes\\Finance::getVInvoiceStatusIcon']),

            F::create('Name', F::TYPE_STRING, ['name' => 'Reference']),

            F::create('Amount', F::TYPE_MONEY),

            CF::currency(),

            F::create('DueDate', F::TYPE_DATE, ['name' => 'Due Date']),

            F::create('DaysLeft', F::TYPE_INT, ['name' => 'Days Left', 'tableAlign' => 'center']),

            F::create('MAWBNumbers', F::TYPE_TEXT, ['name' => 'MAWB Numbers']),

            F::create('FileNumbers', F::TYPE_TEXT, ['name' => 'File Numbers'])

        );
    }

    protected function getTableQuery($userId)
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

    protected function tableLoadData($userId)
    {
        if (User::hasSpecialAccess(Application::SA_HOME_VINVOICE, $userId)) {
            parent::tableLoadData($userId);
        }
    }

}
