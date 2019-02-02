<?php

namespace Forward\Home;

use Forward\Application;
use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class ToBeClosed extends HomeBlockAbstract
{
    protected static $title = 'To be closed';
    protected static $description = 'Все счета получены, выпущены и оплачены, но файл в статусе Open';
    protected static $informationFor = 'Информация для Shipment Responsible CS и Sales.';

    public function __construct(int $userId)
    {
        parent::__construct($userId);

        $this->tableLinkModule = 'Shipment';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

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
                  AND (shipment.ResponsibleCS = $userId OR shipment.ResponsibleSales = $userId)
                  ORDER BY shipment.ETD";
    }
}
