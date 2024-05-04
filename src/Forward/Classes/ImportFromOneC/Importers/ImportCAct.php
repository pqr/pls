<?php

namespace Forward\Classes\ImportFromOneC\Importers;


use Forward\Classes\Constant;
use Forward\Module\CInvoice\CInvoice;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\Shipment\Shipment;
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

            $cinvId = Db::getOne("SELECT ID FROM cinvoice WHERE ID = @i", $data['CInvoiceID']);
            if (!$cinvId) {
                $this->msgBuff->addError('Не удалось найти счёт с ID = ' . htmlspecialchars($data['CInvoiceID']));
            }

        } else {

            $cinvoiceNumber = $data['CInvoiceNumber'];
            $cinvoiceDocDate = $data['CInvoiceDocDate'];

            $cinvId = Db::getOne("SELECT ID FROM cinvoice WHERE Name = @s AND DocDate = @s AND CompanyID = @i",
                $cinvoiceNumber, $cinvoiceDocDate, $companyId);
            if (!$cinvId) {
                $this->msgBuff->addError("Не удалось найти счёт №" . htmlspecialchars($cinvoiceNumber) . " от " . FieldDate::beautify($cinvoiceDocDate) . " для компании с ID = $companyId");
            }

        }

        if (!$cinvId) {
            return 0;
        }

        // Обновим информацию об акте в самой записе о счёте в базе
        $dbLogMsg = "Импорт из 1С " . $this->getImportLink();
        $cinvObj = new CInvoiceObj();
        $cinvObj->setId($cinvId);
        if (!$cinvObj->loadData()) {
            $this->msgBuff->addError('Не удалось загрузить из базы счёт ID = ' . $cinvId);
            return 0;
        }

        $affectedRows = $cinvObj->updateDb([
            'CActName' => $data['DocNumber'],
            'CActDate' => $docDate,
            'CActAmount' => $data['Amount']
        ], $dbLogMsg);
        if ($affectedRows) {
            $this->msgBuff->addSuccess("Информация об акте записана в карточку счёта {$cinvObj->getLinkView()}");
        }

        $this->saveFiles($cinvObj, $data);

        $cinvObj->writeDbLogText($this->msgBuff->getHtml(), '1С', '1С');

        return $cinvObj->getId();
    }

    private function saveFiles(CInvoiceObj $cinvObj, array $data): void
    {
        $cinvId = $cinvObj->getId();
        $cinvLink = $cinvObj->getLinkView();

        $actName = $data['CActName'];
        $actDate = $cinvObj['CActDate'];

        $filename = $data['Filename'] ?? "Акт без подписи $actName от $actDate";

        $cinvFolderRelative = FilesFolder::getRelativeModule(CInvoice::class, $cinvId);

        $pdfDataBase64 = $data['Data'];
        $pdfData = base64_decode($pdfDataBase64);
        $pdfFilename = File::getValidFilename("$filename.pdf");
        $this->writeFile($cinvFolderRelative, $pdfFilename, $pdfData, $cinvLink);

        $excelDataBase64 = $data['DataExcel'];
        $excelData = base64_decode($excelDataBase64);
        $excelFilename = File::getValidFilename("$filename.xlsx");
        $this->writeFile($cinvFolderRelative, $excelFilename, $excelData, $cinvLink);

        // Попробуем определить все перевозки к которым относится данный счёт и скопировать файлы в папку "5 Акт без подписи"
        $shipments = Db::getAll("SELECT s.ID, s.Name FROM cinvoice_file_amount cfa, shipment s WHERE cfa.CInvoiceID = $cinvId AND cfa.FileNumber = s.Name");
        $this->writeFileToShipments($pdfFilename, $pdfData, Constant::$standardFolders[Constant::FOLDER_CACT], $shipments);
        $this->writeFileToShipments($excelFilename, $excelData, Constant::$standardFolders[Constant::FOLDER_CACT], $shipments);

    }

}
