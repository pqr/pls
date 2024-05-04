<?php

namespace Forward\Classes\ImportFromOneC\Importers;


use Forward\Classes\Constant;
use Forward\Classes\Finance;
use Forward\Module\CInvoice\CInvoice;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\CInvoice\Subtables\Lines;
use Forward\Module\CInvoice\Subtables\LinesObj;
use Light\Db;
use Light\File;
use Light\FilesFolder;

class ImportCInvoice extends ImporterAbstract
{
    #[\Override]
    public function import(array $data): int
    {
        if (!$this->validateData($data)) {
            return 0;
        }

        // Попробуем найти искомый счёт в базе
        $cinvId = $data['ID'];
        $cinvObj = new CInvoiceObj();
        $cinvObj->setId($cinvId);
        if (!$cinvObj->loadData()) {
            $this->msgBuff->addError('Не удалось найти в базе счёт с ID <strong>' . htmlspecialchars((string) $data['ID']) . '</strong>');
            return 0;
        }

        // Для запуска updateCacheAfterSubmit() нужно предварительно сохранить старые данные по счёту
        $cinvOldData = $cinvObj->getData();

        $currencyParts = explode('+', (string) $data['CurrencyISO']);

        $updateData = [
            'Name' => $data['DocNumber'],
            'CustomerAccountID' => $data['CustomerAccountID'],
            'Comments' => $data['Comments'],
            'DocDate' => $data['DocDate'],
            'DueDate' => $data['DueDate'],
            'Amount' => $data['Amount'],
            'CurrencyISO' => $currencyParts[0],
            'RatePercent' => $currencyParts[1] ?? '',
        ];

        if ($data['IsDeleted']) {
            $updateData['Status'] = Finance::INVOICESTATUS_DELETED;
        } else {
            if ($cinvObj['Status'] == Finance::INVOICESTATUS_DELETED) {
                $updateData['Status'] = Finance::INVOICESTATUS_OPEN;
            }
        }

        $dbLogMsg = 'Корректировка из 1С ' . $this->getImportLink();

        Db::begin();
        $cinvObj->updateDb($updateData, $dbLogMsg);

        // Обновим строки счёта
        $lineObjBase = new LinesObj();
        $lineObjBase->setMasterIdForDbLog($cinvObj->getId());

        // Для начала загрузим ID текущих строк
        $existLineIds = Db::getCol("SELECT ID FROM cinvoiceline WHERE CInvoiceID = @i ORDER BY ID", $cinvObj->getId());

        // Переберём строки из JSON файла и обновим текущие строки
        if (isset($data['Lines']) && is_array($data['Lines'])) {
            foreach ($data['Lines'] as $indexFromJson => $lineFromJson) {

                // Попробуем сделать перевод услуги обратно на английский
                $en = Db::getOne("SELECT Name FROM list_chargedescription WHERE NameRu = @s LIMIT 1",
                    $lineFromJson['Name']);

                $lineUpdateData = [
                    'Name' => $en ?: $lineFromJson['Name'],
                    'Quantity' => $lineFromJson['Quantity'],
                    'Price' => $lineFromJson['Price'],
                    'Amount' => $lineFromJson['Amount']
                ];

                $lineObj = clone $lineObjBase;

                if (isset($existLineIds[$indexFromJson])) {

                    // Строка под таким порядковым номером есть в базе - обновляем
                    $lineObj->setId($existLineIds[$indexFromJson]);
                    $lineObj->updateDb($lineUpdateData, $dbLogMsg);

                } else {

                    // Строки под таким порядковым номером нет в базе - создаём новую
                    $lineUpdateData['CInvoiceID'] = $cinvObj->getId();
                    // Нужно привязать строку к какому-то номеру файла. Загрузим первый попавшийся номер файла из
                    // уже имеющихся строк
                    if (!isset($fileNumber)) {
                        $fileNumber = (int)Db::getOne(
                            "SELECT FileNumber 
                                 FROM cinvoiceline 
                                 WHERE FileNumber != 0 
                                    AND CInvoiceID = @i 
                                 LIMIT 1",
                            $cinvObj->getId()
                        );
                    }
                    $lineUpdateData['FileNumber'] = $fileNumber;
                    // При вставке новых строк нужно сделать привязку к файлу (к mawb)
                    $lineObj->insertDb($lineUpdateData, $dbLogMsg);

                }
            }
        }

        // После того как вставили или обновили новые строки пришедшие в json файле, удалим лишние старые строки из базы
        $dataLinesCount = isset($data['Lines']) ? count($data['Lines']) : 0;
        $existLinesCount = count($existLineIds);
        if ($existLinesCount > $dataLinesCount) {
            for ($i = $dataLinesCount; $i < $existLinesCount; $i++) {
                // Удаляем лишнюю строку из базы
                $lineObj = clone $lineObjBase;
                $lineObj->setId($existLineIds[$i]);
                $lineObj->loadData();
                $lineObj->deleteDb($dbLogMsg);
            }
        }

        $linesSubtable = new Lines($cinvObj);
        $linesSubtable->updateCache();

        // Если в процессе импорта JSON файла счёт поменял свой статус на OPEN, это значит раньше была пометка на удаление
        // а потом её сняли, поэтому мы перевели счёт из статуса Deleted в Open. Однако, возможно этот счёт нужно
        // сделать Cleared или Part Cleared
        if (isset($updateData['Status']) && $updateData['Status'] == Finance::INVOICESTATUS_OPEN) {
            //Автоматически переведём счёт в статус Part Cleared, Cleared или Open Accepted на основе сумм оплат
            Finance::autoClearInvoice('C', $cinvObj->getId());
        }

        $cinvObj->updateCacheAfterSubmit($cinvOldData, $this->msgBuff, true, $dbLogMsg);
        Db::commit();

        $this->saveFiles($cinvObj, $data);

        $cinvObj->writeDbLogText($this->msgBuff->getHtml(), '1С', '1С');

        return $cinvObj->getId();
    }

