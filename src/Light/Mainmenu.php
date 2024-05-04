<?php

namespace Light;

use Light\Field\FieldDate;
use Light\Module\UserSettings\UserSettings;
use Light\ModuleBase\Badge;
use Light\Services\ModuleBadgeLoader;

class Mainmenu
{
    public const PARAM_PINNED = 'Mainmenu_pinned';

    private static $unpinnedForThisPage = false;

    private static function getUserParams()
    {
        $query = "SELECT Name, Value
                  FROM user_param
                  WHERE UserID = " . User::getId() . " AND Name LIKE 'Mainmenu/%'";

        return Db::getAssoc($query);
    }

    public static function proceedMainmenuSettings(): void
    {
        if (Request::isPost()) {
            self::proceedMainmenuSettingsSubmit();
            return;
        }

        self::proceedMainmenuSettingsShowDialog();
    }

    public static function proceedMainmenuSettingsShowDialog(): void
    {
        $favorites = self::loadFavoriteModules();

        // Сформируем два массива для вывода в шаблон
        $favoriteItems = [];
        $allItems = [];
        foreach (self::getAvailableModules() as $module) {

            $moduleName = ModuleRegister::getModuleName($module);

            // Добавлем текущий модуль либо в массив $favoriteItems, либо в массив $allItems
            if (in_array($module, $favorites)) {
                $favoriteItems[$module] = $moduleName;
            } else {
                $allItems[$module] = $moduleName;
            }
        }

        $tdata = [
            'favoriteItems' => $favoriteItems,
            'allItems' => $allItems,
        ];

        $bodyHtml = Template::parseTpl('MainmenuSettings.tpl.php', $tdata);

        Response::sendHeaderJsEval('Light.mainmenu.initMenuSettingsDialog()');
        Response::showDialog($bodyHtml, 'Сохранить', 'Light.mainmenu.settingsDialogSubmit', null, null, 500);
    }

    private static function loadFavoriteModules(): array
    {
        $favorites = explode(',', (string)User::getParam('MainmenuFavorites'));
        if (!$favorites) {
            return [];
        }

        // Оставим только те модули, которые реально сущетсвуют и к которым у пользователя есть доступ
        $availableModules = self::getAvailableModules();

        $favorites = array_intersect($favorites, $availableModules);
        return $favorites;
    }

    public static function proceedMainmenuSettingsSubmit(): void
    {
        // Список избранных модулей приходит через запятую, так и сохраняем
        $newFavorites = $_POST['favorites'] ?? '';
        User::setParam('MainmenuFavorites', $newFavorites);

        // Нужно вернуть новый HTML код для избранных пунктов меню, которые будут отображены выше остальных
        print self::getFavoritesHtml();
    }

    private static function getFavoritesHtml(): string
    {
        $favorites = self::loadFavoriteModules();
        if (!$favorites) {
            return '';
        }

        // Подготовим данные для шаблона
        $favoritesSection = ['items' => [], 'status' => ''];
        foreach ($favorites as $module) {
            $favoritesSection['items'][$module] = self::getModuleItem($module);;
        }

        $favoritesHtml = Template::parseTpl('MainmenuSection.tpl.php', $favoritesSection);

        // В конце под писком избранных пунктов меню добавим разделитель от отсновго блока меню
        $favoritesHtml .= '<hr>';

        return $favoritesHtml;
    }

    /**
     * Возвращает реально существующие модули к которому у пользователя есть доступ хотя бы на чтение
     * @return array
     */
    private static function getAvailableModules(): array
    {
        $out = [];
        $visibleModules = ModuleRegister::getVisibleModulesKeys();
        foreach ($visibleModules as $module) {
            if (User::hasReadAccess($module)) {
                $out[] = $module;
            }
        }
        return $out;
    }

    /**
     * @return array
     */
    private static function getSections()
    {
        $sections = [];

        // Разложим модули по секциям
        foreach (self::getAvailableModules() as $module) {

            if (!User::hasReadAccess($module)) {
                continue;
            }

            $section = ModuleRegister::getModuleSection($module);
            $sections[$section]['items'][$module] = self::getModuleItem($module);
        }

        // Добавим к каждой секции параметры
        $userParams = self::getUserParams();
        foreach ($sections as $section => &$sectionParams) {

            $paramName = 'Mainmenu/' . $section;
            if (isset($userParams[$paramName]) && $userParams[$paramName] === 'closed') {
                $status = 'closed';
            } else {
                $status = 'open';
            }

            $sectionParams['status'] = $status;
        }

        return $sections;
    }

    private static function getModuleItem(string $module): array
    {
        $moduleName = ModuleRegister::getModuleName($module);
        $badges = ModuleBadgeLoader::getBadgesForModule($module);
        $urlParams = ModuleBadgeLoader::getMainmenuUrlParams($module);

        $text = $moduleName;
        if ($badges) {
            /** @var Badge $badge */
            foreach ($badges as $badge) {

                $badgeTitle = '';
                if ($badge->title) {
                    $badgeTitle = htmlspecialchars($badge->title) . ': ' . $badge->counter;
                }

                $text .= '&nbsp;<span class="badge-' . $badge->type . '" title="' . $badgeTitle . '">' . $badge->counter . '</span>';
            }
        }

        $title = ModuleRegister::getModuleTitle($module);
        $url = Link::create($module, '')->params($urlParams)->getHref();

        return ['text' => $text, 'title' => $title, 'url' => $url];
    }

    private static function getCurrencyRates()
    {
        return [
            'Date' => FieldDate::beautify(Currency::getRealDate('USD')),
            'CBRFEURUSD' => round(Currency::getRate('EUR'), 4),
            'CBRFUSDRUB' => round(Currency::getRate('USD', 'RUB'), 4),
            'CBRFEURRUB' => round(Currency::getRate('EUR', 'RUB'), 4),
        ];
    }

    public static function getMainmenuHtml()
    {
        $favoritesHtml = self::getFavoritesHtml();

        // Основные секции
        $sections = self::getSections();
        $sectionsHtml = [];
        foreach ($sections as $section => $sectionParams) {
            $sectionsHtml[$section] = Template::parseTpl('MainmenuSection.tpl.php', $sectionParams);
        }

        $tdata = [
            'favoritesHtml' => $favoritesHtml,
            'sectionsHtml' => $sectionsHtml,
        ];

        $avatar = User::avatarImgHtml(User::getId());
        $userName = htmlspecialchars(User::getName());
        if (User::hasWriteAccess(UserSettings::class)) {
            $userName = Link::create(UserSettings::class, $userName)->getHtml();
            $avatar = Link::create(UserSettings::class, $avatar)->getHtml();
        }
        $tdata['UserName'] = $userName;
        $tdata['Avatar'] = $avatar;

        if (Config::$showRatesInMenu) {
            $tdata['rates'] = self::getCurrencyRates();
        }
        return Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/Mainmenu.tpl.php', $tdata);
    }

    public static function setUnpinnedForThisPage(): void
    {
        self::$unpinnedForThisPage = true;
    }

    public static function isPinned(): bool
    {
        if (self::$unpinnedForThisPage) {
            // Для данной страницы принудительно была установлена опция "сделать меню unpinned"
            // возвращаем статус pinned = false
            return false;
        }

        // Если принудильных указаний внутри текущего модуля не было, то загрузим данные из настроек пользователя
        return (bool)(User::getParam(self::PARAM_PINNED) ?? true);
    }
}
