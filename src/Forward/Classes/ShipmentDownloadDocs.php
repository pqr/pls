<?php

namespace Forward\Classes;


use Forward\ForwardConfig;
use Light\ApplicationAbstract;
use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\Response;
use Light\User;

class ShipmentDownloadDocs
{
    const ONLY_SHIPMENT_REQUESTS_XLSX = -1;

    public static function download(array $shipmentIds, bool $withPassword, $onlyFolderKey = null)
    {
        $shipments = Db::getAll("SELECT ID, Name, MAWBNumber FROM shipment WHERE ID IN (@a)", $shipmentIds);

        if (!$shipments) {
            Response::abort(400, 'Перевозки не найдены');
        }

        $shipmentsWithFilesCount = 0;

        // Создадим временную локальную папку, куда скопируем все файлы
        $tmpdir = LIGHT_DIR_TEMP . '/Shipments' . date('Y-m-d_H_i_s') . '-' . User::getId();
        File::mkdir($tmpdir);

        foreach ($shipments as $shipment) {
            $shipmentHasSomeFile = false;
            $mawbNumber = $shipment['MAWBNumber'] ?: $shipment['ID'];
            $relativeFolder = 'Shipment/' . (floor($shipment['ID'] / 1000)) . '/' . $shipment['ID'];
            if ($onlyFolderKey === null || $onlyFolderKey === self::ONLY_SHIPMENT_REQUESTS_XLSX) {
                // Запрос на скачивание всех документов либо только заявок в формате Excel
                // которые находятся в корневой папке перевозки. Добавим Excel заявку в папку $tmpdir
                foreach (FilesFolder::scandir($relativeFolder) as $file) {
                    if (mb_stripos($file, 'Заявка') === 0
                        && strtolower(pathinfo(File::encode($file), PATHINFO_EXTENSION)) === 'xlsx') {
                        // Нашли файл, который начинается со слова "Заявка" и имеет расширение xlsx
                        // это нужный файл, копируем его в $tmpdir
                        $srcFilepath = FilesFolder::getAbsolutePath($relativeFolder) . "/$file";
                        $destFilepath = "$tmpdir/$file";
                        copy(File::encode($srcFilepath), File::encode($destFilepath));
                        $shipmentHasSomeFile = true;
                    }
                }
            }

            foreach (Constant::$standardFolders as $folderKey => $standardFolder) {
                if ($onlyFolderKey !== null && $onlyFolderKey !== $folderKey) {
                    continue;
                }
                $folder = "$relativeFolder/$standardFolder";
                foreach (FilesFolder::scandir($folder) as $i => $file) {
                    $srcFilepath = FilesFolder::getAbsolutePath($folder) . "/$file";
                    $srcExtension = pathinfo(File::encode($file), PATHINFO_EXTENSION);
                    // Из папк номер 3 Акт без подписи берём только PDF файл
                    if ($standardFolder[0] === '3' && strtolower($srcExtension) !== 'pdf') {
                        continue;
                    }
                    $destFilename = "$mawbNumber $standardFolder";
                    if ($i > 0) {
                        $destFilename .= " ($i)";
                    }
                    $destFilename .= ".$srcExtension";
                    $destFilename = File::getValidFilename($destFilename);
                    $destFilepath = "$tmpdir/$destFilename";

                    copy(File::encode($srcFilepath), File::encode($destFilepath));

                    $shipmentHasSomeFile = true;
                }
            }

            if ($shipmentHasSomeFile) {
                $shipmentsWithFilesCount++;
            }
        }

        if (!$shipmentsWithFilesCount) {
            Response::abort(500, 'Нет файлов');
        }

        $resultFilename = 'Shipments Docs ' . date('Y-m-d') . " ($shipmentsWithFilesCount).zip";
        $resultFilepath = "$tmpdir/$resultFilename";

        $descriptorspec = [
            2 => ["pipe", "w"]
        ];
        $cmd = '"' . escapeshellcmd(str_replace('\\', '/', ForwardConfig::$path7z)) . '"';
        //Команда "a" означает добавить файл в архив
        $cmd .= ' a';
        //Укажем пароль
        if ($withPassword) {
            $cmd .= ' -p' . ForwardConfig::$vinvoiceZipPass;
        }
        //Укажем тип архива zip
        $cmd .= ' -tzip';
        //Укажем файл назначения
        $cmd .= ' "' . str_replace('\\', '/', $resultFilepath) . '"';
        //Укажем источник - что именно архивировать
        $cmd .= ' "' . str_replace('\\', '/', $tmpdir) . '/*"';

        $process = proc_open($cmd, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
        if (is_resource($process)) {

            $errText = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);
            if ($errText) {

                print 'Error:<br>';
                print mb_convert_encoding($errText, 'UTF-8', 'cp866');

            } else {

                Response::header('Content-Type: application/zip');
                Response::header('Content-Length: ' . filesize($resultFilepath));
                Response::header('Content-Disposition: attachment;filename="' . $resultFilename . '"');
                Response::header('Cache-Control: max-age=0');
                readfile($resultFilepath);

            }

        } else {

            Response::abort(500, 'Error proc_open ' . $cmd);

        }

        ApplicationAbstract::terminate();
    }

}
