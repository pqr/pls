<?php
/**
 * Created by PhpStorm.
 * User: petr
 * Date: 20.06.17
 * Time: 19:18
 */

namespace Forward\Autolaunch;


use Forward\Module\CPayment\CPaymentObj;
use Forward\Module\CPayment\Subtables\CInvoicesObj;
use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;

class FixCPayments extends AutolaunchAbstract
{
    public static $_title = 'Исправить пустые Customer Payments';

    protected function run(array $params)
    {
        $cpaymentIds = Db::getCol("SELECT ID FROM cpayment WHERE NOT EXISTS(SELECT * FROM cpayment_cinvoice WHERE CPaymentID = cpayment.ID)");

        // Найдём все удалённые строки отсортированные по дате удаления
        $deletedLines = Db::getAll("SELECT * FROM deleted WHERE TableName = 'cpayment_cinvoice' AND CreatedBy = 0 ORDER BY CreatedDateTime DESC");

        $restoredLines = 0;

        foreach($cpaymentIds as $cpaymentId) {
            $restoredLines += $this->fixCPayment($cpaymentId, $deletedLines);
        }

        $this->_msgBuff->addEmptyLine();

        $msg = 'Восстановлено строк: ' . $restoredLines;
        $this->_msgBuff->addSuccessOrInfo($msg, $restoredLines);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $restoredLines);
    }

    private function fixCPayment($cpaymentId, array $deletedLines)
    {
        $restoredLines = 0;
        $restoredCInvoiceIds = [];
        foreach($deletedLines as $deletedLine) {
            $lineData = json_decode($deletedLine['JSON'], true);
            if ($lineData['CPaymentID'] == $cpaymentId) {
                if (!isset($restoredCInvoiceIds[$lineData['CInvoiceID']])) {
                    $this->restoreLine($lineData);
                    $restoredLines++;

                    $restoredCInvoiceIds[$lineData['CInvoiceID']] = true;
                }
            }
        }

        if ($restoredLines) {
            $cpaymentObj = new CPaymentObj();
            $cpaymentObj->setId($cpaymentId);
            $cpaymentObj->loadData();
            $cpaymentObj->updateCacheAfterSubmit([], $this->_msgBuff, true, null, 'Автоматическое восстановление привязки счёта к оплате');
        }

        return $restoredLines;
    }

    private function restoreLine(array $deletedLine)
    {
        $lineObj = new CInvoicesObj();
        $lineObj->setMasterIdForDbLog($deletedLine['CPaymentID']);
        $lineObj->insertDb($deletedLine, 'Автоматическое восстановление привязки счёта к оплате');
    }

}