<?php
namespace Light\Autolaunch;

use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;

class MigrateAccessRights extends AutolaunchAbstract
{
    protected static $_title = 'Перевести права доступа на новую схему';
    protected static $_description = 'Переносит права пользователей и групп из отдельных таблиц в JSON поля в таблице user и в таблице usergroup';

    protected function run(array $params)
    {
        $this->updateModuleAccess('user', 'UserID');
        $this->updateModuleAccess('usergroup', 'GroupID');
        $this->updateSpecialAccess('user', 'UserID');
        $this->updateSpecialAccess('usergroup', 'GroupID');
    }

    private function updateModuleAccess($tableName, $rightKeyField)
    {
        $jsonPerKeyId = [];
        $rights = Db::getAll("SELECT $rightKeyField AS KeyID, Module, Permission FROM {$tableName}_right");
        foreach ($rights as $right) {
            $jsonPerKeyId[$right['KeyID']][$right['Module']] = (int)$right['Permission'];
        }

        foreach ($jsonPerKeyId as $keyId => $moduleAccess) {
            Db::updateRow(
                ['ModuleAccessJSON' => json_encode($moduleAccess, JSON_PRETTY_PRINT)],
                $tableName,
                'ID',
                $keyId
            );
        }
    }

    private function updateSpecialAccess($tableName, $rightKeyField)
    {
        $jsonPerKeyId = [];
        $rights = Db::getAll("SELECT $rightKeyField AS KeyID, Module, SpecialID, Permission FROM {$tableName}_rightspecial");
        foreach ($rights as $right) {
            $jsonPerKeyId[$right['KeyID']][$right['Module'] . $right['SpecialID']] = (int)$right['Permission'];
        }

        foreach ($jsonPerKeyId as $keyId => $accessKey) {
            Db::updateRow(
                ['SpecialAccessJSON' => json_encode($accessKey, JSON_PRETTY_PRINT)],
                $tableName,
                'ID',
                $keyId
            );
        }
    }

}