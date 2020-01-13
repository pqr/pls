<?php

namespace Forward\Field;

use Forward\Classes\Constant;
use Forward\Module\DocsInventory\DocsInventory;
use Forward\Module\Shipment\Shipment;
use Forward\Module\Shipment\ShipmentObj;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldMoney;
use Light\File;
use Light\FilesFolder;
use Light\Link;

class ShipmentFields
{
    public static function name()
    {
        return F::createString('Name', [
            'name' => 'File Number',
            'modes' => F::MODE_READONLY | F::MODE_SUBMIT,
            'tableWidthExcel' => 15,
            'tableWidthHtml' => 45,
            'viewCssClass' => function ($value, array $data, $forTable) {
                return $forTable ? 'fn' : '';
            }
        ]);
    }

    public static function fileClosed()
    {
        return F::createSelect('FileClosed', [
            'name' => 'Open/Closed',
            'nameTable' => "Open /\nClosed",
            'tableAlign' => 'center',
            'options' => [
                ShipmentObj::SHIPMENT_OPEN => ['text' => 'OPEN', 'cssClass' => 'fileopen'],
                ShipmentObj::SHIPMENT_CLOSED => ['text' => 'CLOSED', 'cssClass' => 'fileclosed']
            ],
            'empty' => false
        ]);
    }

    public static function eta()
    {
        return F::createDate('ETA', ['title' => 'Estimated Time of Arrival']);
    }

    public static function ata()
    {
        return F::createDate('ATA', ['title' => 'Actual Time of Arrival']);
    }

    public static function flightNo()
    {
        return F::createString('FlightNo', [
            'name' => 'Flight No.',
            'editWidth' => 'small'
        ]);
    }

    public static function flightDate()
    {
        return F::createDate('FlightDate', ['name' => 'Flight Date', 'ajax' => true]);
    }

    public static function month()
    {
        return F::createMonth('Month', [
            'yearFrom' => 2012,
            'yearUntil' => (new \DateTime())->modify('+3 month')->format('Y-m'),
            'editMandatory' => true
        ]);
    }

    public static function fileFields(): array
    {
        $outFields = [];
        foreach (Constant::$standardFolders as $i => $_) {
            $outFields[] = self::fileField($i);
        }

        return $outFields;
    }

    public static function fileField(int $folderNo)
    {
        $standardFolder = Constant::$standardFolders[$folderNo];
        return F::createYesNo("File$folderNo", [
            'name' => $standardFolder,
            'nameTable' => "Файл:\n$standardFolder",
            'sqlSelect' => '',
            'modes' => F::MODE_READONLY,
            'tableWidthExcel' => 10,
            'calcFunction' => function ($value, array $data) use ($standardFolder) {
                if (isset($data['ID'])) {
                    $folderRelative = FilesFolder::getRelativeModule(Shipment::class, $data['ID']);
                    if (FilesFolder::hasFiles("$folderRelative/$standardFolder")) {
                        return 1;
                    }
                }
                return 0;
            }
        ]);
    }

    public static function filterFileNumbers()
    {
        return F::createText(
            'FileNumbers',
            [
                'name' => 'File Numbers',
                'sqlWhere' => function ($value) {
                    return Db::getSqlWhereSeparatedByComa($value, 'shipment.Name', -1, false, true, 1);
                }
            ]
        );
    }

