<?php

namespace Light\Autolaunch;

use Light\Config;
use Light\Db;
use Light\File;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Light\Pdf;

class DailyMaintainance extends AutolaunchAbstract
{
    protected static $title = 'System: Daily Database Maintainance';
    protected static $description = 'Очищает modulesession, удаляет log_autolaunch > 3000шт, log_filter старше 6 мес., удаляет temp/concatenate_pdfs, очищает папки *_pdfs и *_massaction, зипует старые логи';

    #[\Override]
    public function run(array $params): void
    {
        $this->clearModulesession();
        $this->clearLogAutolaunch();
        $this->clearLogFilter();
        $this->clearMassactions();
        $this->clearConcatenatePdfs();
        $this->zipLastMonthLogs();
    }

    private function clearModulesession(): void
    {
        $rows = Db::getOne("SELECT count(*) FROM modulesession");
        Db::query("TRUNCATE modulesession");
        $msg = '<code>modulesession</code> deleted records: ' . $rows;
        $this->_msgBuff->addSuccessOrInfo($msg, $rows);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $rows);
    }

    private function clearLogAutolaunch(): void
    {
        $deleted = 0;
        $classes = Db::getCol("SELECT DISTINCT(ClassName) FROM log_autolaunch");
        foreach ($classes as $className) {
            // Найдём минимальный ID среди последних 3000 записей
            $minKeepId = Db::getOne("SELECT min(ID) FROM (SELECT ID FROM log_autolaunch WHERE ClassName = @s ORDER BY ID DESC LIMIT 3000) AS log",
                $className);
            // Удалим все записи, у которых ID меньше этого порога
            if ($minKeepId) {
                Db::query("DELETE FROM log_autolaunch WHERE ClassName = @s AND ID < $minKeepId", $className);
                $deleted += Db::getAffectedRows();
            }
        }

        Db::query("DELETE FROM log_autolaunch_text WHERE NOT EXISTS(SELECT 1 FROM log_autolaunch la WHERE la.ID = log_autolaunch_text.ID)");

        $msg = '<code>log_autolaunch</code> deleted records: ' . $deleted;
        $this->_msgBuff->addSuccessOrInfo($msg, $deleted);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $deleted);
    }

    private function clearLogFilter(): void
    {
        $deleted = 0;
        Db::query("DELETE FROM log_filter WHERE CreatedDateTime < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)");
        $deleted += Db::getAffectedRows();

        $msg = '<code>log_filter</code> deleted records: ' . $deleted;
        $this->_msgBuff->addSuccessOrInfo($msg, $deleted);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $deleted);
    }

    private function clearMassactions(): void
    {
        $pdfsLen = strlen("_pdfs");
        $massLen = strlen("_massaction");

        foreach (scandir(Config::$filesfolderLocal) as $dir) {
            $dirPath = Config::$filesfolderLocal . "/$dir";
            if ($dir[0] === '.' || !is_dir($dirPath)) {
                continue;
            }

            // Если папка заканчивается на _pdfs или _massaction - это нужная нам папка, очищаем!
            if (substr($dir, -1 * $pdfsLen) === "_pdfs"
                || substr($dir, -1 * $massLen) === "_massaction") {

                try {
                    File::fs()->remove($dirPath);
                    $msg = '<code>' . htmlspecialchars($dir) . '</code> deleted';
                    $this->_msgBuff->addSuccess($msg);
                    $this->_msgBuffShort->addSuccess($msg);
                } catch (\Exception $e) {
                    $this->_msgBuff->addError($e->getMessage());
                }

            }
        }
    }

    private function clearConcatenatePdfs(): void
    {
        // После работы функции Pdf::concatenatePdfs() могли остаться не пустые папки concatenate_pdfs_1, concatenate_pdfs_2, ...
        // В нормальном режиме эти папки должны были бы автоматически удалиться после выполнения скрипта, но если этого
        // не произошло, то удалим их сейчас
        foreach (scandir(LIGHT_DIR_TEMP) as $dir) {
            $dirPath = LIGHT_DIR_TEMP . "/$dir";
            if ($dir[0] === '.' || !is_dir($dirPath)) {
                continue;
            }

            // Если папка начинается с нужного нам префикса  Pdf::CONCAT_TMPDIR_PREFIX, то удаляем её!
            if (str_starts_with($dir, Pdf::CONCAT_TMPDIR_PREFIX)) {

                try {

                    File::fs()->remove($dirPath);
                    $msg = '<code>' . htmlspecialchars($dirPath) . '</code> deleted';
                    $this->_msgBuff->addSuccess($msg);
                    $this->_msgBuffShort->addSuccess($msg);

                } catch (\Exception $e) {

                    $this->_msgBuff->addError($e->getMessage());

                }

            }
        }
    }

    private function zipLastMonthLogs(): void
    {
        exec('7z', $_, $return);
        if ($return !== 0) {
            // В системе не установлен 7z
            $this->_msgBuff->addWarning('7z not found, skipp logs compression');
            return;
        }

        $compressed = 0;
        $errors = 0;

        $currentLogFile = date('Y-m') . ".csv";

        foreach (scandir(LIGHT_DIR_LOGS) as $dir) {
            $dirpath = LIGHT_DIR_LOGS . "/$dir";
            if ($dir[0] === '.' || !is_dir($dirpath)) {
                continue;
            }

            foreach (scandir($dirpath) as $file) {
                $srcFilepath = $dirpath . "/$file";
                if ($file[0] === '.' || !is_file($srcFilepath)) {
                    continue;
                }

                if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                    continue;
                }

                if ($file === $currentLogFile) {
                    continue;
                }

                $destFilepath = "$srcFilepath.zip";
                $cmd = '7z a "' . $destFilepath . '" "' . $srcFilepath . '"';
                exec($cmd, $out, $retur);
                if ($return === 0) {
                    $this->_msgBuff->addInfo("File compressed: " . htmlspecialchars($destFilepath));
                    $compressed++;
                    unlink($srcFilepath);
                } else {
                    $this->_msgBuff->addError("Error compressing " . htmlspecialchars($srcFilepath) . ":<br>" . nl2br(htmlspecialchars(implode("\n",
                            $out))));
                    $errors++;
                }
            }
        }

        $this->_msgBuff->addEmptyLine();
        $msg = "Compressed log files: $compressed";
        $this->_msgBuff->addSuccessOrInfo($msg, $compressed);
        if ($compressed) {
            $this->_msgBuffShort->addSuccess($msg);
        }

        if ($errors) {
            $errMsg = "Error compressing log files: $errors";
            $this->_msgBuff->addError($errMsg);
            $this->_msgBuffShort->addError($errMsg);
        }
    }


}
