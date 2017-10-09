<?php
namespace Forward\Classes;

use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\VInvoice\VInvoiceObj;
use Light\Db;
use Light\Link;
use Light\Request;

class Finance
{
    //Open process
    const INVOICESTATUS_OPEN = 11;
    const INVOICESTATUS_UNDERDISPUTE = 12;
    const INVOICESTATUS_ACCEPTED = 13;
    //Part Cleated/Cleared
    const INVOICESTATUS_PARTCLEARED = 21;
    const INVOICESTATUS_CLEARED = 22;
    //Canceled/Deleted
    const INVOICESTATUS_CREDITNOTED = 31;
    const INVOICESTATUS_DELETED = 32;

    const DOCTYPE_INVOICE = 1;
    const DOCTYPE_CREDITNOTE = -1;

    public static $docType = [
        self::DOCTYPE_INVOICE => 'Invoice',
        self::DOCTYPE_CREDITNOTE => 'Credit Note'
    ];

    public static $cinvoiceStatuses = [
        self::INVOICESTATUS_OPEN => ['text' => 'Open', 'cssClass' => 'invopen'],
        self::INVOICESTATUS_PARTCLEARED => ['text' => 'Partially Cleared', 'cssClass' => 'invpartcleared'],
        self::INVOICESTATUS_CLEARED => ['text' => 'Cleared', 'cssClass' => 'invcleared'],
        self::INVOICESTATUS_CREDITNOTED => ['text' => 'Cancelled with C/N', 'cssClass' => 'invcreditnoted'],
        self::INVOICESTATUS_DELETED => ['text' => 'Deleted', 'cssClass' => 'invdeleted']
    ];

    public static $vinvoiceStatuses = [
        self::INVOICESTATUS_OPEN => ['text' => 'Open - On checking', 'cssClass' => 'invopen'],
        self::INVOICESTATUS_UNDERDISPUTE => ['text' => 'Open - Under dispute', 'cssClass' => 'invunderdispute'],
        self::INVOICESTATUS_ACCEPTED => ['text' => 'Open - Accepted', 'cssClass' => 'invaccepted'],
        self::INVOICESTATUS_PARTCLEARED => ['text' => 'Partially Cleared', 'cssClass' => 'invpartcleared'],
        self::INVOICESTATUS_CLEARED => ['text' => 'Cleared', 'cssClass' => 'invcleared'],
        self::INVOICESTATUS_CREDITNOTED => ['text' => 'Cancelled with C/N', 'cssClass' => 'invcreditnoted'],
        self::INVOICESTATUS_DELETED => ['text' => 'Deleted', 'cssClass' => 'invdeleted']
    ];

    //Хеш таблицы открытых статусов, чтобы быстрее делать проверки
    public static $cinvoiceOpenStatuses = [
        self::INVOICESTATUS_OPEN => self::INVOICESTATUS_OPEN,
        self::INVOICESTATUS_PARTCLEARED => self::INVOICESTATUS_PARTCLEARED
    ];

    public static $vinvoiceOpenStatuses = [
        self::INVOICESTATUS_OPEN => self::INVOICESTATUS_OPEN,
        self::INVOICESTATUS_UNDERDISPUTE => self::INVOICESTATUS_UNDERDISPUTE,
        self::INVOICESTATUS_ACCEPTED => self::INVOICESTATUS_ACCEPTED,
        self::INVOICESTATUS_PARTCLEARED => self::INVOICESTATUS_PARTCLEARED
    ];

    private static function getInvoiceStatusIcon(int $status, string $title)
    {
        if ($status === self::INVOICESTATUS_OPEN) {
            return '<i class="fa fa-exclamation-triangle invicon__open" title="' . htmlspecialchars($title) . '"></i>';
        } elseif ($status === self::INVOICESTATUS_UNDERDISPUTE) {
            return '<i class="fa fa-times-circle invicon__underdispute" title="' . htmlspecialchars($title) . '"></i>';
        } elseif ($status === self::INVOICESTATUS_ACCEPTED) {
            return '<i class="fa fa-check invicon__accepted" title="' . htmlspecialchars($title) . '"></i>';
        } elseif ($status === self::INVOICESTATUS_CLEARED) {
            return '<i class="invicon__cleared" title="' . htmlspecialchars($title) . '"></i>';
        } elseif ($status === self::INVOICESTATUS_PARTCLEARED) {
            return '<i class="invincon__partclearedleft" title="' . htmlspecialchars($title) . '"></i>'
            . '<i class="invincon__partclearedright" title="' . htmlspecialchars($title) . '"></i>';
        } elseif ($status === self::INVOICESTATUS_CREDITNOTED) {
            return '<i class="fa fa-minus-circle invicon__creditnoted" title="' . htmlspecialchars($title) . '"></i>';
        } elseif ($status === self::INVOICESTATUS_DELETED) {
            return '<i class="fa fa-trash" title="' . htmlspecialchars($title) . '"></i>';
        }

        return '<i class="icon-invstatus' . $status . '" title="' . htmlspecialchars($title) . '"></i>';
    }

