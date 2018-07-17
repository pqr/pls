<?php


namespace Light\Autolaunch;


use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;

class MigrateReportAccess extends AutolaunchAbstract
{
    protected static $title = 'Перевод настроек доступа к отчётом в новый формат';

    protected function run(array $params)
    {
        $inserted = 0;
        $all = Db::getAll("SELECT * FROM `_delete_reportaccess`");

        foreach($all as $item) {
            $reportClass = $item['ReportClass'];
            $userIds = array_filter(array_map('intval', explode('|', $item['UserIDs'])));
            if (strpos($item['UserIDs'], '%')) {
                $userIds[] = -1;
            }
            foreach ($userIds as $userId) {
                Db::insertRow([
                    'ReportClass' => $reportClass,
                    'UserID' => $userId
                ], 'reportaccess_user');
                $inserted++;
            }

            $groupIds = array_filter(array_map('intval', explode('|', $item['GroupIDs'])));
            foreach ($groupIds as $groupId) {
                Db::insertRow([
                    'ReportClass' => $reportClass,
                    'GroupID' => $groupId
                ], 'reportaccess_group');
                $inserted++;
            }
        }

        $this->_msgBuffShort->addSuccessOrInfo("Inserted: $inserted", $inserted);
    }

}
