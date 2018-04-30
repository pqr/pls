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

/**
 * @method getTableDataCount()
 * @property array $_tableData
 */
trait TableExcelExportable
{
    protected $allowExportToExcel = true;

    /**
     * @return Fieldset|F[]
     */
    abstract public function getFieldsetShowed();

    abstract public function getTableData();

    protected function getTableExcelMaxRows()
    {
        $maxRows = ApplicationAbstract::getParam('TableExcelMaxRows');
        return $maxRows ?: 30000;
    }

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

        $xls->writeTableData(
            1,
            1,
            $this->getTableData(),
            $this->getFieldsetShowed(),
            function (array $rowData, $rowNum) {
                return $this->getTableRowDataExcelParams($rowData, $rowNum);
            },
            XlsWriter::ALLOPTS
        );

        return $xls;
    }

    public function sendExcel($filename = null, $sheetname = null)
    {
        $timer = new Timer();

        list($filename, $sheetname) = $this->normalizeFilenameSheetname($filename, $sheetname);
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
        $act = isset($_GET['json']) ? '::sendJSON ' : '::sendExcel ';
        Metric::record(Request::getModule() . $act . $this->getTableName(), $timer->getMs(), $payload);
    }

    public function saveExcel($filename = null, $sheetname = null)
    {
        list($filename, $sheetname) = $this->normalizeFilenameSheetname($filename, $sheetname);
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
            $out += XlsWriter::$rowSeparatorStyle;
        }

        if (isset($rowData['IsTotal']) || $rowNum === 'total') {
            $out += XlsWriter::$rowTotalStyle;
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
