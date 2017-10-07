<?php

namespace Forward\Autolaunch;


use Forward\Classes\Constant;
use Forward\Classes\Finance;
use Forward\Module\CInvoice\CInvoiceObj;
use Light\Db;
use Light\Link;
use Light\Module\Autolaunch\AutolaunchAbstract;

class RestoreCInvoiceCleared extends AutolaunchAbstract
{
    protected function run(array $params)
    {
        $updated = 0;

        $companyIds = [Constant::OC_PLS_OOO, Constant::OC_PLS12_OOO, Constant::OC_PGL_OOO, Constant::OC_PGL11_OOO];
        $cinvoices = Db::getAssoc("SELECT ID, Name FROM cinvoice WHERE CompanyID IN (@a) AND `Status` = " . Finance::INVOICESTATUS_OPEN,
            $companyIds);

        foreach ($cinvoices as $cinvId => $cinvName) {

            if ($this->hasDbLogRecord($cinvId)) {

                $cinvObj = new CInvoiceObj();
                $cinvObj->setId($cinvId);
                $affectedRows = $cinvObj->updateDb(['Status' => Finance::INVOICESTATUS_CLEARED],
                    'Статус Cleared восстановлен Петром');
                if ($affectedRows) {

                    $link = Link::moduleViewBlank($cinvName, $cinvId, null, 'CInvoice');
                    $this->_msgBuff->addInfo($link);

                }

            }

        }

        $this->_msgBuff->addEmptyLine();
        $msg = "Updated: $updated";
        $this->_msgBuff->addSuccessOrInfo($msg, $updated);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $updated);
    }

    private function hasDbLogRecord(int $cinvId)
    {
        static $moduleId, $fieldId;
        if (!$moduleId) {
            $moduleId = Db::getOne("SELECT ID FROM dblog_module WHERE ModuleName = 'CInvoice'");
            $tableId = Db::getOne("SELECT ID FROM dblog_table WHERE TableName = 'cinvoice'");
            $fieldId = Db::getOne("SELECT ID FROM dblog_field WHERE TableID = $tableId AND FieldKey='Status'");
        }

        $dblogIds = Db::getCol("SELECT ID FROM dblog WHERE CreatedBy = 0 AND ModuleID = $moduleId AND FieldID = $fieldId AND MasterID = $cinvId");

        return Db::getOne("SELECT dblogID FROM dblog_text WHERE dblogID IN (@a) AND OldValue = " . Finance::INVOICESTATUS_CLEARED . " AND NewValue = " . Finance::INVOICESTATUS_OPEN . " LIMIT 1",
            $dblogIds);
    }

}