<?php
namespace Forward\Classes\ShipmentCriteria\Rules;

use Forward\Classes\Finance;
use Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase;

class OpenCInv extends RuleBase
{
    protected static function getQueryWhere()
    {
        return "EXISTS (SELECT *
                        FROM cinvoice_file_amount cfa, cinvoice ci
                        WHERE ci.ID = cfa.CInvoiceID
                            AND ci.Status IN (" . implode(',', Finance::$cinvoiceOpenStatuses) . ")
                            AND cfa.FileNumber = shipment.Name)";
    }

}