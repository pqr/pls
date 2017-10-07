<?php
namespace Forward\Home;

use Forward\Field\GeneralFields;
use Forward\Field\ShipmentFields;
use Light\HomeBase\HomeBlockAbstract;

class ShipmentsWithoutCs extends HomeBlockAbstract
{
    protected function init()
    {
        $this->_title = 'New Shipments';
        $this->_description = 'Перевозки у которых ещё не выбран Customer Service';
        $this->_tableLinkModule = 'Shipment';

        $this->getFieldset()->addFieldsCreatedBy();
        $this->addField(ShipmentFields::name());
        $this->addField(GeneralFields::customerId());
    }

    protected function getTableQuery($userId)
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