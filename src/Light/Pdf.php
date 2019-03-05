<?php

namespace Light;

use Symfony\Component\Process\Process;

class Pdf
{
    const CONCAT_TMPDIR_PREFIX = 'concatenate_pdfs';

    /**
     * Генерирует pdf из html контента по абсолютному пути. Проверок на сущетсвование файла не осущетсвляет
     * @param string $html
     * @param string $filepathAbs абсолютный путь до файла (с именем файла и расширением .pdf)
     * @param bool $landscape
     * @param Msg $msgBuff
     * @param bool $recall
     * @return bool
     */
    public static function save($html, $filepathAbs, $landscape = false, Msg $msgBuff = null, bool $recall = false)
    {
        //Обработаем html содержимое - заменим все относительные ссылки на абсолютные пути в файловой системе
        //$replacement = [' src="' => ' src="' . Config::$localUrl . '/', ' href="' => ' href="' . Config::$localUrl . '/'];
        if (Config::$useAthena) {
            $replacementBase = Config::$localUrlNetIp;
        } else {
            $replacementBase = LIGHT_DIR_PUBLIC;
        }

        $replacement = [
            ' src="' => ' src="' . $replacementBase . '/',
            ' href="' => ' href="' . $replacementBase . '/',
            ' url(' => ' url(' . $replacementBase . '/',
        ];

        $html = str_replace(array_keys($replacement), array_values($replacement), $html);

        if (Config::$useAthena) {

            $flagSuccess = self::runAthena($html, $filepathAbs, $landscape, $msgBuff);

        } else {
            $flagSuccess = self::runLocal($html, $filepathAbs, $landscape, $msgBuff);
        }

        // проверим, не пустой ли файл сгенерировался
        if ($flagSuccess && !trim(file_get_contents($filepathAbs))) {
            // удалим файл и повторно попробуем сгенерировать pdf
            unlink($filepathAbs);
            if (!$recall) {
                self::save($html, $filepathAbs, $landscape, $msgBuff, true);
            } else {
                // если файл и повторно не смог сгенерироваться - выведем ошибку
                if ($msgBuff) {
                    $msgBuff->addError("Не удалось сгенерировать PDF файл");
                }
                $flagSuccess = false;
            }
        }

        return $flagSuccess;
    }

