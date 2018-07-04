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
}
