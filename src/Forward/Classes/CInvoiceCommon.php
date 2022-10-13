<?php

namespace Forward\Classes;

use Light\Db;
use Light\Field\CommonFields;

class CInvoiceCommon
{
    /**
     * Возвращает данные из перевозки для заполнения шапки счёта при создании
     * Пригождается при создании счёта из Cost-sheet'а кнопкой "Create Customer Invoice" и при массовом создании
     * счетов из Shipments Massaction: Создать Customer Invoice
     * @param int $shipmentId
     * @return array
     */
    public static function getDataFromShipment($shipmentId)
    {
        //Найдём всю информацию по первому шипменту
        return Db::getRow(
            "SELECT shipment.CompanyID,
                 shipment.CustomerID,
                 shipment.CustomerAccountID,
                 ca.RatePercent,
                 CONCAT(ca.Name, '\\n', ca.InvoiceAddress) AS InvoiceTo,
                 DATE_ADD(CURDATE(), INTERVAL ca.CreditPeriod DAY) AS DueDate,
                 IF(ca.CreditPeriod > 0, CONCAT(ca.CreditPeriod, ' days'), '') AS PaymentTerms,
                 IF(shipment.TrafficID = " . Constant::TRAFFIC_OCEAN . ", (SELECT Name FROM port WHERE ID = shipment.POLID), shipment.DepartureLocationName) AS DepartureLocationName,
                 IF(shipment.TrafficID = " . Constant::TRAFFIC_OCEAN . ", (SELECT Name FROM port WHERE ID = shipment.PODID) , shipment.ArrivalLocationName) AS ArrivalLocationName,
                 shipment.Pieces,
                 shipment.ChargeableWeight,
                 shipment.GrossWeight,
                 shipment.Incoterms,
                 shipment.IncotermsLocation,
                 shipment.ConsigneeReference,
                 shipment.MAWBNumber AS ShippingDocument,
                    (SELECT GROUP_CONCAT(DISTINCT Reference SEPARATOR ', ')
                     FROM shipmentshipper
                     WHERE Reference != '' AND ShipmentID = shipment.ID
                     GROUP BY ShipmentID
                    ) AS ShipmentReference

            FROM shipment
                LEFT JOIN customeraccount ca ON ca.ID = shipment.CustomerAccountID
                LEFT JOIN customer c ON c.ID = shipment.CustomerID
            WHERE shipment.ID = @i",
            $shipmentId
        );
    }

    /**
     * Возворащает поля POLName, PODName на основе переданных Shipment IDs (несколько значений)
     * Если было несколько перевозок с разными POL/POD, то значения конкатинируются через запятую
     * @param array $shipmentIds
     * @return array
     */
    public static function getPolPodBlFromShipments(array $shipmentIds)
    {
        //Поля POL, POD берем из всех перевозок, через запятую
        return Db::getRow(
            "SELECT 
                GROUP_CONCAT(DISTINCT(pol.Name) SEPARATOR ', ') AS POLName,
                GROUP_CONCAT(DISTINCT(pod.Name) SEPARATOR ', ') AS PODName
            FROM shipment
                LEFT JOIN port pol ON pol.ID = shipment.POLID
                LEFT JOIN port pod ON pod.ID = shipment.PODID
        WHERE shipment.ID IN (@a)",
            $shipmentIds
        );
    }

    public static function getLinesDataFromCostSheet($shipmentId, array $csLineIds)
    {
        $linesData = [];
        if ($csLineIds) {
            //Если массив с ID линий был не пуст, попробуем загрузить их из базы
            $query = "SELECT s.ContainerName
                        , s.Name AS FileNumber
                        , cs.Name
                        , cs.Quantity
                        , cs.Price
                        , cs.Amount
                      FROM csincome cs, shipment s
                      WHERE s.ID = cs.ShipmentID
                          AND cs.ID IN (@a)
                          AND s.ID = (@i)
                    ORDER BY cs.ID";
            $linesData = Db::getAll($query, $csLineIds, $shipmentId);
        }

        if (!$linesData) {
            //Если всё-таки не удалось загрузить информацию по линиям (возможно, исходный массив $lineIds был пуст),
            //то добавим одну пустую строку с контейнером и номером файла
            $linesData[] = Db::getRow(
                "SELECT ContainerName, Name AS FileNumber, 1 AS Quantity FROM shipment WHERE ID = @i",
                $shipmentId
            );
        }

        return $linesData;
    }


