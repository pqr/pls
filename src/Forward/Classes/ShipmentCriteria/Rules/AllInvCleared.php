<?php
namespace Forward\Classes\ShipmentCriteria\Rules;

use Forward\Classes\Finance;
use Forward\Classes\ShipmentCriteria\Rules\Base\RuleBase;

class AllInvCleared extends RuleBase
{
    #[\Override]
    protected static function getQueryWhere()
    {
        return "IFNULL((SELECT SUM(IF(cs.InvNum = '', 1, 0))
                        FROM csincome cs
                        WHERE shipment.ID = cs.ShipmentID), 1) = 0

                AND IFNULL((SELECT SUM(IF(cs.InvNum = '', 1, 0))
                            FROM csexpenses cs
                            WHERE shipment.ID = cs.ShipmentID), 1) = 0

                AND IFNULL((SELECT SUM(IF(ci.Status IN (".implode(',', Finance::$cinvoiceOpenStatuses)."), 1, 0))
                            FROM cinvoice ci, cinvoice_file_amount cfa
                            WHERE ci.ID = cfa.CInvoiceID
                                AND cfa.FileNumber = shipment.Name), 1) = 0

                AND IFNULL((SELECT SUM(IF(vi.Status IN (".implode(',', Finance::$vinvoiceOpenStatuses)."), 1, 0))
                            FROM vinvoice vi, vinvoice_file_amount vfa
                            WHERE vi.ID = vfa.VInvoiceID
                                AND vfa.FileNumber = shipment.Name), 1) = 0";
    }

} 