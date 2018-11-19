<?php
namespace Light;

use Detection\MobileDetect;

class Request
{
    public static $detect;
    private static $isMobileOrTablet;

    public static function reset()
    {
        self::$isMobileOrTablet = null;
    }

    public static function getMehod()
    {
        return $_SERVER["REQUEST_METHOD"] ?? '';
    }

    public static function isGet()
    {
        return self::getMehod() === 'GET';
    }

    public static function isPost()
    {
        return self::getMehod() === 'POST';
    }

    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public static function isXls()
    {
        return isset($_GET['xls']);
    }

    public static function getModule()
    {
        return $_GET['a'] ?? '';
    }

    public static function isMobileOrTablet()
    {
        if (self::$isMobileOrTablet === null) {

            if (!self::$detect) {
                self::$detect = new MobileDetect();
            }

            self::$isMobileOrTablet = self::$detect->isMobile() || self::$detect->isTablet();

        }

        return self::$isMobileOrTablet;
    }
}
