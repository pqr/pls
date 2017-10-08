<?php

namespace tests\unit;

use Forward\Autolaunch\ImportCActFrom1C;
use Forward\Autolaunch\ImportCInvoicesFrom1C;
use Forward\Autolaunch\ImportCPaymentsFrom1C;
use Forward\Classes\Constant;
use Forward\Classes\Finance;
use Forward\ForwardConfig;
use Light\Db;
use Light\Field\FieldDate;
use Light\File;
use Light\FilesFolder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\SplFileInfo;

class ImportFrom1CTest extends TestCase
{
    private static $originalOnecFtpFolder;

    public static function setUpBeforeClass()
    {
        self::$originalOnecFtpFolder = ForwardConfig::$onecFtpFolderBase;
        ForwardConfig::$onecFtpFolderBase = __DIR__ . '/../_data/ftp';
    }

    public static function tearDownAfterClass()
    {
        ForwardConfig::$onecFtpFolderBase = self::$originalOnecFtpFolder;
    }

    private function getFtpFoldersAbs(string $moduleFolder): array
    {
        $foldrsAbs = [];
        foreach (Constant::$onecCompany as $path) {

            $foldrsAbs[] = ForwardConfig::$onecFtpFolderBase
                . "/$moduleFolder/$path";
        }

        return $foldrsAbs;
    }

    private function loadJson(string $jsonFilepathAbs)
    {
        return json_decode(str_replace("\xEF\xBB\xBF", '', file_get_contents($jsonFilepathAbs)), true);
    }

    private function loadJsonFiles(string $moduleFolder)
    {
        $jsonFiles = [];

        foreach ($this->getFtpFoldersAbs($moduleFolder) as $folderAbs) {

            foreach (scandir($folderAbs) as $jsonFile) {
                if ($jsonFile[0] === '.' || pathinfo($jsonFile, PATHINFO_EXTENSION) !== 'json') {
                    continue;
                }
                $jsonFilepathAbs = $folderAbs . '/' . $jsonFile;
                $jsonFiles[$jsonFilepathAbs] = $this->loadJson($jsonFilepathAbs);
            }
        }

        return $jsonFiles;
    }

    private function getCInvoicePdfFileRelative(array $json)
    {
        return 'CInvoice/' . $json['ID'] . '/' . $json['DocNumber'] . '.pdf';
    }

    private function getCInvoiceExcelFileRelative(array $json)
    {
        return 'CInvoice/' . $json['ID'] . '/' . $json['DocNumber'] . '.xlsx';
    }

    private function fakeCInvoices(array $jsonFiles)
    {
        foreach ($jsonFiles as $json) {
            // Предварительно изменим у счёта несколько полей, чтобы проверить, как отработает алгоритм импорта
            $fakeData = [
                'Comments' => '',
                'DocDate' => '',
                'DueDate' => '',
                'Amount' => 0,
                'CurrencyISO' => '',
                'Name' => ''
            ];
            if ($json['IsDeleted']) {
                // Если в json был флаг на удаление, то нужно изменить статус счёта, чтобы он стал open
                $fakeData['Status'] = Finance::INVOICESTATUS_OPEN;
            }
            Db::updateRow($fakeData, 'cinvoice', 'ID', $json['ID']);

            // Удалим все строки счёта, чтобы проверим, как при импорте строки загрузятся обратно
            Db::query("DELETE FROM cinvoiceline WHERE CInvoiceID = @i", $json['ID']);

            // Удалим из папки счёта файлы, которые там могли быть
            $pdfFileRelative = $this->getCInvoicePdfFileRelative($json);
            if (FilesFolder::isFile($pdfFileRelative)) {
                unlink(FilesFolder::getAbsolutePath($pdfFileRelative));
            }
            $excelFileRelative = $this->getCInvoiceExcelFileRelative($json);
            if (FilesFolder::isFile($excelFileRelative)) {
                unlink(FilesFolder::getAbsolutePath($excelFileRelative));
            }
        }
    }

