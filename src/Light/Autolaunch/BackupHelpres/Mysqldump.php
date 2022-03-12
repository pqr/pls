<?php


namespace Light\Autolaunch\BackupHelpres;


use Light\Config;

class Mysqldump
{
    /** @var array */
    private $onlyTables;
    /** @var array */
    private $ignoreTables;
    /** @var boolean */
    private $noData;

    public static function create() {
        return new self();
    }

    public function onlyTables(array $onlyTables) {
        $this->onlyTables=  $onlyTables;
        return $this;
    }

    public function ignoreTables(array $ignoreTables) {

        $this->ignoreTables = $ignoreTables;
        return $this;
    }

    public function noData()
    {
        $this->noData = true;
        return $this;
    }

    public function getCmd(): string
    {
        $cmd = "mysqldump --max-allowed-packet=16777216 --ssl-mode=disabled"
            . " -h" . Config::$mysqlHost
            . " -u" . Config::$mysqlUser;

        if (Config::$mysqlPassword) {
            $cmd .= " -p" . Config::$mysqlPassword;
        }

        if (Config::$mysqlPort) {
            $cmd .= " -P" . Config::$mysqlPort;
        }

        $options = $this->buildOptions();
        if ($options) {
            $cmd .= " \\\n$options";
        }

        $cmd .= " \\\n" . Config::$mysqlDb;

        if ($this->onlyTables) {
            $cmd .= " \\\n" . implode(" \\\n", $this->onlyTables);
        }

        return $cmd;
    }

    private function buildOptions():string
    {
        $options = [];
        if ($this->ignoreTables) {
            // Получили список таблиц которые нужно проигнорировать при выгрузке данных (таблицы с логами)
            // Добавим к имени каждой таблицы имя базы данных
            $options = array_map(function ($t) {
                return "--ignore-table=". Config::$mysqlDb . ".$t";
            }, $this->ignoreTables);
        }

        if ($this->noData) {
            $options[] = "--no-data";
        }

        return implode(" \\\n", $options);
    }
}