    public static function getCInvoiceStatusIcon($status)
    {
        if (!isset(self::$cinvoiceStatuses[$status])) {
            return '';
        }

        $text = self::$cinvoiceStatuses[$status]['text'];
        return self::getInvoiceStatusIcon($status, $text);
    }

    public static function getVInvoiceStatusIcon($status)
    {
        if (!isset(self::$vinvoiceStatuses[$status])) {
            return '';
        }

        $text = self::$vinvoiceStatuses[$status]['text'];
        return self::getInvoiceStatusIcon($status, $text);
    }

    /**
     * @desc возвращает начало ссылки на модуль Customer Invoices со счетами для указанного клиента и аккаунта
     * @param string $text
     * @param int $customerId
     * @param int $customerAccountId
     * @param string $currencyIso
     * @param bool|null $ovedue
     * @param string|null $docDateFrom
     * @param string|null $docDateUntil
     * @param int|null $companyId
     * @return string
     */
    public static function getCInvoiceLink(
        $text,
        $customerId,
        $customerAccountId,
        $currencyIso,
        $ovedue = null,
        $docDateFrom = null,
        $docDateUntil = null,
        $companyId = null
    ) {
        $filterValues = [];
        if ($customerId) {
            $filterValues['CustomerID'] = $customerId;
        }
        if ($customerAccountId) {
            $filterValues['CustomerAccountID'] = $customerAccountId;
        }
        if ($currencyIso) {
            $filterValues['CurrencyISO'] = $currencyIso;
        }
        if ($ovedue !== null) {
            $filterValues['Overdue'] = $ovedue ? 'Overdue' : 'NotDue';
        }
        if ($docDateFrom) {
            $filterValues['DocDateFrom'] = $docDateFrom;
        }
        if ($docDateUntil) {
            $filterValues['DocDateUntil'] = $docDateUntil;
        }
        if ($companyId) {
            $filterValues['CompanyID'] = $companyId;
        }

        $filterValues['Status'] = 'Open';
        $filterValues['Status' . Finance::INVOICESTATUS_OPEN] = 1;
        $filterValues['Status' . Finance::INVOICESTATUS_PARTCLEARED] = 1;

        return Link::moduleList($text, $filterValues, null, 'CInvoice', 'View Customer Invoices');
    }

    /**
     * @desc возвращает начало ссылки на модуль Customer Invoices со счетами для указанного клиента и аккаунта
     * @param string $text
     * @param int $vendorId
     * @param int $vendorAccountId
     * @param string $currencyIso
     * @param bool $ovedue
     * @param bool $docDateFrom
     * @param bool $docDateUntil
     * @return string
     */
    public static function getVInvoiceLink(
        $text,
        $vendorId,
        $vendorAccountId,
        $currencyIso,
        $ovedue = null,
        $docDateFrom = null,
        $docDateUntil = null
    ) {
        $filterValues = [];
        if ($vendorId) {
            $filterValues['VendorID'] = $vendorId;
        }
        if ($vendorAccountId) {
            $filterValues['VendorAccountID'] = $vendorAccountId;
        }
        if ($currencyIso) {
            $filterValues['CurrencyISO'] = $currencyIso;
        }
        if ($ovedue) {
            $filterValues['Overdue'] = $ovedue ? 'Overdue' : 'NotDue';
        }
        if ($docDateFrom) {
            $filterValues['DocDateFrom'] = $docDateFrom;
        }
        if ($docDateUntil) {
            $filterValues['DocDateUntil'] = $docDateUntil;
        }

        $filterValues['Status'] = 'Open';
        $filterValues['Status' . Finance::INVOICESTATUS_OPEN] = 1;
        $filterValues['Status' . Finance::INVOICESTATUS_ACCEPTED] = 1;
        $filterValues['Status' . Finance::INVOICESTATUS_UNDERDISPUTE] = 1;
        $filterValues['Status' . Finance::INVOICESTATUS_PARTCLEARED] = 1;

        return Link::moduleList($text, $filterValues, null, 'VInvoice', 'View Vendor Invoices');
    }

    /**
     * @param array $fileNumbers
     * @param string $dbLogMsg
     * @param $firstLetter 'C' или 'V' - нужно для правильного вызова обновления критериев
     */
    public static function updateShipmentsGpAndCriteria(array $fileNumbers, $dbLogMsg, $firstLetter)
    {
        ShipmentGp::updateGpEgpDeviationByFileNumbers($fileNumbers);

        //Обновим критерии по файлам (всем файлам - которые были раньше и которые стали сейчас)
        if ($firstLetter === 'C') {
            ShipmentCriteria::updateRelatedCInvByFileNumbers($fileNumbers, $dbLogMsg);
        } else {
            ShipmentCriteria::updateRelatedVInvByFileNumbers($fileNumbers, $dbLogMsg);
        }
    }


