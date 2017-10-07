<?php
namespace Forward\Classes\ShipmentCriteria\Rules;

use Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase;

class NotAllCInv extends RuleBase
{
    protected static function getQueryWhere()
    {
        return "EXISTS (SELECT *
                        FROM csincome cs
                        WHERE cs.InvNum = ''
                            AND cs.ShipmentID = shipment.ID)";
    }
}