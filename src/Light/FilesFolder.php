<?php

namespace Light;

class FilesFolder
{
    private static function isUserAgentNix()
    {
        static $isUserAgentNix;
        if ($isUserAgentNix === null) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $isUserAgentNix = strpos($userAgent, 'OS X') !== false || strpos($userAgent, 'Linux') !== false;
        }

        return $isUserAgentNix;
    }

    public static function isWindowsSharedFolderAllowed()
    {
        static $isWindowsSharedFolderAllowed;
        if ($isWindowsSharedFolderAllowed === null) {

            // Если дошли до этого момента, значит мы в локальной сети и под Windows - тут всё зависит от браузера
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $isFirefox = strpos($userAgent, 'Firefox') !== false;
            $isIe = strpos($userAgent, 'Trident') !== false;
            $isEdge = strpos($userAgent, 'Edge') !== false;
            if (!$isFirefox && !$isIe && !$isEdge) {
                // У нас какой-то бразуер, но точно не Firefox и не IE - значит расшаренные папки не будут работать нативно
                $isWindowsSharedFolderAllowed = false;
                return $isWindowsSharedFolderAllowed;
            }

            // Наконец, если дошли этой строки, значит мы в локальной сети под Windows с браузером Firefox или IE - разрешаем папки
            $isWindowsSharedFolderAllowed = true;

        }

        return $isWindowsSharedFolderAllowed;
    }

    /**
     * Возвращает url для указанной папки внутри filesfolder
     * @param string $relativePath
     * @return string
     */
    public static function getUrl(string $relativePath)
    {
        // Если задана переменная Config::$filesfolder и текущее окружение пользователя позволяет открывать ссылки
        // в проводнике - тогда сформируем ссылку для проводника
        if (Config::$filesfolder) {

            // Когда url передаётся в браузер под видом ссылки file:// важно чтобы имена папок и файлов
            // были закодированы с помощью rawurlencode
            $relativePath = implode('/', array_map('rawurlencode', explode('/', $relativePath)));

            // Разрешены нативные папки. Чтобы сформировать правильный url, нужно определить операционную систему клиента
            if (self::isUserAgentNix()) {
                // Пользователь работает через OS X или Linux - делаем для него ссылку на smb
                return 'smb://' . str_replace(['\\\\', '\\'], ['', '/'], Config::$filesfolder) . '/' . $relativePath;
            }

            if (self::isWindowsSharedFolderAllowed()) {
                // Пользователь работает из Windows, для него делаем ссылки типа file:///
                return 'file:///' . Config::$filesfolder . '/' . $relativePath;
            }

        }

        // Если же не задана переменная Config::$filesfolder или доступ через сетевые папки невозможен
        // сформируем ссыклу на ModuleAbstract - elfinder

        // Относительный путь обычно выглядит так: <ModuleName>/<thousand>/<RecordID>
        $parts = explode('/', $relativePath);
        if (!isset($parts[2])) {
            // Странный относительный путь, по которому невозможно посроить ссылку на ModuleAbstract
            return '';
        }

        $module = $parts[0];
        $recordId = $parts[2];

        return Link::create($module)->view($recordId)->params(['elfinder' => 'dialog'])->getHref();
    }

    /**
     * Возвращает html код изображения в зависимости от того пуста или не пуста указанная в параметре папка
     * @param bool $isNotEmpty
     * @return string
     */
    public static function getImgHtml($isNotEmpty)
    {
        $cssClass = $isNotEmpty ? 'icon-folderfull' : 'icon-folderempty';
        return "<i class=\"fa fa-folder $cssClass\"></i>";
    }

    /**
     * @desc возвращает ссылку на папку <a href=...>
     * @param string $relativePath путь к папке относительно filesfolder
     * @param string $innerHtml текст, который добавлять после иконки папки
     * @return string
     */
    public static function getLinkWithIcon(string $relativePath, string $innerHtml = ''): string
    {
        $innerHtml = trim(self::getImgHtml(self::isNotEmpty($relativePath)) . ' ' . $innerHtml);
        return self::getLink($relativePath, $innerHtml);
    }

    public static function getLink(string $relativePath, string $innerHtml): string
    {
        $url = self::getUrl($relativePath);
        return '<a href="' . $url . '" target="_blank">' . $innerHtml . '</a>';
    }

    public static function getModuleLinkWithIcon(string $module, int $id, string $innerHtml = '')
    {
        $relativePath = self::getModuleRelative($module, $id);
        return self::getLinkWithIcon($relativePath, $innerHtml);
    }

    public static function getModuleLink(string $module, int $id, string $innerHtml)
    {
        $relativePath = self::getModuleRelative($module, $id);
        return self::getLink($relativePath, $innerHtml);
    }

    public static function isDir(string $relativePath): bool
    {
        $pathAbs = Config::$filesfolderLocal . '/' . $relativePath;
        $pathAbsEnc = File::encode($pathAbs);

        return is_dir($pathAbsEnc);
    }

    public static function isFile(string $relativePath): bool
    {
        $pathAbs = Config::$filesfolderLocal . '/' . $relativePath;
        $pathAbsEnc = File::encode($pathAbs);

        return is_file($pathAbsEnc);
    }

    public static function mkdir(string $relativePath): bool
    {
        return File::mkdir(Config::$filesfolderLocal . '/' . $relativePath);
    }

    /**
     * @param string $srcRelativePath путь к файлу источнику относительно filesfolder
     * @param string $destRelativePath путь к файлу назначения относительно filesfolder
     * @return bool
     */
    public static function copy(string $srcRelativePath, string $destRelativePath): bool
    {
        $srcPathAbsEnc = File::encode(Config::$filesfolderLocal . '/' . $srcRelativePath);
        $destPathAbsEnc = File::encode(Config::$filesfolderLocal . '/' . $destRelativePath);
        $result = copy($srcPathAbsEnc, $destPathAbsEnc);
        @chmod($destPathAbsEnc, 0666);
        return $result;
    }

    public static function rename(string $srcRelativePath, string $destRelativePath): bool
    {
        $srcPathAbsEnc = File::encode(Config::$filesfolderLocal . '/' . $srcRelativePath);
        if (!file_exists($srcPathAbsEnc)) {
            return false;
        }
        $destPathAbsEnc = File::encode(Config::$filesfolderLocal . '/' . $destRelativePath);
        $destParentEnc = dirname($destPathAbsEnc);
        if (!file_exists($destParentEnc)) {
            File::mkdir(File::decode($destParentEnc));
        }
        $result = rename($srcPathAbsEnc, $destPathAbsEnc);
        @chmod($destPathAbsEnc, 0666);
        return $result;
    }

    public static function moveToDeleted(string $folderRelativePath)
    {
        // Перемещаем папку в специальную директорию deleted
        $destRelativePath = 'deleted/' . $folderRelativePath;
        FilesFolder::rename($folderRelativePath, $destRelativePath);
    }

    /**
     * Возвращает массив файлов внутри директории относительно filesfolder
     * Особенность этого метода по сравнению со стандартным scandir:
     * 1) в массиве не будет элементов . и ..
     * 2) имена файлов в массиве будут в кодировке utf8, а не в кодировке файловой системы, как это происходит с обычным scandir
     * @param string $relativePath
     * @return array
     */
    public static function scandir(string $relativePath): array
    {
        $pathEnc = File::encode(Config::$filesfolderLocal . '/' . $relativePath);
        if (!is_dir($pathEnc)) {
            return [];
        }

        $filesEnc = scandir($pathEnc);
        if (!is_array($filesEnc)) {
            //стандартная функция scandir вернула false
            return [];
        }

        $files = [];
        foreach ($filesEnc as $fileEnc) {
            if ($fileEnc[0] === '.') {
                continue;
            }

            $files[] = File::decode($fileEnc);
        }

        return $files;
    }

    /**
     * @param string $relativePath
     * @return bool|null возвращает true, если папка не пуста; false, если папка пуста; null, если папка не существует
     */
    public static function isNotEmpty(string $relativePath)
    {
        $pathAbs = Config::$filesfolderLocal . '/' . $relativePath;
        $pathAbsEnc = File::encode($pathAbs);
        if (!is_dir($pathAbsEnc)) {
            return null;
        }

        $isNotEmpty = false;

        //Открываем директорию с помощью opendir (вместо scandir), чтобы найти первый файл отличный от . и ..
        //Как только нашли, можно закрывать директрию!
        if ($dh = opendir($pathAbsEnc)) {
            while (($fileEnc = readdir($dh)) !== false) {
                if ($fileEnc[0] === '.') {
                    continue;
                }

                if (is_file($pathAbsEnc . '/' . $fileEnc)) {
                    //Нашли файл в текущей папке!
                    closedir($dh);

                    $isNotEmpty = true;
                    break;

                } else {
                    //Проверим эту вложенную директорию рекурсивным вызовом
                    $subResult = self::isNotEmpty($relativePath . '/' . File::decode($fileEnc));
                    if ($subResult) {
                        //Поддиректория оказалась не пуста!
                        closedir($dh);

                        $isNotEmpty = true;
                        break;
                    }
                }
            }
            if (!$isNotEmpty) {
                closedir($dh);
            }
        }

        return $isNotEmpty;
    }

    // Переменные, которые будут накапливать данные о пустых и не пустых папках, чтобы в конце рендринга страницы
    // сделать массовое обновление в базе
    /** @var  array */
    private static $bufferNotEmpty;
    /** @var  array */
    private static $bufferEmpty;

    /**
     * Обновляет в базе в таблице cache_notemptyfilesfolder текущее состояние папки - пустая она или нет?
     * @param string $relativePath относительный путь внутри FilesFolder - папка которую проверяем на пустоту
     * @param string $moduleName - имя модуля для записи в таблицу cache_notemptyfilesfolder
     * @param int $recordId - номер записи в базе для записи в таблицу cache_notemptyfilesfolder
     * @return bool возвращает текущее состояние: false - пустая, true - заполнена чем-то
     */
    public static function updateCacheNotEmpty(string $relativePath, string $moduleName, int $recordId)
    {
        if (self::$bufferNotEmpty === null) {
            self::$bufferNotEmpty = [];
            self::$bufferEmpty = [];
            ApplicationAbstract::$flushFilesFolder = true;
        }

        $isNotEmpty = FilesFolder::isNotEmpty($relativePath) ?? false;
        if ($isNotEmpty) {

            // Если же метод проверки на пустоту вернул true - значит папка не пуста, сохраним это
            // знание в кеширующую таблицу, чтобы в следующий раз не обращаться к файловой системе
            // Делать вставку в базу сразу не будем, накопим в буфер, чтобы потом одним insert запросом вставить в базу
            // все строки. Формат буфера такой, чтобы было удобно передать в Db::insertRows(...)
            self::$bufferNotEmpty[] = [
                'ModuleName' => $moduleName,
                'RecordID' => $recordId
            ];

        } else {

            // Папка пустая, удалим её из кеширующей таблицы
            // Делать запрос на удаление из базы сразу не будем, накопим в буфер. Формат буфера таков, чтобы было
            // удобно сделать несколько запросов объединённых по модулю (см. метод flushCacheNotEmpty())
            self::$bufferEmpty[$moduleName][] = $recordId;

        }

        return $isNotEmpty;
    }

    public static function flushCacheNotEmpty()
    {
        if (self::$bufferNotEmpty) {
            Db::insertRows(self::$bufferNotEmpty, 'cache_notemptyfilesfolder', true);
            self::$bufferNotEmpty = [];
        }

        foreach (self::$bufferEmpty as $moduleName => $ids) {
            Db::query("DELETE FROM cache_notemptyfilesfolder WHERE ModuleName = @s AND RecordID IN (@a)", $moduleName,
                $ids);
        }
        self::$bufferEmpty = [];
    }

    public static function size(string $relativePath)
    {
        $pathAbs = Config::$filesfolderLocal . '/' . $relativePath;
        return File::size($pathAbs);
    }

    public static function filesCount(string $relativePath)
    {
        $pathAbs = Config::$filesfolderLocal . '/' . $relativePath;
        return File::filesCount($pathAbs);
    }

    /**
     * Возвращает абсолютный путь в файловой системе
     * @param string $relativePath
     * @return string
     */
    public static function getAbsolutePath(string $relativePath)
    {
        return Config::$filesfolderLocal . '/' . $relativePath;
    }

    /**
     * Удаляет абсолютный путь в файловой системе
     * @param string $absPath
     * @return string
     */
    public static function getRelativePath(string $absPath)
    {
        return str_replace(Config::$filesfolderLocal . '/', '', $absPath);
    }

    public static function getModuleRelative(string $module, int $id)
    {
        $sub = floor($id / 1000);
        return "$module/$sub/$id";
    }

    public static function getModuleAbsolute(string $module, int $id)
    {
        $relativePath = self::getModuleRelative($module, $id);
        return self::getAbsolutePath($relativePath);
    }

    /**
     * Принимает имя файла вместе путём до него и проверяет - если такой файл уже есть, то меняет имя файла,
     * чтобы оно стало уникально, добавляя в конец индекс в скобках, как это делает проводник.
     * @param $relrelativePath
     * @return string
     */
    public static function getUniqueFilename(string $relrelativePath): string
    {
        $filepathAbs = Config::$filesfolderLocal . '/' . $relrelativePath;

        //Для начала тестируем имя файла как есть без всяких добавок - если такого файла нет, то его и вернём!
        if (!file_exists(File::encode($filepathAbs))) {
            return $relrelativePath;
        }

        //Если такой файл уже существует, запустим алгоритм поиска уникального файла из класса File
        //Метод File::getUniqueFilename() принимает абсолютный путь
        $uniqueFilepathAbs = File::getUniqueFilename($filepathAbs);

        //Теперь от абсолютного уникального пути нужно отрезать префикс с путём до filesfolder
        $prefixLen = mb_strlen(Config::$filesfolderLocal . '/', 'UTF-8');
        return mb_substr($uniqueFilepathAbs, $prefixLen);
    }

    /**
     * Отправляет в браузер пользователю файл из папки, при этом сначала пытается
     * сделать редирект на Location: file:///...сетевой путь до файла
     * Если же у пользователя такой браузер, который не поддерживает прямой доступ к папкам
     * тогда отправляется копия файла по http
     * @param string $relativePath
     */
    public static function streamFileTryWindowsShared(string $relativePath)
    {
        if (Config::$filesfolder && self::isWindowsSharedFolderAllowed()) {
            Response::redirectUrl(self::getUrl($relativePath));
        } else {
            self::streamFile($relativePath);
        }
    }

    public static function streamFile(string $relativePath)
    {
        $filepathAbs = Config::$filesfolderLocal . '/' . $relativePath;
        if (!Request::isAjax()) {

            // Если это не ajax запрос, то можем прямо в поток пользователю отдать искомый файл
            Response::streamFileFromFilesystem($filepathAbs);
            return;

        }

        // Если же доступ через сетевые папки невозможен, сформируем ссыклу на ModuleAbstract
        // Относительный путь обычно бывает двух видов: <ModuleName>/<thousand>/<RecordID>/filename
        $parts = explode('/', $relativePath, 4);
        if (count($parts) < 4) {
            // Странный относительный путь, по которому невозможно посроить ссылку на ModuleAbstract
            return '';
        }

        $module = $parts[0];
        $recordId = (int)$parts[2];
        $file = $parts[3];

        $url = "?a=$module&id=$recordId&elfinder=download&file=" . rawurlencode($file);

        // Дадим браузеру команду на редирект на специальный адрес ModuleAbstract
        // который умеет отдавать файлы из FilesFolder
        Response::redirectUrl($url);
    }

    /**
     * Возвращает все файлы с путями, обходя директории рекурсивно.
     * Например, если $mainPath = 'Shipment/183/183400', то в результат получим документы из этой папки и вложенных
     * в виде следующего массива:
     * [
     *      'bl.pdf'
     *      'some email.eml'
     *      'docs/some document.doc'
     *      'docs/another.doc'
     *      'docs/subfolder/scan.jpg'
     *      'ндс/отчёт.xls'
     * ]
     *
     * @param string $mainPath - базовая папка от которой нужно сделать рекурсивный обход
     * @param string $subPath - это параметр будет заполнен только при рекурсивных вызовах - если мы уже углубились
     * @return array
     */
    public static function recursivelyScanDir($mainPath, $subPath = null)
    {
        $result = [];

        $path = ($subPath === null) ? $mainPath : $mainPath . '/' . $subPath;

        $currentFiles = self::scandir($path);

        foreach ($currentFiles as $file) {

            $filePath = $path . '/' . $file;
            if (self::isDir($filePath)) {
                $newSubPath = ($subPath === null) ? $file : $subPath . '/' . $file;
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $result = array_merge($result, self::recursivelyScanDir($mainPath, $newSubPath));
            } else {
                //Путь к файлу начиная от $subPath
                $filepath = ($subPath === null) ? $file : $subPath . '/' . $file;
                $result[] = $filepath;
            }
        }

        return $result;
    }

    /**
     * Метод пригождается, когда нужно выполнить какие-то операции над файлами в папках через консольные утилиты
     * Например, склеить PDF файлы. Этот метод берёт указнные файлы из FilesFolders и копирует их во временную папку
     * на локальном диске, при этом файлы переименовываются в 1, 2, 3, ... (чтобы исключить русские буквы в именах файлов)
     *
     * По окончании метода временная папка не уничтожается. Она будет удалена в специальном autolaunch Maintainance
     *
     * @param array $files массив файлов (относительные пути от FilesFolders)
     * @return array $resultFilesAbsEnc массив полных путей файлов в кодировке файловой системы
     * @throws \Exception
     */
    public static function copyToTmpDirAndRename(array $files)
    {
        $tmpDirAbs = LIGHT_DIR_TEMP . '/FilesFolder_copyToTmpDirAndRename/' . User::getId() . '_' . time();
        if (!@mkdir($tmpDirAbs, 0777, true) && !is_dir($tmpDirAbs)) {
            throw new \Exception('Cant create temp dir ' . $tmpDirAbs);
        }
        @chmod($tmpDirAbs, 0777);

        $resultFilesAbsEnc = [];
        $count = 1;
        foreach ($files as $file) {
            $srcFilepathAbsEnc = File::encode(Config::$filesfolderLocal . '/' . $file);
            $ext = pathinfo($srcFilepathAbsEnc, PATHINFO_EXTENSION);
            $destFilepathAbsEnc = $tmpDirAbs . '/' . $count . '.' . $ext;
            copy($srcFilepathAbsEnc, $destFilepathAbsEnc);
            @chmod($destFilepathAbsEnc, 0666);
            $resultFilesAbsEnc[] = $destFilepathAbsEnc;
            $count++;
        }

        return $resultFilesAbsEnc;
    }
}