    private function checkNewCInvoice(array $json)
    {
        // В результате запись о счёте должна быть обновлена, проверим результат
        $newCInvData = Db::getRow("SELECT * FROM cinvoice WHERE ID = @i", $json['ID']);

        $this->assertArrayHasKey('Name', $newCInvData, $json['ID']);

        $this->assertEquals($json['DocNumber'], $newCInvData['Name'], $json['ID']);
        $this->assertEquals($json['Comments'], $newCInvData['Comments'], $json['ID']);
        $this->assertEquals(FieldDate::parse($json['DocDate'], $json['ID']), $newCInvData['DocDate'], $json['ID']);
        $this->assertEquals(FieldDate::parse($json['DueDate'], $json['ID']), $newCInvData['DueDate'], $json['ID']);
        $this->assertEquals($json['Amount'], (float)$newCInvData['Amount'], $json['ID']);
        $this->assertEquals(substr($json['CurrencyISO'], 0, 3), $newCInvData['CurrencyISO'],$json['ID']);
        $this->assertEquals(substr($json['CurrencyISO'], 4), $newCInvData['RatePercent'],$json['ID']);

        if ($json['IsDeleted']) {
            // Если в json был флаг IsDeleted, то нужно проверить статус счёта, что он стал Deleted
            $this->assertEquals(Finance::INVOICESTATUS_DELETED, $newCInvData['Status'],$json['ID']);
        }

        // Проверим приавильно ли загрузилась строка с услугой
        $newLines = Db::getAll("SELECT * FROM cinvoiceline WHERE CInvoiceID = @i", $json['ID']);
        $this->assertEquals(count($json['Lines']), count($newLines), $json['ID']);

        $jsonLine = $json['Lines'][0];
        $newLine = $newLines[0];
        $this->assertEquals($jsonLine['Name'], $newLine['Name'], $json['ID']);
        $this->assertEquals((int)$jsonLine['Quantity'], (int)$newLine['Quantity'], $json['ID']);
        $this->assertEquals($jsonLine['Price'], (float)$newLine['Price'], $json['ID']);
        $this->assertEquals($jsonLine['Amount'], (float)$newLine['Amount'], $json['ID']);

        // Проверим, что в папке счёта образовался PDF файл и Excel файл
        $this->assertTrue(FilesFolder::isFile($this->getCInvoicePdfFileRelative($json), $json['ID']));
        $this->assertTrue(FilesFolder::isFile($this->getCInvoiceExcelFileRelative($json)), $json['ID']);
    }

