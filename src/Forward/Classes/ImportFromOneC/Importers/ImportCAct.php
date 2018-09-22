<?php

namespace Forward\Classes\ImportFromOneC\Importers;


use Forward\Classes\Constant;
use Forward\Module\CInvoice\CInvoiceObj;
use Light\Db;
use Light\Field\FieldDate;
use Light\File;
use Light\FilesFolder;
use Light\Link;

class ImportCAct extends ImporterAbstract
{
    public function import(array $data): int
    {
        if (!$this->validateData($data)) {
            return 0;
        }

        $docDate = FieldDate::parse($data['DocDate']);
        $companyId = (int)$data['CompanyID'];

        // Попробуем найти счёт в базе по ID
        if ($data['CInvoiceID']) {

            $cinvoiceId = Db::getOne("SELECT ID FROM cinvoice WHERE ID = @i", $data['CInvoiceID']);
            if (!$cinvoiceId) {
                $this->msgBuff->addError('Не удалось найти счёт с ID = ' . htmlspecialchars($data['CInvoiceID']));
            }

        } else {

            $cinvoiceNumber = $data['CInvoiceNumber'];
            $cinvoiceDocDate = $data['CInvoiceDocDate'];

            $cinvoiceId = Db::getOne("SELECT ID FROM cinvoice WHERE Name = @s AND DocDate = @s AND CompanyID = @i",
                $cinvoiceNumber, $cinvoiceDocDate, $companyId);
            if (!$cinvoiceId) {
                $this->msgBuff->addError("Не удалось найти счёт №" . htmlspecialchars($cinvoiceNumber) . " от " . FieldDate::beautify($cinvoiceDocDate) . " для компании с ID = $companyId");
            }

        }

        if (!$cinvoiceId) {
            return 0;
        }

        // Обновим информацию об акте в самой записе о счёте в базе
        $dbLogMsg = "Импорт из 1С " . $this->getImportLink();
        $cinvoiceObj = new CInvoiceObj();
        $cinvoiceObj->setId($cinvoiceId);
        if (!$cinvoiceObj->loadData()) {
            $this->msgBuff->addError('Не удалось загрузить из базы счёт ID = ' . $cinvoiceId);
            return 0;
        }

        $actName = $data['DocNumber'];

        $cinvoiceLink = $cinvoiceObj->getLinkView();
        $cinvoiceFolder = "CInvoice/" . floor($cinvoiceId / 1000) . "/$cinvoiceId";

        $pdfDataBase64 = $data['Data'];
        $pdfData = base64_decode($pdfDataBase64);
        $pdfFilename = File::getValidFilename("Акт без подписи $actName от $docDate.pdf");
        $this->writeFile($cinvoiceFolder, $pdfFilename, $pdfData, $cinvoiceLink);

        $excelDataBase64 = $data['DataExcel'];
        $excelData = base64_decode($excelDataBase64);
        $excelFilename = File::getValidFilename("Акт без подписи $actName от $docDate.xlsx");
        $this->writeFile($cinvoiceFolder, $excelFilename, $excelData, $cinvoiceLink);


        $affectedRows = $cinvoiceObj->updateDb([
            'CActName' => $data['DocNumber'],
            'CActDate' => $docDate,
            'CActAmount' => $data['Amount']
        ], $dbLogMsg);
        if ($affectedRows) {
            $this->msgBuff->addSuccess("Информация об акте записа в карточку счёта $cinvoiceLink");
        }

        $standardFolderAct = Constant::$standardFolders[3];

        // Попробуем определить все перевозки к которым относится данный счёт
        $shipmentIds = Db::getAll("SELECT s.ID, s.Name FROM cinvoice_file_amount cfa, shipment s WHERE cfa.CInvoiceID = $cinvoiceId AND cfa.FileNumber = s.Name");
        foreach ($shipmentIds as ['ID' => $shipmentId, 'Name' => $fileNumber]) {

            $shipmentLink = Link::create('Shipment', $fileNumber)
                ->view($shipmentId)
                ->getHtml();

            // Перекладываем файл в папку для акта 3
            $sub = floor($shipmentId / 1000);
            $shipmentFolder3 = "Shipment/$sub/$shipmentId/$standardFolderAct";
            $this->writeFile($shipmentFolder3, $pdfFilename, $pdfData, $shipmentLink);
            $this->writeFile($shipmentFolder3, $excelFilename, $excelData, $shipmentLink);

        }

        $cinvoiceObj->writeDbLogText($this->msgBuff->getHtml(), '1С', '1С');

        return $cinvoiceObj->getId();
    }

    private function writeFile(
        string $folder,
        string $filename,
        string $fileData,
        string $cinvocieOrShipmentLink
    ) {

        FilesFolder::mkdir($folder);
        $folderpathAbs = FilesFolder::getAbsolutePath($folder);
        $filepathAbs = "$folderpathAbs/$filename";
        $filepathAbsEnc = File::encode($filepathAbs);
        $result = file_put_contents($filepathAbsEnc, $fileData);
        if ($result) {
            $this->msgBuff->addSuccess('Файл <strong>' . htmlspecialchars($filename) . '</strong> сохранён в папку  ' . $cinvocieOrShipmentLink);
        } else {
            // Файл сущетсвует, значит мы не смогли его перезаписать из-за блокировки (он открыт кем-то)
            $uniqueFilepath = File::getUniqueFilename($filepathAbs);
            $uniqueFilename = pathinfo($uniqueFilepath, PATHINFO_BASENAME);
            $result = file_put_contents($filepathAbsEnc, $fileData);
            if ($result) {
                $this->msgBuff->addSuccess('Файл <strong>' . htmlspecialchars($uniqueFilename) . '</strong> сохранён в папку ' . $cinvocieOrShipmentLink);
            } else {
                $this->msgBuff->addError('Не удалось сохранить файл <strong>' . htmlspecialchars($uniqueFilename) . '</strong> в папку ' . $cinvocieOrShipmentLink);
            }
        }
    }
}
