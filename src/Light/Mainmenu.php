<?php
namespace Light;

use Light\Field\FieldDate;

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

            $section = (string)ModuleRegister::getModuleSection($module);
            $sections[$section]['items'][] = $module;
        }


        //Добавим к каждой секции параметры
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
        $outHtml = Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/Mainmenu.tpl.php', [
            'sections' => $sections,
            'badges' => self::getBadges($sections),
            'rates' => self::getCurrencyRates()
        ]);
        $outHtml = Misc::minifyHtml($outHtml);
        return $outHtml;
    }

    private static function getBadges($sections)
    {
        $badges = [];
        foreach ($sections as $section) {
            foreach ($section['items'] as $module) {

                if (!ModuleRegister::hasBadge($module)) {
                    continue;
                }

                $class = ModuleRegister::getModuleClass($module);
                /** @var callable $function */
                /**
                 * В модуле может быть определён статический метод getMainmenuBadge()
                 * Он может вернуть false - значит для данного модуля нет бейджа
                 * или он может вернуть массив с параметрами:
                 * нулевой элемент массива - текст для бейджа (обычно число с кол-вом уведомлений)
                 * первый элемент массива - css класс для подстветки, обычно это классы badge-* (см. Light.css)
                 * второй элемент массива (опционально) - urlParams - массив дополнительных параметров для ссылки в меню, если есть какие-то уведомления
                 * Если все элементы - массивы, значит это несколько бейджей сразу
                 */
                $function = [$class, 'getMainmenuBadge'];
                $result = $function();
                if (is_array($result)) {

                    if (A::every($result, function ($elem) {
                        return is_array($elem);
                    })) {
                        $html = [];
                        $urlParams = [];
                        foreach ($result as $badgeData) {
                            list($text, $cssClass) = $badgeData;
                            $html[] = '<span class="' . $cssClass . '">' . $text . '</span>';
                            if (isset($badgeData[2])) {
                                $urlParams = array_merge($urlParams, $badgeData[2]);
                            }
                        }
                        $badges[$module] = [
                            'html' => implode(' ', $html),
                            'urlParams' => $urlParams ?: null
                        ];
                    } else {
                        list($text, $cssClass) = $result;
                        $badges[$module] = [
                            'html' => '<span class="' . $cssClass . '">' . $text . '</span>',
                            'urlParams' => $result[2] ?? null
                        ];
                    }

                }
            }
        }

        return $badges;
    }
}