    private function restoreFromArchive(string $moduleFolder)
    {
        foreach ($this->getFtpFoldersAbs($moduleFolder) as $folderAbs) {

            $archive = $folderAbs . '/archive';
            if (is_dir($archive)) {
                /** @var SplFileInfo $file */
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($archive)) as $file) {
                    if ($file->getFilename()[0] !== '.') {
                        rename($file->getPathname(), $folderAbs . '/' . pathinfo($file, PATHINFO_BASENAME));
                    }
                }

                // Удаляем папку архива
                File::rmdir($archive);
            }
        }
    }

    public function testImportCInvoice()
    {
        // Для начала восстановим все файлы оставшиеся в папках archive с предыдущего запуска тестов (если он был не удачным)
        $this->restoreFromArchive('cinvoice');

        // Теперь прочитаем все файлы
        $jsonFiles = $this->loadJsonFiles('cinvoice');

        // Испортим данные по уже существующим в базе счетам
        $this->fakeCInvoices($jsonFiles);

        // Теперь запускаем загрузку
        $autolaunch = new ImportCInvoicesFrom1C();
        $autolaunch->proceedRemoteRun([]);

        foreach ($jsonFiles as $json) {
            $this->checkNewCInvoice($json);
        }

        // Вернём все файлы из архива
        $this->restoreFromArchive('cinvoice');

        // Прочитаем исходные файлы ещё раз
        $restoredJsonFiles = $this->loadJsonFiles('cinvoice');

        // Удостоверимся, что все файлы вернулись
        $this->assertEquals($jsonFiles, $restoredJsonFiles);

    }

    private function deleteCPayments(array $jsonFiles)
    {
        foreach ($jsonFiles as $json) {
            // Удалим ранее загруженные оплаты
            $cpaymentId = Db::getOne("SELECT ID FROM cpayment WHERE OneCGUID = @s", $json['GUID']);
            if ($cpaymentId) {
                Db::query("DELETE FROM cpayment WHERE ID = @i", $cpaymentId);
                Db::query("DELETE FROM cpayment_cinvoice WHERE CPaymentID = @i", $cpaymentId);
            }
        }
    }

    private function checkNewCPayment(array $json)
    {
        $newCPaymentData = Db::getRow("SELECT * FROM cpayment WHERE OneCGUID = @s", $json['GUID']);

        $this->assertEquals($json['Name'] . ' (from 1С)', $newCPaymentData['Name']);
        $this->assertEquals($json['Comments'], $newCPaymentData['Comments']);
        $this->assertEquals(FieldDate::parse($json['PaymentDate']) ?: date('Y-m-d'), $newCPaymentData['PaymentDate']);
        $this->assertEquals($json['Amount'], (float)$newCPaymentData['Amount']);
        $this->assertEquals($json['CurrencyISO'], $newCPaymentData['CurrencyISO']);

        // Проверим правильно ли загрузилась привязка счетов
        $newLines = Db::getAll("SELECT * FROM cpayment_cinvoice WHERE CPaymentID = @i", $newCPaymentData['ID']);
        $this->assertEquals(count($json['Lines']), count($newLines));

        foreach ($json['Lines'] as $index => $jsonLine) {
            $newLine = $newLines[$index];
            $this->assertEquals($jsonLine['CInvoiceName'], $newLine['CInvoiceName'], $json['GUID']);
            $this->assertEquals((float)$jsonLine['AmountInCurrency'],
                (float)$newLine['ClearingAmountInInvoiceCurrency'], $json['GUID']);
            $this->assertEquals((float)$jsonLine['CurrencyRate'], (float)$newLine['RateToInvoiceCurrency'], $json['GUID']);
            $this->assertEquals($jsonLine['Amount'], $newLine['ClearingAmountInPaymentCurrency'], $json['GUID']);
        }
    }

    public function testImportCPayment()
    {
        // Для начала восстановим все файлы оставшиеся в папках archive с предыдущего запуска тестов (если он был не удачным)
        $this->restoreFromArchive('cpayment');

        // Прочитаем все файлы
        $jsonFiles = $this->loadJsonFiles('cpayment');

        // Удалим оплаты, которые уже есть в базе
        $this->deleteCPayments($jsonFiles);

        // Теперь запускаем загрузку
        $autolaunch = new ImportCPaymentsFrom1C();
        $autolaunch->proceedRemoteRun([]);

        foreach ($jsonFiles as $jsonFile => $json) {
            $this->checkNewCPayment($json);
        }

        // Вернём все файлы из архива
        $this->restoreFromArchive('cpayment');

        // Прочитаем исходные файлы ещё раз
        $restoredJsonFiles = $this->loadJsonFiles('cpayment');

        // Удостоверимся, что все файлы вернулись
        $this->assertEquals($jsonFiles, $restoredJsonFiles);
    }

    public function testImportCAct()
    {
        // Для начала восстановим все файлы оставшиеся в папках archive с предыдущего запуска тестов (если он был не удачным)
        $this->restoreFromArchive('cact');

        // Прочитаем все файлы
        $jsonFiles = $this->loadJsonFiles('cact');

        $autolaunch = new ImportCActFrom1C();
        $autolaunch->proceedRemoteRun([]);

        foreach ($jsonFiles as $jsonFile => $json) {
            $this->checkCAct($json);
        }

        // Вернём все файлы из архива
        $this->restoreFromArchive('cact');

        // Прочитаем исходные файлы ещё раз
        $restoredJsonFiles = $this->loadJsonFiles('cact');

        // Удостоверимся, что все файлы вернулись
        $this->assertEquals($jsonFiles, $restoredJsonFiles);
    }

    private function checkCAct(array $json)
    {
        $docDate = FieldDate::parse($json['DocDate']);
        $actFilename = File::getValidFilename("Акт без подписи " . $json['DocNumber'] . " от " . $docDate . ".pdf");
        $filepathAbs = FilesFolder::getAbsolutePath('CInvoice/' . $json['CInvoiceID'] . '/' . $actFilename);
        $this->assertFileExists($filepathAbs);

        $cinvoiceInfo = Db::getRow("SELECT CActName, CActDate, CActAmount FROM cinvoice WHERE ID = @i", $json['CInvoiceID']);

        $this->assertEquals($json['DocNumber'], $cinvoiceInfo['CActName']);
        $this->assertEquals($docDate, $cinvoiceInfo['CActDate']);
        $this->assertEquals($json['Amount'], (float)$cinvoiceInfo['CActAmount']);

        // Найдём все перевозки по данному счёту и проверим как скопировались файлы внутри них
        $standardFolderAct = Constant::$standardFolders[3];

        $shipmentIds = Db::getAll("SELECT s.ID, s.Name FROM cinvoice_file_amount cfa, shipment s WHERE cfa.CInvoiceID = ".(int)$json['CInvoiceID']." AND cfa.FileNumber = s.Name");
        foreach ($shipmentIds as ['ID' => $shipmentId, 'Name' => $fileNumber]) {
            // Перекладываем файл в папку для акта 3
            $sub = floor($shipmentId / 1000);
            $folder = "Shipment/$sub/$shipmentId/$standardFolderAct/$actFilename";
            $filepathAbs = FilesFolder::getAbsolutePath($folder);
            $this->assertFileExists($filepathAbs);
        }
    }
}
