<?php
namespace Forward\Autolaunch;

use Forward\Classes\ShipmentGp;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\VInvoice\VInvoiceObj;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Misc;
use Light\Module\Autolaunch\AutolaunchAbstract;

class UpdateShipmentsGp extends AutolaunchAbstract
{
    protected static $_title = 'Update Shipments GP';
    protected static $_description = 'Обновляет кеш по GP модуле Shipments. При этом полностью обновляются кеширующие
                                таблицы <strong>cinvoice_file_amount</strong> и <strong>vinvoice_file_amount</strong>, если выбрана опция TruncateInvoiceFileAmount';

    protected function initFieldset()
    {
        $this->addFields(

            F::createYesNo('TruncateInvoiceFileAmount',
                ['name' => 'Truncate invoice_file_amount', 'empty' => false, 'default' => 0])

        );

    }

    public function run(array $params)
    {
        if ($params['TruncateInvoiceFileAmount']) {
            //Перед тем как обновлять GP в самих перевозках, нужно обновить кеширующие таблицы
            //cinvoice_file_amount и vinvoice_file_amount

            //Найдём все Customer Invoices и по каждому из них запустим функцию пересчёта cinvoice_file_amount
            Db::query("TRUNCATE TABLE cinvoice_file_amount");
            Db::begin();
            $res = Db::query("SELECT ID FROM cinvoice");
            while ($row = $res->fetch_row()) {
                $cinvObj = new CInvoiceObj();
                $cinvObj->setId($row[0]);
                $cinvObj->loadData();
                $cinvObj->updateAmountPerFile();
                $cinvObj->updateAmountPerFileCurrencyRate();
            }
            Db::commit();
            $this->_msgBuff->addInfo(
                $res->num_rows . ' Customer Invoice' . Misc::getSSuffix($res->num_rows) . ' updated');

            //Аналогично для Vendor Invoices
            Db::query("TRUNCATE TABLE vinvoice_file_amount");
            Db::begin();
            $res = Db::query("SELECT ID FROM vinvoice");
            while ($row = $res->fetch_row()) {
                $vinvObj = new VInvoiceObj();
                $vinvObj->setId($row[0]);
                $vinvObj->loadData();
                $vinvObj->updateAmountPerFile();
                $vinvObj->updateAmountPerFileCurrencyRate();
            }
            Db::commit();
            $this->_msgBuff->addInfo(
                $res->num_rows . ' Vendor Invoice' . Misc::getSSuffix($res->num_rows) . ' updated');

        }

        //Обновим кеш по шипментам
        $res = Db::query("SELECT ID, Name FROM shipment");
        while ($row = $res->fetch_row()) {
            ShipmentGp::updateGpEgpDeviation($row[1]);

            //Узнаем, на какую дату конвертировать валюты в USD, если в этом Cost-sheet нет привязанных счетов?
            $convertDate = Db::getOne("SELECT max(CreatedDateTime) FROM cshistory WHERE ShipmentID = " . $row[0]);
            if ($convertDate) {
                //Отрежим время
                $convertDate = substr($convertDate, 0, 10);
            }
            ShipmentGp::flush($convertDate);
        }

        $msg = 'Updated: ' . $res->num_rows;
        $msg .= ' Shipment' . Misc::getSSuffix($res->num_rows);
        $this->_msgBuff->addInfo($msg);
        $this->_msgBuffShort->addInfo($msg);
    }
}