    /**
     * Обновляет кеширующие поля CacheLastPaymentDate, CachePaymentsReference, CachePaymentsTotalAmount, CacheOutstandingAmount
     * в таблице cinvoice или vinvoice
     * @param int $invId ID в таблице vinvoice или cinvoice
     * @param string $dbTable имя таблицы vinvoice или cinvoice
     * @return void
     */
    public static function updateInvoiceCacheClearingFields($invId, $dbTable)
    {
        //Загрузим сам инвойс
        if ($invInfo = Db::getRow(
            "SELECT ID, Name, Amount, CurrencyISO, Status, CompanyID FROM $dbTable WHERE ID = " . (int)$invId
        )
        ) {

            $prefix = $dbTable[0];
            $paymentTable = $prefix . 'payment'; //cpayment или vpayment
            $paymentIdField = strtoupper($prefix) . 'PaymentID';
            $paymentInvoiceTable = $prefix . 'payment_' . $dbTable; //cpayment_cinvoice или vpayment_vinvoice
            //Узнаем ID всех связанных оплат (при этом делаем запрос в том числе и на таблицу cpayment/vpayment, чтобы
            //точно привязаться по CompanyID
            $query = "SELECT DISTINCT(" . $paymentInvoiceTable . "." . $paymentIdField . ")
                       FROM " . $paymentInvoiceTable . ", " . $paymentTable . "
                       WHERE " . $paymentInvoiceTable . "." . $paymentIdField . " = " . $paymentTable . ".ID";

            if ($dbTable === 'vinvoice') {
                $whereInvoice = " AND VInvoiceID = '" . $invInfo['ID'] . "'";
            } else {
                $whereInvoice = " AND CInvoiceID = '" . $invInfo['ID'] . "'";
            }

            $query .= $whereInvoice;

            $paymentIds = Db::getCol($query);
            if ($paymentIds) {

                $paymentIdsSql = implode(',', $paymentIds);

                $paymentTable = $prefix . 'payment'; //cpayment или vpayment
                //Найдём дату последнего платежа и сконкатинируем референсы всех платежей
                $query = "SELECT max(PaymentDate) AS LastPaymentDate, min(PaymentDate) AS FirstPaymentDate
                             , GROUP_CONCAT(Name ORDER BY PaymentDate, Name SEPARATOR '\\n') AS PaymentsReference
                          FROM $paymentTable
                          WHERE ID IN ($paymentIdsSql)";

                $paymentsData = Db::getRow($query);
                $lastPaymentDate = $paymentsData['LastPaymentDate'];
                $firstPaymentDate = $paymentsData['FirstPaymentDate'];
                $paymentsReference = $paymentsData['PaymentsReference'];

                //Посчитаем общую сумму всех оплат в валюте счёта, относящихся к данному счёту
                $query = "SELECT SUM(ClearingAmountInInvoiceCurrency)
                                      FROM $paymentInvoiceTable
                          WHERE $paymentIdField IN ($paymentIdsSql) " . $whereInvoice;
                $paymentsClearingAmountInInvoiceCurrency = Db::getOne($query);

                //Отдельно расчитаем CacheOutstandingAmount - сумма задолженности по данному счёту
                $outstandingAmount = $invInfo['Amount'] - $paymentsClearingAmountInInvoiceCurrency;

            } else {
                //Не осталось ни одной оплаты, обнулим кеширующие поля
                $lastPaymentDate = null;
                $firstPaymentDate = null;
                $paymentsReference = null;
                $paymentsClearingAmountInInvoiceCurrency = null;
                $outstandingAmount = $invInfo['Amount'];
            }


            //Запишем получившиеся данные в таблицу cinvoice или vinvoice
            $fieldsData = [
                'CacheLastPaymentDate' => $lastPaymentDate,
                'CacheFirstPaymentDate' => $firstPaymentDate,
                'CachePaymentsReference' => $paymentsReference,
                'CachePaymentsTotalAmount' => $paymentsClearingAmountInInvoiceCurrency,
                'CacheOutstandingAmount' => $outstandingAmount
            ];
            Db::updateRow($fieldsData, $dbTable, $invInfo['ID']);
        }
    }

