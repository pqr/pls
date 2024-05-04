<?php

namespace Light\Codeception;

use Light\Db;
use Light\File;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

trait TesterTrait
{
    public function canSeePageHead(string $text): void
    {
        $this->expect('Page head contains: ' . $text);
        $this->canSee($text, '#page-head');
    }

    public function cantSeeXdebugError(): void
    {
        $this->expect('No Xdebug errors');
        $this->cantSeeElement('.xdebug-error');
    }

    public function assertNotContainsXdebugError($html): void
    {
        $this->expect('No Xdebug errors');
        $this->assertNotContains('xdebug-error', $html);
    }

    public function cantSeeSqlError(): void
    {
        $this->expect('No SQL errors');
        $this->cantSee('SQL error:', '.alert-danger');
    }

    public function assertNotContainsSqlError($html): void
    {
        $this->expect('No SQL errors');
        $this->assertNotContains('SQL error:', $html);
    }

    public function canSeeAjaxDialog($url, $hasButtonOk = true): void
    {
        $this->expect('See Ajax Dialog');
        $this->sendAjaxGetRequest($url);
        $this->canSeeHttpHeader('x-light-showdialog');

        $jsonSchema = ['html' => 'string'];
        if ($hasButtonOk) {
            $jsonSchema['dialogOptions'] = [
                'buttonOkText' => 'string'
            ];
        }

        $this->seeResponseMatchesJsonType($jsonSchema);
    }

    public function assertDownloadingExcel($url, $method = 'GET', $postData = [])
    {
        if ($method === 'POST') {
            $this->sendPOST($url, $postData);
        } else {
            $this->sendGET($url);
        }
        $this->canSeeHttpHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $filepath = LIGHT_DIR_ROOT . '/tests/_output/' . File::getValidFilename($url) . '.xlsx';
        file_put_contents($filepath, $this->grabResponse());

        // Проверим, что внутри Excel нет ни одного плейсхолдера %fieldKey%
        $spreadsheet = IOFactory::load($filepath);
        $this->assertInstanceOf(Spreadsheet::class, $spreadsheet);

        $count = $spreadsheet->getSheetCount();
        for ($sheetIndex = 0; $sheetIndex < $count; $sheetIndex++) {
            $sheet = $spreadsheet->getSheet($sheetIndex);

            //Пройдёмся по всем ячейкам
            $highestRow = $sheet->getHighestRow(); // e.g. 10
            $highestColumn = $sheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g. 5

            for ($currentRow = 1; $currentRow <= $highestRow; $currentRow++) {
                for ($currentCol = 1; $currentCol <= $highestColumnIndex; $currentCol++) {

                    $cellValue = $sheet->getCellByColumnAndRow($currentCol, $currentRow)->getValue();
                    // Проверим, есть ли внутри ячейки что-то связанное с процентамии (типа %fieldKey%)
                    preg_match_all("/%([\\w\\d]+)%/", $cellValue, $matches);
                    $hasPlaceholders = isset($matches[1][0]);
                    if ($hasPlaceholders) {
                        // Найдены какие-то прейсхолдеры в чейке - и это плохо
                        // Сделаем assertTrue с сообщением, чтобы тесты упали
                        $msg = 'Found placeholder in Excel file: ' . $matches[1][0] . ", sheet $sheetIndex, column $currentCol, row $currentRow";
                        $this->assertTrue(false, $msg);
                    }

                }
            }
        }

        return $spreadsheet;

    }

    public function setSpecialAccess(string $accessKey, int $newValue, callable $testFunc): void
    {
        // Пользователь admin захардкожен в LightConnector - именно под ним запускаются все функциональные тесты
        $user = Db::getRow("SELECT ID, SpecialAccessJSON FROM `user` WHERE Login='admin'");
        $specialAccess = json_decode($user['SpecialAccessJSON'], true);
        // Меняем в базе значение на требуемое
        $specialAccess[$accessKey] = $newValue;
        Db::updateRow(['SpecialAccessJSON' => json_encode($specialAccess)], 'user', $user['ID']);
        // Вызываем callback функцию в которой определена вся логика теста
        $testFunc();
        // Возвращаем старое значение прав доступа
        Db::updateRow(['SpecialAccessJSON' => $user['SpecialAccessJSON']], 'user', $user['ID']);
    }
}
