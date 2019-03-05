<?php

namespace Light;

class FilesFolder
{
    private static $folderStatus = [];
    private static $newFoldersStatus = [];
    private static $onAfterSendRegistered = false;

    /**
     * Обновляет статус директории в базе данных (в таблице folderstatus).
     * Если передан параметр $forceHasFiles - то именно это значение будет записано в базу.
     * Если не передан параметр $forceHasFiles, то функция проверит реальное состояние в файловой системе
     * и после этого обновит значение в базе.
     *
     * @param string $module
     * @param int $id
     * @param bool|null $forceHasFiles
     * @return bool возвращает обновлённое значение HasFiles для данной директории
     */
    public static function updateFolderStatus(string $module, int $id, bool $forceHasFiles = null): bool
    {
        $module = Misc::getModule($module);
        if ($forceHasFiles === null) {

            // Если параметр $forceHasFiles не передан явно, нужно проверить директорию в файловой системе
            // и принять решение исходя из фактического содержимого

            // Проверка осуществляется с помощью метода hasFiles, который требует $relative путь
            $relative = self::getRelativeModule($module, $id);
            $hasFiles = FilesFolder::hasFiles($relative);

        } else {

            $hasFiles = $forceHasFiles;

        }

        // Проверим текущее значение в кеширующем массиве $folderStatus, который отражает состояние в базе данных
        $key = "$module-$id";

        if (!isset(self::$folderStatus[$key]) || self::$folderStatus[$key] !== $hasFiles) {
            // Состояние директории изменилось относительно того, что было в базе данных
            // (либо в базе данных вообще не было сведений)

            // Нужно записать новое состояние в базу данных (или обновить существующую строку в базе данных)
            // это обновление будет произведено отложенно в ApplicationAbstract::onAfterSend
            if (!self::$onAfterSendRegistered) {
                ApplicationAbstract::onAfterSend([__CLASS__, 'flushFoldersStatus']);
            }
            self::$newFoldersStatus[$key] = [$module, $id, $hasFiles];

            // Также обновим локальный кеширующий массив, чтобы не делать лишних запросов к базе данных на чтение
            // в рамках текущего запроса, если понадобится проверить состояние папки ещё раз
            self::$folderStatus[$key] = $hasFiles;
        }

        // Возвращаем финальное состояние директории
        return $hasFiles;

    }

    /**
     * Метод вызывается в коцне работы скрипта в ApplicationAbstract::onAfterSend и записывает в базу данных
     * все новые состояния из массива self::$newFoldersStatus
     * @throws \Exception
     */
    public static function flushFoldersStatus()
    {
        if (!self::$newFoldersStatus) {
            return;
        }
        $values = [];
        foreach (self::$newFoldersStatus as [$module, $id, $hasFiles]) {
            $moduleId = DbLog::getModuleId($module);
            $hasFiles = $hasFiles ? 1 : 0;
            $values[] = "($moduleId, $id, $hasFiles)";
        }

        $query = "INSERT INTO folderstatus (ModuleID, RecordID, HasFiles) 
                  VALUES " . implode(', ', $values) . "
                  ON DUPLICATE KEY UPDATE HasFiles = VALUES(HasFiles)";

        Db::query($query);

        self::$newFoldersStatus = [];
    }

    /**
     * Метод проверяет, есть ли в директории указанного объекта файлы?
     * При этом используется кеширующий массив self::$folderStatus
     * Но если в нём нет информации, то запускается процедура обновления статуса по фактическому состоянию директории
     * с помощью метода self::updateFolderStatus()
     * @param string $module
     * @param int $id
     * @return bool
     */
    public static function hasFilesModule(string $module, int $id): bool
    {
        $module = Misc::getModule($module);

        $key = "$module-$id";
        // Если состояние папки для текущего модуля уже есть в кеширующем массиве self::$folderStatus
        // то сразу вернём изветсное значение.
        // Если же в массиве self::$folderStatus нет значения, то проверим фактическое состояние директоии и заодно
        // обновим закешированные в базе данных значения.
        return self::$folderStatus[$key] ?? self::updateFolderStatus($module, $id);
    }

