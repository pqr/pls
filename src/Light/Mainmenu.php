<?php

namespace Light;

use Light\Field\FieldDate;
use Light\ModuleBase\Badge;
use Light\Services\ModuleBadgeLoader;

class Mainmenu
{
    private static function getUserParams()
    {
        $query = "SELECT Name, Value
                  FROM user_param
                  WHERE UserID = " . User::getId() . " AND Name LIKE 'Mainmenu/%'";

        return Db::getAssoc($query);
    }

    /**
     * Подготавливает массив секций на основе validActions
     * @return array
     */
    private static function getSections()
    {
        $sections = [];

        //Разложим модули по секциям

        foreach (ModuleRegister::getVisibleModulesKeys() as $module) {

            if (!User::hasReadAccess($module)) {
                continue;
            }

            $section = ModuleRegister::getModuleSection($module);
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
            $sections[$section]['modules'][$module] = ['text' => $text, 'title' => $title, 'url' => $url];
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
        $sections = self::getSections();
        $tdata = [
            'sections' => $sections,
        ];
        if (Config::$showRatesInMenu) {
            $tdata['rates'] = self::getCurrencyRates();
        }
        return Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/Mainmenu.tpl.php', $tdata);
    }

    public static function hasUserAvatar(int $userId): bool
    {
        return file_exists(self::getUserAvatarFilepathAbs($userId));
    }

    public static function getUserAvatarFilepathAbs(int $userId): string
    {
        $relativePath = FilesFolder::getModuleRelative('User', $userId);
        $avatarFilepathAbs = FilesFolder::getAbsolutePath("$relativePath/Avatar.jpg");
        return $avatarFilepathAbs;
    }

    public static function getUserAvatarImgTag(int $userId)
    {
        if (Mainmenu::hasUserAvatar($userId)) {
            $avatarFilepathAbs = self::getUserAvatarFilepathAbs($userId);
            $mtime = filemtime($avatarFilepathAbs);
            return '<img class="avatar" src="index.php?a=Core&ps=GetUserAvatar&UserID=' . $userId . '&nouserrefreshtime=1&mtime=' . $mtime . '">';
        }
        return '<img class="avatar" src="lightpublic/i/user.png">';
    }

    public static function streamUserAvatar(int $userId)
    {
        if (Mainmenu::hasUserAvatar($userId)) {
            $avatarFileAbs = Mainmenu::getUserAvatarFilepathAbs($userId);
            // Кэшируем изображение навечно, т.к. в url есть признак времени изменения файла (см getUserAvatarImgTag)
            // https://www.mnot.net/cache_docs/
            Response::header('Content-Type: image/jpg');
            Response::header('Cache-Control: public, max-age=31556926');
            Response::header('Expires: Sun, 17-Jan-2038 19:14:07 GMT');
            Response::header('Pragma: ');
            readfile($avatarFileAbs);
            ApplicationAbstract::terminate();
            return;
        }

        Response::redirectUrl('lightpublic/i/user.png');
    }
}
