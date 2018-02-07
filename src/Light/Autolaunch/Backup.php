<?php

namespace Light\Autolaunch;


use Light\Config;
use Light\File;
use Light\Misc;
use Light\Module\Autolaunch\AutolaunchAbstract;

class Backup extends AutolaunchAbstract
{
    protected function run(array $params)
    {
        if (!Config::$backupFolder) {
            $this->_msgBuffShort->addError('Config::$backupFolder not set');
            return;
        }

        if (!File::mkdir(Config::$backupFolder)) {
            $this->_msgBuffShort->addError('Cant mkdir ' . htmlspecialchars(Config::$backupFolder));
            return;
        }

        $date = date('Y_m_d_H_i_s');
        $cmd = "mysqldump -h" . Config::$mysqlHost
            . " -u" . Config::$mysqlUser;

        if (Config::$mysqlPassword) {
            $cmd .= " -p" . Config::$mysqlPassword;
        }

        if (Config::$mysqlPort) {
            $cmd .= " -P" . Config::$mysqlPort;
        }

        $filename = $date . "_" . Config::$mysqlDb . ".sql";
        $achivename = $filename.".7z";

        // https://stackoverflow.com/questions/11214437/php-mysqldump-and-zip-7z
        $cmd .= " " . Config::$mysqlDb . " | 7z a -si$filename \"".Config::$backupFolder."/$achivename\"";

        $output = shell_exec($cmd);
        $this->_msgBuff->addInfo($output);

        $filepath = Config::$backupFolder . "/$achivename";
        if (!file_exists($filepath) || !filesize($filepath)) {
            $msg = "File <strong>" . htmlspecialchars($filepath) . "</strong> not found or empty";
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);
        } else {
            $msg = "Success: <strong>" . htmlspecialchars($filepath) . "</strong>";
            $this->_msgBuff->addSuccess($msg);
            $this->_msgBuffShort->addSuccess($msg);
        }

    }

}