    #[\Override]
    protected function validateData(array $data)
    {
        $result = parent::validateData($data);

        if (!isset($data['ID'])) {
            $this->msgBuff->addError('Отсутствует обязательное поле ID');
            $result = false;
        } else {
            if (!is_numeric($data['ID'])) {
                $this->msgBuff->addError('Поле ID должно быть числом, получено "' . htmlspecialchars((string) $data['Amount']) . '"');
                $result = false;
            }
        }

        return $result;
    }

    private function saveFiles(CInvoiceObj $cinvObj, array $data): void
    {
        $cinvId = $cinvObj->getId();
        $cinvLink = $cinvObj->getLinkView();

        // Запишем файл PDF файл и Excel файл в папку
        $cinvFolderRelative = FilesFolder::getRelativeModule(CInvoice::class, $cinvId);

        $pdfDataBase64 = $data['Data'];
        $pdfData = base64_decode((string) $pdfDataBase64);
        $pdfFilename = File::getValidFilename($cinvObj['Name'] . '.pdf');
        $this->writeFile($cinvFolderRelative, $pdfFilename, $pdfData, $cinvLink);

        $excelDataBase64 = $data['ExcelData'];
        $excelData = base64_decode((string) $excelDataBase64);
        $excelFilename = File::getValidFilename($cinvObj['Name']) . '.xlsx';
        $this->writeFile($cinvFolderRelative, $excelFilename, $excelData, $cinvLink);

        // Попробуем определить все перевозки к которым относится данный счёт и скопировать файлы в папку "2 Счёт"
        // По просьбе Валерии копируем только Excel
        $shipments = Db::getAll("SELECT s.ID, s.Name FROM cinvoice_file_amount cfa, shipment s WHERE cfa.CInvoiceID = $cinvId AND cfa.FileNumber = s.Name");
        /*
        $this->writeFileToShipments($pdfFilename, $pdfData, Constant::$standardFolders[Constant::FOLDER_CINVOICE],
            $shipments);
        */
        $this->writeFileToShipments($excelFilename, $excelData, Constant::$standardFolders[Constant::FOLDER_CINVOICE],
            $shipments);
    }


}
