<?php
/**
 * Created by PhpStorm.
 * User: petr
 * Date: 18.07.17
 * Time: 19:13
 */

namespace Forward\Home;


use Forward\Classes\Constant;
use Forward\Field\GeneralFields;
use Forward\Field\HomeFields;
use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\HomeBase\HomeBlockAbstract;

class CActNotReceived extends HomeBlockAbstract
{
    protected function init()
    {
        $this->_title = 'Нет оригинала акта';
        $this->_description = 'Клиенту отправлен акт (заполнена папка 3), но ещё не получен подписанный оригинал (пустая папка 4). Проверяются перевозки только в статусе Open.';
        $this->_informationFor = 'Информация для Shipment Customer Service.';
        $this->tableLinkModule = 'Shipment';

        $this->addFields(
            HomeFields::name(),
            HomeFields::mawbNumber(),
            GeneralFields::customerId()
        );
    }

    protected function getTableQuery($userId)
    {
        return "";
    }

    protected function tableLoadData($userId)
    {
        $userId = (int)$userId;
        $shipments = Db::getAll(
            "SELECT ID, Name, MAWBNumber, CustomerID 
            FROM shipment 
            WHERE ResponsibleCS = $userId 
                AND FileClosed = 0 
                AND CompanyID IN (".implode(',', array_keys(Constant::$onecCompany)).")
            ORDER BY MAWBDate, ID"
        );
        if (!$shipments) {
            return;
        }

        foreach($shipments as $shipment) {
            $folderRelative = "Shipment/" . floor($shipment['ID'] / 1000) . '/' . $shipment['ID'];
            $folder3NotEmpty = FilesFolder::isNotEmpty("$folderRelative/" . File::decode(Constant::$standardFolders[3]));
            $folder4NotEmpty = FilesFolder::isNotEmpty("$folderRelative/" . File::decode(Constant::$standardFolders[4]));
            if ($folder3NotEmpty && !$folder4NotEmpty) {
                $this->_tableData[] = $shipment;
            }
        }

    }


}
