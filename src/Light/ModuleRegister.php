<?php

namespace Light;

class ModuleRegister
{
    const MODULE_HIDDEN = 0b01;
    const MODULE_HASBADGE = 0b10;

    private static $coreModules = [
        'Core' => 'Core',
        'Login' => 'Login',
        'Logout' => 'Logout',
        'Bot' => 'Bot',
        'UserSettings' => 'UserSettings',
    ];

    public static $modules = [
        'UserSettings' => [
            'name' => 'User Settings',
            'order' => -1,
            'section' => '',
        ],
        'Home' => [
            'name' => 'Home',
            'order' => -1,
            'section' => '',
        ],
        'Reportmenu' => [
            'name' => 'Reports',
            'order' => 80,
            'section' => '',
        ],
        'Currency' => [
            'name' => 'Currency',
            'order' => 90,
            'section' => 'Currency',
        ],
        'CurrencyRate' => [
            'name' => 'Currency Rates',
            'order' => 91,
            'section' => 'Currency',
        ],
        'UserModule' => [
            'name' => 'Users',
            'order' => 100,
            'section' => 'User Management',
        ],
        'ProductionCalendar' => [
            'name' => 'Производств.календарь',
            'order' => 102,
            'section' => 'System',
        ],
        'Logviewer' => [
            'name' => 'Logs',
            'order' => 103,
            'section' => 'System',
        ],
        'Deleted' => [
            'name' => 'Deleted',
            'order' => 105,
            'section' => 'System',
        ],
        'Autolaunch' => [
            'name' => 'Autolaunch',
            'order' => 106,
            'section' => 'System',
        ],
        'EmailAccount' => [
            'name' => 'E-mail Accounts',
            'order' => 107,
            'section' => 'System',
        ],
        'EmailQueueModule' => [
            'name' => 'E-mail Queue',
            'order' => 108,
            'section' => 'System',
        ],
        'EmailLogModule' => [
            'name' => 'Email Log',
            'order' => 109,
            'section' => 'System',
        ],
        'SettingsBackup' => [
            'name' => 'Backup',
            'order' => 110,
            'section' => 'System',
        ],
        'ColorHint' => [
            'name' => 'Color Hints',
            'order' => 111,
            'section' => 'System',
        ],
        'FiltersLog' => [
            'name' => 'Filters Log',
            'order' => 112,
            'section' => 'System',
        ],
        'Phpinfo' => [
            'name' => 'PHP Info',
            'order' => 113,
            'section' => 'System',
        ],
    ];

    private static $titles = [];

    private static $hiddenModules = ['UserSettings' => true];

    private static $withBadges = [];

    private static $defaultModule = 'Home';

    public static function getDefaultModule(): string
    {
        return static::$defaultModule;
    }

    public static function setDefaultModule(string $defaultModule)
    {
        static::$defaultModule = $defaultModule;
    }

    public static function getModulesKeys(): array
    {
        static $orderedModulesKeys;
        if ($orderedModulesKeys === null) {
            $orderedModulesKeys = array_keys(self::$modules);
            $orders = array_column(self::$modules, 'order');
            // TODO: проверить, похоже здесь не верно реализована сортировка
            array_multisort($orders, $orderedModulesKeys);
        }

        return $orderedModulesKeys;
    }

    public static function getVisibleModulesKeys(): array
    {
        return array_filter(
            self::getModulesKeys(),
            function ($module) {
                return !isset(self::$hiddenModules[$module]);
            }
        );
    }

    public static function isModuleExists(string $module): bool
    {
        return isset(self::$modules[$module]);
    }

    public static function isModuleCore(string $module): bool
    {
        return isset(self::$coreModules[$module]);
    }

    public static function getModuleName(string $module): string
    {
        return self::$modules[$module]['name'] ?? '';
    }

    public static function getModuleTitle(string $module): string
    {
        return self::$titles[$module] ?? '';
    }

    public static function getModuleSection(string $module): string
    {
        return self::$modules[$module]['section'] ?? '';
    }

    public static function addModule(string $module, string $name, string $section = '', int $binOptions = 0)
    {
        static $autoorder = 0;
        $autoorder++;

        self::$modules[$module] = [
            'name' => $name,
            'section' => $section,
            'order' => $autoorder,
        ];

        if ($binOptions & self::MODULE_HIDDEN) {
            self::$hiddenModules[$module] = true;
        }

        if ($binOptions & self::MODULE_HASBADGE) {
            self::$withBadges[$module] = true;
        }
    }

    public static function setTitle(string $module, string $title)
    {
        self::$titles[$module] = $title;
    }

    public static function removeModule(string $modue)
    {
        unset(self::$modules[$modue]);
    }

    public static function hasBadge(string $module): bool
    {
        return isset(self::$withBadges[$module]);
    }

    public static function getModuleClass(string $module): string
    {
        static $moduleClass = [];

        if (!isset($moduleClass[$module])) {

            // Произведём поиск модуля в двух папках:
            // в папке проекта в подпапке Module и в папке Light/Module
            $moduleFile = PROJECT_NAMESPACE . "/Module/$module/$module.php";
            if (!file_exists(LIGHT_DIR_ROOT . "/src/$moduleFile")) {
                // Если в папке проекта не нашли, попробуем найти в папке движка
                $moduleFile = "Light/Module/$module/$module.php";
                if (!file_exists(LIGHT_DIR_ROOT . "/src/$moduleFile")) {
                    throw new \InvalidArgumentException("Unknown module $module");
                }
            }
            $moduleClass[$module] = str_replace(['/', '.php'], ['\\', ''], $moduleFile);

        }

        return $moduleClass[$module];
    }
}
