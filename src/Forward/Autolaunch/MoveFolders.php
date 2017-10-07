<?php
namespace Forward\Autolaunch;

use Light\Config;
use Light\Db;
use Light\File;
use Light\Module\Autolaunch\AutolaunchAbstract;

class MoveFolders extends AutolaunchAbstract
{
    protected function run(array $params)
    {
        $this->moveFolders('CInvoice');
        $this->moveFolders('VInvoice');
        $this->moveFolders('VPayment');
        $this->moveFolders('CPayment');
        $this->moveFolders('Customer');
        $this->moveFolders('Vendor');
        $this->moveFolders('Quotation');
    }

    protected function moveFolders($folderName)
    {
        $renamed = 0;
        $dbtable = strtolower($folderName);
        $ids = Db::getCol("SELECT ID FROM $dbtable");
        foreach($ids as $id) {
            $oldDir = Config::$filesfolderLocal . "/$folderName/$id";
            if (!is_dir($oldDir)) {
                continue;
            }

            $sub = floor($id / 1000);
            $subDir = Config::$filesfolderLocal . "/$folderName/$sub";
            File::mkdir($subDir);

            $newDir = "$subDir/$id";

            if (!rename($oldDir, $newDir)) {
                $this->_msgBuff->addError("Cant rename $oldDir to $newDir");
            } else {
                $renamed++;
            }
        }

        $this->_msgBuff->addEmptyLine();
        $msg = "Renamed $folderName: $renamed";
        $this->_msgBuff->addSuccess($msg);
        $this->_msgBuffShort->addSuccess($msg);
    }

}
