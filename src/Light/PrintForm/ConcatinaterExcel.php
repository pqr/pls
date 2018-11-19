<?php


namespace Light\PrintForm;


class ConcatinaterExcel implements ConcatinaterInterface
{
    public function concat(array $files, string $resultFile, string $title)
    {
        throw new \Exception('ConcatinaterExcel::concat not implemented');
        /*
        $sheet = $sheetTemplate->copy();
        $sheetIndex++;
        $spreadsheet->addSheet($sheet, $sheetIndex);
        $spreadsheet->setActiveSheetIndex($sheetIndex);

        $title = $obj->getFieldText($obj->getNameFieldKey());
        $sheet->setTitle($title);

        $this->writeToExcelTemplate($xls, $obj, $title);
        if ($this->out & self::OUT_SAVE) {
            $this->excelSave($xls, $destFilepathAbsEnc);
            @chmod($destFilepathAbsEnc, 0666);
        }
        break;
        */
    }

}