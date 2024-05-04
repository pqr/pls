<?php

namespace Forward\Classes\ImportFromOneC\Importers;

use Forward\Classes\Constant;
use Forward\Module\Shipment\Shipment;
use Light\File;
use Light\FilesFolder;
use Light\Link;
use Light\Msg;

abstract class ImporterAbstract
{

    public const RESULT_INCORRECT = 'incorrect';
    public const RESULT_NOTFOUND = 'notfound';
    public const RESULT_INSERTED = 'inserted';
    public const RESULT_UPDATED = 'updated';
    public const RESULT_DELETED = 'deleted';
    public const RESULT_NOTCHANGED = 'notchanged';

    protected $importId;
    protected $msgBuff;

    public function __construct(int $importId, Msg $msgBuff)
    {
        $this->importId = $importId;
        $this->msgBuff = $msgBuff;
    }

    protected function validateData(array $data)
    {
        $result = true;
        if (!isset($data['type'])) {
            $this->msgBuff->addError('Отсуствует обязательное поле type');
            $result = false;
        } else {
            $validTypes = ['cact', 'cinvoice', 'cpayment'];
            if (!in_array($data['type'], $validTypes)) {
                $this->msgBuff->addError('Поле type имеет недопустимое значение "' . $data['type'] . '", возможные значения: ' . implode(', ',
                        $validTypes));
                $result = false;
            }
        }

        if (!isset($data['CompanyID'])) {
            $this->msgBuff->addError('Отсуствует обязательное поле CompanyID');
            $result = false;
        } else {
            if (!Constant::isOneCCompany($data['CompanyID'])) {
                $this->msgBuff->addError('Поле CompanyID имеет недопустимое значение "' . $data['CompanyID'] . '", возможные значения: ' . implode(', ',
                        array_keys(Constant::$onecCompany)));
                $result = false;
            }
        }

        if (!isset($data['Amount'])) {
            $this->msgBuff->addError('Отсуствует обязательное поле Amount');
            $result = false;
        } else {
            if (!is_numeric($data['Amount'])) {
                $this->msgBuff->addError('Поле Amount должно быть числом, получено "' . htmlspecialchars($data['Amount']) . '"');
                $result = false;
            }
        }

        return $result;
    }

    abstract public function import(array $data): int;

    protected function getImportLink()
    {
        return Link::create('ImportFromOneCLog', $this->importId)
            ->view($this->importId)
            ->blank()
            ->getHtml();
    }

    protected function writeFile(
        string $folderRelative,
        string $filename,
        string $fileData,
        string $cinvocieOrShipmentLink
    ) {

        FilesFolder::mkdir($folderRelative);
        $folderpathAbs = FilesFolder::getAbsolute($folderRelative);
        $filepathAbs = "$folderpathAbs/$filename";
        $result = file_put_contents($filepathAbs, $fileData);
        if ($result) {
            $this->msgBuff->addSuccess('Файл <strong>' . htmlspecialchars($filename) . '</strong> сохранён в папку  ' . $cinvocieOrShipmentLink);
        } else {
            // Файл сущетсвует, значит мы не смогли его перезаписать из-за блокировки (он открыт кем-то)
            $uniqueFilepath = File::getUniqueFilename($filepathAbs);
            $uniqueFilename = pathinfo($uniqueFilepath, PATHINFO_BASENAME);
            $result = file_put_contents($filepathAbs, $fileData);
            if ($result) {
                $this->msgBuff->addSuccess('Файл <strong>' . htmlspecialchars($uniqueFilename) . '</strong> сохранён в папку ' . $cinvocieOrShipmentLink);
            } else {
                $this->msgBuff->addError('Не удалось сохранить файл <strong>' . htmlspecialchars($uniqueFilename) . '</strong> в папку ' . $cinvocieOrShipmentLink);
            }
        }
    }

    protected function writeFileToShipments(
        string $filename,
        string $fileData,
        string $shipmentSubfolder,
        array $shipments
    ) {
        foreach ($shipments as ['ID' => $shipmentId, 'Name' => $fileNumber]) {
            $shipmentLink = Link::create(Shipment::class, $fileNumber)
                ->view($shipmentId)
                ->getHtml();

            // Перекладываем файл в папку внутри shipment
            $shipmentFolderRelative = FilesFolder::getRelativeModule(Shipment::class, $shipmentId);
            $targetRelative = "$shipmentFolderRelative/$shipmentSubfolder";
            $this->writeFile($targetRelative, $filename, $fileData, $shipmentLink);
        }
    }
}
