<?php

namespace Forward\Autolaunch;

use Forward\Autolaunch\ImportFrom1C\ImportFrom1CAbstract;
use Forward\Classes\Constant;
use Forward\Module\CInvoice\CInvoiceObj;
use Light\Db;
use Light\Field\FieldDate;
use Light\File;
use Light\FilesFolder;
use Light\Link;
use Light\Msg;

class ImportCActFrom1C extends ImportFrom1CAbstract
{
    protected static $_title = 'Импортировать акты из 1С (через FTP сервер)';
    protected static $_description = 'Проверяет наличие файлов с информацией об актах из 1С на FTP сервере и загружает в папки счетов и в папки перевозок';

    public function __construct()
    {
        parent::__construct();

        $this->folder = 'cact';
    }

    protected function importFromJson(array $json, int $companyId, string $filenameForLog)
    {
        $docDate = FieldDate::parse($json['DocDate']);
        $companyId = (int)$json['CompanyID'];

        // Попробуем найти счёт в базе по ID
        if ($json['CInvoiceID']) {

            $cinvoiceId = Db::getOne("SELECT ID FROM cinvoice WHERE ID = @i", $json['CInvoiceID']);
            if (!$cinvoiceId) {
                $this->_msgBuff->addError('Не удалось найти счёт с ID = ' . htmlspecialchars($json['CInvoiceID']));
            }

        } else {

            $cinvoiceNumber = $json['CInvoiceNumber'];
            $cinvoiceDocDate = $json['CInvoiceDocDate'];

            $cinvoiceId = Db::getOne("SELECT ID FROM cinvoice WHERE Name = @s AND DocDate = @s AND CompanyID = @i",
                $cinvoiceNumber, $cinvoiceDocDate, $companyId);
            if (!$cinvoiceId) {
                $this->_msgBuff->addError("Не удалось найти счёт №" . htmlspecialchars($cinvoiceNumber) . " от " . FieldDate::beautify($cinvoiceDocDate) . " для компании с ID = $companyId");
            }

        }

        if (!$cinvoiceId) {
            return self::RESULT_NOTFOUND;
        }


        // Обновим информацию об акте в самой записе о счёте в базе
        $dbLogMsg = "Импорт из 1С <small>" . htmlspecialchars($filenameForLog) . "</small>";
        $cinvoiceObj = new CInvoiceObj();
        $cinvoiceObj->setId($cinvoiceId);
        if (!$cinvoiceObj->loadData()) {
            $this->_msgBuff->addError('Не удалось загрузить из базы счёт ID = ' . $cinvoiceId);
        }

        $msgBuff = new Msg();

        $actName = $json['DocNumber'];

        $cinvoiceLink = Link::moduleViewBlank((string)$cinvoiceObj, $cinvoiceId, null, 'CInvoice');
        $cinvoiceFolder = "CInvoice/" . floor($cinvoiceId / 1000) . "/$cinvoiceId";

        $pdfDataBase64 = $json['Data'];
        $pdfData = base64_decode($pdfDataBase64);
        $pdfFilename = File::getValidFilename("Акт без подписи $actName от $docDate.pdf");
        $this->writeFile($cinvoiceFolder, $pdfFilename, $pdfData, $cinvoiceLink, $msgBuff);

        $excelDataBase64 = $json['DataExcel'];
        $excelData = base64_decode($excelDataBase64);
        $excelFilename = File::getValidFilename("Акт без подписи $actName от $docDate.xlsx");
        $this->writeFile($cinvoiceFolder, $excelFilename, $excelData, $cinvoiceLink, $msgBuff);


        $affectedRows = $cinvoiceObj->updateDb([
            'CActName' => $json['DocNumber'],
            'CActDate' => $docDate,
            'CActAmount' => $json['Amount']
        ], $dbLogMsg);
        if ($affectedRows) {
            $msgBuff->addSuccess("Информация об акте записа в карточку счёта $cinvoiceLink");
        }

        $standardFolderAct = Constant::$standardFolders[3];

        // Попробуем определить все перевозки к которым относится данный счёт
        $shipmentIds = Db::getAll("SELECT s.ID, s.Name FROM cinvoice_file_amount cfa, shipment s WHERE cfa.CInvoiceID = $cinvoiceId AND cfa.FileNumber = s.Name");
        foreach ($shipmentIds as ['ID' => $shipmentId, 'Name' => $fileNumber]) {

            $shipmentLink = Link::moduleViewBlank($fileNumber, $shipmentId, null, 'Shipment');

            // Перекладываем файл в папку для акта 3
            $sub = floor($shipmentId / 1000);
            $shipmentFolder3 = "Shipment/$sub/$shipmentId/$standardFolderAct";
            $this->writeFile($shipmentFolder3, $pdfFilename, $pdfData, $shipmentLink, $msgBuff);
            $this->writeFile($shipmentFolder3, $excelFilename, $excelData, $shipmentLink, $msgBuff);

        }

        $cinvoiceObj->writeDbLogText($msgBuff->getHtml(), '1С', '1С');
        $this->_msgBuff->addMessagess($msgBuff->getAll());

        return $affectedRows ? self::RESULT_UPDATED : self::RESULT_NOTCHANGED;

    }

    private function writeFile(
        string $folder,
        string $filename,
        string $fileData,
        string $cinvocieOrShipmentLink,
        Msg $msgBuff
    ) {

        FilesFolder::mkdir($folder);
        $folderpathAbs = FilesFolder::getAbsolutePath($folder);
        $filepathAbs = "$folderpathAbs/$filename";
        $filepathAbsEnc = File::encode($filepathAbs);
        $result = file_put_contents($filepathAbsEnc, $fileData);
        if ($result) {
            $msgBuff->addSuccess('Файл <strong>' . htmlspecialchars($filename) . '</strong> сохранён в папку  ' . $cinvocieOrShipmentLink);
        } else {
            // Файл сущетсвует, значит мы не смогли его перезаписать из-за блокировки (он открыт кем-то)
            $uniqueFilepath = File::getUniqueFilename($filepathAbs);
            $uniqueFilename = pathinfo($uniqueFilepath, PATHINFO_FILENAME);
            $result = file_put_contents($filepathAbsEnc, $fileData);
            if ($result) {
                $msgBuff->addSuccess('Файл <strong>' . htmlspecialchars($uniqueFilename) . '</strong> сохранён в папку ' . $cinvocieOrShipmentLink);
            } else {
                $msgBuff->addError('Не удалось сохранить файл <strong>' . htmlspecialchars($uniqueFilename) . '</strong> в папку ' . $cinvocieOrShipmentLink);
            }
        }
    }

}
