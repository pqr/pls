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

    private static function getTag($src, $alt, $title): string
    {
        if ($alt) {
            $alt = ' alt="' . htmlspecialchars($alt) . '"';
        }

        if ($title) {
            $title = ' title="' . htmlspecialchars($title) . '"';
        }

        return '<img src="' . $src . '"' . $alt . $title . '>';
    }

    public static function gif($file, $alt = null, $title = null): string
    {
        return self::getTag(self::findFile($file . '.gif'), $alt, $title);
    }

    public static function gifLight($file, $alt = null, $title = null): string
    {
        return self::getTag('Light/i/' . $file . '.gif', $alt, $title);
    }

    public static function png($file, $alt = null, $title = null): string
    {
        return self::getTag(self::findFile($file . '.png'), $alt, $title);
    }

    public static function pngLight($file, $alt = null, $title = null): string
    {
        return self::getTag('Light/i/' . $file . '.png', $alt, $title);
    }

    public static function icon(string $cssClass): string
    {
        if (strpos($cssClass, 'fa-') !== 0) {
            $cssClass = "fa-$cssClass";
        }
        return "<i class=\"fa $cssClass\"></i>";
    }

    public static function iconExternalLink(): string
    {
        return self::icon('fa-external-link');
    }

    public static function iconExcel(): string
    {
        return self::icon('fa-file-excel-o');
    }

    public static function iconCopy(): string
    {
        return self::icon('fa-clone');
    }

    public static function iconPlus(): string
    {
        return self::icon('fa-plus');
    }

    public static function iconUpload(): string
    {
        return self::icon('fa-upload');
    }

    public static function iconDownload(): string
    {
        return self::icon('fa-download');
    }

    public static function iconPrint(): string
    {
        return self::icon('fa-print');
    }

    public static function iconWord(): string
    {
        return self::icon('fa-file-word-o');
    }

    public static function iconPdf(): string
    {
        return self::icon('fa-file-pdf-o');
    }

    public static function iconMail(): string
    {
        return self::icon('fa-envelope-o icon-mail');
    }

    public static function iconMailRed(): string
    {
        return self::icon('fa-envelope-o icon-red');
    }

    public static function iconMailBlue(): string
    {
        return self::icon('fa-envelope-o icon-blue');
    }

    public static function iconMailGreen(): string
    {
        return self::icon('fa-envelope-o icon-green');
    }

    public static function iconSearch(): string
    {
        return self::icon('fa-search icon-search');
    }

    public static function iconEdit(): string
    {
        return self::icon('fa-pencil icon-edit');
    }

    public static function iconDelete(): string
    {
        return self::icon('fa-trash icon-delete');
    }

    public static function iconInfo(): string
    {
        return self::icon('fa-info-circle icon-info');
    }

    public static function iconFolder(bool $hasFiles): string
    {
        $cssClass = $hasFiles ? 'icon-folderfull' : 'icon-folderempty';
        return self::icon("fa-folder $cssClass");
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