    /**
     * Автоматически переводит указанный счёт (VInvoice или CInvoice) в статус Part Cleared или Cleared на основе сумма оплат по этому счёту
     * если его текущий статус находится среди так называемых "OpenStatuses"
     * Для CInvoice и VInvoice этот набор "OpenStatuses" различается.
     * Эта проврка на текущий статус сделана для того, чтобы случайно не закрыть Delted или Creadit Noted счёт,
     * а также чтобы не делать переход Cleared -> Part Clerared, т.к. иногда счета закрывают вручную, даже если они не полностью оплачены
     *
     * @param int $invId ID в таблице vinvoice или cinvoice
     * @param string $dbTable имя таблицы vinvoice или cinvoice
     */
    public static function autoClearInvoice($invId, $dbTable)
    {
        //Загрузим сам инвойс
        $query = "SELECT ID
                , CacheOutstandingAmount
                , CachePaymentsTotalAmount
                , Status
                , CurrencyISO
            FROM $dbTable
            WHERE ID = @i";
        if ($invInfo = Db::getRow($query, $invId)) {

            if ($invInfo['Status'] == Finance::INVOICESTATUS_CLEARED) {
                // Счёт уже в статусе "оплачено", этот сатус сбрасывать не будем
                return;
            }

            //Автоматически обновмм статус до Part Cleared или Cleared, если текущий статус среди открытх статуосв
            //Если abs($outstandingAmount) < 1 руб., счёт можно автоматически закрыть
            //Если $outstandingAmount != 0, но $paymentsTotalAmount != 0, значит Part Cleared
            switch ($invInfo['CurrencyISO']) {
                case 'USD':
                    $limitForClearence = 0.04;
                    break;
                case 'EUR':
                    $limitForClearence = 0.03;
                    break;
                case 'RUB':
                default:
                    $limitForClearence = 1;
            }
            $newStatus = null;
            if ($invInfo['CacheOutstandingAmount'] < $limitForClearence) {
                $newStatus = Finance::INVOICESTATUS_CLEARED;
            } elseif ($invInfo['CachePaymentsTotalAmount'] != 0) {
                $newStatus = Finance::INVOICESTATUS_PARTCLEARED;
            } else {
                //Переводим счёт обратно в Open статус
                if ($dbTable === 'vinvoice') {
                    //Со счётом Vendor Invoice всё сложно, т.к. он имеет несколько open статусов, в какой переводить?
                    //Если текущий был Partially Cleared или Cleared, то переведём в статус Open Accepted.
                    //Во всех остальных случаях не будем менять статус вообще!
                    if ($invInfo['Status'] == Finance::INVOICESTATUS_CLEARED || $invInfo['Status'] == Finance::INVOICESTATUS_PARTCLEARED) {
                        $newStatus = Finance::INVOICESTATUS_ACCEPTED;
                    }
                } else {
                    //Для счетов Customer Invoice всё проще - здесь только один Open статус
                    $newStatus = Finance::INVOICESTATUS_OPEN;
                }
            }

            if ($newStatus !== null && $newStatus != $invInfo['Status']) {

                //Проверим, что статус счёта среди открытых статусов, чтобы случайно не обновить Deleted или Credit Noted до Cleared
                //Добавим к открытым статусам и Cleared - например, из оплаты удаляется какой-то счёт, который ранее блы Cleared
                //соответсвенно его нужно пересчитать и он должен стать Partially Cleared
                if ($dbTable === 'vinvoice') {
                    $openStatuses = Finance::$vinvoiceOpenStatuses;
                } else {
                    $openStatuses = Finance::$cinvoiceOpenStatuses;
                }
                if ($invInfo['Status'] == Finance::INVOICESTATUS_CLEARED || isset($openStatuses[$invInfo['Status']])) {

                    //Подготовим объект соответсвующего типа, чтобы сделать обновление с записью логов
                    if ($dbTable === 'vinvoice') {
                        $invObj = new VInvoiceObj();
                    } else {
                        $invObj = new CInvoiceObj();
                    }

                    $invObj->setId($invInfo['ID']);
                    $invObj->updateDb(['Status' => $newStatus], 'Обновлено автоматически на основе суммы оплат');

                }
            }
        }
    }

    /**
     * Возвращает подходящий cssClass для полей Overdue и DueDate. Используется в VInvoiceObj, CInvoiceObj
     * @param string $value не используется, т.к. сюда приходит либо Overdue, либо DueDate - заранее не понятно
     * @param array $data массив со всеми данными сторки, из него будем вытягивать значение Overdue
     * @return string
     */
    public static function getOverdueViewCssClass($value, array $data)
    {
        if (isset($data['Overdue'], $data['Status'])) {

            $status = $data['Status'];
            $module = Request::getModule();
            if (($module === 'CInvoice' && isset(Finance::$cinvoiceOpenStatuses[$status]))
                || ($module === 'VInvoice' && isset(Finance::$vinvoiceOpenStatuses[$status]))
            ) {
                $overdue = (int)$data['Overdue'];
                if ($overdue > 0) {
                    return ' bad';
                } elseif ($overdue >= -3) {
                    return ' warning';
                }
            }
        }

        return '';
    }
}

