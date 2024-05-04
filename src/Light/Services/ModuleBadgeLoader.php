<?php


namespace Light\Services;


use Light\ModuleBase\ModuleBadgeInterface;
use Light\ModuleRegister;

class ModuleBadgeLoader
{
    public static function getBadgesForModule(string $module): array
    {
        static $badgesForModule = [];

        if (!isset($badgesForModule[$module])) {
            // Механизм кеширования.
            // Сначала получим объект имплементирующий ModuleBadgeInterface
            // он получается из класса, который должен лежать в папке модуля с суффикстом Badge,
            // например ShipmentsBadge.php
            $moduleBadgeObj = self::getModuleBadgeObj($module);
            if ($moduleBadgeObj) {
                // Если такой класс есть и из него успешно создан обдъект имплементирующий ModuleBadgeInterface
                // Получаем массив инстансов класса Badge
                $badgesForModule[$module] = $moduleBadgeObj->getBadges();
            } else {
                // Для текущего модуля не определён класс имплементирующий ModuleBadgeInterface (нет файла {module}Badge.php в папке модуля)
                $badgesForModule[$module] = [];
            }
        }

        return $badgesForModule[$module];
    }

    public static function getMainmenuUrlParams(string $module): array
    {
        $moduleBadgeObj = self::getModuleBadgeObj($module);
        if (!$moduleBadgeObj) {
            return [];
        }

        return $moduleBadgeObj->getMainmenuUrlParams();
    }

    /**
     * Приватый метод, инстанциирует и возвращает объекты отвечающие за бейджи в указанном модуле
     * Чтобы этот метод вернул объект имплементирующий ModuleBadgeInterface, в папке модуля должен быть файл {module}Badge.php
     * А также при регистрации модуля в Application::registerModules() должен был быть указан послдений параметр ModuleRegister::MODULE_HASBADGE
     * @return ModuleBadgeInterface|null
     */
    private static function getModuleBadgeObj(string $module): ?ModuleBadgeInterface
    {
        static $moduleBadgeObjects = [];
        if (!array_key_exists($module, $moduleBadgeObjects)) {

            if (!ModuleRegister::hasBadge($module)) {

                $moduleBadgeObjects[$module] = null;

            } else {

                /**
                 * В папке модуля может располагаться класс с именем "{$module}Badge"
                 * Этот класс должен имплементировать интерфейс ModuleBadgeInterface,
                 * т.е. у него должны быть методы getBadges() и getUrlParams()
                 */
                $badgeClass = PROJECT_NAMESPACE . "\\Module\\$module\\{$module}Badge";
                if (!class_exists($badgeClass)) {

                    // Если в Application напротив модуля стоит флаг MODULE_HASBADGE, но реально класса нет, ничего не делаем
                    $moduleBadgeObjects[$module] = null;

                } else {

                    $moduleBadgeObj = new $badgeClass();
                    $moduleBadgeObjects[$module] = $moduleBadgeObj;

                }

            }
        }

        return $moduleBadgeObjects[$module];
    }
}
