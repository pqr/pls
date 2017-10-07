<?php
namespace Forward\Classes\ShipmentCriteria\Rules;

use Forward\Classes\Finance;
use Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase;

class NoVInv extends RuleBase
{
    protected static function getQueryWhere()
    {
        return "NOT EXISTS (SELECT *
                            FROM vinvoice_file_amount vfa, vinvoice vi
                            WHERE vfa.VInvoiceID = vi.ID
                                AND vi.Status NOT IN (" . Finance::INVOICESTATUS_CREDITNOTED . ", " . Finance::INVOICESTATUS_DELETED . ")
                                AND vfa.FileNumber = shipment.Name)";
    }
}