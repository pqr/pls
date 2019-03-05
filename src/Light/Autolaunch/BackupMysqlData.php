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
 * Делает бэкап данных (все таблицы, кроме log_*, dblog_*, deleted_*, queue_*, metric и прочие служебные).
 * Логи и служебные таблицы добавляются в архив только в виде структуры, но без данных.
 * Архив сохраняется в папку указанную в настройках (?a=SettingsBackup),
 * после этого отправляется на почту (также указанную в настройках) с разбивкой по 10Мб
 */
class BackupMysqlData extends AutolaunchAbstract
{
    protected static $title = 'Backup Database';
    protected static $description = '<a href="?a=SettingsBackup">Settings >></a>';

    private const MAX_MAIL_SIZE_MB = 10;

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
            $this->runProcess($this->getDumpDataCmd());
            // Выгружаем с помощью mysqldump и архивируем с помощью 7z структуру таблиц с логами (без данных)
            $this->runProcess($this->getDumpLogsStructureCmd());

            // Проверяем, что архив существует и не пуст
            $this->checkArchive();

            $this->email();

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

        $this->sqlFilename = Config::$mysqlDb . '_' . date('Y_m_d_H_i_s') . '.sql';
        $this->archiveFilepath = $this->backupDir . '/' . $this->sqlFilename . '.7z';

