<?php

declare(strict_types=1);

namespace Forward\Module\Shipment\PrintForms;

use Light\Db;
use Light\ObjAbstract;
use Light\PrintForm\PrintForm;

final class MAWBExcel extends PrintForm
{
    public function __construct()
    {
        parent::__construct('MAWBExcel', 'MAWB', self::FORMAT_EXCEL, self::OUT_STREAM);
    }

    protected function getTplDataForExcel(ObjAbstract $obj): array
    {
        $tdata = parent::getTplDataForExcel($obj);

        $mawbPars = explode('-', $obj['MAWBNumber'], 2);
        $tdata['MAWBPrefix'] = $mawbPars[0];
        $tdata['MAWBSuffix'] = $mawbPars[1] ?? '';
        $tdata['ShipperName'] = $this->getShipperName($obj->getId());
        $tdata['ConsigneeName'] = $this->getConsigneeName($obj);
        $tdata['AirlineAlphaCode_raw'] = $obj['AirlineAlphaCode'];

        return $tdata;
    }

    private function getShipperName(int $shipmentId): string
    {
        return (string)Db::getOne(
            "SELECT Name FROM shipmentshipper WHERE ShipmentID = @i AND Name != '' ORDER BY ID LIMIT 1",
            $shipmentId
        );
    }

    private function getConsigneeName(ObjAbstract $obj): string
    {
        if ($obj->notEmpty('ConsigneeID')) {
            return $obj->getFieldExcel('ConsigneeID');
        }

        return $obj->getFieldExcel('CustomerAccountID');
    }
}