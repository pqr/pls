<?php

namespace Forward\Autolaunch;

use Forward\Autolaunch\ImportFrom1C\ImportFrom1CAbstract;
use Forward\Classes\Finance;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\CInvoice\Subtables\Lines;
use Forward\Module\CInvoice\Subtables\LinesObj;
use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\Link;

class ImportCInvoicesFrom1C extends ImportFrom1CAbstract
{
    protected static $_title = 'Импортировать коррекции счетов из 1С (через FTP сервер)';
    protected static $_description = 'Проверяет наличие файлов с информацией о корректе счетов из 1С на FTP сервере и загружает эти корректировки в Customer Invoices';

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'cinvoice';
    }

    protected function importFromJson(array $json, int $companyId, string $filenameForLog)
    {
        Db::begin();
        // Попробуем найти искомый счёт в базе
        $cinvObj = new CInvoiceObj();
        $cinvObj->setId($json['ID']);
        if (!$cinvObj->loadData()) {
            $this->_msgBuff->addError('Не удалось найти в базе счёт с ID <strong>' . htmlspecialchars($json['ID']) . '</strong>');
            Db::commit();
            return self::RESULT_NOTFOUND;
        }

        // Для запуска updateCacheAfterSubmit() нужно предварительно сохранить старые данные по счёту
        $cinvOldData = $cinvObj->getData();

        $currencyParts = explode('+', $json['CurrencyISO']);

        $updateData = [
            'Name' => $json['DocNumber'],
            'CustomerAccountID' => $json['CustomerAccountID'],
            'Comments' => $json['Comments'],
            'DocDate' => $json['DocDate'],
            'DueDate' => $json['DueDate'],
            'Amount' => $json['Amount'],
            'CurrencyISO' => $currencyParts[0],
            'RatePercent' => $currencyParts[1] ?? '',
        ];

        if ($json['IsDeleted']) {
            $updateData['Status'] = Finance::INVOICESTATUS_DELETED;
        } else {
            if ($cinvObj['Status'] == Finance::INVOICESTATUS_DELETED) {
                $updateData['Status'] = Finance::INVOICESTATUS_OPEN;
            }
        }

        $dbLogMsg = 'Корректировка из 1С <small>' . htmlspecialchars($filenameForLog) . '</small>';

        $affectedRows = $cinvObj->updateDb($updateData, $dbLogMsg);

        $link = Link::moduleViewBlank($cinvObj->getDescriptionHtml(), $cinvObj->getId(), null, 'CInvoice');

        // Запишем файл PDF файл и Excel файл в папку
        $folderpath = 'CInvoice/' . (floor($cinvObj->getId() / 1000)) . '/' . $cinvObj->getId();
        FilesFolder::mkdir($folderpath);
        $folderpathAbs = FilesFolder::getAbsolutePath($folderpath);

        $pdfFilename = File::getValidFilename($cinvObj['Name'] . '.pdf');
        $pdfFilepathAbsEnc = File::encode($folderpathAbs . '/' . $pdfFilename);
        $pdfDataBase64 = $json['Data'];
        $pdfData = base64_decode($pdfDataBase64);
        if (!file_put_contents($pdfFilepathAbsEnc, $pdfData)) {
            $this->_msgBuff->addError($link . ': не удалось сохранить файл <strong>' . htmlspecialchars($pdfFilepathAbsEnc) . '</strong>');
        }

        $excelFilename = File::getValidFilename($cinvObj['Name']) . '.xlsx';
        $excelFilepathAbsEnc = File::encode($folderpathAbs . '/' . $excelFilename);
        $excelDataBase64 = $json['ExcelData'];
        $excelData = base64_decode($excelDataBase64);
        if (!file_put_contents($excelFilepathAbsEnc, $excelData)) {
            $this->_msgBuff->addError($link . ': не удалось сохранить файл <strong>' . htmlspecialchars($excelFilepathAbsEnc) . '</strong>');
        }

        // Обновим строки счёта
        $lineObjBase = new LinesObj();
        $lineObjBase->setMasterIdForDbLog($cinvObj->getId());

        // Для начала загрузим ID текущих строк
        $existLineIds = Db::getCol("SELECT ID FROM cinvoiceline WHERE CInvoiceID = @i ORDER BY ID", $cinvObj->getId());

        // Переберём строки из JSON файла и обновим текущие строки
        if (isset($json['Lines']) && is_array($json['Lines'])) {
            foreach ($json['Lines'] as $indexFromJson => $lineFromJson) {

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
                    $affectedRows += $lineObj->updateDb($lineUpdateData, $dbLogMsg);

                } else {

                    // Строки под таким порядковым номером нет в базе - создаём новую
                    $lineUpdateData['CInvoiceID'] = $cinvObj->getId();
                    // Нужно привязать строку к какому-то номеру файла. Загрузим первый попавшийся номер файла из
                    // уже имеющихся строк
                    if (!isset($fileNumber)) {
                        $fileNumber = Db::getOne(
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
                    $affectedRows++;

                }
            }
        }

        // После того как вставили или обновили новые строки пришедшие в json файле, удалим лишние старые строки из базы
        $jsonLinesCount = isset($json['Lines']) ? count($json['Lines']) : 0;
        $existLinesCount = count($existLineIds);
        if ($existLinesCount > $jsonLinesCount) {
            for ($i = $jsonLinesCount; $i < $existLinesCount; $i++) {
                // Удаляем лишнюю строку из базы
                $lineObj = clone $lineObjBase;
                $lineObj->setId($existLineIds[$i]);
                $lineObj->loadData();
                $lineObj->deleteDb($dbLogMsg);
                $affectedRows++;
            }
        }

        $linesSubtable = new Lines($cinvObj);
        $linesSubtable->updateCache();

        // Если в процессе импорта JSON файла счёт поменял свой статус на OPEN, это значит раньше была пометка на удаление
        // а потом её сняли, поэтому мы перевели счёт из статуса Deleted в Open. Однако, возможно этот счёт нужно
        // сделать Cleared или Part Cleared
        if (isset($updateData['Status']) && $updateData['Status'] == Finance::INVOICESTATUS_OPEN) {
            //Автоматически переведём счёт в статус Part Cleared, Cleared или Open Accepted на основе сумм оплат
            Finance::autoClearInvoice($cinvObj->getId(), 'cinvoice');
        }

        $cinvObj->updateCacheAfterSubmit($cinvOldData, $this->_msgBuff, true, $dbLogMsg);
        Db::commit();

        $result = $affectedRows ? self::RESULT_UPDATED : self::RESULT_NOTCHANGED;

        switch ($result) {
            case self::RESULT_UPDATED:
                $this->_msgBuff->addSuccess($link . ': обновлён');
                break;

            case self::RESULT_NOTCHANGED:
                $this->_msgBuff->addSkipp($link . ': не изменился');
                break;
        }

        return $result;

    }

}
