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

/**
 * @method getTableDataCount()
 * @property array $_tableData
 */
trait TableExcelExportable
{
    protected $_tableExcelEnabled = true;

    /**
     * @return Fieldset|F[]
     */
    abstract public function getFieldsetShowed();

    abstract public function getTableData();

    public function isExcelEnabled()
    {
        return $this->_tableExcelEnabled;
    }

    /**
     * Метод для переопределения в классах использующих этот Trait
     */
    public function getTableExcelUrl()
    {
        return '?a=' . Request::getModule() . '&xls=1';
    }

    protected function getTableExcelMaxRows()
    {
        $maxRows = ApplicationAbstract::getParam('TableExcelMaxRows');
        return $maxRows ?: 30000;
    }

    protected function getXlsWriter($sheetname)
    {
        $xls = new XlsWriter();
        $sheet = $xls->getPhpExcelObj()->getActiveSheet();
        // В имени листа не могут быть различные символы, которые запрещены в именах файлов
        $sheet->setTitle(File::getValidFilename($sheetname));
        $sheet->setPrintGridlines(true);
        $sheet->getPageSetup()
            ->setRowsToRepeatAtTopByStartAndEnd(1, 1)
            ->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $xls->writeTableData(
            $this->getTableData(),
            $this->getFieldsetShowed(),
            0,
            1,
            true,
            function (array $rowData, $rowNum) {
                return $this->getTableRowDataExcelParams($rowData, $rowNum);
            }
        );

        return $xls;
    }

    public function sendExcel($filename = null, $sheetname = null)
    {
        ini_set('display_errors', 1);
        error_reporting(E_ERROR);
        $timer = new Timer();

        list($filename, $sheetname) = $this->normalizeFilenameSheetname($filename, $sheetname);
        //Получим специальный объект типа XlsWriter, в котором содержится PHPExcel с заполненными данными
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
        //Получим специальный объект типа XlsWriter, в котором содержится PHPExcel с заполненными данными
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
        return [];
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