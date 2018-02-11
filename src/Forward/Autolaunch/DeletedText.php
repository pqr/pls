<?php

namespace Forward\Autolaunch;


use Light\Db;
use Light\DbLog;
use Light\Module\Autolaunch\AutolaunchAbstract;

class DeletedText extends AutolaunchAbstract
{
    protected function run(array $params)
    {
        $affectedRows = 0;

        $allDeleted = Db::getAll("SELECT ID, TableName, `JSON` FROM deleted ORDER BY ID");

        foreach ($allDeleted as $deleted) {
            $tableId = (int)DbLog::getTableId($deleted['TableName']);
            Db::query("UPDATE deleted SET TableID = $tableId WHERE ID = " . $deleted['ID']);
            $affectedRows += Db::getAffectedRows();

            Db::query("REPLACE INTO deleted_text (deletedID, `JSON`) VALUES (@i, @s)", $deleted['ID'],
                $deleted['JSON']);
            $affectedRows += Db::getAffectedRows();
        }

        $this->_msgBuffShort->addSuccess("Affected rows: $affectedRows");

        /**
         * После запустить
        ALTER TABLE deleted DROP TableName;
        ALTER TABLE deleted DROP Query;
        ALTER TABLE deleted DROP JSON;
         */
    }

}
