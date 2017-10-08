<?php

namespace Forward\Autolaunch\ImportFrom1C;

use Forward\Classes\Constant;
use Forward\ForwardConfig;
use Light\File;
use Light\Module\Autolaunch\AutolaunchAbstract;

abstract class ImportFrom1CAbstract extends AutolaunchAbstract
{
    const RESULT_INCORRECT = 'incorrect';
    const RESULT_NOTFOUND = 'notfound';
    const RESULT_INSERTED = 'inserted';
    const RESULT_UPDATED = 'updated';
    const RESULT_DELETED = 'deleted';
    const RESULT_NOTCHANGED = 'notchanged';

    protected $folder; // определяется в классах потомках как 'cinvoice' или 'cpayment', нужно для выбора папки

    abstract protected function importFromJson(array $json, int $companyId, string $filenameForLog);

    protected function run(array $params)
    {
        $this->_writeTextLog = true;
        foreach (Constant::$onecCompany as $companyId => $path) {
            $this->importFromFolder($companyId, $path);
        }
    }

    private function importFromFolder(int $companyId, string $ftpSubfolder)
    {
        $this->_msgBuff->addEmptyLine();
        $this->_msgBuff->addInfo('<strong>' . $ftpSubfolder . '</strong>');

        $files = 0;
        $results = [];

        // D:/FTP/cinvoice/pls12
        $srcFolderAbs = ForwardConfig::$onecFtpFolderBase . '/' . $this->folder . '/' . $ftpSubfolder;
        $archiveSubfolder = 'archive/' . date('Y-m-d_H_i_s');
        $destFolderAbs = "$srcFolderAbs/$archiveSubfolder";

        foreach (scandir($srcFolderAbs) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'json') {
                continue;
            }
            $files++;

            if ($files === 1) {
                // В момент обработки первого файла создадим папку для архива
                File::mkdir($destFolderAbs);
            }

            $destFilepathAbs = "$destFolderAbs/$file";
            // Сразу перемещаем файл в архивную папку
            rename("$srcFolderAbs/$file", $destFilepathAbs);

            $this->_msgBuff->addEmptyLine();
            $this->_msgBuff->addInfo('<u>' . htmlspecialchars($destFilepathAbs) . '</u>');

            $json = json_decode(str_replace("\xEF\xBB\xBF", '', file_get_contents($destFilepathAbs)), true);
            if (!$json) {
                $this->_msgBuff->addError('Не удалось распознать json структуру');
                switch (json_last_error()) {
                    case JSON_ERROR_DEPTH:
                        $this->_msgBuff->addError('Достигнута максимальная глубина стека');
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $this->_msgBuff->addError('Некорректные разряды или не совпадение режимов');
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $this->_msgBuff->addError('Некорректный управляющий символ');
                        break;
                    case JSON_ERROR_SYNTAX:
                        $this->_msgBuff->addError('Синтаксическая ошибка, не корректный JSON');
                        break;
                    case JSON_ERROR_UTF8:
                        $this->_msgBuff->addError('Некорректные символы UTF-8, возможно неверная кодировка');
                        break;
                    default:
                        $this->_msgBuff->addError('Неизвестная ошибка');
                        break;
                }

                if (!isset($results[self::RESULT_INCORRECT])) {
                    $results[self::RESULT_INCORRECT] = 0;
                }
                $results[self::RESULT_INCORRECT]++;
                continue;
            }

            // Передадим данные из json в метод, который переопределяется в классе потомке
            $filenameForLog = "$ftpSubfolder/$archiveSubfolder/$file";
            $result = $this->importFromJson($json, $companyId, $filenameForLog);

            if (!isset($results[$result])) {
                $results[$result] = 0;
            }

            $results[$result]++;
        }

        $this->_msgBuff->addEmptyLine();

        $msg = $ftpSubfolder . ' файлов: ' . $files;
        $this->_msgBuff->addInfo($msg);
        $this->_msgBuffShort->addInfo($msg);

        if (isset($results[self::RESULT_INCORRECT]) && $results[self::RESULT_INCORRECT]) {
            $msg = $ftpSubfolder . ' не корректные файлы: ' . $results[self::RESULT_INCORRECT];
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);
        }

        if (isset($results[self::RESULT_NOTFOUND]) && $results[self::RESULT_NOTFOUND]) {
            $msg = $ftpSubfolder . ' не найдено в базе: ' . $results[self::RESULT_NOTFOUND];
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);
        }

        if (isset($results[self::RESULT_INSERTED]) && $results[self::RESULT_INSERTED]) {
            $msg = $ftpSubfolder . ' вставлено новых: ' . $results[self::RESULT_INSERTED];
            $this->_msgBuff->addSuccessOrInfo($msg, $results[self::RESULT_INSERTED]);
            $this->_msgBuffShort->addSuccessOrInfo($msg, $results[self::RESULT_INSERTED]);
        }

        if (isset($results[self::RESULT_UPDATED]) && $results[self::RESULT_UPDATED]) {
            $msg = $ftpSubfolder . ' обновлено: ' . $results[self::RESULT_UPDATED];
            $this->_msgBuff->addSuccessOrInfo($msg, $results[self::RESULT_UPDATED]);
            $this->_msgBuffShort->addSuccessOrInfo($msg, $results[self::RESULT_UPDATED]);
        }

        if (isset($results[self::RESULT_NOTCHANGED]) && $results[self::RESULT_NOTCHANGED]) {
            $msg = $ftpSubfolder . ' не изменилось: ' . $results[self::RESULT_NOTCHANGED];
            $this->_msgBuff->addSkipp($msg);
            $this->_msgBuffShort->addSkipp($msg);
        }

        if (isset($results[self::RESULT_DELETED]) && $results[self::RESULT_DELETED]) {
            $msg = $ftpSubfolder . ' удалено: ' . $results[self::RESULT_DELETED];
            $this->_msgBuff->addInfo($msg);
            $this->_msgBuffShort->addInfo($msg);
        }
    }


}
