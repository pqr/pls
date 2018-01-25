<?php
namespace Light;

class User
{
    private static $id = 0;
    private static $data;
    private static $moduleAccess;
    private static $specialAccess;
    private static $groupAccess;
    private static $groupSpecialAccess;

    public static function loadFromSession()
    {
        // Проверим сессию пользователя, есть ли там lightauth
        if (isset($_SESSION['lightauth'])) {

            // Проверим, есть ли такой пользователь в базе
            if (!self::loadData($_SESSION['lightauth'])) {
                unset($_SESSION['lightauth']);
                return;
            }

            //Проверим, активен ли этот пользователь всё ещё?
            if (!self::$data['Active']) {
                unset($_SESSION['lightauth']);
                return;
            }
        }
    }

    public static function loadData(string $login)
    {
        $query = "SELECT user.*
                      , usergroup.Name AS GroupName
                      , usergroup.ID AS GroupID
                      , usergroup.ModuleAccessJSON AS GroupModuleAccessJSON
                      , usergroup.SpecialAccessJSON AS GroupSpecialAccessJSON
                  FROM user, usergroup 
                  WHERE 
                    `user`.GroupID = usergroup.ID
                    AND Login = " . Db::escString($login);

        $row = Db::getRow($query);
        if (!$row) {
            return false;
        }

        self::$data = $row;
        self::$id = (int)$row['ID'];

        self::$moduleAccess = json_decode($row['ModuleAccessJSON'], true);
        self::$specialAccess = json_decode($row['SpecialAccessJSON'], true);
        self::$groupAccess = json_decode($row['GroupModuleAccessJSON'], true);
        self::$groupSpecialAccess = json_decode($row['GroupSpecialAccessJSON'], true);

        return true;
    }

    public static function getId()
    {
        return self::$id;
    }

    public static function getData()
    {
        return self::$data;
    }

    public static function getFieldData($fieldKey)
    {
        return self::$data[$fieldKey] ?? null;
    }

    public static function getLogin()
    {
        return self::$data['Login'] ?? null;
    }

    public static function getName()
    {
        return self::$data['Name'] ?? null;
    }

    public static function getGroupId()
    {
        return self::$data['GroupID'] ?? 0;
    }

    public static function getGroupName()
    {
        return self::$data['GroupName'] ?? null;
    }

    public static function getEmail()
    {
        return self::$data['Email'] ?? null;
    }

    public static function hasResponsibleRole(string $role)
    {
        $responsibleRoles = self::$data['ResponsibleRoles'] ?? null;
        if (!$responsibleRoles) {
            // Либо пользователь вообще не загружен, либо у него нет никаких ролей (поле ResponsibleRoles пусто)
            return false;
        }

        // Если внутри текста $responsibleRoles есть вхождение запрашиваемой роли, вернём true
        return strpos($responsibleRoles, '|' . $role . '|') !== false;
    }


    /**
     * @desc проверяет, залогинен ли пользователь
     * @return bool
     */
    public static function isAuth()
    {
        return self::$id !== 0;
    }

    public static function isActive()
    {
        return (bool)(self::$data['Active'] ?? false);
    }

    private static function getAccess(string $module = null): int
    {
        if (!$module) {
            $module = Request::getModule();
        }

        $moduleAccess = self::$moduleAccess[$module] ?? -1;
        if ($moduleAccess === -1) {
            return self::$groupAccess[$module] ?? 0;
        }
        return $moduleAccess;
    }

    public static function hasReadAccess($module = null)
    {
        return self::getAccess($module) > 0;
    }

    public static function hasWriteAccess($module = null)
    {
        return self::getAccess($module) === 2;
    }

    public static function hasLogInPlaceAccess()
    {
        return self::hasSpecialAccess('Logviewer1');
    }

    public static function hasSpecialAccess(string $accessKey, $userId = null):bool
    {
        if ($userId === null || $userId == self::$id) {

            $userSpecialAccess = self::$specialAccess[$accessKey] ?? -1;
            $groupSpecialAccess = self::$groupSpecialAccess[$accessKey] ?? 0;

        } else {
            // Если запрошен доступ по какому-то другому пользователю (не текущему), то загрузим из базы отдельно
            static $specialAccessCache = [];
            if (!array_key_exists($userId, $specialAccessCache)) {
                $data = Db::getRow(
                    "SELECT u.SpecialAccessJSON, g.SpecialAccessJSON AS GroupSpecialAccessJSON
                     FROM user u, usergroup g
                     WHERE u.GroupID = g.ID
                        AND u.ID = " . (int)$userId
                );
                if (!$data) {
                    $specialAccessCache[$userId] = null;
                } else {
                    $specialAccessCache[$userId] = [
                        'SpecialAccess' => json_decode($data['SpecialAccessJSON'], true),
                        'GroupSpecialAccess' => json_decode($data['GroupSpecialAccessJSON'], true)
                    ];
                }
            }

            $userSpecialAccess = $specialAccessCache[$userId]['SpecialAccess'][$accessKey] ?? -1;
            $groupSpecialAccess = $specialAccessCache[$userId]['GroupSpecialAccess'][$accessKey] ?? 0;
        }

        return $userSpecialAccess === -1 ? (bool)$groupSpecialAccess : (bool)$userSpecialAccess;

    }

    public static function getParam(string $name, int $userId = null)
    {
        return Db::getOne(
            "SELECT Value
             FROM user_param
             WHERE UserID=" . ($userId ?? self::getId()) . "
                 AND Name=" . Db::escString($name)
        );
    }

    public static function setParam(string $name, $value, int $userId = null)
    {
        //Если стока типа bool, преобразуем значения в текст
        if ($value === true) {
            $value = '1';
        } elseif ($value === false) {
            $value = '0';
        }

        Db::insertRow(['Name' => $name, 'Value' => $value, 'UserID' => $userId ?? self::getId()], 'user_param', true);

        return $value;
    }

    public static function encryptPassword(string $password, $salt = null)
    {
        if ($salt === null) {
            $salt = Misc::uniqid('salt');
        }
        return $salt . md5($password . $salt);
    }

    public static function updateLastRefreshDateTime()
    {
        Db::query("UPDATE user_visit SET LastRefreshDateTime=NOW() WHERE UserID = " . (int)self::getId());
    }
}
