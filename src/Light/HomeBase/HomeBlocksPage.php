<?php
namespace Light\HomeBase;

use Light\ApplicationAbstract;
use Light\Css;
use Light\Response;
use Light\Template;

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
    private static function getHomeFullClass($homeClass)
    {
        return PROJECT_NAMESPACE . "\\Home\\$homeClass";
    }


    /**
     * @param string $fullClassName
     * @return string
     */
    private static function getHomeShortClass($fullClassName)
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }

    /**
     * Возвращает отсортированный массив шаблонных переменных для вывода блоков
     * @param int $userId
     * @return array
     */
    private static function getBlocksTplData($userId)
    {
        /** @var HomeBlockAbstract[] $objects */
        $tdata = [];
        foreach (ApplicationAbstract::$app->getHomeBlocks() as list($fullClassName, $cssClass)) {
            $className = self::getHomeShortClass($fullClassName);
            /** @var HomeBlockAbstract $homeObj */
            $homeObj = new $fullClassName($userId);
            $blockTdata = $homeObj->getTplData();
            if ($blockTdata) {
                $tdata[$className] = $blockTdata + ['cssClass' => $cssClass];
            }
        }
        return $tdata;
    }

    public static function printHomeBlocks($userId)
    {
        Css::addLight('Home.css');

        //Располагаем все блоки в одну линейку
        print '<table><tr>';
        foreach (self::getBlocksTplData($userId) as $blockTdata) {
            print '<td>';
            Template::includeTpl('homeblock.tpl.php', $blockTdata);
            print '</td>';
        }
        print '</tr></table>';
    }

    /**
     * Отправляет в поток Excel файл и возвращает true, если передано правельное имя класса
     * Если блока с таким $homeClass нет, то метод просто возвращает false, ничего не отправляя в поток
     * @param int $userId
     * @param string $homeClass
     */
    public static function sendExcel($userId, $homeClass)
    {
        $homeFullClass = self::getHomeFullClass($homeClass);
        /** @var HomeBlockAbstract $homeBlock */
        $homeBlock = new $homeFullClass($userId);
        $homeBlock->sendExcel();
    }

    public static function tableAjaxReload($userId, $homeClass)
    {
        $homeFullClass = self::getHomeFullClass($homeClass);
        /** @var HomeBlockAbstract $homeBlock */
        $homeBlock = new $homeFullClass($userId);
        print $homeBlock->getHtmlTableForAjax();
    }
}