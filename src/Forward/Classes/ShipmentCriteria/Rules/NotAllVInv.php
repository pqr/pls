<?php
namespace Forward\Classes\ShipmentCriteria\Rules;

use Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase;

class NotAllVInv extends RuleBase
{
    protected static function getQueryWhere()
    {
        return "EXISTS (SELECT *
                        FROM csexpenses cs
                        WHERE cs.InvNum = ''
                            AND cs.ShipmentID = shipment.ID)";
    }
}