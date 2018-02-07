<?php
namespace Forward\Home;

use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;

class ToBeClosed extends HomeBlockAbstract
{
    protected function init()
    {
        $this->_title = 'To be closed';
        $this->_description = ShipmentCriteria::getText(ShipmentCriteria::ToBeClosed) . '.';
        $this->_informationFor = 'Информация для Shipment Responsible CS и Sales.';
        $this->tableLinkModule = 'Shipment';

        $this->addFields(
            F::createString('Name', ['name' => 'File Number']),
            F::createString('MAWBNumber', ['name' => 'MAWB']),
            F::createDate('ETD')
        );
    }

    protected function getTableQuery($userId)
    {
        return "SELECT shipment.ID, shipment.Name, shipment.MAWBNumber, shipment.ETD
                  FROM shipmentcriterion
                  JOIN shipment ON shipment.ID = shipmentcriterion.ShipmentID
                  WHERE shipmentcriterion.CriterionID = " . ShipmentCriteria::ToBeClosed . "
                  AND (shipment.ResponsibleCS = " . (int)$userId . " OR shipment.ResponsibleSales = " . (int)$userId . ")
                  ORDER BY shipment.ETD";
    }
}
