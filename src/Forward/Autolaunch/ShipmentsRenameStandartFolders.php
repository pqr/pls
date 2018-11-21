<?php


namespace Forward\Autolaunch;


use Forward\Classes\Constant;
use Forward\Module\Shipment\Shipment;
use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\Module\Autolaunch\AutolaunchAbstract;

class ShipmentsRenameStandartFolders extends AutolaunchAbstract
{
    protected static $title = 'Переименовать стандартные папки';
    protected static $description = '1 Накладная -> 3 Накладная; 2 Входящий счёт -> 4 Входящий счёт; 3 Акт без подписи -> 5 Акт без подписи; 4 Акт подписанный -> 6 Акт подписанный; 5 Акт от нерезидента -> 7 Акт от нерезидента';

    protected function run(array $params)
    {
        $rename = [
            '1 Накладная' => Constant::$standardFolders[Constant::FOLDER_MAWB],
            '2 Входящий счёт' => Constant::$standardFolders[Constant::FOLDER_VINVOICE],
            '3 Акт без подписи' => Constant::$standardFolders[Constant::FOLDER_CACT],
            '4 Акт подписанный' => Constant::$standardFolders[Constant::FOLDER_CACT_SIGNED],
            '5 Акт от нерезидента' => Constant::$standardFolders[Constant::FOLDER_CACT_NOTRESIDENT],
        ];

        $renamed = 0;

        $ids = Db::getCol("SELECT ID FROM shipment");
        foreach($ids as $id) {
            $folderAbs = FilesFolder::getModuleAbsolute(Shipment::class, $id);
            foreach($rename as $originalSubfolder => $newSubfolder) {
                $src = "$folderAbs/$originalSubfolder";
                if (is_dir($src)) {
                    $dest = "$folderAbs/$newSubfolder";
                    rename($src, $dest);
                    $renamed++;
                }
            }
        }

        $created = 0;
        $create = [
            Constant::$standardFolders[Constant::FOLDER_REQUEST_SIGNED],
            Constant::$standardFolders[Constant::FOLDER_CINVOICE],
            Constant::$standardFolders[Constant::FOLDER_VINVOICE]
        ];
        $ids = Db::getCol("SELECT ID FROM shipment WHERE CreatedDateTime >= '2018-09-01'");
        foreach($ids as $id) {
            $folderAbs = FilesFolder::getModuleAbsolute(Shipment::class, $id);
            if (is_dir($folderAbs)) {
                foreach($create as $subfolder) {
                    $target = "$folderAbs/$subfolder";
                    if (!is_dir($target)) {
                        File::mkdir($target);
                        $created++;
                    }
                }
            }
        }

        $this->_msgBuffShort->addInfo("Renamed: $renamed");
        $this->_msgBuffShort->addInfo("Created: $created");
    }
}
