<?php

namespace Light;

class Img
{
    public static function getUserAvatarUrl(int $userId): string
    {
        $avatarUrl = "i/employees/users/avatar/$userId.jpg";
        $avatarFilepathAbs = LIGHT_DIR_PUBLIC . "/$avatarUrl";
        if (is_file($avatarFilepathAbs)) {
            $nocache = filemtime($avatarFilepathAbs);
            return "$avatarUrl?$nocache";
        }

        return 'lightpublic/i/user.png';
    }

    private static function getTag($src, $alt, $title)
    {
        if ($alt) {
            $alt = ' alt="' . htmlspecialchars($alt) . '"';
        }

        if ($title) {
            $title = ' title="' . htmlspecialchars($title) . '"';
        }

        return '<img src="' . $src . '"' . $alt . $title . '>';
    }

    public static function gif($file, $alt = null, $title = null)
    {
        return self::getTag(self::findFile($file . '.gif'), $alt, $title);
    }

    public static function gifLight($file, $alt = null, $title = null)
    {
        return self::getTag('Light/i/' . $file . '.gif', $alt, $title);
    }

    public static function png($file, $alt = null, $title = null)
    {
        return self::getTag(self::findFile($file . '.png'), $alt, $title);
    }

    public static function pngLight($file, $alt = null, $title = null)
    {
        return self::getTag('Light/i/' . $file . '.png', $alt, $title);
    }

    public static function icon(string $cssClass)
    {
        if (strpos($cssClass, 'fa-') !== 0) {
            $cssClass = "fa-$cssClass";
        }
        return "<i class=\"fa $cssClass\"></i>";
    }

    public static function iconExcel()
    {
        return self::icon('fa-file-excel-o');
    }

    public static function iconCopy()
    {
        return self::icon('fa-clone');
    }

    public static function iconPlus()
    {
        return self::icon('fa-plus');
    }

    public static function iconUpload()
    {
        return self::icon('fa-upload');
    }

    public static function iconDownload()
    {
        return self::icon('fa-download');
    }

    public static function iconPrint()
    {
        return self::icon('fa-print');
    }

    public static function iconWord()
    {
        return self::icon('fa-file-word-o');
    }

    public static function iconPdf()
    {
        return self::icon('fa-file-pdf-o');
    }

    public static function iconMail()
    {
        return self::icon('fa-envelope-o icon-mail');
    }

    public static function iconMailRed()
    {
        return self::icon('fa-envelope-o icon-red');
    }

    public static function iconMailBlue()
    {
        return self::icon('fa-envelope-o icon-blue');
    }

    public static function iconMailGreen()
    {
        return self::icon('fa-envelope-o icon-green');
    }

    public static function iconSearch()
    {
        return self::icon('fa-search icon-search');
    }

    public static function iconEdit()
    {
        return self::icon('fa-pencil icon-edit');
    }

    public static function iconDelete()
    {
        return self::icon('fa-trash icon-delete');
    }

    public static function iconInfo()
    {
        return self::icon('fa-info-circle icon-info');
    }

    /**
     * Ищет указанный файл в иерархии папок
     * В папке текущего модуля в проекте
     * В папке текущего модуля в Light/Module
     * В папке i в проекте
     * В папке Light/i
     * Проверяет, нет ли файла "как есть", т.е. предплагая, что передан абсолютный путь
     * @param string $filename имя файла картинки включая расширение. Тут может быть абсолютный путь
     * @return mixed
     */
    private static function findFile($filename)
    {
        static $foundFilesCache = [];
        if (!isset($foundFilesCache[$filename])) {

            static $folders;
            if ($folders === null) {
                $module = Request::getModule();
                $folders = [
                    PROJECT_NAMESPACE . "/Module/$module/",
                    "Light/Module/$module/",
                    'i/',
                    'Light/i/',
                    ''
                ];
            }

            $foundFilesCache[$filename] = null;
            foreach ($folders as $folder) {
                $filepath = $folder . $filename;
                if (file_exists($filepath)) {
                    $foundFilesCache[$filename] = $filepath;
                    break;
                }
            }
        }

        return $foundFilesCache[$filename];
    }
}
