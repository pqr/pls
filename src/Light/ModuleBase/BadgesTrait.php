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
            if ($badge->listFilters) {

                // Если есть параметр listFilters, то оборачиваем в ссылку
                $text = Link::create($module, $text)
                    ->filters($badge->listFilters)
                    ->cssClass($cssClass)
                    ->getHtml();

            } else {

                // Если же listFilters нет, то обренём в простой div для подсветки
                $text = "<div class=\"$cssClass\">$text</div>";

            }
            $out[] = $text;
        }

        return $out;
    }
}
