<?php
namespace Light;

if (PHP_OS === 'WINNT') {
    define('FILESYSTEM_ENCODING', 'cp1251');
} else {
    define('FILESYSTEM_ENCODING', 'UTF-8');
}

class File
{
    public static function getValidFilename(string $filename, bool $removespaces = false):string
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
     * Создаёт директрию и все промеждуточные директории если нужно
     * Созданной директрии и всем промежуточным присваивается chmod 777
     * Для правильной работы chmod 777 все промежуточные директории создаются одна за одной
     * @param string $path
     * @return bool
     */
    public static function mkdir(string $path)
    {
        $pathEnc = rtrim(str_replace('\\', '/', self::encode($path)), '/');
        $partsEnc = explode('/', $pathEnc);

        $currentFullEnc = '';

        foreach($partsEnc as $partEnc) {
            if ($partEnc === '') {
                // Какая-то пустая часть, возможно это начало пути начинающегося с / или где-то в среедине было //
                continue;
            }

            if (strpos($partEnc, ':') !== false) {
                // Это индикатор диска в Windows (например C:)
                $currentFullEnc = $partEnc;
                continue;
            }

            // Создадим директорию на текущем нам уровне
            $currentFullEnc .= '/' . $partEnc;
            if (!is_dir($currentFullEnc)) {
                if (!@mkdir($currentFullEnc, 0777) && !is_dir($currentFullEnc)) {
                    $err = error_get_last();
                    $errMsg = $err['message'] ?? '';
                    reportException(new \Exception('Cant create dir ' . $currentFullEnc . ' from File::mkdir() ' . $errMsg));
                    return false;
                } else {
                    if (!@chmod($currentFullEnc, 0777)) {
                        $err = error_get_last();
                        $errMsg = $err['message'] ?? '';
                        reportException(new \Exception('Cant change chmod ' . $currentFullEnc . ' to 0777 from File::mkdir() ' . $errMsg));
                    }
                }
            }

        }

        return true;
    }

    /**
     * Удаляет папку и все вложенные папки, включая файлы
     * @param string $path
     * @return bool
     */
    public static function rmdir(string $path):bool
    {
        $pathEnc = self::encode($path);

        // Функция работает только с директориями
        if (!is_dir($pathEnc)) {
            return false;
        }

        // Очищаем содержимое директории
        self::clearDir($path);

        // Удаляем саму директорию
        return rmdir($pathEnc);
    }

    /**
     * Очищает директорию
     * @param string $path
     * @return int
     */
    public static function clearDir(string $path)
    {
        $pathEnc = self::encode($path);

        // Функция работает только с директориями
        if (!is_dir($pathEnc)) {
            return 0;
        }

        $deletedFiles = 0;

        // Удалим всё содержимое директории
        foreach (scandir($pathEnc) as $fileEnc) {
            if ($fileEnc === '.' || $fileEnc === '..') {
                continue;
            }

            $subpathEnc = $pathEnc . '/' . $fileEnc;

            if (is_dir($subpathEnc)) {

                //Удаляем поддиректрию рекурсивно
                $subpath = $path . '/' . self::decode($fileEnc);

                // Очищаем содержимое директории
                $deletedFiles += self::clearDir($subpath);

                // Удаляем саму директорию
                rmdir($subpathEnc);

            } else {

                // Удаляем файл
                unlink($subpathEnc);
                $deletedFiles++;

            }
        }

        return $deletedFiles;
    }

    /**
     * Подсчитывает размер директории или файла в байтах включая вложенные директории
     * @param string $path
     * @return int
     */
    public static function size(string $path):int
    {
        $pathEnc = self::encode($path);

        if (!file_exists($pathEnc)) {
            return 0;
        }

        if (is_file($pathEnc)) {
            return filesize($pathEnc);
        }

        //Получается, это директория
        $size = 0;
        foreach (scandir($pathEnc) as $fileEnc) {
            if ($fileEnc[0] === '.') {
                continue;
            }

            $filepathAbs = $path . '/' . self::decode($fileEnc);
            $size += self::size($filepathAbs);
        }

        return $size;
    }

    /**
     * Возвращает количество файлов включая вложенные поддиректории
     * @param string $path
     * @return int
     */
    public static function filesCount(string $path):int
    {
        $pathEnc = self::encode($path);

        if (!file_exists($pathEnc)) {
            return 0;
        }

        //Получается, это директория
        $filesCount = 0;
        foreach (scandir($pathEnc) as $fileEnc) {
            if ($fileEnc[0] === '.') {
                continue;
            }

            $fileAbsEnc = $pathEnc . '/' . $fileEnc;
            if (is_file($fileAbsEnc)) {
                //Это файл, увеличиваем счётчик
                $filesCount++;
            } else {
                //Похоже это директория - запускаем рекурсивный подсчёт
                $fileAbs = self::decode($fileAbsEnc);
                $filesCount += self::filesCount($fileAbs);
            }
        }

        return $filesCount;
    }

    /**
     * Аналог стандартного метода pathinfo в PHP с той разницей, что:
     * 1) этот путь в кодировке utf8 (а не в кодировке файловой системы, как для стандартного pathinfo)
     * 2) возвращает результаты в кодировке uft8 (а не в кодировке файловой системы, как для стандартного pathinfo)
     * @param string $path
     * @param int|null $options
     * @return string|array
     */
    public static function pathinfo($path, $options = null)
    {
        $pathEnc = self::encode($path);
        $resultEnc = pathinfo($pathEnc, $options);
        if (is_array($resultEnc)) {
            $result = [];
            foreach ($resultEnc as $r) {
                $result[] = self::decode($r);
            }
        } else {
            $result = self::decode($resultEnc);
        }
        return $result;
    }

    /**
     * @param string $path имя файла в кодировке UTF-8
     * @return string имя файла в кодировке FILESYSTEM_ENCODING (cp1251)
     */
    public static function encode($path)
    {
        return mb_convert_encoding($path, FILESYSTEM_ENCODING, 'UTF-8');
    }

    public static function decode($pathEnc)
    {
        return mb_convert_encoding($pathEnc, 'UTF-8', FILESYSTEM_ENCODING);
    }

    /**
     * Принимает имя файла вместе путём до него и проверяет - если такой файл уже есть, то меняет имя файла,
     * чтобы оно стало уникально, добавляя в конец индекс в скобках, как это делает проводник.
     * @param string $filepath
     * @return string
     */
    public static function getUniqueFilename(string $filepath):string
    {
        //Для начала тестируем имя файла как есть без всяких добавок - если такого файла нет, то его и вернём!
        $filepathEnc = self::encode($filepath);
        if (!file_exists($filepathEnc)) {
            return $filepath;
        }

        //Разобьём путь к файлу на путь без расширения и само расширение
        $pathinfo = pathinfo($filepathEnc);
        $filepathWithoutExtEnc = $pathinfo['dirname'] . '/' . $pathinfo['filename'];
        $extEnc = $pathinfo['extension'];
        //Счётчик, который будем добавлять в конец файла при тестировании
        //Поскольку оригинальное имя файла уже протестировали (в самом начале метода), начинаем добавлять индекс с 1
        $index = 0;
        do {
            $index++;
            $testFilepathEnc = $filepathWithoutExtEnc . ' (' . $index . ').' . $extEnc;
        } while (file_exists($testFilepathEnc));

        //Как только вышли из цикла, значит нашли уникальное имя!
        return self::decode($testFilepathEnc);
    }
}
