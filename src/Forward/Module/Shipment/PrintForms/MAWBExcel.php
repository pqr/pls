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

        $mawbPars = explode('-', (string) $obj['MAWBNumber'], 2);
        $tdata['MAWBPrefix'] = $mawbPars[0];
        $tdata['MAWBSuffix'] = $mawbPars[1] ?? '';
        $shipmentId = $obj->getId();
        $tdata += $this->getShipperData($shipmentId);
        $tdata += $this->getConsigneeData($obj);
        $tdata['AirlineAlphaCode_raw'] = $obj['AirlineAlphaCode'];
        $tdata['HandlingInformation'] = $obj['Comments'];

        return $tdata;
    }

    private function getShipperData(int $shipmentId): array
    {
        $shipperName = Db::getOne(
            "SELECT Name FROM shipmentshipper WHERE ShipmentID = @i AND Name != '' ORDER BY ID LIMIT 1",
            $shipmentId
        );
        if (!$shipperName) {
            return [];
        }

        $shipperInfo = Db::getRow(
            "SELECT ShipmentRequestZIP, ShipmentRequestCountry, ShipmentRequestCity, ShipmentRequestStreet, ShipmentRequestPhone
             FROM list_shipper
             WHERE Name = @s",
            $shipperName
        );
        $shipperAddress = implode(', ', array_filter($shipperInfo));

        return [
            'ShipperName' => $shipperName,
            'ShipperAddress' => $shipperAddress,
        ];
    }

    private function getConsigneeData(ObjAbstract $shipmentObj): array
    {
        if ($shipmentObj['ConsigneeID'] === -1) {
            return $this->getConsigneeDataFromCustomerAccount($shipmentObj['CustomerAccountID']);
        }

        $consigneeInfo = Db::getRow(
            "SELECT Name, ShipmentRequestZIP, ShipmentRequestCountry, ShipmentRequestCity, ShipmentRequestStreet, ShipmentRequestPhone
            FROM customerconsignee
            WHERE ID = @i",
            $shipmentObj['ConsigneeID'],
        );

        if (!$consigneeInfo) {
            return [];
        }

        $consigneeName = $consigneeInfo['Name'];
        unset($consigneeInfo['Name']);
        $consigneeAddress = implode(', ', array_filter($consigneeInfo));

        return [
            'ConsigneeName' => $consigneeName,
            'ConsigneeAddress' => $consigneeAddress,
        ];
    }

    private function getConsigneeDataFromCustomerAccount(?int $customerAccountId): array
    {
        if (!$customerAccountId) {
            return [];
        }

        $customerAccountInfo = Db::getRow(
            "SELECT Name, InvoiceAddress
            FROM customeraccount
            WHERE ID = @i",
            $customerAccountId,
        );

        if (!$customerAccountInfo) {
            return [];
        }

        return [
            'ConsigneeName' => $customerAccountInfo['Name'],
            'ConsigneeAddress' => $customerAccountInfo['InvoiceAddress'],
        ];
    }
}