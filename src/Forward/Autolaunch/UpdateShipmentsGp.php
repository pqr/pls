<?php
namespace Forward\Autolaunch;

use Forward\Classes\ShipmentGp;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\VInvoice\VInvoiceObj;
use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;

class UpdateShipmentsGp extends AutolaunchAbstract
{
    protected static $title = 'Update Shipments GP';
    protected static $description = 'Обновляет кеш по GP модуле Shipments где CreatedDateTime >= 2018-03-01. При этом обновляются кеширующие таблицы <strong>cinvoice_file_amount</strong> и <strong>vinvoice_file_amount</strong>';

    public function run(array $params): void
    {
        //Обновим кеш по шипментам
        $shipments = Db::getAll("SELECT ID, Name FROM shipment WHERE CreatedDateTime >= '2018-03-01'");
        $fileNumbers = array_column($shipments, 'Name');
        $this->updateInvoiceFileAmount('C', $fileNumbers);
        $this->updateInvoiceFileAmount('V', $fileNumbers);

        foreach ($shipments as ['ID' => $shipmentId, 'Name' => $fileNumber]) {
            ShipmentGp::updateGpEgpDeviation($fileNumber);

            //Узнаем, на какую дату конвертировать валюты в USD, если в этом Cost-sheet нет привязанных счетов?
            $convertDate = Db::getOne("SELECT max(CreatedDateTime) FROM cshistory WHERE ShipmentID = @i", $shipmentId);
            if ($convertDate) {
                //Отрежим время
                $convertDate = substr((string) $convertDate, 0, 10);
            }
            ShipmentGp::flush($convertDate);
        }

        $msg = 'Updated: ' . count($shipments);
        $msg .= ' Shipments';
        $this->_msgBuff->addInfo($msg);
        $this->_msgBuffShort->addInfo($msg);
    }

    private function updateInvoiceFileAmount(string $firstLetter, array $fileNumbers): void
    {
        $firstLetterLower = strtolower($firstLetter);
        $invIds = Db::getCol(
            "SELECT {$firstLetter}InvoiceID 
                FROM {$firstLetterLower}invoice_file_amount 
                WHERE FileNumber IN (@a)"
            , $fileNumbers
        );

        $invObjBase = $firstLetter === 'C' ? new CInvoiceObj() : new VInvoiceObj();
        foreach($invIds as $invId) {
            $invObj = clone $invObjBase;
            $invObj->setId($invId);
            if ($invObj->loadData()) {
                Db::begin();
                $invObj->updateAmountPerFile();
                $invObj->updateAmountPerFileCurrencyRate();
                Db::commit();
            }
        }
        $this->_msgBuff->addInfo(count($invIds) . ' Invoices updated');
    }
}