    public static function filterMawbs()
    {
        return F::createText('MAWBNumbers', [
                'name' => 'MAWB Numbers',
                'sqlWhereFormatFunction' => function ($value) {
                    return str_replace(['-', ' '], '', $value);
                },
                'sqlWhere' => function ($value) {
                    return "(" .
                        Db::getSqlWhereSeparatedByComa(
                            $value,
                            "REPLACE(REPLACE(shipment.MAWBNumber, ' ', ''), '-', '')",
                            11,
                            true,
                            false,
                            0
                        ) . "
                            OR EXISTS (SELECT ID FROM shipmentshipper WHERE ShipmentID = shipment.ID AND " .
                        Db::getSqlWhereSeparatedByComa(
                            $value,
                            'shipmentshipper.HAWBNumber',
                            0,
                            true,
                            false,
                            0
                        ) . "))";
                }

            ]
        );
    }

    public static function shipmentRequestNo()
    {
        return F::createInt('ShipmentRequestNo', [
            'name' => 'Заявка №',
            'tableAlign' => 'center',
            'zeroEmpty' => true,
            'zeroEmptyExcel' => true,
            'sqlSelectRequires' => 'ETD',
            'getHtmlFunction' => function ($value, array $data, $outHtml) {
                if ($value && isset($data['ETD'])) {
                    $shipmentRequestDate = $data['ETD'] !== '0000-00-00' ? $data['ETD'] : date('Y-m-d');
                    $outHtml = $value . '-' . substr($shipmentRequestDate, 2, 2);
                }
                return $outHtml;
            }
        ]);
    }

    public static function docsPrintedDate()
    {
        return F::createDate('DocsPrintedDate', [
            'name' => 'Оригиналы отправлены на печать',
            'ajax' => true
        ]);
    }

    public static function docsSentToAccountingDate()
    {
        return F::createDate('DocsSentToAccountingDate', [
            'name' => 'Оригиналы отправлены в бухгалтерию',
            'ajax' => true
        ]);
    }

    public static function cacts()
    {
        return F::createString('CActs', [
            'name' => 'Акт на клиента',
            'modes' => F::MODE_READONLY,
            'tableWidthExcel' => 35,
            'groupconcat' => true,
            'sqlSelect' => "SELECT GROUP_CONCAT(CONCAT(ci.CActName, '|', ci.CActDate, '|', ci.Amount, '|', ci.CurrencyISO) ORDER BY ci.CActDate SEPARATOR '\\n')
                                FROM cinvoice_file_amount cfa, cinvoice ci
                                WHERE ci.ID = cfa.CInvoiceID AND cfa.FileNumber = shipment.Name AND ci.CActName != ''",
            'getHtmlFunction' => function ($value) {
                if (strpos($value, '|') === false) {
                    return '';
                }
                [$actName, $actDate, $actAmount, $currencyIso] = explode('|', $value);
                return htmlspecialchars($actName) . ' <small class="gray"> от ' . FieldDate::beautify($actDate) . '</small> <small> на ' . FieldMoney::beautify($actAmount) . ' ' . $currencyIso . '</small>';
            },
            'getExcelFunction' => function ($value) {
                if (strpos($value, '|') === false) {
                    return '';
                }
                [$actName, $actDate, $actAmount, $currencyIso] = explode('|', $value);
                $actDate = FieldDate::beautify($actDate);
                return "$actName от $actDate на $actAmount $currencyIso";
            }
        ]);
    }

    public static function docsInventory()
    {
        return F::createString('DocsInventory', [
            'name' => 'Опись переданных док-ов',
            'modes' => F::MODE_READONLY,
            'tableWidthExcel' => 35,
            'groupconcat' => true,
            'sqlSelect' => "SELECT GROUP_CONCAT(CONCAT(di.ID, '|', di.SentDate) ORDER BY di.SentDate SEPARATOR '\\n')
                                FROM docsinventory di, docsinventoryline dil
                                WHERE di.ID = dil.DocsInventoryID AND dil.FileNumber = shipment.Name",
            'getHtmlFunction' => function ($value, array $data, $outHtml, $forTable) {
                if (strpos($value, '|') === false) {
                    return '';
                }
                [$diId, $diSentDate] = explode('|', $value);
                $text = FieldDate::beautify($diSentDate);
                if ($forTable) {
                    return $text;
                }
                return Link::create(DocsInventory::class, $text)
                    ->viewDialog($diId)
                    ->getHtml();
            },
            'getExcelFunction' => function ($value) {
                if (strpos($value, '|') === false) {
                    return '';
                }
                $diSentDate = explode('|', $value)[1];
                $diSentDate = FieldDate::beautify($diSentDate);
                return "$diSentDate";
            }
        ]);
    }
}
