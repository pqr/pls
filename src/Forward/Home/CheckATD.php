<?php

namespace Forward\Home;

use Forward\Application;
use Forward\Field\HomeFields;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class CheckATD extends HomeBlockAbstract
{
    protected static $title = 'Check ATD';
    protected static $description = 'Остался один день до даты Ready For Carriage или ETD уже наступило, но дата ATD ещё не проставлена.';
    protected static $informationFor = 'Информация для Shipment Responsible CS и Sales.';

    public function __construct(int $userId)
    {
        parent::__construct($userId);
        $this->tableLinkModule = 'Shipment';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            HomeFields::name(),
            HomeFields::mawbNumber(),
            F::createDate('ReadyForCarriageDate', [
                'name' => 'Ready For Carriage',
                'nameTable' => "Ready For\nCarriage",
                'editMandatory' => true
            ]),
            F::createDate('ETD')

        );
    }

    protected function getDataQuery(int $userId)
    {
        return "SELECT shipment.ID, shipment.Name, shipment.MAWBNumber, shipment.ETD, shipment.ReadyForCarriageDate
                  FROM shipment
                  WHERE (shipment.ResponsibleCS = $userId
                         OR shipment.ResponsibleSales = $userId
                         )
                      AND (shipment.ETD != '0000-00-00'
                           AND shipment.ETD <= CURDATE()

                           OR

                           shipment.ReadyForCarriageDate != '0000-00-00'
                           AND shipment.ReadyForCarriageDate <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                          )
                      AND shipment.ATD = '0000-00-00'
                  ORDER BY shipment.ETD";
    }

    protected function tableLoadData(int $userId)
    {
        if (User::hasSpecialAccess(Application::SA_HOME_CHECKATD, $userId)) {
            parent::tableLoadData($userId);
        }
    }
}