    private static function runLocal($html, $filepathAbs, $landscape = false, Msg $msgBuff = null)
    {
        $tmpfilepathAbs = LIGHT_DIR_TEMP . '/' . User::getId() . '_' . time() . '.pdf';
        $cmd = self::getCmdPdfFromHtmlStdin($tmpfilepathAbs, $landscape);

        try {

            $proc = Process::fromShellCommandline($cmd, null, null, $html);
            // Запускаем синхронно. Если процесс вернёт код отличный от 0, то бростися исключение
            // https://symfony.com/doc/current/components/process
            $proc->mustRun();

        } catch (\Exception $e) {
            if ($msgBuff) {
                $msgBuff->addError($e->getMessage());
            }
            reportException($e);
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

        try {
            // Переименуем временный файл в реальный
            $dir = \dirname($filepathAbs);
            File::fs()->mkdir($dir);
            File::fs()->rename($tmpfilepathAbs, $filepathAbs, true);
            File::fs()->chmod($filepathAbs, 0666);
        } catch (\Exception $e) {
            if ($msgBuff) {
                $msgBuff->addError($e->getMessage());
            }
            reportException($e);
            return false;
        }

        return true;
    }

    public static function getCmdPdfFromHtmlStdin(string $targetFile, bool $landscape = false): string
    {
        // Путь к исполняемому файлу wkhtmltopd должен быть в конфиге
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
        //if (stristr(PHP_OS, 'DAR')) {
        //    $cmd .= " --dpi 800 --zoom 1.5";
        //} else {
        //Если принудительно не поставить dpi, то размеры указанные в css в милиметрах на печати будут совсем не такими!
        $cmd .= " --dpi 96";
        //}

        /*
         * Баг исправлен, теперь не нужно делать zoom
        if (PHP_OS !== 'WINNT') {
            //https://github.com/wkhtmltopdf/wkhtmltopdf/issues/2171#issuecomment-139164039
            //$cmd .= " --zoom 0.78125";
        }
        */

        //В качестве истоничка укажем символ "-", что обозначает чтение из stdin
        $cmd .= " -";

        //В качестве файла назначения указываем $filepathAbs
        //если имя файла содержит русские буквы, то в итоге получаем иероглифы в проводнике
        //поэтому сначала создадим временный файл, а потом переименуем

        $cmd .= ' "' . $targetFile . '"';

        return $cmd;
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

        $process = Process::fromShellCommandline($cmd, null, null, $html);
        $process->mustRun();
        $content = $process->getOutput();

        // Удалим временный файл
        unlink($tmpFilename);

        if (!$content) {
            if ($msgBuff) {
                $msgBuff->addError('PDF content is empty');
            }
            return false;
        }

        // Сохраним stdOut в файл
        File::fs()->dumpFile($filepathAbs, $content);
        File::fs()->chmod($filepathAbs, 0666);

        return true;
    }

    /**
     * Склеивает несколько PDF файлов в один с помощью утилиты pdftk
     * @param array $srcFilepaths массив абсолютных путей к файлам источникам (не encoded)
     * @param string $destFilepath путь к файлу назначения (не encoded).
     * @param Msg $msgBuff опционально буфер для сообщений об ошибках.
     * @return bool
     */
    public static function concatenatePdfs(array $srcFilepaths, string $destFilepath, Msg $msgBuff = null): bool
    {
        $timer = new Timer();

        // Чтобы не было проблем с исходными файлами и русскими буквами в них, скопируем все исходные файлы
        // в отдельную временную папку
        $tmpDir = File::mkdirTmp(self::CONCAT_TMPDIR_PREFIX);
        if (!$tmpDir) {
            if ($msgBuff) {
                $msg = Config::$lang === 'ru'
                    ? 'Не удалось создать временную директорию concatenate_pdfs'
                    : 'Can\'t create temp dir concatenate_pdfs';
                $msgBuff->addError($msg);
            }
            return false;
        }

        $tmpFiles = [];
        $i = 0;
        foreach ($srcFilepaths as $filepath) {
            $tmpFilepath = "$tmpDir/{$i}.pdf";
            $successCopy = File::copy($filepath, $tmpFilepath);
            if ($successCopy) {
                // Успешно скопированные файлы накапливаем в массив
                $tmpFiles[] = $tmpFilepath;
                $i++;
            } elseif ($msgBuff) {
                // Если по каким-то причинам не удалось скопировать очередной исходный pdf во временный файл
                // то добавим сообщение об ошибке
                $msg = Config::$lang === 'ru' ? 'Не удалось прочитать файл ' : 'Can\'t read file ';
                $msg .= htmlspecialchars($filepath);
                $msgBuff->addError($msg);
            }
        }

        $tmpDest = "$tmpDir/result.pdf";
        $cmdArgs = array_merge([Config::$pdftkPath], $tmpFiles, ["output", $tmpDest]);

        try {

            $process = new Process($cmdArgs);
            $process->setTimeout(600);
            $process->mustRun();

            // Теперь копируем получившийся временный файл result.pdf под нужным именем в нужную директорию назначения
            $successCopy = File::copy($tmpDest, $destFilepath);
            Metric::record('Pdf::concatenatePdfs', $timer->getMs(), 'files: ' . count($srcFilepaths));

            if (!$successCopy && $msgBuff) {
                $msg = Config::$lang === 'ru' ? 'Не удалось записать файл ' : 'Can\'t write file ';
                $msg .= htmlspecialchars($destFilepath);
                $msgBuff->addError($msg);
            }

            return $successCopy;

        } catch (\Exception $e) {

            reportException($e);
            if ($msgBuff) {
                $msgBuff->addError($e->getMessage());
            }

            return false;

        }
    }

    public static function getNumberOfPages(string $file): int
    {
        $cmdAgts = [Config::$pdftkPath, $file, 'dump_data'];

        $process = new Process($cmdAgts);
        $process->mustRun();
        $out = $process->getOutput();

        preg_match_all('/NumberOfPages: (\d+)/', $out, $matches);

        return (int)($matches[1][0] ?? 0);
    }
}
