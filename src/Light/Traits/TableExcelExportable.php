<?php

namespace Light\Traits;

use Light\ApplicationAbstract;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\File;
use Light\Metric;
use Light\Request;
use Light\Response;
use Light\Timer;
use Light\XlsWriter;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

trait TableExcelExportable
{
    public $allowExportToExcel = true;

    protected function getTableExcelMaxRows()
    {
        $maxRows = ApplicationAbstract::getParam('TableExcelMaxRows');
        return $maxRows ?: 30000;
    }

    /**
     * @param $sheetname
     * @return XlsWriter
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    protected function getXlsWriter($sheetname)
    {
        $xls = new XlsWriter();
        $sheet = $xls->getSpreadsheet()->getActiveSheet();
        // В имени листа не могут быть различные символы, которые запрещены в именах файлов
        $sheet->setTitle(File::getValidFilename($sheetname));
        $sheet->setPrintGridlines(true);
        $sheet->getPageSetup()
            ->setRowsToRepeatAtTopByStartAndEnd(1, 1)
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $fieldsetSowed = $this->getFieldsetShowed();
        $this->prefetchSelectOptions($fieldsetSowed->getFieldsArr());

        $dataCalculated = $this->getTableDataCalculated();
        $dataTotal = $this->getTableDataTotal($dataCalculated);
        if (!$this->hasTotalRow($dataTotal)) {
            // Если в текущей таблице метод hasTotalRow был переопределён так, что возвращает false
            // это значит что для данной таблицы total строка не нужна. Чтобы не выдавать её в Excel,
            // обнулим массив $dataTotal
            $dataTotal = [];
        }

        $xls->writeTableData(
            1,
            1,
            $dataCalculated,
            $dataTotal,
            $fieldsetSowed,
            fn(array $rowData, $rowNum) => $this->getTableRowDataExcelParams($rowData, $rowNum),
            XlsWriter::ALLOPTS
        );

        return $xls;
    }

    /**
     * @param null $filename
     * @param null $sheetname
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function sendExcel($filename = null, $sheetname = null): void
    {
        $timer = new Timer();

        [$filename, $sheetname] = $this->normalizeFilenameSheetname($filename, $sheetname);
        if (isset($_GET['json'])) {

            $json = json_encode($this->getTableData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Response::streamFileFromContent($json, "$filename.json");

        } else {
            $xls = $this->getXlsWriter($sheetname);
            //Отправляем в поток
            $xls->send($filename);
        }

        // Запишем метрику экспорта в Excel - в том числе удобно для логов кто сколько выгружает
        $payload = ['count' => $this->getTableDataCount()];
        if (method_exists($this, 'getFiltersValues')) {
            $payload['filterValues'] = $this->getFiltersValues();
        }
        $act = isset($_GET['json']) ? 'sendJSON ' : 'sendExcel ';
        $actionType = Request::getModule() . '/' . $this->getTableName() . '::' . $act;
        Metric::record($actionType, $timer->getMs(), $payload);
    }

    /**
     * @param null $filename
     * @param null $sheetname
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function saveExcel($filename = null, $sheetname = null): void
    {
        [$filename, $sheetname] = $this->normalizeFilenameSheetname($filename, $sheetname);
        $xls = $this->getXlsWriter($sheetname);
        //Отправляем в поток
        $xls->send($filename);
    }

    protected function normalizeFilenameSheetname($filename, $sheetname)
    {
        if ($filename === null) {
            $filename = $this->getExcelFilename();
        }

        //Если расширение неизвестное или отсутсвует, используем настройку из базы
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext !== 'xls' && $ext !== 'xlsx') {
            $filename .= '.xlsx';
        }

        if ($sheetname === null) {
            $sheetname = $this->getExcelWorksheetName();
            $sheetname = mb_substr($sheetname, 0, 31, 'UTF-8');
        }

        return [$filename, $sheetname];
    }

    /**
     * Для переопределения в классах потомках, которые используют этот trait
     * @return string
     */
    protected function getExcelFilename()
    {
        return 'Table';
    }

    /**
     * Для переопределения в классах потомках
     * @param array $rowData
     * @param $rowNum
     * @return array
     */
    public function getTableRowDataExcelParams(array $rowData, $rowNum)
    {
        $out = [];
        if (isset($rowData['IsSeparator'])) {
            $out += XlsWriter::STYLE_SEPARATOR;
        }

        if (isset($rowData['IsTotal']) || $rowNum === 'total') {
            $out += XlsWriter::STYLE_TOTAL;
        }
        return $out;
    }


    /**
     * Для переопределения в классах потомках, которые используют этот trait
     * @return string
     */
    protected function getExcelWorksheetName()
    {
        return 'Sheet';
    }
}
