<?php
namespace Light\Intro;

use Light\Css;
use Light\Db;
use Light\Js;
use Light\Request;
use Light\Response;
use Light\User;

class IntroManager
{
    private static $intros = [];

    public static function addIntro($selector, $text, $additionalUniqueKey = ''): void
    {
        $hash = md5(Request::getModule() . $additionalUniqueKey . $selector . $text);
        self::$intros[$hash] = [$selector, $text];
    }

    public static function addIntroWithHash($selector, $text, $hash): void
    {
        self::$intros[$hash] = [$selector, $text];
    }

    public static function show(): void
    {
        if (!self::$intros) {
            return;
        }

        $hashes = array_keys(self::$intros);
        $paramsNames = array_map(fn($hash) => 'intro' . $hash, $hashes);

        $viewedIntros = Db::getAssoc("SELECT Name, 1 FROM user_param WHERE Name IN (@a) AND UserID = @i", $paramsNames,
            User::getId());
        $introsToShow = [];
        foreach (self::$intros as $hash => $selectorAndText) {
            if (!isset($viewedIntros['intro' . $hash])) {
                //Это интро ещё не просмотрено текущим пользователем
                $introsToShow[$hash] = $selectorAndText;
            }
        }

        if (!$introsToShow) {
            return;
        }

        Response::addJsData(['intros' => $introsToShow]);
        Css::addLight('joyride-2.1.css');
        Js::addLight('jquery.joyride-2.1.js');
        Js::addLight('IntroManager.js');
    }
}