    /**
     * Для ООО компаний сворачиваем строки в одну строку Транспортно-экспедиторское обслуживание по маршруту: ...
     */
    public static function getLinesDataFromCostSheetPlsPgl($shipmentId, array $csLineIds, int $companyId)
    {
        $lineData = null;
        if ($csLineIds) {
            //Если массив с ID линий был не пуст, попробуем загрузить их из базы
            $query = "SELECT s.ContainerName
                        , s.Name AS FileNumber
                        , sum(cs.Amount) AS Amount
                      FROM csincome cs, shipment s
                      WHERE s.ID = cs.ShipmentID
                          AND cs.ID IN (@a)
                          AND s.ID = (@i)
                      GROUP BY s.ID
                      ORDER BY cs.ID";
            $lineData = Db::getRow($query, $csLineIds, $shipmentId);
        }

        if (!$lineData) {
            //Если всё-таки не удалось загрузить информацию по линиям (возможно, исходный массив $lineIds был пуст),
            //то добавим одну пустую строку с контейнером и номером файла
            $lineData = Db::getRow(
                "SELECT ContainerName, Name AS FileNumber, 0 AS Amount FROM shipment WHERE ID = @i",
                $shipmentId
            );
        }

        $lineData['Quantity'] = 1;
        $lineData['Price'] = $lineData['Amount'];
        $lineData['Name'] = "Транспортно-экспедиторское обслуживание по маршруту: ";

        // Загрузим информацию по всем локациям
        $shipment = Db::getRow(
            "SELECT TrafficID, IncotermsLocation, POLID, PODID, DepartureLocationName, ArrivalLocationName FROM shipment WHERE ID = @i",
            $shipmentId
        );
        if ($shipment) {
            $locations = [];

            $locations[] = self::getIncomtermsLocationRu($shipment);
            $locations[] = self::getDepartureNameRu($shipment);
            $locations[] = self::getArrivalNameRu($shipment);

            // Убираем пустые локации и дубилкаты (например, локация по Incoterms может совпадать с Departure)
            $locations = array_filter(array_unique($locations));

            $lineData['Name'] .= implode(' - ', $locations);
        }

        if ($companyId === Constant::OC_PGL18_OOO) {
            // Для ПГЛ по умолчанию ставим НДС 0%
            $lineData['VATPercent'] = CommonFields::VAT_0;
        }

        // Возвращаем обернув в массив, т.к. нам нужно вернуть массив строк счёта (в данном случае одну строку счёта)
        return [$lineData];
    }

    private static function getIncomtermsLocationRu(array $shipment)
    {
        $incotermsNameRu = Db::getOne(
            "SELECT NameRu FROM location WHERE Name = @s AND NameRu != ''",
            $shipment['IncotermsLocation']
        );

        if (!$incotermsNameRu) {
            return $shipment['IncotermsLocation'];
        }

        return $incotermsNameRu . ' (' . $shipment['IncotermsLocation'] . ')';
    }

    private static function getDepartureNameRu(array $shipment)
    {
        if ($shipment['TrafficID'] == Constant::TRAFFIC_OCEAN) {
            $departure = Db::getRow("SELECT Name, NameRu FROM port WHERE ID = @i AND NameRu != ''", $shipment['POLID']);
            if (!$departure) {
                return '';
            }
            $departureName = $departure['Name'];
            $departureNameRu = $departure['NameRu'];
        } else {
            $departureName = $shipment['DepartureLocationName'];
            $departureNameRu = Db::getOne(
                "SELECT NameRu FROM location WHERE Name = @s AND NameRu != ''",
                $shipment['DepartureLocationName']
            );
        }

        if (!$departureNameRu) {
            return $departureName;
        }

        return $departureNameRu . ' (' . $departureName . ')';
    }

    private static function getArrivalNameRu(array $shipment)
    {
        if ($shipment['TrafficID'] == Constant::TRAFFIC_OCEAN) {
            $arrival = Db::getRow("SELECT Name, NameRu FROM port WHERE ID = @i AND NameRu != ''", $shipment['PODID']);
            if (!$arrival) {
                return '';
            }
            $arrivalName = $arrival['Name'];
            $arrivalNameRu = $arrival['NameRu'];
        } else {
            $arrivalName = $shipment['ArrivalLocationName'];
            $arrivalNameRu = Db::getOne(
                "SELECT NameRu FROM location WHERE Name = @s AND NameRu != ''",
                $shipment['ArrivalLocationName']
            );
        }

        if (!$arrivalNameRu) {
            return $arrivalName;
        }

        return $arrivalNameRu . ' (' . $arrivalName . ')';
    }
} 
