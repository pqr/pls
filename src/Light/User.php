<?php

namespace Light;

use Light\Module\Core\Core;

class User
{
    /** @var User */
    private static $current;

    // Если запрошен доступ по какому-то другому пользователю (не текущему), то загрузим из базы отдельно
    static $usersCache = [];

    private $id = 0;
    private $data = [];
    private $moduleAccess = [];
    private $specialAccess = [];
    private $groupAccess = [];
    private $groupSpecialAccess = [];

    public function __construct(int $userId)
    {
        if (!$userId) {
            return;
        }

        $query = "SELECT user.*
                      , usergroup.Name AS GroupName
                      , usergroup.ID AS GroupID
                      , usergroup.ModuleAccessJSON AS GroupModuleAccessJSON
                      , usergroup.SpecialAccessJSON AS GroupSpecialAccessJSON
                  FROM user, usergroup 
                  WHERE 
                    `user`.GroupID = usergroup.ID
                    AND `user`.ID = $userId";

        $data = Db::getRow($query);
        if (!$data) {
            // Пользователь с таким ID не найден, возвращаем пустого пользователя
            return;
        }

        $this->data = $data;
        $this->id = $userId;

        $this->moduleAccess = json_decode($data['ModuleAccessJSON'], true);
        $this->specialAccess = json_decode($data['SpecialAccessJSON'], true);
        $this->groupAccess = json_decode($data['GroupModuleAccessJSON'], true);
        $this->groupSpecialAccess = json_decode($data['GroupSpecialAccessJSON'], true);

    }

    public static function resetCache(): void
    {
        self::$usersCache = [];
    }

    private static function getUserById(int $userId = null): self
    {
        if ($userId === null) {
            // Если явно не передан ID пользователя, значит нужно вернуть текущего (current) пользователя
            // Если и текущего пользователя нет, то быстренько вернём нулевого пользователя
            return self::$current ?? new User(0);
        }

        // Сначала проверим в кэше
        if (isset(self::$usersCache[$userId])) {
            return self::$usersCache[$userId];
        }

        // Если в кэше нет, то создаём на лету
        $user = new User($userId);
        if ($user->id === $userId) {
            // Если удалось-таки загрузить пользователя с таким id из базы, то сохраним в кэш
            self::$usersCache[$userId] = $user;
        }

        return $user;
    }

    public static function loadFromSession(): void
    {
        // Проверим сессию пользователя, есть ли там lightauth
        if (isset($_SESSION['lightauth'])) {

            // Проверим, есть ли такой пользователь в базе
            if (!self::loadAsCurrent($_SESSION['lightauth'])) {
                unset($_SESSION['lightauth']);
                return;
            }

            // Проверим, активен ли этот пользователь всё ещё?
            if (!self::$current->data['Active']) {
                self::$current = null;
                unset($_SESSION['lightauth']);
                return;
            }
        }
    }

    public static function loadAsCurrent(string $login)
    {
        $userId = Db::getOne("SELECT ID FROM user WHERE Login = @s", $login);
        if (!$userId) {
            return false;
        }

        self::$current = self::getUserById($userId);

        ErrorHandler::setUser(self::getId(), self::getName(), self::getEmail());

        if (extension_loaded('newrelic')) {
            // https://docs.newrelic.com/docs/agents/php-agent/php-agent-api/newrelic_add_custom_parameter
            // Record custom data about this web transaction
            newrelic_add_custom_parameter('user', User::getName());
            newrelic_add_custom_parameter('user_id', User::getId());
        }

        return true;
    }

    public static function getId(): int
    {
        if (!self::$current) {
            return 0;
        }
        return self::$current->id;
    }

    public static function getData(int $userId = null): array
    {
        return self::getUserById($userId)->data;
    }

    public static function getFieldData(string $fieldKey, int $userId = null)
    {
        return self::getUserById($userId)->data[$fieldKey] ?? null;
    }

    public static function getLogin(int $userId = null)
    {
        return self::getUserById($userId)->data['Login'] ?? null;
    }

    public static function getName(int $userId = null)
    {
        return self::getUserById($userId)->data['Name'] ?? null;
    }

    public static function getGroupId(int $userId = null)
    {
        return self::getUserById($userId)->data['GroupID'] ?? 0;
    }

    public static function getGroupName(int $userId = null)
    {
        return self::getUserById($userId)->data['GroupName'] ?? null;
    }

    public static function getEmail(int $userId = null)
    {
        return self::getUserById($userId)->data['Email'] ?? null;
    }

    public static function hasResponsibleRole(string $role, int $userId = null)
    {
        $responsibleRoles = self::getUserById($userId)->data['ResponsibleRoles'] ?? null;
        if (!$responsibleRoles) {
            // Либо пользователь вообще не загружен, либо у него нет никаких ролей (поле ResponsibleRoles пусто)
            return false;
        }

        // Если внутри текста $responsibleRoles есть вхождение запрашиваемой роли, вернём true
        return str_contains($responsibleRoles, '|' . $role . '|');
    }

    public static function hasResponsibleRoleSql(string $role): string
    {
        return "LOCATE('|" . Db::esc($role) . "|', ResponsibleRoles)";
    }

    public static function getUsersEmailsByRole(string $role): array
    {
        return Db::getCol(
            "SELECT Email
            FROM `user`
            WHERE Email != ''
                AND Active = 1
                AND " . self::hasResponsibleRoleSql($role)
        );
    }

    public static function getUserIdsByRole(string $role): array
    {
        return Db::getCol(
            "SELECT ID
            FROM `user`
            WHERE Active = 1
                AND " . self::hasResponsibleRoleSql($role)
        );
    }

