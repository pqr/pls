<?php
namespace Forward\Home;

use Forward\Application;
use Forward\Field\HomeFields;
use Light\Field\FieldAbstract as F;
use Light\HomeBase\HomeBlockAbstract;
use Light\User;

class CheckATA extends HomeBlockAbstract
{
    protected function init()
    {
        $this->_title = 'Check ATA';
        $this->_description = 'За 1 день до наступления ETA, если ATA ещё не проставлено.';
        $this->_informationFor = 'Информация для Shipment Responsible CS и Sales.';
        $this->_tableLinkModule = 'Shipment';

        $this->addFields(
            HomeFields::name(),
            HomeFields::mawbNumber(),
            F::createDate('ETA')
        );
    }

    protected function getTableQuery($userId)
    {
        return "SELECT shipment.ID, shipment.Name, shipment.MAWBNumber, shipment.ETA
                  FROM shipment
                  WHERE (shipment.ResponsibleCS = " . $userId . " OR shipment.ResponsibleSales = " . $userId . ")
                      AND shipment.ETA != '0000-00-00'
                      AND shipment.ETA <= DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                      AND shipment.ATA = '0000-00-00'
                  ORDER BY shipment.ETA";
    }

    protected function tableLoadData($userId)
    {
        if (User::hasSpecialAccess(Application::SA_HOME_CHECKATA, $userId)) {
            parent::tableLoadData($userId);
        }
    }
}
