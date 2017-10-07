<?php
namespace Forward\Module\Shipment\CostSheet\Subtables\Base;

use Light\Table;
use Light\Traits\TableWithFoldersTrait;

abstract class InvoicesAbstract extends Table
{
    use TableWithFoldersTrait;

    private $_fileNumber;

    public function __construct($tableName, $fileNumber)
    {
        parent::__construct($tableName);
        $this->_fileNumber = $fileNumber;
        $this->_folders = true;

        $this->getFieldsetFirst()->addField($this->fieldFolder());
    }

    protected function getTableRowDataParams(array $rowData, $rowNum)
    {
        $out = [];

        $cssClass = [];
        //Статус 4 - это Deleted как для Customer Invoices, так и для Vendor Invoices
        if (isset($rowData['Status']) && $rowData['Status'] == 4) {
            $cssClass[] = 'deleted';
        }

        //Если в GET параметрах есть параметр fromInvNum, значит нужно подсветить все строки с этим номером счёта
        if (isset($_GET['fromInvNum']) && isset($rowData['Name']) && $_GET['fromInvNum'] == $rowData['Name']) {
            $cssClass[] = 'findid';
        }

        if ($cssClass) {
            $out['cssClass'] = implode(' ', $cssClass);
        }

        //Добавим data параметр с ID счёта
        if (isset($rowData['ID']) && $rowData['ID']) {
            $out['tagParams'] = 'data-invid="' . $rowData['ID'] . '"';
        }

        return $out;
    }

    public function getAmountPerCurrency():array
    {
        //Создает готовую для отображения информацию о сумме каждой валюты в подтаблице
        return array_reduce($this->_tableData, function ($amountPerCurrency, $rowData) {
            if ($rowData['Status'] != 4) {
                $currencyIso = $rowData['CurrencyISO'];
                $amount = $rowData['AmountOnShipment'];
                if (!isset($amountPerCurrency[$currencyIso])) {
                    $amountPerCurrency[$currencyIso] = 0;
                }
                $amountPerCurrency[$currencyIso] += $amount;
            }

            return $amountPerCurrency;
        }, []);
    }

    protected function getTableLinkUrlParams(): array
    {
        //Переопределяем метод (который предназначен именно для переопределения) чтобы добавить к ссылкам на модули
        //CInvoice и VInvoice параметр fromFileNumber - благодаря этому при переходе на конкретный счёт в нём будут
        //подсвечены все строки с этим номером файла
        return ['fromFileNumber' => $this->_fileNumber];
    }
}
