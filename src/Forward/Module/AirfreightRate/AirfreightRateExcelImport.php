<?php
namespace Forward\Module\AirfreightRate;

use Light\Db;
use Light\Field\FieldDate;
use Light\Msg;
use Light\Timer;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\BaseReader;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AirfreightRateExcelImport
{
    public const VENDOR = 1;
    public const DEPARTUREIATA = 2;
    public const ARRIVALIATA = 3;
    public const AIRLINE = 4;
    public const TRANSITTIME = 5;
    public const CONTRACT = 6;
    public const VALIDFROM = 7;
    public const VALIDUNTIL = 8;
    public const MIN = 9;
    public const N = 10;
    public const AMOUNT45 = 11;
    public const AMOUNT100 = 12;
    public const AMOUNT200 = 13;
    public const AMOUNT300 = 14;
    public const AMOUNT500 = 15;
    public const AMOUNT750 = 16;
    public const AMOUNT1000 = 17;
    public const AMOUNT2000 = 18;
    public const FSC = 19;
    public const SSC = 20;
    public const CURRENCY = 21;
    public const COMMENTS = 22;

    public function __invoke($filepath, Msg $msgBuff)
    {
        $timer = new Timer();

        /** @var BaseReader $reader */
        $reader = IOFactory::createReaderForFile($filepath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filepath);

        $sheet = $spreadsheet->getSheet(0);

        //Пройдёмся по всем ячейкам
        if (!$msgBuff->isEmpty()) {
            $msgBuff->addEmptyLine();
        }
        $msgBuff->addInfo('<strong>Reading Worksheet: ' . htmlspecialchars($sheet->getTitle()) . '</strong>');

        $sheetRawData = $this->getSheetRawData($sheet);

        //Массив $dataForDb будет содержать структуры ['header' => [...], 'surcharges' => [...]]
        $dataForDb = [];

        //В строке некоторые данные записаны текстом - их нужно сконвертировать ID: VendorID
        //Также сконвертриуем даты в формат для MySQL
        //В процессе некоторые строки могут содержать ошибки - неизвестные порты, они будут пропущены
        //и будет добавлено сообщение в msgBuff
        $dataForDb = array_merge($dataForDb, $this->convertRowsForDb($sheetRawData, $msgBuff));

        //Удалим дубликаты, которые могли возникнуть в разных строках из Excel при этом запишем сообщения в логи
        //Выигрывает последняя строка
        $dataForDb = $this->removeDuplicates($dataForDb, $msgBuff);

        //Пора вставлять или обновлять данные по текущему листу базу.
        //В логи будет записано чилсло добавленных и обновлённых записей
        [$inserted, $updated, $notChanged] = $this->insertOrUpdateDb($dataForDb);

        $msgBuff->addEmptyLine();
        $msgBuff->addHtml('<hr>');
        $msgBuff->addSuccessOrInfo('New rates: ' . $inserted, $inserted);
        $msgBuff->addSuccessOrInfo('Updated: ' . $updated, $updated);
        $msgBuff->addSkipp('Not changed: ' . $notChanged);

        $msgBuff->addEmptyLine();
        $msgBuff->addHtml('<small>Upload time: ' . $timer->getHtml() . '</small>');

        return [$inserted, $updated, $notChanged];
    }

    /**
     * Проходит по всему листу и возвращает двумерный массив с данными
     * Каждая строка при этом имеет дополнительный элемент с ключём '_excelRowNum' - оригинальный номер строки
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function getSheetRawData(Worksheet $sheet)
    {
        $sheetRawData = [];

        $highestRowNum = $sheet->getHighestRow(); // e.g. 10
        $highestColNum = Coordinate::columnIndexFromString($sheet->getHighestColumn()); // e.g. 5

        //Начинаем чтение со второй строки - оттуда начинаются данные
        for ($rowNum = 2; $rowNum <= $highestRowNum; $rowNum++) {

            //Заготовим массив под текущую строку и добавим в него сразу скрытое поле с номером строки из Excel
            //Этот номер пригодится в сообщениях для логов
            $sheetRawData[$rowNum] = ['_excelRowNum' => $rowNum];

            for ($colNum = 1; $colNum <= $highestColNum; $colNum++) {

                if ($sheet->cellExistsByColumnAndRow($colNum, $rowNum)) {
                    $cell = $sheet->getCellByColumnAndRow($colNum, $rowNum);
                    if ($cell->isFormula()) {
                        $value = $cell->getOldCalculatedValue();
                    } else {
                        $value = $cell->getValue();
                    }
                    $value = trim($value);
                } else {
                    $value = '';
                }

                $sheetRawData[$rowNum][$colNum] = $value;
            }
        }

        return $sheetRawData;
    }

    /**
     * Принимает сырую строку из Excle файла и создаёт на её основе структуру с полями для шапки и массивом надбавок
     * При этом текстовые представления портов и перевозчиков конвертируются в их ID из базы.
     * Если текст не удалось опознать
     * @return array
     */
    private function convertRowsForDb(array $sheetRawData, Msg $msgBuff)
    {
        $dataForDb = [];
        foreach ($sheetRawData as $rowData) {

            if (empty($rowData[self::VENDOR]) || empty($rowData[self::DEPARTUREIATA]) || empty($rowData[self::ARRIVALIATA])) {
                //Это просто пустая строка, пропускаем без сообщений об ошибках
                continue;
            }

            //Распарсим даты
            $validFrom = FieldDate::parse($rowData[self::VALIDFROM]);
            $validUntil = FieldDate::parse($rowData[self::VALIDUNTIL]);

            $vendorId = $this->getVendorId($rowData[self::VENDOR]);
            $departureLocationName = $this->getLocationName($rowData[self::DEPARTUREIATA]);
            $arrivalLocationName = $this->getLocationName($rowData[self::ARRIVALIATA]);

            if (!$validFrom) {
                $msgBuff->addWarning('Строка ' . $rowData['_excelRowNum'] . ': не удалось рапознать дату Valid From <storng>' . htmlspecialchars($rowData[self::VALIDFROM]) . '</storng>');
            }
            if (!$validUntil) {
                $msgBuff->addWarning('Строка ' . $rowData['_excelRowNum'] . ': не удалось рапознать дату Valid Until <storng>' . htmlspecialchars($rowData[self::VALIDUNTIL]) . '</storng>');
            }

            if (!$vendorId) {
                $msgBuff->addWarning('Строка ' . $rowData['_excelRowNum'] . ': неизвестный Vendor <storng>' . htmlspecialchars($rowData[self::VENDOR]) . '</storng>');
            }
            if (!$departureLocationName) {
                $msgBuff->addWarning('Строка ' . $rowData['_excelRowNum'] . ': неизвестный Departure Location <storng>' . htmlspecialchars($rowData[self::DEPARTUREIATA]) . '</storng>');
            }
            if (!$arrivalLocationName) {
                $msgBuff->addWarning('Строка ' . $rowData['_excelRowNum'] . ': неизвестный Arrival Location <storng>' . htmlspecialchars($rowData[self::ARRIVALIATA]) . '</storng>');
            }

            if (!$validFrom || !$validUntil || !$vendorId || !$departureLocationName || !$arrivalLocationName) {
                $msgBuff->addError('Строка ' . $rowData['_excelRowNum'] . ': <strong>пропускаем</strong>');
                continue;
            }

            //Подготовим структуру
            $dataForDb[] = [
                '_excelRowNum' => $rowData['_excelRowNum'],
                'Name' => $rowData[self::CONTRACT],
                'VendorID' => $vendorId,
                'AirlineAlphaCode' => $rowData[self::AIRLINE],
                'TransitTime' => str_replace([' days', ' day'], '', strtolower($rowData[self::TRANSITTIME])),
                'DepartureIATA' => $rowData[self::DEPARTUREIATA],
                'DepartureLocationName' => $departureLocationName,
                'ArrivalIATA' => $rowData[self::ARRIVALIATA],
                'ArrivalLocationName' => $arrivalLocationName,
                'ValidFrom' => $validFrom,
                'ValidUntil' => $validUntil,
                'Comments' => $rowData[self::COMMENTS],
                'MinAmount' => $rowData[self::MIN],
                'AmountN' => $rowData[self::N],
                'Amount45' => $rowData[self::AMOUNT45],
                'Amount100' => $rowData[self::AMOUNT100],
                'Amount200' => $rowData[self::AMOUNT200],
                'Amount300' => $rowData[self::AMOUNT300],
                'Amount500' => $rowData[self::AMOUNT500],
                'Amount750' => $rowData[self::AMOUNT750],
                'Amount1000' => $rowData[self::AMOUNT1000],
                'Amount2000' => $rowData[self::AMOUNT2000],
                'FSCAmount' => $rowData[self::FSC],
                'SSCAmount' => $rowData[self::SSC],
                'CurrencyISO' => strtoupper($rowData[self::CURRENCY]),
            ];

        }

        return $dataForDb;
    }

    private function getVendorId($vendorName)
    {
        static $vendors;
        if ($vendors === null) {
            $vendors = Db::getAssoc("SELECT LOWER(Name), ID FROM vendor");
        }

        $vendorName = mb_strtolower(trim($vendorName), 'UTF-8');
        return $vendors[$vendorName] ?? null;
    }

    private function getLocationName($locationIata)
    {
        static $locations;
        if ($locations === null) {
            $locations = Db::getAssoc("SELECT UPPER(IATA), Name FROM location");
        }

        $locationIata = mb_strtoupper(trim($locationIata), 'UTF-8');
        return $locations[$locationIata] ?? null;
    }

    private function removeDuplicates(array $dataForDb, Msg $msgBuff)
    {
        $newDataForDb = [];
        foreach ($dataForDb as $data) {

            //Скомбинируем уникальный ключ на основе шапки записи
            $key = $data['Name'] . '-' . $data['VendorID'] . '-' . $data['DepartureIATA'] . '-' . $data['ArrivalIATA'] . '-' . $data['AirlineAlphaCode'] . '-' . $data['ValidFrom'] . '-' . $data['ValidUntil'] . '-' . $data['Comments'];
            if (isset($newDataForDb[$key])) {
                $oldRowNum = $newDataForDb[$key]['_excelRowNum'];
                $newRowNum = $data['_excelRowNum'];
                $msgBuff->addWarning('Строки ' . $oldRowNum . ' и ' . $newRowNum . ' совпадают, в базу будет записана последняя');
            }

            $newDataForDb[$key] = $data;
        }

        return $dataForDb;
    }

    private function insertOrUpdateDb(array $dataForDb)
    {
        $inserted = 0;
        $updated = 0;
        $notChanged = 0;
        foreach ($dataForDb as $data) {

            //Попробуем найти запись в базе
            $query = "SELECT ID
                      FROM airfreightrate
                      WHERE Name = @s
                          AND VendorID = @i
                          AND DepartureIATA = @s
                          AND ArrivalIATA = @s
                          AND AirlineAlphaCode = @s
                          AND ValidFrom = @s
                          AND ValidUntil = @s
                          AND Comments = @s
                      LIMIT 1";

            $existId = Db::getOne($query, $data['Name'], $data['VendorID'], $data['DepartureIATA'],
                $data['ArrivalIATA'], $data['AirlineAlphaCode'], $data['ValidFrom'], $data['ValidUntil'],
                $data['Comments']);
            if ($existId) {
                //Делаем обновление
                if ($this->updateDb($data, $existId)) {
                    $updated++;
                } else {
                    $notChanged++;
                }
            } else {
                //Делаем вставку новой записи
                $this->insertDb($data);
                $inserted++;
            }

        }

        return [$inserted, $updated, $notChanged];
    }

    /**
     * @param int $rateId
     * @return int affectedRows
     */
    private function updateDb(array $data, $rateId)
    {
        $rateObj = new AirfreightRateObj();
        $rateObj->setId($rateId);

        $dbLogMsg = 'Загрузка из Excel файла, строка ' . $data['_excelRowNum'];
        unset($data['_excelRowNum']);

        return $rateObj->updateDb($data, $dbLogMsg);
    }

    private function insertDb(array $data): void
    {
        $rateObj = new AirfreightRateObj();

        $dbLogMsg = 'Загрузка из Excel файла, строка ' . $data['_excelRowNum'];
        unset($data['_excelRowNum']);
        $rateObj->insertDb($data, $dbLogMsg);
    }
} 