        $this->archivePass = ApplicationAbstract::getParam(SettingsBackup::PARAM_PASS);

    }

    protected function runProcess(string $cmd)
    {
        try {
            $process = Process::fromShellCommandline($cmd);
            $process->setTimeout(null);
            $process->mustRun();
            $output = $process->getOutput();
            $this->_msgBuff->addHtml('<pre style="font-weight: bold;">' . htmlspecialchars($cmd) . '</pre>');
            $this->_msgBuff->addHtml('<pre>' . htmlspecialchars($output) . '</pre>');
            $this->_msgBuff->addHtml('<hr>');
            $this->_msgBuff->addEmptyLine();
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $this->_msgBuff->addHtml('<pre>' . htmlspecialchars($errorMsg) . '</pre>');
        }
    }

    protected function makeBackupDir()
    {
        $backupDir = $this->backupDir;
        $this->fs->mkdir($backupDir);
    }

    protected function getDumpDataCmd(): string
    {
        $logsTables = $this->getLogsTables();

        $mysqldumpCmd = Mysqldump::create()
            ->ignoreTables($logsTables)
            ->getCmd();

        $archiveCmd = P7zip::create($this->archiveFilepath)
            ->fromStdin($this->sqlFilename)
            ->password($this->archivePass)
            ->getCmd();

        // https://stackoverflow.com/questions/11214437/php-mysqldump-and-zip-7z
        return "$mysqldumpCmd | $archiveCmd";
    }

    protected function getDumpLogsStructureCmd(): string
    {
        $logsTables = $this->getLogsTables();
        if (!$logsTables) {
            return '';
        }

        $mysqldumpCmd = Mysqldump::create()
            ->noData()
            ->onlyTables($logsTables)
            ->getCmd();

        $sqlFilename = substr($this->sqlFilename, 0, -4) . '_logs_structure.sql';
        $archiveCmd = P7zip::create($this->archiveFilepath)
            ->fromStdin($sqlFilename)
            ->password($this->archivePass)
            ->getCmd();

        // https://stackoverflow.com/questions/11214437/php-mysqldump-and-zip-7z
        return "$mysqldumpCmd | $archiveCmd";
    }

    protected function getLogsTables(): array
    {
        $ignoreTables = [
            'dblog',
            'dblog_text',
            'dblog_trace',
            'deleted',
            'deleted_text',
            'deleted_trace',
            'folderstatus',
            'metric',
            'modulesession',
            'phpsession',
            'user_flash',
        ];

        $ignoreTables = array_merge($ignoreTables, Db::getCol("show tables LIKE '%log%'"));
        $ignoreTables = array_unique($ignoreTables);

        return $ignoreTables;
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

    protected function email()
    {
        $mailTo = $this->getMailTo();
        if (!$mailTo) {
            return;
        }

        $mailer = $this->getMailer();
        if (!$mailer) {
            return;
        }

        $totalSent = 0;
        $attachements = $this->splitArchiveForEmail();
        $partsCount = count($attachements);
        foreach ($attachements as $i => $attachement) {

            try {
                $message = $this->getMessage($mailTo, $attachement, $i + 1,
                    $partsCount);
                $sent = $mailer->send($message);
                $totalSent += $sent;

                $msg = 'Sent ' . htmlspecialchars($message->getSubject()) . ' to ' . htmlspecialchars($mailTo);
                if ($sent) {
                    $this->_msgBuff->addInfo($msg);
                } else {
                    $this->_msgBuff->addError("Not $msg");
                }
            } catch (\Throwable $e) {
                $this->_msgBuff->addError($e->getMessage());
            }
        }

        $msg = "E-mail's Sent: $totalSent";
        $this->_msgBuff->addSuccessOrInfo($msg, $totalSent);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $totalSent);

        // Удаляем временную папку созданную для разбивки большого 7z файла по частям
        $this->removeSplitPartsTempDir();
    }

    protected function getMailTo(): ?string
    {
        $to = ApplicationAbstract::getParam(SettingsBackup::PARAM_MAIL_TO);
        if (!$to) {
            $link = $this->getBackupSettingsLink();
            $msg = "E-mail settings for backup not set, $link";
            $this->_msgBuff->addSkipp($msg);
            return null;
        }

        return $to;
    }

    protected function getMailer()
    {
        $transport = new \Swift_SmtpTransport(Config::$swiftmailerHost, Config::$swiftmailerPort,
            Config::$swiftmailerSecuriy);

        if (Config::$swiftmailerUser) {
            $transport->setUsername(Config::$swiftmailerUser);
        }
        if (Config::$swiftmailerPassword) {
            $transport->setPassword(Config::$swiftmailerPassword);
        }

        return new \Swift_Mailer($transport);

    }

    /**
     * Разбивает исходный архив на части размером не более 10 Мб
     * Возвращает массив с полнымипутями до результирующих частей
     */
    protected function splitArchiveForEmail(): array
    {
        if (filesize($this->archiveFilepath) < self::MAX_MAIL_SIZE_MB * 1024 * 1024) {
            // Если текущий архив и так меньше 10М, то разбивать на части нет смысла
            return [$this->archiveFilepath];
        }

        // Удаляем старую директорию со всем содержимом (если такая была)
        $this->removeSplitPartsTempDir();

        // Создаём заново пустую директорию
        $this->makeSplitPartsTempDir();

        $cmd = $this->getArchiveSplitCmd();

        $this->runProcess($cmd);

        // Собираем имена всех получившихся после разбиения файлов
        return $this->getSplitPartsTempDirFiles();
    }

    protected function getSplitPartsTempDir()
    {
        return $this->backupDir . '/parts_for_email';
    }

    protected function removeSplitPartsTempDir()
    {
        $partsDir = $this->getSplitPartsTempDir();
        $this->fs->remove($partsDir);
    }

    protected function makeSplitPartsTempDir()
    {
        $partsDir = $this->getSplitPartsTempDir();
        $this->fs->mkdir($partsDir);
    }

    protected function getSplitPartsTempDirFiles()
    {
        $outFiles = [];
        $partsDir = $this->getSplitPartsTempDir();
        foreach (scandir($partsDir) as $file) {
            $filepath = "$partsDir/$file";
            if (!is_file($filepath)) {
                continue;
            }

            $outFiles[] = $filepath;
        }

        return $outFiles;
    }

    protected function getArchiveSplitCmd()
    {
        $outFilepath = $this->getSplitPartsTempDir() . '/' . $this->sqlFilename . '.7z';

        return P7zip::create($outFilepath)
            ->inputFilepath($this->archiveFilepath)
            ->password($this->archivePass)
            ->noCompression()
            ->volumeSizeMb(self::MAX_MAIL_SIZE_MB)
            ->getCmd();
    }

    protected function getMessage(
        string $to,
        string $attachement,
        int $partNum,
        int $partsCount
    ): \Swift_Message {
        $subject = $this->getSubject($partNum, $partsCount);
        $message = new \Swift_Message($subject);
        $message->setFrom(Config::$swiftmailerUser);
        $message->setTo($to);

        $attachement = \Swift_Attachment::fromPath($attachement);

        $message->attach($attachement);

        return $message;
    }

    protected function getSubject(int $partNum, int $partsCount)
    {
        return 'Backup ' . $this->sqlFilename . " (part $partNum of $partsCount)";
    }

}
