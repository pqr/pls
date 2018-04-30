<?php

namespace Light;

class Pdf
{
    /**
     * Генерирует pdf из html контента по абсолютному пути. Проверок на сущетсвование файла не осущетсвляет
     * @param string $html
     * @param string $filepathAbs абсолютный путь до файла (с именем файла и расширением .pdf)
     * @param bool $landscape
     * @param Msg $msgBuff
     * @return bool
     */
    public static function save($html, $filepathAbs, $landscape = false, Msg $msgBuff = null, bool $recall = false)
    {
        //Создадим директорию, если её ещё нет
        //$filepathAbs = File::encode($filepathAbs);
        $dir = pathinfo($filepathAbs, PATHINFO_DIRNAME);
        File::mkdir($dir);

        //Обработаем html содержимое - заменим все относительные ссылки на абсолютные пути в файловой системе
        //$replacement = [' src="' => ' src="' . Config::$localUrl . '/', ' href="' => ' href="' . Config::$localUrl . '/'];
        if (Config::$useAthena) {
            $replacementBase = Config::$localUrlNetIp;
        } else {
            $isHttp = strpos(Config::$wkhtmltopdfPath, 'http://') === 0;
            if ($isHttp) {
                $replacementBase = Config::$localUrl;
            } else {
                $replacementBase = LIGHT_DIR_PUBLIC;
            }
        }

        $replacement = [
            ' src="' => ' src="' . $replacementBase . '/',
            ' href="' => ' href="' . $replacementBase . '/',
            ' url(' => ' url(' . $replacementBase . '/'
        ];

        $html = str_replace(array_keys($replacement), array_values($replacement), $html);

        if (Config::$useAthena) {

            $flagSuccess = self::runAthena($html, $filepathAbs, $landscape, $msgBuff);

        } else {
            if ($isHttp) {
                $flagSuccess = self::runHttp($html, $filepathAbs, $msgBuff);
            } else {
                $flagSuccess = self::runLocal($html, $filepathAbs, $landscape, $msgBuff);
            }
        }

        // проверим, не пустой ли файл сгенерировался
        $filepathAbsEnc = File::encode($filepathAbs);
        if ($flagSuccess && !trim(file_get_contents($filepathAbsEnc))) {
            // удалим файл и повторно попробуем сгенерировать pdf
            unlink($filepathAbsEnc);
            if (!$recall) {
                self::save($html, $filepathAbs, $landscape, $msgBuff, true);
            } else {
                // если файл и повторно не смог сгенерироваться - выведем ошибку
                $msgBuff->addError("Не удалось сгенерировать PDF файл");
                $flagSuccess = false;
            }
        }

        return $flagSuccess;
    }

