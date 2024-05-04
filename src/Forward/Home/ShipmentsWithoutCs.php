<?php
namespace Forward\Home;

use Forward\Application;
use Forward\Field\GeneralFields;
use Forward\Field\ShipmentFields;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class ShipmentsWithoutCs extends HomeBlockAbstract
{
    protected static $title = 'New Shipments';
    protected static $description = 'Перевозки у которых ещё не выбран Customer Service';

    public function __construct(int $userId)
    {
        parent::__construct($userId);

        $this->tableLinkModule = 'Shipment';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->getFieldset()->addFieldsCreatedBy();
        $this->addField(ShipmentFields::name());
        $this->addField(GeneralFields::customerId());
    }

    #[\Override]
    protected function getDataQuery(int $userId)
    {
        return "SELECT ID
                    , CreatedBy
                    , CreatedDateTime
                    , Name
                    , CustomerID
                FROM shipment
                WHERE ResponsibleCS = 0
                    AND FileClosed = 0
                ORDER BY ID DESC";
    }
}
