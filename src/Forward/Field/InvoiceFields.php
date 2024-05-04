<?php

namespace Forward\Field;

use Forward\Classes\Finance;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoneyWithCurrency;
use Light\Link;

class InvoiceFields
{
    public static function docType()
    {
        return F::createSelect('DocType', [
            'name' => 'Doc.Type',
            'default' => Finance::DOCTYPE_INVOICE,
            'options' => Finance::$docType,
            'empty' => false,
            'sqlSelectMandatory' => true,
            'tableAlign' => 'center'
        ]);
    }

    public static function receivedDate()
    {
        return F::createDate('ReceivedDate', [
            'name' => 'Received Date',
            'default' => date('Y-m-d')
        ]);
    }

    public static function docDate($editMandatory = false)
    {
        $field = F::createDate('DocDate', [
            'name' => 'Doc Date',
            'default' => date('Y-m-d')
        ]);

        if ($editMandatory) {
            $field->setEditMandatory('не указана дата Doc Date');
        }

        return $field;
    }

    public static function dueDate($editMandatory = false)
    {
        return F::createDate('DueDate', [
            'name' => 'Due Date',
            'editTitle' => 'Оставьте пустым, чтобы программа расчитала Due Date автоматически',
            'viewCssClass' => [Finance::class, 'getOverdueViewCssClass'],
            'sqlSelectRequires' => ['Status', 'Overdue'],
            'ajax' => true,
            'editMandatory' => $editMandatory
        ]);
    }

    public static function vName()
    {
        return F::createString('Name', ['name' => 'Reference', 'sqlSelectMandatory' => true]);
    }

    public static function cStatusIco()
    {
        return F::createSelect('StatusIco', [
            'name' => 'St.Ico',
            'title' => 'Status Icon',
            'nameTableSettings' => 'Status Icon',
            'nobr' => true,
            'modes' => F::MODE_READONLY,
            'tableAlign' => 'center',
            'default' => Finance::INVOICESTATUS_OPEN,
            'options' => Finance::$cinvoiceStatuses,
            'getHtmlFunction' => [Finance::class, 'getCInvoiceStatusIcon'],
            'sqlSelect' => '`Status`'
        ]);
    }

    public static function vStatus()
    {
        return F::createSelect('Status', [
            'default' => Finance::INVOICESTATUS_OPEN,
            'options' => Finance::$vinvoiceStatuses,
            'empty' => false,
            'sqlSelectMandatory' => true
        ]);
    }

    public static function vStatusIco()
    {
        return F::createSelect('StatusIco', [
            'name' => 'St.Ico',
            'title' => 'Status Icon',
            'nameTableSettings' => 'Status Icon',
            'tableWidthExcel' => 18,
            'modes' => F::MODE_READONLY,
            'tableAlign' => 'center',
            'default' => Finance::INVOICESTATUS_OPEN,
            'options' => Finance::$vinvoiceStatuses,
            'getHtmlFunction' => [Finance::class, 'getVInvoiceStatusIcon'],
            'sqlSelect' => '`Status`'
        ]);
    }

    public static function cinvoiceId()
    {
        return self::invoiceId('CInvoiceID');
    }

    public static function vinvocieId()
    {
        return self::invoiceId('VInvoiceID');
    }

    public static function cinvoiceName(array $params = [])
    {
        return self::invoiceName('CInvoiceName', $params);
    }

    public static function vinvoiceName(array $params = [])
    {
        return self::invoiceName('VInvoiceName', $params);
    }

    public static function invoiceId(string $fieldKey, array $params = [])
    {
        $firstLetter = $fieldKey[0];
        $defaultParams = [
            'name' => $firstLetter === 'C' ? 'Customer Invoice ID' : 'Vendor Invoice ID',
            'sqlSelectMandatory' => true,
            'editWidth' => 'small',
            'editAlign' => 'left',
            'editCssClass' => 'input-int',
            'viewCssClass' => [InvoiceFields::class, 'invoiceViewCssClass'],
            'viewTitle' => [InvoiceFields::class, 'invoiceViewTitle']
        ];

        $params += $defaultParams;

        return F::createInt($fieldKey, $params);
    }

