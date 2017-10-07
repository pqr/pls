<?php
namespace Forward\Module\Core;

use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\FilesFolder;
use Light\Module\Core\Core as LightCore;
use Light\Response;
use Light\Template;

class Core extends LightCore
{
    public static function acSearchLocationName($value)
    {
        //Найдём локацию и её IATA код в базе
        return Db::getAll(
            "SELECT UPPER(Name) AS value, IATA, UPPER(Name) AS LocationName
             FROM location
             WHERE Name LIKE '" . Db::esc($value) . "%'
             ORDER BY IATA
             LIMIT 10"
        );
    }

    public static function acSearchLocationIata($value)
    {
        //Найдём локацию и её IATA код в базе
        return Db::getAll(
            "SELECT IATA AS value, IATA, UPPER(Name) AS LocationName
             FROM location
             WHERE IATA LIKE '" . Db::esc($value) . "%'
             ORDER BY IATA
             LIMIT 10"
        );
    }


    public static function acSearchIncotermsLocationName($value)
    {
        //Найдём локацию и её IATA код в базе
        return Db::getAll(
            "SELECT Name AS value
            FROM location
            WHERE Name LIKE '" . Db::esc($value) . "%'
            ORDER BY Name
            LIMIT 10"
        );
    }

    public static function acSearchMawb($value)
    {
        return self::acSearchShipmentOrMawb($value, 'MAWBNumber');
    }

    public static function acSearchShipment($value)
    {
        return self::acSearchShipmentOrMawb($value, 'Name');
    }

    private static function acSearchShipmentOrMawb($value, $searchField)
    {
        if ($searchField === 'Name') {
            $where = "Name = '" . Db::esc($value) . "'";
        } else {
            $where = "REPLACE(REPLACE(MAWBNumber, ' ', ''), '-', '') LIKE '%" .
                Db::esc(str_replace([' ', '-'], '', $value)) . "%'";
        }

        $query = "SELECT " . $searchField . " AS value
                    , MAWBNumber
                    , Name AS FileNumber
                    , ETD
                  FROM shipment
                  WHERE " . $where . "
                  ORDER BY ETD DESC
                  LIMIT 0, 20";

        return Db::getAll($query);
    }
}
