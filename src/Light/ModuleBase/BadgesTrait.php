<?php


namespace Light\ModuleBase;

use Light\Link;
use Light\Services\ModuleBadgeLoader;

/**
 * Этот trait безусловно подключается к ModuleAbstract.
 * Код вынесен в этот trait исключительно чтобы облегчить файл ModuleAbstract.php
 */
trait BadgesTrait
{
    protected function getListBadgeMessagess()
    {
        $out = [];

        $module = $this->getModule();
        $badges = ModuleBadgeLoader::getBadgesForModule($module);
        /** @var Badge $badge */
        foreach ($badges as $badge) {

            $text = $badge->title . ': <strong>' . $badge->counter . '</strong>';
            $cssClass = 'alert-badge alert-' . $badge->type;
            if ($badge->listFilters || $badge->urlParams) {

                // Если есть параметр listFilters или urlParams, то оборачиваем в ссылку
                $link = Link::create($module, $text)->cssClass($cssClass);

                if ($badge->listFilters) {
                    $link->filters($badge->listFilters);
                }

                if ($badge->urlParams) {
                    $link->params($badge->urlParams);
                }

                $text = $link->getHtml();

            } else {

                // Если же listFilters нет, то обренём в простой div для подсветки
                $text = "<div class=\"$cssClass\">$text</div>";

            }
            $out[] = $text;
        }

        return $out;
    }
}