    public static function invoiceName(string $fieldKey, array $params = [])
    {
        $firstLetter = $fieldKey[0];
        $defaultParams = [
            'name' => $firstLetter === 'C' ? 'Customer Invoice' : 'Vendor Invoice',
            'nameTable' => $firstLetter === 'C' ? "Customer\nInvoice" : "Vendor\nInvoice",
            'tableAlign' => 'center',
            'editWidth' => $firstLetter === 'C' ? 'small' : 'medium',
            'tableWidthExcel' => 14,
            'viewCssClass' => [InvoiceFields::class, 'invoiceViewCssClass'],
            'viewTitle' => [InvoiceFields::class, 'invoiceViewTitle'],
            'getHtmlFunction' => function ($value, array $data, $outHtml, $forTable, $field) {

                if (array_key_exists('RealInvoiceID', $data) && !empty($data['RealInvoiceID'])) {

                    $firstLetter = $field->key[0];

                    $link = Link::create("{$firstLetter}Invoice", $outHtml)
                        ->view($data['RealInvoiceID']);

                    if (isset($data['ShipmentID'])) {
                        $urlParams = ['Lines_findid' => 'ShipmentID|' . $data['ShipmentID']];
                        if (isset($data['FinalAmount']) || isset($data['Amount'])) {
                            $amount = $data['FinalAmount'] ?? $data['Amount'];
                            $findidAmountFieldKey = $firstLetter === 'C' ? 'Amount' : 'FinalAmount';
                            $urlParams['Lines_findid'] .= '+' . $findidAmountFieldKey . '|' . $amount;
                        }

                        $link->params($urlParams);

                    }

                    $outHtml = $link->getHtml();

                }

                return $outHtml;
            }
        ];

        $params += $defaultParams;

        return F::createString($fieldKey, $params);
    }

    public static function invoiceViewCssClass($value, array $data)
    {
        if (array_key_exists('RealInvoiceID', $data) && !$data['RealInvoiceID']) {
            return 'bad';
        }

        return '';
    }

    public static function invoiceViewTitle($value, array $data)
    {
        if (array_key_exists('RealInvoiceID', $data) && !$data['RealInvoiceID']) {
            return 'Счёт не найден';
        }

        return '';
    }

    public static function cacheFirstPaymentDate()
    {
        return F::createDate('CacheFirstPaymentDate', [
            'name' => 'First Payment Date',
            'title' => 'Date of first Payment',
            'modes' => F::MODE_READONLY
        ]);
    }

    public static function cacheLastPaymentDate()
    {
        return F::createDate('CacheLastPaymentDate', [
            'name' => 'Last Payment Date',
            'title' => 'Date of last Payment',
            'modes' => F::MODE_READONLY
        ]);
    }

    public static function cachePaymentsReference()
    {
        return F::createString('CachePaymentsReference', [
            'name' => 'Payments References',
            'nameTable' => "Payments\nReferences",
            'title' => 'References of all Payments',
            'modes' => F::MODE_READONLY,
            'tableAlign' => 'center',
            'groupconcat' => true
        ]);
    }

    public static function cachePaymentsTotalAmount()
    {
        return F::createMoneyWithCurrency('CachePaymentsTotalAmount', [
            'name' => 'Payments Amount',
            'nameTable' => "Payments\nTotal Amount",
            'nameTableSettings' => "Payments Total Amount",
            'title' => 'Сумма оплат относящихся к данному счёту в валюте счёта',
            'modes' => F::MODE_READONLY,
            'zeroEmpty' => true,
            'sqlSelect' => "CONCAT(CurrencyISO, ' ', CachePaymentsTotalAmount)",
            'sqlSelectRequires' => 'Amount',
            'viewCssClass' => function ($value, array $data) {
                $amount = FieldMoneyWithCurrency::getAsTouple($value)[1];
                if ($amount == $data['Amount']) {
                    return 'good';
                }

                return '';
            }
        ]);
    }

