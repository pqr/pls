<?php
namespace Forward\Home;

use Forward\Field\GeneralFields;
use Forward\Field\ShipmentFields;
use Light\HomeBase\HomeBlockAbstract;

class ShipmentsWithoutCs extends HomeBlockAbstract
{
    protected static $title = 'New Shipments';
    protected static $description = 'Перевозки у которых ещё не выбран Customer Service';

    public function __construct(int $userId)
    {
        parent::__construct($userId);

        $this->tableLinkModule = 'Shipment';

        $this->getFieldset()->addFieldsCreatedBy();
        $this->addField(ShipmentFields::name());
        $this->addField(GeneralFields::customerId());
    }

    protected function getDataQuery(int $userId)
    {
        return "SELECT ID
                    , CreatedBy
                    , CreatedDateTime
                    , Name
                    , CustomerID
                FROM shipment
                WHERE ResponsibleCS = 0
                ORDER BY ID DESC";
    }
}
