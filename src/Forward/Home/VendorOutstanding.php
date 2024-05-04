<?php

namespace Forward\Home;

use Forward\Application;
use Forward\Classes\Finance;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoney;
use Light\HomeBase\HomeBlockAbstract;
use Light\Link;
use Light\User;

class VendorOutstanding extends HomeBlockAbstract
{
    protected static $title = 'Vendor Outstanding';

    public function __construct(int $userId)
    {
        parent::__construct($userId);

        $this->tableLinkModule = 'Vendor';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            F::createString('Name', ['name' => 'Vendor']),
            F::createHtml(
                'Outstanding',
                [
                    'title' => 'Sum of Amount of Open & Part Cleared Vendor Invoices',
                    'tableAlign' => 'right',
                ]
            ),

            F::createHtml(
                'OverdueCL',
                [
                    'name' => 'Overdue CL',
                    'title' => 'Overdue Credit Limit',
                    'tableAlign' => 'right',
                    'viewCssClass' => 'bad',
                ]
            ),

            F::createHtml(
                'OverdueTime',
                [
                    'name' => 'Overdue Time',
                    'title' => 'Overdue by Invoices Due Date',
                    'tableAlign' => 'right',
                    'viewCssClass' => 'bad',
                ]
            )

        );
    }

    #[\Override]
    protected function getDataQuery(int $userId)
    {
        return '';
    }

    #[\Override]
    protected function tableLoadData(int $userId)
    {
        if (!User::hasSpecialAccess(Application::SA_HOME_VOUTSTANDING, $userId)) {
            return;
        }

        $totalOutstanding = [
            'EUR' => 0,
            'RUB' => 0,
            'USD' => 0,
        ];

        $totalOverdueCl = [
            'EUR' => 0,
            'RUB' => 0,
            'USD' => 0,
        ];

        $totalOverdueTime = [
            'EUR' => 0,
            'RUB' => 0,
            'USD' => 0,
        ];

        //Найдём всех подрядчиков у которых был хотя бы один счёт
        $query = "SELECT ID AS i, ID, Name, CreditAmount, CreditCurrencyISO FROM vendor
                  WHERE EXISTS(SELECT VendorID FROM vinvoice WHERE VendorID = vendor.ID)
                      AND Active = 1
                  ORDER BY Name";
        $data = Db::getAssoc($query);

        if (count($data)) {
            //Соберём ID подрядчиков
            $vendorIds = array_keys($data);

            //Объект Currency нужен для получения символов валют
            //Найдём Outstanding (сумма неопрлаченных счетов по подрядчикам
            $query = "SELECT sum(vinvoice.Amount)  - sum(IF(vinvoice.Status=" . Finance::INVOICESTATUS_PARTCLEARED . ", IFNULL(vinvoice.CachePaymentsTotalAmount, 0), 0)) AS Amount,
                          vinvoice.CurrencyISO,
                          vinvoice.VendorID
                      FROM vinvoice
                      WHERE vinvoice.VendorID IN (@a)
                          AND vinvoice.Status IN (@a)
                      GROUP BY VendorID, CurrencyISO";

            $res = Db::query($query, $vendorIds, Finance::$vinvoiceOpenStatuses);
            while ($row = $res->fetch_assoc()) {
                $vendorId = $row['VendorID'];
                $currencyIso = $row['CurrencyISO'];

                // Для новых экзотических валют, помимо RUB, USD, EUR, динамически проверим и добавим записи в массивы
                if (!isset($totalOutstanding[$currencyIso])) {
                    $totalOutstanding[$currencyIso] = 0;
                }
                if (!isset($totalOverdueCl[$currencyIso])) {
                    $totalOverdueCl[$currencyIso] = 0;
                }
                if (!isset($totalOverdueTime[$currencyIso])) {
                    $totalOverdueTime[$currencyIso] = 0;
                }

                $totalOutstanding[$currencyIso] += $row['Amount'];

                $outstanding = FieldMoney::beautify($row['Amount'], true, $currencyIso);
                $outstanding = Finance::getVInvoiceLink($outstanding, $vendorId, false, $currencyIso);

                //Если в текущей строке посчитана сумма в валюте == кредитной валюте => сразу посчитаем превышение кредитной линии
                if ($currencyIso == $data[$vendorId]['CreditCurrencyISO']) {
                    if ($row['Amount'] > $data[$vendorId]['CreditAmount']) {
                        //Подкрасим основную сумму в красный
                        $outstanding = '<span class="bad">' . $outstanding . '</span>';

                        //Дополнительно заполним колонку OverdueCL
                        $overdueClAmount = $row['Amount'] - $data[$vendorId]['CreditAmount'];
                        $totalOverdueCl[$currencyIso] += $overdueClAmount;
                        $overdueCl = FieldMoney::beautify($overdueClAmount, true, $currencyIso);
                        $data[$vendorId]['OverdueCL'] = $overdueCl;
                    } elseif ($row['Amount'] >= 0.8 * $data[$vendorId]['CreditAmount'] && $row['Amount'] > 0) {
                        //Подкрасим основную сумму в оранжевый
                        $outstanding = '<span class="warning"">' . $outstanding . '</span>';
                    }
                }

                //Заполняем поле Outstanding массивом CurrencyISO => Amount (для каждой валюты будет создан новый элемент массива
                $data[$vendorId]['Outstanding'][$currencyIso] = $outstanding;
            }

            $query = "SELECT sum(vinvoice.Amount)  - sum(IF(vinvoice.Status=" . Finance::INVOICESTATUS_PARTCLEARED . ", IFNULL(vinvoice.CachePaymentsTotalAmount, 0), 0)) AS Amount,
                          vinvoice.CurrencyISO,
                          vinvoice.VendorID
                      FROM vinvoice
                      WHERE vinvoice.VendorID IN (@a)
                          AND vinvoice.DueDate < CURDATE()
                          AND vinvoice.Status IN (@a)
                      GROUP BY VendorID, CurrencyISO";

            $res = Db::query($query, $vendorIds, Finance::$vinvoiceOpenStatuses);
            while ($row = $res->fetch_assoc()) {
                $vendorId = $row['VendorID'];
                $currencyIso = $row['CurrencyISO'];

                $totalOverdueTime[$currencyIso] += $row['Amount'];
                $overdueTime = FieldMoney::beautify($row['Amount'], true, $currencyIso);
                $overdueTime = Finance::getVInvoiceLink($overdueTime, $vendorId, false, $currencyIso, true);

                //Заполняем поле Outstanding массивом CurrencyISO => Amount (для каждой валюты будет создан новый элемент массива
                $data[$vendorId]['OverdueTime'][$currencyIso] = $overdueTime;
            }

            //Кода всё заполнено данными, объединим массивы в ячейке Outstanding и OverdueTime в одно текстовое поле
            foreach ($data as $key => &$row) {
                if (!isset($row['Outstanding'], $row['OverdueTime'])) {
                    // Если у данного вендора нет задолженности вообще, то скрываем его из списка
                    unset($data[$key]);
                } else {
                    if (isset($row['Outstanding'])) {
                        $row['Outstanding'] = implode('<br>', $row['Outstanding']);
                    }
                    if (isset($row['OverdueTime'])) {
                        $row['OverdueTime'] = implode('<br>', $row['OverdueTime']);
                    }
                }
            }
            unset($row);
        }

        $this->tableData = array_values($data);

        $this->tableDataTotal = [
            'Outstanding' => $this->getTotalHtml($totalOutstanding),
            'OverdueCL' => $this->getTotalHtml($totalOverdueCl),
            'OverdueTime' => $this->getTotalHtml($totalOverdueTime),
        ];
    }

    private function getTotalHtml(array $amountsPerCurrency): string
    {
        $lines = [];
        foreach ($amountsPerCurrency as $currencyIso => $amount) {
            if ($amount) {
                $lines[] = FieldMoney::beautify($amount, true, $currencyIso);
            }
        }
        return implode('<br>', $lines);
    }

    #[\Override]
    protected function getCountForTitle()
    {
        // Отобразим число просроченных счетов по клиентам на этом Home
        $overdueCount = Db::getOne(
            "SELECT COUNT(*) FROM vinvoice WHERE Status IN (@a) AND DueDate < CURDATE()",
            Finance::$vinvoiceOpenStatuses
        );

        return Link::create('VInvoice', $overdueCount)
            ->filters(['Overdue' => 'Overdue'])
            ->title('Число просроченных счетов')
            ->getHtml();
    }
}
