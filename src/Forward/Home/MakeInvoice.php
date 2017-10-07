<?php
namespace Forward\Home;

use Forward\Field\HomeFields;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;

class MakeInvoice extends HomeBlockAbstract
{
    protected function init()
    {
        $this->_title = 'Make Invoice';
        $this->_description = 'Проставлена дата ATA, но не выставлен счёт.';
        $this->_informationFor = 'Информация для Shipment Responsible CS и Sales.';
        $this->_tableLinkModule = 'Shipment';

        $this->addFields(
            HomeFields::name(),
            HomeFields::mawbNumber(),
            F::createDate('ATA')
        );
    }

    protected function getTableQuery($userId)
    {
        return "SELECT shipment.ID, shipment.Name, shipment.MAWBNumber, shipment.ATA
                  FROM shipment
                  WHERE (shipment.ResponsibleCS = " . (int)$userId . " OR shipment.ResponsibleSales = " . (int)$userId . ")
                      AND shipment.ATA != '0000-00-00'
                      AND NOT EXISTS (SELECT * FROM cinvoiceline WHERE FileNumber = shipment.Name)
                  ORDER BY shipment.ATA";
    }
}