    private static function runHttp($html, $filepathAbs, Msg $msgBuff = null)
    {
        $url = Config::$wkhtmltopdfPath;

        $postField = ['html' => $html];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postField));
        $output = curl_exec($curl);

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ((int)$status === 200) {
            //Переименуем временный файл в реальный
            $filepathAbsEnc = File::encode($filepathAbs);
            file_put_contents($filepathAbsEnc, $output);
            @chmod($filepathAbsEnc, 0666);
            return true;
        } else {
            if ($msgBuff) {
                $msgBuff->addError($output);
            }
            return false;
        }
    }

    private static function runLocal($html, $filepathAbs, $landscape = false, Msg $msgBuff = null)
    {
        //Путь к исполняемому файлу wkhtmltopd должен быть в конфиге
        $cmd = '"' . Config::$wkhtmltopdfPath . '"';

        if ($landscape) {
            $cmd .= " -O landscape";
        }

        //Do not use lossless compression on pdf objects
        //$cmd .= " --no-pdf-compression";

        //Disable the intelligent shrinking strategy
        //used by WebKit that makes the pixel/dpi
        //ratio none constant
        $cmd .= " --disable-smart-shrinking";

        //Do not include the page in the table of
        //contents and outlines
        $cmd .= " --exclude-from-outline";

        //Use print media-type instead of screen
        $cmd .= " --print-media-type";

        //Set the page left margin (default 10mm)
        $cmd .= " --margin-left 20mm";
        $cmd .= " --load-error-handling \"ignore\"";
        $cmd .= " --load-media-error-handling \"ignore\"";

        // for macOS and wkhtmltopdf version 0.12.4 https://github.com/wkhtmltopdf/wkhtmltopdf/issues/3241
        if (stristr(PHP_OS, 'DAR')) {
            $cmd .= " --dpi 800 --zoom 1.5";
        } else {
            //Если принудительно не поставить dpi, то размеры указанные в css в милиметрах на печати будут совсем не такими!
            $cmd .= " --dpi 96";
        }

        /*
         * Баг исправлен, теперь не нужно делать zoom
        if (PHP_OS !== 'WINNT') {
            //https://github.com/wkhtmltopdf/wkhtmltopdf/issues/2171#issuecomment-139164039
            $cmd .= " --zoom 0.78125";
        }
        */

        //В качестве истоничка укажем символ "-", что обозначает чтение из stdin
        $cmd .= " -";

        //В качестве файла назначения указываем $filepathAbs
        //если имя файла содержит русские буквы, то в итоге получаем иероглифы в проводнике
        //поэтому сначала создадим временный файл, а потом переименуем
        $tmpfilepathAbs = LIGHT_DIR_TEMP . '/' . User::getId() . '_' . time() . '.pdf';
        $cmd .= ' "' . $tmpfilepathAbs . '"';

        $result = Misc::procOpen($cmd, $msgBuff, $html);
        if (!$result) {
            return false;
        }

        // Проверим, а сгенерировался ли pdf файл вообще?
        if (!is_file($tmpfilepathAbs)) {
            // Выходного pdf файла почему-то нет
            if ($msgBuff) {
                $msgBuff->addError('Destination file ' . htmlspecialchars($tmpfilepathAbs) . 'was not created ');
            }
            return false;
        }

        //Переименуем временный файл в реальный
        $filepathAbsEnc = File::encode($filepathAbs);
        rename($tmpfilepathAbs, $filepathAbsEnc);
        @chmod($filepathAbsEnc, 0666);

        return true;
    }

    private static function runAthena($html, $filepathAbs, $landscape = false, Msg $msgBuff = null)
    {
        //Путь к исполняемому файлу Athena должен быть в конфиге
        $cmd = Config::$athenaPath;

        if ($landscape) {
            $cmd .= " --no-portrait";
        }

        // Вывод результата через stdOut
        $cmd .= " --stdout";

        // Создадим временный файл, так как при stdIn не работает кодировка
        // Использовать будем текущую папку, так как из другой Athena не сможет подтянуть
        $tmpFilename = User::getId() . '_' . time() . '.html';
        file_put_contents($tmpFilename, $html);
        @chmod($tmpFilename, 0666);
        $cmd .= ' "' . $tmpFilename . '"';

        $content = Misc::procOpen($cmd, $msgBuff, $html, true);

        // Удалим временный файл
        unlink($tmpFilename);

        if (!$content) {
            $msgBuff->addError('PDF content is empty');
            return false;
        }

        // Сохраним stdOut в файл
        $filepathAbsEnc = File::encode($filepathAbs);
        file_put_contents($filepathAbsEnc, $content);
        @chmod($filepathAbsEnc, 0666);

        return true;
    }

    /**
     * Конвертирует переданный html в pdf и сохраняет по указанному пути внутри filesfolder
     * Перед тем как запускать процесс конвертации, программа проверяет, возможно этот pdf файл уже есть в папке
     * Для проверки, нужно ли его обновить, используется вспомагательная таблица в базе данных pdfhash, в которую сохраняется
     * md5 хэш от html контента
     * @param string $html
     * @param string $fileRelativePath путь и имя pdf файла относительно filesfolder (расширение .pdf тоже нужно указать)
     * @param Msg $msgBuff
     * @return bool обновился ли файл на файловой системе реально?
     */
    public static function saveIntoFilesFolder(string $html, string $fileRelativePath, Msg $msgBuff = null)
    {
        //Для начала проверим, нужно ли перегенерировать этот pdf файл? Возможно он уже есть в папке и содержимое совпаадает?
        if (!self::isRegenerationNeeded($html, $fileRelativePath)) {
            //Проверка показала, что такой файл уже есть и содержимое совпадает.
            //Ничего дальше делать не будем
            return false;
        }

        //Определим абсолютный путь до генерируемого файла
        $filepathAbs = FilesFolder::getAbsolutePath($fileRelativePath);
        //Сохраняем pdf файла
        $result = self::save($html, $filepathAbs, false, $msgBuff);
        if ($result) {
            //Если успешно сохранили файл в папку, нужно записать md5 хеш от html в специальную таблицу pdfhash
            Db::replaceRow(['Filepath' => $fileRelativePath, 'HtmlMD5' => md5($html)], 'pdfhash');
        }

        return $result;
    }

    /**
     * Принимает массив путей к pdf файлам (относительно filesfolder), склеивает их и отдаёт пользователю в браузер
     * @param array $filesRelative массив имён файлов относительно FilesFolder
     * @param string $attachementFilename имя файла, которое пользователь увидит в браузере в диалоговом окне "скачать..."
     * @throws Codeception\TestResponseException
     */
    public static function concatenateAndStreamFromFilesFolder(array $filesRelative, $attachementFilename)
    {
        $timer = new Timer();

        Response::header('Content-Type: application/pdf');
        Response::header('Content-Disposition: attachment;filename*=UTF-8\'\'' . $attachementFilename);
        Response::header('Cache-Control: max-age=0');

        // Важный параметр -dSubsetFonts=false, чтобы не терять символы при склейке
        // http://stackoverflow.com/questions/12806911/ghostscript-skips-characters-when-merging-pdfs
        // Чтобы окончательно победить баг с пропадающими символами при склейке PDF файлов, не будем использовать ghostscript
        // $cmd = '"' . Config::$ghostScriptPath . '" -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dSubsetFonts=false -sOutputFile=-';
        // будем использовать более простую утилиту pdftk
        $cmd = self::getCmdConcatenatePdf($filesRelative, '-');
        passthru($cmd);

        if ($timer->get() > 1) {
            Metric::record('Pdf::concatenateAndStreamFromFilesFolder >1s', $timer->getMs(), 'files: ' . count($filesRelative));
        }

        ApplicationAbstract::terminate();
    }

    public static function concatenateAndSaveFromFilesFolder(
        array $filesRelative,
        string $outAbsFilepathEnc,
        Msg $msgBuff = null
    )
    {
        $cmd = self::getCmdConcatenatePdf($filesRelative, $outAbsFilepathEnc);
        Misc::procOpen($cmd, $msgBuff);
    }

    private static function getCmdConcatenatePdf(array $files, string $outFilepathEnc)
    {
        $filesAbsEnc = FilesFolder::copyToTmpDirAndRename($files);

        $cmd = '"' . Config::$pdftkPath . '"';
        foreach ($filesAbsEnc as $filepathAbsEnc) {
            $cmd .= ' "' . $filepathAbsEnc . '"';
        }
        $cmd .= ' output ' . $outFilepathEnc;

        return $cmd;
    }

    /**
     * Склеивает несколько PDF файлов в один с помощью утилиты pdftk
     * @param array $srcFilepaths массив абсолютных путей к файлам источникам (не encoded)
     * @param string $destFilepath путь к файлу назначения (не encoded).
     * @param Msg $msgBuff опционально буфер для сообщений об ошибках.
     */
    public static function concatenatePdfs(array $srcFilepaths, string $destFilepath, Msg $msgBuff = null)
    {
        $timer = new Timer();

        $cmd = '"' . Config::$pdftkPath . '"';
        foreach ($srcFilepaths as $filepathAbsEnc) {
            $cmd .= ' "' . File::encode($filepathAbsEnc) . '"';
        }
        $cmd .= ' output ' . File::encode($destFilepath);

        Misc::procOpen($cmd, $msgBuff);
        Metric::record('Pdf::concatenatePdfs', $timer->getMs(), 'files: ' . count($srcFilepaths));

    }

    /**
     * @param array $srcFilepaths массив абсолютных путей к файлам источникам (не encoded)
     * @param string $attachementFilename имя под которым результат будет отправлен в браузер пользователю
     */
    public static function concatenatePdfsAndStream(array $srcFilepaths, string $attachementFilename)
    {
        $timer = new Timer();

        Response::header('Content-Type: application/pdf');
        Response::header('Content-Disposition: attachment;filename*=UTF-8\'\'' . rawurlencode(File::getValidFilename($attachementFilename)));
        Response::header('Cache-Control: max-age=0');

        $cmd = '"' . Config::$pdftkPath . '"';
        foreach ($srcFilepaths as $filepathAbsEnc) {
            $cmd .= ' "' . File::encode($filepathAbsEnc) . '"';
        }
        $cmd .= ' output -';

        passthru($cmd);

        if ($timer->get() > 1000) {
            Metric::record('Pdf::concatenatePdfsAndStream >1s', $timer->getMs(), 'files: ' . count($srcFilepaths));
        }
    }

    /**
     * Проверяет, нужно ли перегенрировать pdf файл?
     * Его нужно перегенерировать в двух случаях:
     * 1) если его нет по указанному пути
     * 2) если он есть, но содержимое отличается
     * @param string $html
     * @param string $filepath
     * @return bool
     */
    private static function isRegenerationNeeded($html, $filepath)
    {
        //Сначала проверим, может pdf файл уже есть в этой папке?
        if (FilesFolder::isFile($filepath)) {
            //pdf файл уже существует. Возможно его нужно обновить?
            //для этого сверим хэш с таблицей pdfhash
            //найдём старый хеш (относящийся у уже существующему файлу)
            $oldMd5 = Db::getOne("SELECT HtmlMD5 FROM pdfhash WHERE Filepath = '" . Db::esc($filepath) . "'");
            if ($oldMd5 && $oldMd5 === md5($html)) {
                //md5 у html контента существуюего файла и у нового html совпадают!
                //ничего пересохранять не будем, возвращаем false
                return false;
            }
        }

        //Если одно из условий в алгоритме выше не выполнилось, значит pdf файл нуждается в перегенерации
        return true;
    }
}
