<?php

namespace Light;

class Js
{
    public static $select2 = false;
    public static $choices = false;
    public static $angularJs = false;
    public static $react = false;
    public static $preact = false;
    public static $preactcompat = false;
    public static $mobx = false;

    private static $lightFiles = [];
    private static $projectFiles = [];

    public static function reset(): void
    {
        self::$select2 = false;
        self::$choices = false;
        self::$angularJs = false;
        self::$react = false;
        self::$preact = false;
        self::$preactcompat = false;
        self::$mobx = false;

        self::$lightFiles = [];
        self::$projectFiles = [];
    }

    public static function addLight(string $file): void
    {
        self::$lightFiles[] = "lightpublic/js/$file";
    }

    public static function add(string $file): void
    {
        if (strpos($file, '//') !== 0) {
            $file = "js/$file";
        }
        self::$projectFiles[] = $file;
    }

    public static function getSources(): array
    {
        $min = Config::$debug ? '' : '.min';

        $out = [
            'lightpublic/js/vendors.js'
        ];
        if (self::$select2) {
            $out[] = 'lightpublic/js/select2.min.js';
        }
        if (self::$choices) {
            $out[] = 'lightpublic/js/choices.min.js';
        }
        $out[] = 'lightpublic/js/build/lightbuild.js';
        if (self::$angularJs) {
            $out[] = "lightpublic/js/angular/angular$min.js";
            $out[] = "lightpublic/js/angular/angular-sanitize$min.js";
        }
        if (self::$react) {

            $suffix = Config::$debug ? 'development' : 'production.min';
            $out[] = "lightpublic/js/react/react.$suffix.js";
            $out[] = "lightpublic/js/react/react-dom.$suffix.js";

        }
        if (self::$preact) {
            if (Config::$debug) {
                $out[] = "lightpublic/js/preact/preact.dev.js";
            } else {
                $out[] = "lightpublic/js/preact/preact.min.js";
            }
        }
        if (self::$preactcompat) {
            $out[] = "lightpublic/js/preact/prop-types$min.js";
            $out[] = "lightpublic/js/preact/preact-compat$min.js";
            $out[] = "lightpublic/js/preact/preactCompatGlobal.js";
        }
        if (self::$mobx) {
            $out[] = "lightpublic/js/mobx/mobx.umd$min.js";
            $out[] = "lightpublic/js/mobx/serializr$min.js";
            if (self::$react || self::$preactcompat) {
                $out[] = "lightpublic/js/mobx/mobx-react$min.js";
            }
        }

        $out = array_merge($out, self::$lightFiles, ['js/general.js'], self::$projectFiles);

        // Добавим файл отвечающий за текущий модуль
        $module = Request::getModule();
        $moduleFile = "js/Module/$module/$module.js";
        if (file_exists(LIGHT_DIR_PUBLIC . "/$moduleFile")) {
            $out[] = $moduleFile;
        }

        if (Config::$jslog) {
            $out[] = 'lightpublic/js/Jslog.js';
        }

        // Добавим метки времени, чтобы обновлять закешированные файлы
        foreach ($out as &$file) {
            if (strpos($file, '//') !== 0) {
                // Добавим метку времени для уникальности
                // но только если имя файла не начинается с // - это глобальные интернет пути
                if (strpos($file, 'lightpublic/') === 0) {
                    $srcFile = LIGHT_DIR_LIGHTPUBLIC . '/' . str_replace('lightpublic/', '', $file);
                } else {
                    $srcFile = LIGHT_DIR_PUBLIC . "/$file";
                }
                $file .= '?' . filemtime($srcFile);
            }
        }

        return $out;
    }

}
