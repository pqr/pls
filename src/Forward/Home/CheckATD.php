<?php
namespace Forward\Home;

use Forward\Application;
use Forward\Field\HomeFields;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class CheckATD extends HomeBlockAbstract
{
    protected function init()
    {
        $this->_title = 'Check ATD';
        $this->_description = 'Остался один день до даты Ready For Carriage или ETD уже наступило, но дата ATD ещё не проставлена.';
        $this->_informationFor = 'Информация для Shipment Responsible CS и Sales.';
        $this->_tableLinkModule = 'Shipment';

        $this->addFields(

            HomeFields::name(),
            HomeFields::mawbNumber(),
            F::createDate(
                'ReadyForCarriageDate',
                ['name' => 'Ready For Carriage', 'nameTable' => "Ready For\nCarriage", 'editMandatory' => true]
            ),
            F::createDate('ETD')

        );
    }

    protected function getTableQuery($userId)
    {
        return "SELECT shipment.ID, shipment.Name, shipment.MAWBNumber, shipment.ETD, shipment.ReadyForCarriageDate
                  FROM shipment
                  WHERE (shipment.ResponsibleCS = " . $userId . "
                         OR shipment.ResponsibleSales = " . $userId . "
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

    protected function tableLoadData($userId)
    {
        if (User::hasSpecialAccess(Application::SA_HOME_CHECKATD, $userId)) {
            parent::tableLoadData($userId);
        }
    }
}
