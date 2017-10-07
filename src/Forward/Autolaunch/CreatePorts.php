<?php
namespace Forward\Autolaunch;

use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;

class CreatePorts extends AutolaunchAbstract
{
    protected static $_title = 'Create Ports';
    protected static $_description = 'Создаёт порты в модуле Port на основе уже созданных перевозок';

    protected function run(array $params)
    {

        $shipments = Db::getAll("SELECT ID, DepartureIATA, DepartureLocationName, ArrivalIATA, ArrivalLocationName FROM shipment WHERE TrafficID = 7");
        $insertedPort = 0;
        $updatedPort = 0;
        $updatedShipment = 0;
        foreach ($shipments as $shipment) {

            $departureName = $shipment['DepartureLocationName'] ?: $shipment['DepartureIATA'];

            $polId = Db::getOne("SELECT ID FROM port WHERE Name = @s", $departureName);
            if (!isset($polId)) {
                $polId = Db::insertRow(['name' => mb_strtoupper($departureName), 'isPOL' => 1],
                    'port');
                $insertedPort++;
            } else {
                $updatedPort += Db::updateRow(['isPOL' => 1], 'port', 'ID', $polId);
            }
            $updatedShipment += Db::updateRow(['POLID' => $polId], 'shipment', 'ID', $shipment['ID']);


            $arrivalName = $shipment['ArrivalLocationName'] ?: $shipment['ArrivalIATA'];

            $podId = Db::getOne("SELECT ID FROM port WHERE Name = @s", $arrivalName);
            if (!isset($podId)) {
                $podId = Db::insertRow(['name' => mb_strtoupper($arrivalName), 'isPOD' => 1],
                    'port');
                $insertedPort++;
            } else {
                $updatedPort += Db::updateRow(['isPOD' => 1], 'port', 'ID', $podId);
            }
            $updatedShipment += Db::updateRow(['PODID' => $podId], 'shipment', 'ID', $shipment['ID']);

        }

        $msg = "Inserted ports:  " . $insertedPort;
        $this->_msgBuff->addSuccess($msg);
        $this->_msgBuffShort->addSuccess($msg);
        $msg = "Updated ports:  " . $updatedPort;
        $this->_msgBuff->addSuccess($msg);
        $this->_msgBuffShort->addSuccess($msg);
        $msg = "Updated shipments:  " . $updatedShipment;
        $this->_msgBuff->addSuccess($msg);
        $this->_msgBuffShort->addSuccess($msg);

    }


}