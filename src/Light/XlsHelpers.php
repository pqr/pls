<?php


namespace Light;


use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class XlsHelpers
{
    public static function getSheetRawData(Worksheet $sheet, $colAsAlpha = false)
    {
        $sheetRawData = [];

        $highestRowNum = $sheet->getHighestRow(); // e.g. 10
        $highestColNum = Coordinate::columnIndexFromString($sheet->getHighestColumn()); // e.g. 5

        for ($rowNum = 1; $rowNum <= $highestRowNum; $rowNum++) {

            //Заготовим массив под текущую строку и добавим в него сразу скрытое поле с номером строки из Excel
            //Этот номер пригодится в сообщениях для логов
            $sheetRawData[$rowNum] = ['_excelRowNum' => $rowNum];

            for ($colNum = 0; $colNum <= $highestColNum; $colNum++) {

                $value = '';
                if ($sheet->cellExistsByColumnAndRow($colNum, $rowNum)) {
                    $cell = $sheet->getCellByColumnAndRow($colNum, $rowNum);
                    if ($cell) {
                        if ($cell->isFormula()) {
                            $value = $cell->getOldCalculatedValue();
                        } else {
                            $value = $cell->getValue();
                        }
                        $value = trim((string) $value);
                    }
                }

                $colKey = $colAsAlpha ? Coordinate::stringFromColumnIndex($colNum) : $colNum;
                $sheetRawData[$rowNum][$colKey] = $value;
            }
        }

        return $sheetRawData;
    }
}
