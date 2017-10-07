<?php
namespace Forward\Classes\ShipmentCriteria\Rules;

use Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase;

class AllInv extends RuleBase
{
    protected static function getQueryWhere()
    {
        return "IFNULL((SELECT SUM(IF(cs.InvNum = '', 1, 0))
                        FROM csincome cs
                        WHERE shipment.ID = cs.ShipmentID), 1) = 0

                AND IFNULL((SELECT SUM(IF(cs.InvNum = '', 1, 0))
                            FROM csexpenses cs
                            WHERE shipment.ID = cs.ShipmentID), 1) = 0";
    }
}