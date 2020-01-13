<?php

namespace Light;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class File
{
    public static function fs(): Filesystem
    {
        static $fs;
        if (!$fs) {
            $fs = new Filesystem();
        }
        return $fs;
    }

    /**
     * В отличие от Symfony/Filesystem реализации rename(),
     * эта обёртка предварительно создаст необходимые директории в $target
     * а также залогирует возможный exception и вернёт true или false
     * @param $origin
     * @param $target
     * @return bool
     */
    public static function rename($origin, $target): bool
    {
        try {

            if (!file_exists($origin)) {
                return false;
            }

            $targetDir = \dirname($target);
            File::fs()->mkdir($targetDir);
            File::fs()->rename($origin, $target, true);
            return true;

        } catch (\Exception $e) {

            \Light\ErrorHandler::reportException($e);
            return false;

        }
    }

    /**
     * В отличие от Symfony/Filesystem реализации copy()
     * эта обёртка залогирует возможный exception и вернёт true или false
     * @param $origin
     * @param $target
     * @return bool
     */
    public static function copy($origin, $target): bool
    {
        try {

            if (!file_exists($origin)) {
                return false;
            }

            File::fs()->copy($origin, $target, true);
            return true;

        } catch (\Exception $e) {

            \Light\ErrorHandler::reportException($e);
            return false;

        }
    }

    public static function getValidFilename(string $filename, bool $removespaces = false): string
    {
        $search = ['/', '\\', ':', '*', '?', '"', '<', '>', '|',];

        $filename = str_replace($search, ' ', $filename);
        /** @noinspection CascadeStringReplacementInspection */
        $filename = str_replace(['[', ']'], ['(', ')'], $filename);

        if ($removespaces) {
            $filename = str_replace(" ", "_", $filename);
        }

        return $filename;
    }

    /**
     * Создаёт временную директорию в папке LIGHT_DIR_TEMP и автоматически удаляет её после окончания работы скрипта
     * @param string $prefix
     * @return string
     * @throws IOException On any directory creation failure
     */
    public static function mkdirTmp(string $prefix): string
    {
        try {

            $tmpDir = File::getUniqueDir(LIGHT_DIR_TEMP . "/$prefix");
            self::fs()->mkdir($tmpDir);

            ApplicationAbstract::onAfterSend(function () use ($tmpDir) {
                try {
                    if (is_dir($tmpDir)) {
                        File::fs()->remove($tmpDir);
                    }
                } catch (\Exception $e) {
                    \Light\ErrorHandler::reportException($e);
                }
            });

            return $tmpDir;

        } catch (\Exception $e) {

            \Light\ErrorHandler::reportException($e);

            return '';
        }
    }

    /**
     * @param string $dir
     * @return array SplFileInfo[]
     */
    public static function scandirRecurcive(string $dir): array
    {
        if (!file_exists($dir) || !is_dir($dir)) {
            return [];
        }

        $out = [];
        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, $flags),
            \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $splFileInfo) {
            if ($splFileInfo->isDir()) {
                continue;
            }
            $out[] = $splFileInfo;
        }
        return $out;
    }

    /**
     * Подсчитывает размер директории или файла в байтах включая вложенные директории
     * @param string $path
     * @return int
     */
    public static function size(string $path): int
    {
        if (!file_exists($path)) {
            return 0;
        }

        if (is_file($path)) {
            return filesize($path);
        }

        // Получается, это директория
        $size = 0;
        /** @var \SplFileInfo $splFileInfo */
        foreach (self::scandirRecurcive($path) as $splFileInfo) {
            $size += $splFileInfo->getSize();
        }

        return $size;
    }

    /**
     * Возвращает количество файлов включая вложенные поддиректории
     * @param string $path
     * @return int
     */
    public static function filesCount(string $path): int
    {
        if (!file_exists($path)) {
            return 0;
        }

        if (!is_dir($path)) {
            return 0;
        }

        // Получается, это директория
        $files = self::scandirRecurcive($path);
        return count($files);
    }

    /**
     * Принимает имя файла вместе путём до него и проверяет - если такой файл уже есть, то меняет имя файла,
     * чтобы оно стало уникально, добавляя в конец индекс в скобках, как это делает проводник.
     * @param string $filepath
     * @return string
     */
    public static function getUniqueFilename(string $filepath): string
    {
        // Для начала тестируем имя файла как есть без всяких добавок - если такого файла нет, то его и вернём!
        if (!file_exists($filepath)) {
            return $filepath;
        }

        //Разобьём путь к файлу на путь без расширения и само расширение
        $pathinfo = pathinfo($filepath);
        $filepathWithoutExtEnc = $pathinfo['dirname'] . '/' . $pathinfo['filename'];
        $ext = $pathinfo['extension'];
        //Счётчик, который будем добавлять в конец файла при тестировании
        //Поскольку оригинальное имя файла уже протестировали (в самом начале метода), начинаем добавлять индекс с 1
        $index = 0;
        do {
            $index++;
            $newFilepath = "$filepathWithoutExtEnc ($index).$ext";
        } while (file_exists($newFilepath));

        // Как только вышли из цикла, значит нашли уникальное имя!
        return $newFilepath;
    }

    public static function getUniqueDir(string $dir): string
    {
        if (!file_exists($dir)) {
            return $dir;
        }

        $index = 0;
        do {
            $index++;
            $newDir = "{$dir}_{$index}";
        } while (file_exists($newDir));

        // Как только вышли из цикла, значит нашли уникальное имя!
        return $newDir;
    }
}