    public static function cacheOutstandingAmount()
    {
        return F::createMoney('CacheOutstandingAmount', [
            'name' => 'Outstanding Amount',
            'nameTable' => "Outstanding\nAmount",
            'title' => 'Разница между Payments Amount и суммой счёта = задолженность по данному счёту',
            'modes' => F::MODE_READONLY,
            'zeroEmpty' => true,
            // 'sqlSelectRequires' => 'DocType',
            'viewCssClass' => function ($value, array $data) {
                if ($value == 0) {
                    return '';
                }

                if (!isset($data['DocType'])) {
                    return '';
                }

                if (($data['DocType'] == Finance::DOCTYPE_INVOICE && $value > 0)
                    || ($data['DocType'] == Finance::DOCTYPE_CREDITNOTE && $value < 0)
                ) {
                    return 'bad';
                } else {
                    return 'good';
                }
            }
        ]);
    }

    /**
     * Поле-фильтр по статусу счёта. Используется в модулях CInvoice и VInvoice
     * @return \Light\Field\FieldSelect
     */
    public static function filterStatus(array $statuses, array $mixedOptions)
    {
        return F::createSelect('Status', [
            'options' => $statuses + $mixedOptions,
            'sqlWhere' => function ($value, $filtersValues) use ($statuses) {
                //Если значение не числовое, значит был выобна не один статус в выдающем списке, а набор
                //чекбоксов, котрые нужно проверить отдельно
                if (!is_numeric($value)) {
                    //Перебираем все статусы и проверим, какие чекбоксы отмечены
                    $checkedStatuses = [];
                    foreach ($statuses as $status => $info) {
                        if ($filtersValues['Status' . $status]) {
                            $checkedStatuses[] = $status;
                        }
                    }
                    if (count($checkedStatuses) == 0) {
                        //Никакие галочки отмечены не были
                        return "FALSE";
                    } else {
                        //Были отмечены какие-то галочки
                        return "Status IN (" . implode(',', $checkedStatuses) . ")";
                    }

                } else {
                    return "Status =  " . (int)$value;
                }
            }
        ]);
    }

    public static function shippingDocument()
    {
        return F::createString('ShippingDocument', [
            'name' => 'Shipping Document',
            'upper' => true,
            'tableWidthExcel' => 15
        ]);
    }

    public static function uomid()
    {
        return F::createSelect('UOMID', [
            'name' => 'UOM',
            'title' => 'Unit of Measure',
            'copy' => true,
            'options' => 'uom',
            'empty' => true,
            'default' => 1,
            'tableWidthHtml' => 57,
            'tableAlign' => 'center'
        ]);
    }


    public static function overdue(string $firstLetter)
    {
        return F::createSelect('Overdue', [
            'title' => 'Overdue Days',
            'options' => [
                'NotDue' => ['text' => 'Not Due', 'cssClass' => 'good'],
                'Overdue' => ['text' => 'Overdue'],
                'OverdueLess30' => ['text' => 'Overdue < 30 days'],
                'Overdue3069' => ['text' => 'Overdue 30-59 days'],
                'Overdue6089' => ['text' => 'Overdue 60-89 days'],
                'Overdue90179' => ['text' => 'Overdue 90-179 days'],
                'Overdue180' => ['text' => 'Overdue 180+ days'],
            ],
            'sqlWhere' => function ($value) use ($firstLetter) {

                $query = match ($value) {
                    'NotDue' => "CURDATE() < DueDate",
                    'Overdue' => "CURDATE() > DueDate",
                    'OverdueLess30' => "(CURDATE() > DueDate AND CURDATE() < DATE_ADD(DueDate, INTERVAL 30 DAY))",
                    'Overdue3069' => "(CURDATE() >= DATE_ADD(DueDate, INTERVAL 30 DAY)
                                                AND CURDATE() < DATE_ADD(DueDate, INTERVAL 60 DAY))",
                    'Overdue6089' => "(CURDATE() >= DATE_ADD(DueDate, INTERVAL 60 DAY)
                                                AND CURDATE() < DATE_ADD(DueDate, INTERVAL 90 DAY))",
                    'Overdue90179' => "(CURDATE() >= DATE_ADD(DueDate, INTERVAL 90 DAY)
                                                AND CURDATE() < DATE_ADD(DueDate, INTERVAL 180 DAY))",
                    default => "CURDATE() >= DATE_ADD(DueDate, INTERVAL 180 DAY)",
                };

                $openStatues = $firstLetter === 'C' ? Finance::$cinvoiceOpenStatuses : Finance::$vinvoiceOpenStatuses;
                $query .= " AND Status IN (" . implode(',', $openStatues) . ")";

                return $query;
            }
        ]);
    }
}
