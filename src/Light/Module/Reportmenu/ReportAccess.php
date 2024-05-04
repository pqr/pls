<?php


namespace Light\Module\Reportmenu;


use Light\Db;
use Light\ObjAbstract;
use Light\User;

class ReportAccess
{
    protected $userAccessCache = [];
    protected $groupAccessCache = [];

    /**
     * Проверяет, есть ли у указанного пользователя доступ к указанному отчёту?
     * @param int $userId
     * @param int $groupId
     * @param string $reportClass
     * @return bool
     */
    public function checkAccess(int $userId, int $groupId, string $reportClass)
    {
        if (User::hasWriteAccess('Reportmenu', $userId)) {
            // Если у пользователя есть доступ на запись в модуль отчётов, значит у него есть доступ ко всем отчётам
            return true;
        }

        if ($this->hasUserAccess(-1, $reportClass)) {
            // К данному отчёту есть доступ у ВСЕХ (UserID = -1)
            return true;
        }

        if ($this->hasUserAccess($userId, $reportClass)) {
            return true;
        }

        if ($this->hasGroupAccess($groupId, $reportClass)) {
            return true;
        }

        // Никаких признаков доступа не нашлось
        return false;
    }

    private function hasUserAccess(int $userId, string $reportClass): bool
    {
        if (!isset($this->userAccessCache[$userId])) {
            // Загрузим полный список отчётов пользователя
            $this->userAccessCache[$userId] = Db::getCol("SELECT ReportClass FROM reportaccess_user WHERE UserID = $userId");
        }

        return in_array($reportClass, $this->userAccessCache[$userId]);
    }

    private function hasGroupAccess(int $groupId, string $reportClass): bool
    {
        if (!isset($this->groupAccessCache[$groupId])) {
            // Загрузим полный список отчётов пользователя
            $this->groupAccessCache[$groupId] = Db::getCol("SELECT ReportClass FROM reportaccess_group WHERE GroupID = $groupId");
        }

        return in_array($reportClass, $this->groupAccessCache[$groupId]);
    }

    /**
     * Используется для вывода списка пользователей, которым разрешен доступ, в диалоговое окно редактирования доступа
     * @param string $reportClass
     * @return array
     */
    public function getReportUsers(string $reportClass): array
    {
        return Db::getCol("SELECT UserID FROM reportaccess_user WHERE ReportClass = @s", $reportClass);
    }

    /**
     * Используется для вывода списка групп, которым разрешен доступ, в диалоговое окно редактирования доступа
     * @param string $reportClass
     * @return array
     */
    public function getReportGroups(string $reportClass): array
    {
        return Db::getCol("SELECT GroupID FROM reportaccess_group WHERE ReportClass = @s", $reportClass);
    }

    /**
     * Устанавливает новый список из пользователей, кому разрешен доступ к отчёту
     * записывая UserID+ReportClass в таблицу reportaccess_user
     * @param array $newUserIds
     * @param string $reportClass
     * @throws \Exception
     */
    public function setNewUsers(array $newUserIds, string $reportClass): void
    {
        $oldUserIds = $this->getReportUsers($reportClass);
        $insertUserIds = array_diff($newUserIds, $oldUserIds);
        foreach ($insertUserIds as $userId) {
            $accessUserObj = new ReportAccessUserObj();
            $accessUserObj->insertDb([
                'UserID' => $userId,
                'ReportClass' => $reportClass
            ]);
        }

        $deleteRecordIds = Db::getCol(
            "SELECT ID 
            FROM reportaccess_user 
            WHERE ReportClass = @s 
                AND UserID NOT IN (@a)"
            , $reportClass
            , $newUserIds
        );
        foreach($deleteRecordIds as $deleteRecordId) {
            $accessUserObj = new ReportAccessUserObj();
            $accessUserObj->setId($deleteRecordId);
            $accessUserObj->loadData();
            $accessUserObj->deleteDb();
        }
    }

    /**
     * Устанавливает новый список из пользователей, кому разрешен доступ к отчёту
     * записывая GroupID+ReportClass в таблицу reportaccess_group
     * @param array $newGroupIds
     * @param string $reportClass
     * @throws \Exception
     */
    public function setNewGroups(array $newGroupIds, string $reportClass): void
    {
        $oldGroupIds = $this->getReportUsers($reportClass);
        $insertGroupIds = array_diff($newGroupIds, $oldGroupIds);
        foreach ($insertGroupIds as $userId) {
            $accessGroupObj = new ReportAccessGroupObj();
            $accessGroupObj->insertDb([
                'GroupID' => $userId,
                'ReportClass' => $reportClass
            ]);
        }

        $deleteRecordIds = Db::getCol(
            "SELECT ID 
            FROM reportaccess_group 
            WHERE ReportClass = @s 
                AND GroupID NOT IN (@a)"
            , $reportClass
            , $newGroupIds
        );
        foreach($deleteRecordIds as $deleteRecordId) {
            $accessGroupObj = new ReportAccessGroupObj();
            $accessGroupObj->setId($deleteRecordId);
            $accessGroupObj->loadData();
            $accessGroupObj->deleteDb();
        }
    }
}
