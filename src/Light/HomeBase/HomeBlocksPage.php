<?php
namespace Light\HomeBase;

use Light\ApplicationAbstract;
use Light\Css;
use Light\Request;

/**
 * Этот класс используется для вывода блоков Home непосредственно на экране и на вкладке Home при просмотре
 * отдельно взятого пользователя в модуле Users
 * Class HomeBlocksPage
 * @package Light\SubmoduleBase
 */
class HomeBlocksPage
{
    /**
     * @param string $homeClass
     * @return string
     */
    private static function getHomeFullClass(string $homeClass): string
    {
        return PROJECT_NAMESPACE . "\\Home\\$homeClass";
    }

    public static function printHomeBlocks(int $userId): void
    {
        Css::addLight('Home.css');

        // Располагаем все блоки в одну линейку
        print '<table><tr>';
        foreach (ApplicationAbstract::$app->getHomeBlocks() as [$fullClassName, $cssClass]) {
            /** @var HomeBlockAbstract $homeBlockObj */
            $homeBlockObj = new $fullClassName($userId);
            print '<td>';
            print $homeBlockObj->getHtml($cssClass);
            print '</td>';
        }
        print '</tr></table>';
    }

    public static function proceedOneHomeBlockClass(int $userId, string $homeClass): void
    {
        $homeFullClass = self::getHomeFullClass($homeClass);
        /** @var HomeBlockAbstract $homeBlock */
        $homeBlock = new $homeFullClass($userId);

        if (Request::isXls()) {

            $homeBlock->proceedSendExcel();

        } else {

            print $homeBlock->getHtmlTableForAjax();

        }
    }
}
