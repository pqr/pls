<?php

namespace Forward\Home;

use Forward\Application;
use Forward\Field\HomeFields;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class MakeInvoice extends HomeBlockAbstract
{
    protected static $title = 'Make Invoice';
    protected static $description = 'Проставлена дата ATA, но не выставлен счёт.';
    protected static $informationFor = 'Информация для Shipment Responsible CS и Sales.';

    public function __construct(int $userId)
    {
        parent::__construct($userId);

        $this->tableLinkModule = 'Shipment';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(
            HomeFields::name(),
            HomeFields::mawbNumber(),
            F::createDate('ATA')
        );
    }

    protected function getDataQuery(int $userId)
    {
        return "SELECT shipment.ID, shipment.Name, shipment.MAWBNumber, shipment.ATA
                  FROM shipment
                  WHERE (shipment.ResponsibleCS = " . (int)$userId . " OR shipment.ResponsibleSales = " . (int)$userId . ")
                      AND shipment.ATA != '0000-00-00'
                      AND NOT EXISTS (SELECT * FROM cinvoiceline WHERE FileNumber = shipment.Name)
                  ORDER BY shipment.ATA";
    }
}