    /**
     * @desc проверяет, залогинен ли пользователь
     * @return bool
     */
    public static function isAuth()
    {
        return self::$current && self::$current->id;
    }

    public static function isActive(int $userId = null)
    {
        return (bool)(self::getUserById($userId)->data['Active'] ?? false);
    }

    public static function getAccess(string $module = null, int $userId = null): int
    {
        try {

            $user = self::getUserById($userId);

        } catch (\Exception) {

            // Если не удалось загрузить пользователя из базы, то возвращаем 0 - доступ запрещён
            return 0;

        }

        if (!$module) {
            $module = Request::getModule();
        } else {
            // Модуль передан явно. Возможно, он задан полным путём к классу,
            // например Shipment::class, что в итоге даст \Basis\Module\Shipment\Shipment
            $module = Misc::getModule($module);
        }

        $userModuleAccess = $user->moduleAccess[$module] ?? -1;

        if ($userModuleAccess === -1) {
            return $user->groupAccess[$module] ?? 0;
        }

        return $userModuleAccess;
    }

    public static function hasReadAccess($module = null, int $userId = null)
    {
        return self::getAccess($module, $userId) > 0;
    }

    public static function hasWriteAccess($module = null, int $userId = null)
    {
        return self::getAccess($module, $userId) >= 2;
    }

    public static function hasDeleteAccess($module = null, int $userId = null)
    {
        return self::getAccess($module, $userId) >= 3;
    }

    public static function hasLogInPlaceAccess(int $userId = null)
    {
        return self::hasSpecialAccess('Logviewer1', $userId);
    }

    public static function hasSpecialAccess(string $accessKey, $userId = null): bool
    {
        try {

            $user = self::getUserById($userId);

        } catch (\Exception) {

            // Если не удалось загрузить пользователя из базы, то возвращаем 0 - доступ запрещён
            return 0;

        }

        $userSpecialAccess = $user->specialAccess[$accessKey] ?? -1;

        if ($userSpecialAccess === -1) {
            return (bool)($user->groupSpecialAccess[$accessKey] ?? 0);
        }

        return (bool)$userSpecialAccess;
    }

    public static function getUserIdsBySpecialAccess(string $accessKey): array
    {
        $userIds = [];
        $activeUsers = Db::getAll("SELECT u.ID, u.SpecialAccessJSON, g.SpecialAccessJSON AS GroupSpecialAccessJSON FROM user u, usergroup g WHERE u.GroupID = g.ID AND Active = 1");
        if ($activeUsers) {
            foreach ($activeUsers as $activeUser) {
                $specialAccess = json_decode($activeUser['GroupSpecialAccessJSON'], true);
                if (isset($specialAccess[$accessKey]) && $specialAccess[$accessKey] == 1) {
                    $userIds[] = $activeUser['ID'];
                } elseif (!isset($specialAccess[$accessKey]) || $specialAccess[$accessKey] == -1) {
                    $groupSpecialAccess = json_decode($activeUser['SpecialAccessJSON'], true);
                    if (isset($groupSpecialAccess[$accessKey]) && $groupSpecialAccess[$accessKey] == 1) {
                        $userIds[] = $activeUser['ID'];
                    }
                }
            }
        }
        return $userIds;
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
        // Если стока типа bool, преобразуем значения в текст
        if ($value === true) {
            $value = '1';
        } elseif ($value === false) {
            $value = '0';
        }

        $data = [
            'UserID' => $userId ?? self::getId(),
            'Name' => $name,
            'Value' => $value,
        ];

        // Используем insert on duplicate key update (обновляем Value)
        Db::insertRow($data, 'user_param', false, false, ['Value']);

        return $value;
    }

    public static function encryptPassword(string $password, $salt = '')
    {
        return $salt . md5($password . $salt);
    }

    public static function updateLastRefreshDateTime(): void
    {
        Db::query("UPDATE user_visit SET LastRefreshDateTime=NOW() WHERE UserID = " . (int)self::getId());
    }

    public static function getEmailAccountId(): ?int
    {
        return Db::getOne("SELECT ID FROM emailaccount WHERE Username = @s", User::getEmail());
    }

    public static function avatarImgHtml(int $userId = null): string
    {
        return '<img class="avatar" src="' . self::avatarImgSrc($userId ?? self::getId()) . '">';
    }

    public static function hasAvatar(int $userId = null): bool
    {
        return file_exists(self::avatarFilepath($userId ?? self::getId()));
    }

    public static function avatarImgWithNameHtml(int $userId = null, string $innerHtml = null): string
    {
        $userId ??= self::getId();
        $innerHtml ??= htmlspecialchars(self::getName());
        return '<div class="AvatarWithUsername">' . self::avatarImgHtml($userId) . ' ' . $innerHtml . '</div>';
    }

    public static function avatarFilepath(int $userId = null): string
    {
        $relative = FilesFolder::getRelativeModule('User', $userId ?? self::getId());
        return FilesFolder::getAbsolute("$relative/Avatar.jpg");
    }

    public static function avatarImgSrc(int $userId = null): string
    {
        $userId ??= self::getId();
        if (self::hasAvatar($userId)) {
            $avatarFilepathAbs = self::avatarFilepath($userId);
            $mtime = filemtime($avatarFilepathAbs);
            return Link::create(Core::class)
                ->proceedStatic('GetUserAvatar')
                ->params(['UserID' => $userId, 'nouserrefreshtime' => 1, 'mtime' => $mtime])
                ->getHref();
        }
        return "lightpublic/i/user.png";
    }
}