    /**
     * Метод проверяет фактическое наличие файлов в диектории. Поскольку метод работает с $relative путём,
     * а не с обектами и модулями, он не опирается на кеширующие значения из базы из таблицы folderstatus
     * и не обновляет их.
     * @param string $relative
     * @return bool
     */
    public static function hasFiles(string $relative): bool
    {
        $dir = self::getAbsolute($relative);
        if (!file_exists($dir) || !is_dir($dir)) {
            return false;
        }

        $flags = \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, $flags),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        /** @var \SplFileInfo $splFileInfo */
        foreach ($iterator as $splFileInfo) {
            if ($splFileInfo->isDir()) {
                // Директории не учитываем
                continue;
            }
            if (strpos($splFileInfo->getFilename(), '.') === 0) {
                // Скрытые файлы не считаем за значимые
                continue;
            }

            // Нашли какой-то файл! Возвращаем true, т.к. директория определённо не пуста
            return true;
        }

        return false;
    }

    /**
     * Загружаем из базы данных массив закешированных статусов папок
     * (используется в режиме таблицы в ModuleAbstract и в Subtable)
     * Загруженная информация сохраняется во внутренний массив self::$folderStatus,
     * который позже используется в методе FilesFolder::hasFilesModule($module, $id)
     * @param string $module
     * @param array $ids
     */
    public static function loadFoldersStatus(string $module, array $ids)
    {
        if (!$module || !$ids) {
            return;
        }
        $module = Misc::getModule($module);
        $moduleId = DbLog::getModuleId($module);
        $foldesStatusFromDb = Db::getAssoc(
            "SELECT RecordID, HasFiles
            FROM folderstatus
            WHERE ModuleID = $moduleId
                AND RecordID IN (@a)"
            , $ids
        );
        foreach ($ids as $id) {

            $hasFiles = $foldesStatusFromDb[$id] ?? false;
            if (!$hasFiles) {
                // Если про данную директорию нет информации в базе, или информация есть и там стоит HasFiles=0
                // сверимся с жестким диском ещё раз, возможно, файлы уже были добавлены?
                $hasFiles = self::updateFolderStatus($module, $id);
            }

            self::$folderStatus["$module-$id"] = $hasFiles;
        }
    }

    /**
     * @desc возвращает ссылку на папку <a href=...>
     * @param string $relativePath путь к папке относительно filesfolder
     * @param string $innerHtml текст, который добавлять после иконки папки
     * @return string
     */
    public static function getLinkWithIcon(string $relativePath, string $innerHtml = ''): string
    {
        $hasFiles = self::hasFiles($relativePath);
        $icon = Img::iconFolder($hasFiles);
        $innerHtml = trim("$icon $innerHtml");
        return self::getLink($relativePath, $innerHtml);
    }

    public static function getLinkWithIconModule(string $module, int $id, string $innerHtml = ''): string
    {
        if (!$module || !$id) {
            return '';
        }
        $hasFiles = self::hasFilesModule($module, $id);
        $icon = Img::iconFolder($hasFiles);
        $innerHtml = trim("$icon $innerHtml");
        return self::getLinkModule($module, $id, $innerHtml);
    }

    public static function getLink(string $relative, string $innerHtml): string
    {
        $url = self::getUrl($relative);
        return '<a href="' . $url . '" target="_blank">' . $innerHtml . '</a>';
    }

    public static function getLinkModule(string $module, int $id, string $innerHtml): string
    {
        if (!$module || !$id) {
            return '';
        }
        $url = self::getUrlModule($module, $id);
        return '<a href="' . $url . '" target="_blank">' . $innerHtml . '</a>';
    }

    /**
     * Возвращает url для указанной папки внутри filesfolder
     * @param string $relative
     * @return string
     */
    public static function getUrl(string $relative): string
    {
        // Если задана переменная Config::$filesfolder и текущее окружение пользователя позволяет открывать ссылки
        // в проводнике - тогда сформируем ссылку для проводника
        if (Config::$filesfolder) {
            $url = self::getUrlNetworkFolder($relative);
            if ($url !== null) {
                return $url;
            }
        }

        // Если же не задана переменная Config::$filesfolder или доступ через сетевые папки невозможен
        // сформируем ссыклу на ModuleAbstract - elfinder

        // Относительный путь обычно выглядит так: <ModuleName>/<thousand>/<RecordID>
        $parts = explode('/', $relative);
        if (!isset($parts[2])) {
            // Странный относительный путь, по которому невозможно посроить ссылку на ModuleAbstract
            return '';
        }

        [$module, , $id] = $parts;
        return self::getUrlElfinder($module, (int)$id);
    }

    public static function getUrlModule(string $module, int $id): string
    {
        if (!$module || !$id) {
            return '';
        }
        // Если задана переменная Config::$filesfolder и текущее окружение пользователя позволяет открывать ссылки
        // в проводнике - тогда сформируем ссылку для проводника
        if (Config::$filesfolder) {
            $relative = self::getRelativeModule($module, $id);
            $url = self::getUrlNetworkFolder($relative);
            if ($url !== null) {
                return $url;
            }
        }

        // Если же не задана переменная Config::$filesfolder или доступ через сетевые папки невозможен
        // сформируем ссыклу на ModuleAbstract - elfinder
        return self::getUrlElfinder($module, $id);
    }

    private static function getUrlNetworkFolder(string $relative): ?string
    {
        // Когда url передаётся в браузер под видом ссылки file:// важно чтобы имена папок и файлов
        // были закодированы с помощью rawurlencode
        $relative = implode('/', array_map('rawurlencode', explode('/', $relative)));

        // Разрешены нативные папки. Чтобы сформировать правильный url, нужно определить операционную систему клиента
        if (self::isUserAgentNix()) {
            // Пользователь работает через OS X или Linux - делаем для него ссылку на smb
            return 'smb://' . str_replace(['\\\\', '\\'], ['', '/'], Config::$filesfolder) . '/' . $relative;
        }

        if (self::isWindowsSharedFolderAllowed()) {
            // Пользователь работает из Windows, для него делаем ссылки типа file:///
            return 'file:///' . Config::$filesfolder . '/' . $relative;
        }

        return null;
    }


    private static function isUserAgentNix()
    {
        static $isUserAgentNix;
        if ($isUserAgentNix === null) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $isUserAgentNix = strpos($userAgent, 'OS X') !== false || strpos($userAgent, 'Linux') !== false;
        }

        return $isUserAgentNix;
    }

    private static function isWindowsSharedFolderAllowed()
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

    private static function getUrlElfinder(string $module, int $id): string
    {
        if (!$module || !$id) {
            return '';
        }
        return Link::create($module)->view($id)->params(['elfinder' => 'dialog'])->getHref();
    }

    public static function mkdir(string $relative): bool
    {
        try {

            File::fs()->mkdir(self::getAbsolute($relative));
            return true;

        } catch (\Exception $e) {

            reportException($e);
            return false;

        }
    }

    public static function mkdirModule(string $module, int $id): bool
    {
        if (!$module || !$id) {
            return false;
        }
        return self::mkdir(self::getRelativeModule($module, $id));
    }

    /**
     * @param string $originRelative путь к файлу источнику относительно filesfolder
     * @param string $targetRelative путь к файлу назначения относительно filesfolder
     * @return bool
     */
    public static function copy(string $originRelative, string $targetRelative): bool
    {
        $origin = self::getAbsolute($originRelative);
        $target = self::getAbsolute($targetRelative);
        return File::copy($origin, $target);
    }

    public static function moveToDeleted(string $relative)
    {
        // Перемещаем папку в специальную директорию deleted
        FilesFolder::rename($relative, "deleted/$relative");
    }

    /**
     * @param string $originRelative путь к файлу источнику относительно filesfolder
     * @param string $targetRelative путь к файлу назначения относительно filesfolder
     * @return bool
     */
    public static function rename(string $originRelative, string $targetRelative): bool
    {
        $origin = self::getAbsolute($originRelative);
        $target = self::getAbsolute($targetRelative);
        return File::rename($origin, $target);
    }

    public static function isFile(string $relative): bool
    {
        $relative = self::trimRelative($relative);
        return $relative !== '' && is_file(self::getAbsolute($relative));
    }

    public static function isDir(string $relative): bool
    {
        $relative = self::trimRelative($relative);
        return $relative !== '' && is_dir(self::getAbsolute($relative));
    }

    public static function trimRelative(string $relative): string
    {
        return rtrim($relative, " /");
    }

    /**
     * Возвращает массив файлов внутри директории относительно filesfolder
     * Особенность этого метода по сравнению со стандартным scandir:
     * 1) в массиве не будет элементов . и ..
     * 2) в массиве не будет директорий (только файлы)
     * 3) не будет скрытых файлов (начинающихся с точки)
     * 4) не будет файла 'Thumbs.db'
     * @param string $relative
     * @param string|null $filterExtension
     * @return array
     */
    public static function scandir(string $relative, string $filterExtension = null): array
    {
        $abs = self::getAbsolute($relative);
        if (!is_dir($abs)) {
            return [];
        }

        $files = scandir($abs);
        if (!is_array($files)) {
            //стандартная функция scandir вернула false
            return [];
        }

        $out = [];
        foreach ($files as $file) {
            $fileAbs = "$abs/$file";
            if ($file === 'Thumbs.db' || $file[0] === '.' || !is_file($fileAbs)) {
                continue;
            }
            if ($filterExtension && strtolower(pathinfo($fileAbs,
                    PATHINFO_EXTENSION)) !== strtolower($filterExtension)) {
                continue;
            }

            $out[] = $file;
        }

        return $out;
    }

    public static function size(string $relative): int
    {
        $pathAbs = Config::$filesfolderLocal . '/' . $relative;
        return File::size($pathAbs);
    }

    public static function filesCount(string $relative): int
    {
        $pathAbs = Config::$filesfolderLocal . '/' . $relative;
        return File::filesCount($pathAbs);
    }

    /**
     * Возвращает абсолютный путь в файловой системе
     * @param string $relative
     * @return string
     */
    public static function getAbsolute(string $relative): string
    {
        return Config::$filesfolderLocal . '/' . self::trimRelative($relative);
    }

    public static function getAbsoluteModule(string $module, int $id): string
    {
        if (!$module || !$id) {
            return '';
        }
        $relative = self::getRelativeModule($module, $id);
        return self::getAbsolute($relative);
    }

    /**
     * Удаляет абсолютный путь в файловой системе
     * @param string $abs
     * @return string
     */
    public static function getRelative(string $abs): string
    {
        return rtrim(File::fs()->makePathRelative($abs, Config::$filesfolderLocal), '/');
    }

    public static function getRelativeModule(string $module, int $id): string
    {
        if (!$module || !$id) {
            return '';
        }
        $module = Misc::getModule($module);
        $sub = floor($id / 1000);
        return "$module/$sub/$id";
    }


    /**
     * Принимает имя файла вместе путём до него и проверяет - если такой файл уже есть, то меняет имя файла,
     * чтобы оно стало уникально, добавляя в конец индекс в скобках, как это делает проводник.
     * @param $relative
     * @return string
     */
    public static function getUniqueFilename(string $relative): string
    {
        $abs = self::getAbsolute($relative);

        // Для начала тестируем имя файла как есть без всяких добавок - если такого файла нет, то его и вернём!
        if (!file_exists($abs)) {
            return $relative;
        }

        // Если такой файл уже существует, запустим алгоритм поиска уникального файла из класса File
        // Метод File::getUniqueFilename() принимает абсолютный путь
        $uniqueAbs = File::getUniqueFilename($abs);
        return File::fs()->makePathRelative($uniqueAbs, Config::$filesfolderLocal);
    }

    /**
     * Отправляет в браузер пользователю файл из папки, при этом сначала пытается
     * сделать редирект на Location: file:///...сетевой путь до файла
     * Если же у пользователя такой браузер, который не поддерживает прямой доступ к папкам
     * тогда отправляется копия файла по http
     * @param string $relative
     */
    public static function streamFileTryWindowsShared(string $relative)
    {
        if (Config::$filesfolder && self::isWindowsSharedFolderAllowed()) {
            Response::redirectUrl(self::getUrl($relative));
        } else {
            self::streamFile($relative);
        }
    }

    public static function streamFile(string $relative, bool $preview = false)
    {
        $filepathAbs = self::getAbsolute($relative);
        if (!Request::isAjax()) {

            // Если это не ajax запрос, то можем прямо в поток пользователю отдать искомый файл
            Response::streamFileFromFilesystem($filepathAbs, null, $preview);
            return;

        }

        // Если же доступ через сетевые папки невозможен, сформируем ссыклу на ModuleAbstract
        // Относительный путь обычно бывает двух видов: <ModuleName>/<thousand>/<RecordID>/filename
        $parts = explode('/', $relative, 4);
        if (count($parts) < 4) {
            // Странный относительный путь, по которому невозможно построить ссылку на ModuleAbstract
            return;
        }

        $module = $parts[0];
        $recordId = (int)$parts[2];
        $file = $parts[3];

        $url = Link::create($module)->view($recordId)->params(['elfinder' => 'download', 'file' =>  $file])->getHref();
        // Дадим браузеру команду на редирект на специальный адрес ModuleAbstract
        // который умеет отдавать файлы из FilesFolder
        Response::redirectUrl($url);
    }
}
