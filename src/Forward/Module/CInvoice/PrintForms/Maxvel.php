<?php

namespace Forward\Module\CInvoice\PrintForms;

use Forward\Classes\Constant;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\ObjAbstract;
use Light\PrintForm\PrintForm;
use Light\Table;

class Maxvel extends PrintForm
{
    private const BANK_ACCOUNTS = [
        'USD' => 'ES1401825000892012500032',
        'EUR' => 'ES2001825000810201804226',
    ];

    public function __construct(string $format)
    {
        parent::__construct('Maxvel' . $format, 'Maxvel Freight Invoice', $format, PrintForm::OUT_SAVE_AND_STREAM);
        ini_set('display_errors', 0);
        $this->templateFormat = self::TEMPLATE_FORMAT_EXCEL;
        error_reporting(0);
    }

    public function testForObj(ObjAbstract $obj): bool
    {
        return $obj['CompanyID'] === Constant::OC_MAXVEL;
    }

    protected function getTemplate(ObjAbstract $obj): string
    {
        return __DIR__ . '/Maxvel.xlsx';
    }

    protected function getTplDataForExcel(ObjAbstract $obj): array
    {
        $tdata = parent::getTplDataForExcel($obj);
        $tdata['BankAccount'] = self::BANK_ACCOUNTS[$obj['CurrencyISO']] ?? '';
        $tdata['DocDateFormatted'] = $this->formatDate($obj['DocDate']);

        $ca = Db::getRow(
            "SELECT InvoiceAddress FROM customeraccount WHERE ID = @i",
            $obj['CustomerAccountID']
        );

        $tdata['CustomerAccountInvoiceAddress'] = $ca['InvoiceAddress'] ?? '';
        $tdata['CustomerAccountINN'] = ''; // номера ИНН в базе нет $ca['INN'] ?? '';

        $cinvId = $obj->getId();

        $linesTable = $this->getLinesTable($cinvId);
        $tdata['Lines'] = $linesTable;

        return $tdata;
    }

    private function formatDate(?string $date): string
    {
        if (FieldDate::empty($date)) {
            return '';
        }

        return date('d-M-Y', strtotime($date));
    }

    private function getLinesTable(int $cinvId): Table
    {
        $lines = new Table('MaxvelExcelLines');
        $lines->addFields(
            F::createInt('No'),
            F::createString(
                'Description',
                [
                    'name' => 'Description',
                ]
            ),
            F::createString(
                'MAWB',
                [
                    'name' => 'MAWB',
                ]
            ),
            F::createString(
                'CountryOfOrigin',
                [
                    'name' => '',
                ]
            ),
            F::createString(
                'TradeMark',
                [
                    'name' => '',
                ]
            ),
            F::createFloat('Quantity'),
            F::createMoney('Price'),
            F::createMoney('Amount')
        );

        $linesData = Db::getAll(
            "SELECT
                (SELECT MAWBNumber FROM shipment WHERE Name = cil.FileNumber LIMIT 1) AS MAWB
                , Name AS Description
                , Quantity
                , Price
                , Amount
            FROM cinvoiceline cil
            WHERE CInvoiceID = $cinvId
            ORDER BY ID"
        );

        foreach ($linesData as $i => &$row) {
            $row['No'] = $i + 1;
        }
        unset($row);

        $lines->setTableData($linesData);
        $lines->setTableDataTotal([]);

        return $lines;
    }
}
