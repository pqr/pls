<?php

namespace Forward\Home;

use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;

class ToBeClosed extends HomeBlockAbstract
{
    protected static $title = 'To be closed';
    protected static $description = 'Все счета получены, выпущены и оплачены, но файл в статусе Open';
    protected static $informationFor = 'Информация для Shipment Responsible CS и Sales.';

    public function __construct(int $userId)
    {
        parent::__construct($userId);

        $this->tableLinkModule = 'Shipment';

        $this->addFields(
            F::createString('Name', ['name' => 'File Number']),
            F::createString('MAWBNumber', ['name' => 'MAWB']),
            F::createDate('ETD')
        );
    }

    protected function getDataQuery(int $userId)
    {
        return "SELECT shipment.ID, shipment.Name, shipment.MAWBNumber, shipment.ETD
                  FROM shipmentcriterion
                  JOIN shipment ON shipment.ID = shipmentcriterion.ShipmentID
                  WHERE shipmentcriterion.CriterionID = " . ShipmentCriteria::ToBeClosed . "
                  AND (shipment.ResponsibleCS = " . (int)$userId . " OR shipment.ResponsibleSales = " . (int)$userId . ")
                  ORDER BY shipment.ETD";
    }
}
