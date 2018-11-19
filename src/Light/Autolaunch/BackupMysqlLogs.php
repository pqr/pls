<?php

namespace Light\Autolaunch;


use Light\ApplicationAbstract;
use Light\Autolaunch\BackupHelpres\Mysqldump;
use Light\Autolaunch\BackupHelpres\P7zip;
use Light\Config;
use Light\Db;
use Light\Link;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Light\Module\SettingsBackup\SettingsBackup;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Создаёт бэкап таблиц с логами (dblog_*, log_*, deleted_*, metric) с помощью mysqldump,
 * архивирует в 7z архив и сохраняет в папку указанную в настройках
 * для бэкапов (?a=SettingsBackup).
 * Эта задача должна запускаться раз в месяц.
 */
class BackupMysqlLogs extends AutolaunchAbstract
{
    protected static $title = 'Backup Database Logs Tables';
    protected static $description = '<a href="?a=SettingsBackup">Settings >></a>';

    /** @var string */
    private $backupDir;
    /** @var string */
    private $sqlFilename;
    /** @var string */
    private $archiveFilepath;
    /** @var string */
    private $archivePass;

    /** @var Filesystem */
    protected $fs;

    protected function run(array $params)
    {
        try {

            $this->fs = new Filesystem();

            $this->initSettings();
            $this->makeBackupDir();

            // Выгружаем с помощью mysqldump и архивируем с помощью 7z основные данные
            $this->runProcess($this->getDumpLogsCmd());

            // Проверяем, что архив существует и не пуст
            $this->checkArchive();

        } catch (\Throwable $e) {

            $msg = $e->getMessage();
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);

        }
    }

    protected function initSettings()
    {
        $this->backupDir = ApplicationAbstract::getParam(SettingsBackup::PARAM_DIR);
        if (!$this->backupDir) {
            throw new \Exception('Empty Backup Dir');
        }

        $this->sqlFilename = Config::$mysqlDb . '_' . date('Y_m_d_H_i_s') . '_logs.sql';
        $this->archiveFilepath = $this->backupDir . '/' . $this->sqlFilename . '.7z';

        $this->archivePass = ApplicationAbstract::getParam(SettingsBackup::PARAM_PASS);

    }

    protected function runProcess(string $cmd)
    {
        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->mustRun();
        $output = $process->getOutput();
        $this->_msgBuff->addHtml('<pre style="font-weight: bold;">' . htmlspecialchars($cmd) . '</pre>');
        $this->_msgBuff->addHtml('<pre>' . htmlspecialchars($output) . '</pre>');
        $this->_msgBuff->addHtml('<hr>');
        $this->_msgBuff->addEmptyLine();
    }

    protected function makeBackupDir()
    {
        $backupDir = $this->backupDir;
        $this->fs->mkdir($backupDir);
    }

    protected function getDumpLogsCmd(): string
    {
        $logTables = $this->getLogTables();
        $mysqldumpCmd = Mysqldump::create()
            ->onlyTables($logTables)
            ->getCmd();

        $archiveCmd = P7zip::create($this->archiveFilepath)
            ->fromStdin($this->sqlFilename)
            ->password($this->archivePass)
            ->getCmd();

        // https://stackoverflow.com/questions/11214437/php-mysqldump-and-zip-7z
        return "$mysqldumpCmd | $archiveCmd";
    }

    protected function getLogTables(): array
    {
        $logTables = [
            'dblog',
            'dblog_text',
            'dblog_trace',
            'deleted',
            'deleted_text',
            'deleted_trace',
            'metric',
        ];

        $logTables = array_merge($logTables, Db::getCol("show tables LIKE 'log_%'"));

        return $logTables;
    }

    protected function getBackupSettingsLink()
    {
        return Link::create(SettingsBackup::class, 'setup >>');
    }

    protected function checkArchive()
    {
        if (!file_exists($this->archiveFilepath)) {
            throw new \Exception("File <strong>" . htmlspecialchars($this->archiveFilepath) . "</strong> not exists");
        }

        if (!filesize($this->archiveFilepath)) {
            throw new \Exception("File <strong>" . htmlspecialchars($this->archiveFilepath) . "</strong> is empty");
        }
        $msg = "Archive Success: <strong>" . htmlspecialchars($this->archiveFilepath) . "</strong>";
        $this->_msgBuff->addSuccess($msg);
        $this->_msgBuff->addEmptyLine();
        $this->_msgBuffShort->addSuccess($msg);
    }

}
