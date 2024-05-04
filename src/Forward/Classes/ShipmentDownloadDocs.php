<?php

namespace Forward\Classes;


use Forward\ForwardConfig;
use Forward\Module\Shipment\Shipment;
use Light\Autolaunch\BackupHelpres\P7zip;
use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\Msg;
use Light\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ShipmentDownloadDocs
{
    public const ONLY_SHIPMENT_REQUESTS_XLSX = -1;
    /**
     * @var Filesystem
     */
    private $fs;
    /**
     * @var Msg
     */
    private $msgBuff;

    /**
     * @var string
     */
    private $tmpDir;
    /**
     * @var Process
     */
    private $process;

    public function __construct(Filesystem $fs, Process $process, Msg $msgBuff)
    {
        $this->fs = $fs;
        $this->process = $process;
        $this->msgBuff = $msgBuff;
    }

    public static function create(Msg $msgBuff)
    {
        return new self(new Filesystem(), new Process(''), $msgBuff);
    }

    /**
     * @param array $shipmentIds
     * @param bool $withPassword
     * @param array|null $onlyFoldersNo
     * @return null|string полный путь к созданному zip файлу
     */
    public function generateZip(array $shipmentIds, bool $withPassword, array $onlyFoldersNo = null): ?string
    {
        $shipments = Db::getAll("SELECT ID, Name, MAWBNumber FROM shipment WHERE ID IN (@a)", $shipmentIds);
        if (!$shipments) {
            $this->msgBuff->addError('Перевозки не найдены');
            return null;
        }

        // Создадим временную локальную папку, куда скопируем все файлы
        if (!$this->createTmpDir()) {
            return null;
        }

        $copied = 0;
        $shipmentsWithFilesCount = 0;

        foreach ($shipments as $shipment) {
            $thisShipmentCopied = $this->copyShipmentFiles($shipment, $onlyFoldersNo);
            $copied += $thisShipmentCopied;
            if ($thisShipmentCopied) {
                $shipmentsWithFilesCount++;
            }
        }

        if (!$copied) {
            $this->msgBuff->addError('Нет файлов');
            return null;
        }

        $resultFilename = 'Shipments Docs ' . date('Y-m-d') . " ($shipmentsWithFilesCount).zip";
        $resultFilepath = $this->tmpDir . "/$resultFilename";

        $p7z = P7zip::create($resultFilepath)
            ->inputFilepath($this->tmpDir . '/*');

        if ($withPassword) {
            $p7z->password(ForwardConfig::$vinvoiceZipPass);
        }

        $cmd = $p7z->getCmd();

        try {
            $this->process->setCommandLine($cmd);
            $this->process->mustRun();
        } catch (\Throwable $e) {
            $this->msgBuff->addError($e->getMessage());
            return null;
        }

        return $resultFilepath;
    }

    private function createTmpDir(): bool
    {
        $this->tmpDir = LIGHT_DIR_TEMP . '/Shipments' . date('Y-m-d_H_i_s') . '-' . User::getId();
        try {
            $this->fs->mkdir($this->tmpDir);
            return true;
        } catch (\Throwable $e) {
            $this->msgBuff->addError($e->getMessage());
            return false;
        }
    }

    public function removeTmpDir(): void
    {
        if ($this->tmpDir) {
            File::fs()->remove($this->tmpDir);
        }
    }

    private function copyShipmentFiles(array $shipment, ?array $onlyFoldersNo): int
    {
        $shipmentId = $shipment['ID'];
        $mawbNumber = $shipment['MAWBNumber'] ?: $shipment['ID'];
        $shipmentFolderRelative = FilesFolder::getRelativeModule(Shipment::class, $shipmentId);

        $copied = 0;

        if ($onlyFoldersNo === null || in_array(self::ONLY_SHIPMENT_REQUESTS_XLSX, $onlyFoldersNo)) {
            // Запрос на скачивание всех документов либо только заявок в формате Excel
            // которые находятся в корневой папке перевозки.
            // Добавим Excel заявку в папку $tmpdir (файл должен начинаться со слова "Заявка" и быть xlsx)
            $copied += $this->copyFiles($shipmentFolderRelative, $mawbNumber, '0 заявка', 'xlsx', 'Заявка');
        }

        // Теперь перебираем все стандартные папки
        foreach (Constant::$standardFolders as $folderNo => $standardFolder) {
            if ($onlyFoldersNo !== null && !in_array($folderNo, $onlyFoldersNo)) {
                // На вход передан определённый массив из номеров папок, которые копировать
                // и текущей папки в нём нет, пропускаем
                continue;
            }

            $srcFolderRelative = "$shipmentFolderRelative/$standardFolder";
            $nameSuffix = $standardFolder;
            /**
             * Добрый день!
             * Можем немного скорректировать? Акт для печати при нажатии кнопки «Документы на печать» выгружать в Excel, а не в PDF?
             * С уважением,
             * Валерия Титова
             * 2018.12.14
             */
            $onlyExtension = $folderNo == Constant::FOLDER_CACT ? 'xlsx' : null;

            $copied += $this->copyFiles($srcFolderRelative, $mawbNumber, $nameSuffix, $onlyExtension, null);

        }

        return $copied;
    }

    private function copyFiles(
        string $srcFolderRelative,
        string $mawbNumber,
        string $nameSuffix,
        ?string $onlyExtension,
        ?string $filenameStartsWith
    ): int {

        $copied = 0;
        $i = 0;
        $srcFolderAbs = FilesFolder::getAbsolute($srcFolderRelative);

        foreach (FilesFolder::scandir($srcFolderRelative) as $file) {

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

            if ($onlyExtension && $onlyExtension !== $ext) {
                continue;
            }

            if ($filenameStartsWith && mb_stripos($file, 'Заявка') !== 0) {
                continue;
            }

            // это нужный файл, копируем его в $tmpdir
            $srcFilepath = "$srcFolderAbs/$file";
            $destFilename = "$mawbNumber $nameSuffix";
            if ($i > 0) {
                $destFilename .= " ($i)";
            }
            $destFilename .= '.' . $ext;
            $destFilename = File::getValidFilename($destFilename);
            $destFilepath = $this->tmpDir . "/$destFilename";

            try {
                $this->fs->copy($srcFilepath, $destFilepath);
                $copied++;
            } catch (\Throwable $e) {
                $this->msgBuff->addError($e->getMessage());
            }

        }

        return $copied;
    }
}
