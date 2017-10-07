<?php
namespace Forward\Classes\ShipmentCriteria\Rules;

use Forward\Classes\Finance;
use Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase;

class NoCInv extends RuleBase
{
    protected static function getQueryWhere()
    {
        return "NOT EXISTS (SELECT *
                            FROM cinvoice_file_amount cfa, cinvoice ci
                            WHERE ci.ID = cfa.CInvoiceID
                                AND ci.Status != " . Finance::INVOICESTATUS_CREDITNOTED . "
                                AND cfa.FileNumber = shipment.Name)";
    }

} 