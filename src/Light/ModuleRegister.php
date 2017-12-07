<?php

namespace Light;

class ModuleRegister
{
    const MODULE_HIDDEN = 0b01;
    const MODULE_HASBADGE = 0b10;

    private static $coreModules = ['Core' => 'Core', 'Login' => 'Login', 'Logout' => 'Logout'];

    public static $modules = [
        'Home' => [
            'name' => 'Home',
            'order' => -1,
            'hidden' => false,
            'section' => ''
        ],
        'Reportmenu' => [
            'name' => 'Reports',
            'order' => 80,
            'hidden' => false,
            'section' => ''
        ],
        'Currency' => [
            'name' => 'Currency',
            'order' => 90,
            'hidden' => false,
            'section' => 'Currency'
        ],
        'CurrencyRate' => [
            'name' => 'Currency Rates',
            'order' => 91,
            'hidden' => false,
            'section' => 'Currency'
        ],
        'UserModule' => [
            'name' => 'Users',
            'order' => 100,
            'hidden' => false,
            'section' => 'User Management'
        ],
        'Logviewer' => [
            'name' => 'Logs',
            'order' => 103,
            'hidden' => false,
            'section' => 'System'
        ],
        'Deleted' => [
            'name' => 'Deleted',
            'order' => 105,
            'hidden' => false,
            'section' => 'System'
        ],
        'Autolaunch' => [
            'name' => 'Autolaunch',
            'order' => 106,
            'hidden' => false,
            'section' => 'System'
        ],
        'EmailQueueModule' => [
            'name' => 'Email Queue',
            'order' => 107,
            'hidden' => false,
            'section' => 'System'
        ],
        'EmailLogModule' => [
            'name' => 'Email Log',
            'order' => 108,
            'hidden' => false,
            'section' => 'System'
        ],
        'ColorHint' => [
            'name' => 'Color Hints',
            'order' => 109,
            'hidden' => false,
            'section' => 'System'
        ],
        'FiltersLog' => [
            'name' => 'Filters Log',
            'order' => 111,
            'hidden' => false,
            'section' => 'System'
        ],
        'Phpinfo' => [
            'name' => 'PHP Info',
            'order' => 112,
            'hidden' => false,
            'section' => 'System'
        ]
    ];

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
            array_multisort(array_column(self::$modules, 'order'), $orderedModulesKeys);
        }

        return $orderedModulesKeys;
    }

    public static function getVisibleModulesKeys(): array
    {
        return array_filter(
            self::getModulesKeys(),
            function ($module) {
                return !self::$modules[$module]['hidden'];
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
            'hidden' => $binOptions & self::MODULE_HIDDEN,
            'badge' => $binOptions & self::MODULE_HASBADGE
        ];
    }

    public static function removeModule(string $modue)
    {
        unset(self::$modules[$modue]);
    }

    public static function hasBadge(string $module): bool
    {
        return isset(self::$modules[$module]['badge']) && self::$modules[$module]['badge'];
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
