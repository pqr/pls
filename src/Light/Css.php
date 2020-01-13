<?php
namespace Light;

class Css
{
    public static $select2 = false;
    public static $choices = false;

    private static $lightFiles = [];
    private static $projectFiles = [];

    public static function reset()
    {
        self::$select2 = false;
        self::$lightFiles = [];
        self::$projectFiles = [];
    }

    public static function addLight(string $file)
    {
        self::$lightFiles[] = "lightpublic/css/$file";
    }

    public static function add(string $file)
    {
        if (strpos($file, '//') !== 0) {
            $file = "css/$file";
        }
        self::$projectFiles[] = $file;
    }

    public static function getSources(): array
    {
        $out = [
            'lightpublic/css/all.css'
        ];
        if (self::$select2) {
            $out[] = 'lightpublic/css/select2.min.css';
        }
        if (self::$choices) {
            $out[] = 'lightpublic/css/choices.min.css';
        }

        $out = array_merge($out, self::$lightFiles, ['css/general.css'], self::$projectFiles);

        // Добавим файл отвечающий за текущий модуль
        $module = Request::getModule();
        $moduleFileLight = "lightpublic/css/$module.css";
        if (file_exists($moduleFileLight)) {
            $out[] = $moduleFileLight;
        }
        $moduleFile = "css/$module.css";
        if (file_exists($moduleFile)) {
            $out[] = $moduleFile;
        }

        // Добавим метки времени, чтобы обновлять закешированные файлы
        foreach($out as &$file) {
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
