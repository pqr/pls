<?php

namespace Forward\Module\CInvoice;

use Forward\Classes\Constant;
use Forward\Classes\Finance;
use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Forward\Module\Customer\CustomerObj;
use Forward\ModuleBase\Invoices\InvoiceObjAbstract;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Msg;

class CInvoiceObj extends InvoiceObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('cinvoice');

        $this->addFields(

            F::createString(
                'Name',
                [
                    'name' => 'Inv. No',
                    'tableWidthExcel' => 12,
                ]
            ),
            GeneralFields::companyId(),
            GeneralFields::bankId(),
            GeneralFields::customerId(true, false), //editMandatory => true, filter => false

            GeneralFields::customerAccountId(),
            InvoiceFields::docType(),
            F::createSelect(
                'Status',
                [
                    'name' => 'Payment Status',
                    'options' => Finance::$cinvoiceStatuses,
                    'empty' => false,
                    'sqlSelectMandatory' => true,
                ]
            ),
            InvoiceFields::cStatusIco(),
            F::createText(
                'InvoiceTo',
                [
                    'name' => 'Invoice To',
                    'rows' => 5,
                    'upper' => true,
                    'getHtmlFunction' => function ($value, array $data, $outHtml) {
                        $lines = explode("\n", $value);
                        $htmlLines = [];
                        foreach ($lines as $i => $line) {
                            $htmlLine = \htmlspecialchars($line);
                            if ($i === 0) {
                                $htmlLine = "<strong>$htmlLine</strong>";
                            }
                            $htmlLines[] = $htmlLine;
                        }
                        return implode('<br>', $htmlLines);
                    },
                ]
            ),
            InvoiceFields::shippingDocument(),
            F::createString('ShipmentReference', ['name' => 'Shipment Reference', 'upper' => true]),
            GeneralFields::consigneeReference(),
            F::createString('PaymentTerms', ['name' => 'Payment Terms', 'upper' => true]),
            F::createInt('Pieces'),
            CF::incoterms(),
            GeneralFields::incotermsLocation(),
            F::createString('DepartureLocationName', ['name' => 'Departure', 'upper' => true]),
            F::createString('ArrivalLocationName', ['name' => 'Arrival', 'upper' => true]),
            CF::grossWeight(),
            CF::chargeableWeight(),
            F::createMoney('Amount', ['modes' => F::MODE_READONLY | F::MODE_LOG]),
            CF::currency(),
            CF::currency('Payment')->setDefault('RUB')->setOptions(['RUB' => 'RUB', 'USD' => 'USD', 'EUR' => 'EUR']),
            CF::comments(['rows' => 2, 'ajax' => false]),
            InvoiceFields::docDate(true), //editMandatory = true
            InvoiceFields::dueDate(), //поле не обязательно к заполнению, будет вычеслено автоматически при сохранении

            F::createInt(
                'Overdue',
                [
                    'name' => 'Overdue',
                    'nameTable' => "Overdue\n(days)",
                    'sqlSelectReqires' => 'Status',
                    'tableAlign' => 'center',
                    'sqlSelect' => "IF(Status IN (" . implode(', ', Finance::$cinvoiceOpenStatuses) . ")
                           , DATEDIFF(CURDATE(), DueDate)
                           , -999)",
                    //Определим функцию, которая в зависимости от значения Overdue окрасит ячеку таблицы в класс bad или warning
                    'viewCssClass' => Finance::getOverdueViewCssClass(...),
                    //Определим функцию, которая очистит ячейку Overdue, если статус счёта CLEARD или CREDITNOTED
                    'getHtmlFunction' => function ($value, $data, $outHtml, $forTable) {
                        if (isset($data['Status'])) {
                            $status = $data['Status'];
                            if (!isset(Finance::$cinvoiceOpenStatuses[$status])) {
                                $outHtml = '';
                            }
                        }

                        return $outHtml;
                    },
                ]
            ),

            GeneralFields::ratePercent(),

            GeneralFields::cactName(),
            GeneralFields::cactDate(),
            GeneralFields::cactAmount(),

            F::createMoney(
                'AmountInUSD',
                [
                    'name' => 'Amount in USD',
                    'title' => 'Фиксированная сумма в USD для статистики',
                    'currencyIso' => 'USD',
                ]
            )

        );

        $this->addStandrtFields();
    }

    public function updateAmount($dbLogMsg = ''): void
    {
        $amount = Db::getOne("SELECT SUM(Amount) FROM cinvoiceline WHERE CInvoiceID = @i", $this->getId());
        $this->updateDb(['Amount' => $amount], $dbLogMsg);
    }

    /**
     * Этот метод обновляет Cost-sheet'ы по всем файлам относящимся к текущему счёту
     * Метод пытается заполнить поле InvNum в блоке Estimated Income в тех строках Cost-sheet'а, где InvNum пусто
     * Алгоритм пытается сравнить строки Cost-sheet'а со строками счёта по суммам Amount
     *
     * Метод вызывается при сохранении инвойса в модуле CInvoice и при добавлении счёта из 1С через вызов updateAllCache
     *
     */
    private function guessAndUpdateCostSheetsInvNum(): void
    {
        //Загрузим информацию по всем строкам счёта
        $invLines = Db::getAll(
            "SELECT FileNumber, Amount
             FROM cinvoiceline
             WHERE FileNumber != 0 AND CInvoiceID = " . $this->getId()
        );
        if (!$invLines) {
            return;
        }

        //Перегруппируем массив $invLines по номерам файлов
        $invLinesPerFile = [];
        foreach ($invLines as $invLine) {
            $invLinesPerFile[$invLine['FileNumber']][] = $invLine;
        }

        //Подготовим массив, куда попадут ID всех строк из всех Cost-sheet'ов, которые нужно обновить
        //Обновим все строки Cost-sheet'ов одним запросом в самом конце работы метода
        $csLinesIdsToBeUpdated = [];

        //Пройдём по каждому файлу в отдельности
        foreach ($invLinesPerFile as $fileNumber => $invLinesForFile) {
            //Проверим существование этого файла
            $shipmentId = Db::getOne("SELECT ID FROM shipment WHERE Name = " . $fileNumber);
            if (!$shipmentId) {
                continue;
            }

            //Попробуем загрузить все строки Cost-sheet'а по найденной перевозке
            $csLines = Db::getAll(
                "SELECT ID, Amount, CurrencyISO, InvNum FROM csincome WHERE ShipmentID = " . $shipmentId
            );
            if (!$csLines) {
                continue;
            }

            //Пройдём по каждой строке в исходном счёте по данному файлу (массив $invLinesForFile) и попробуем найти соответсвие в $csLines
            /** @var array $invLinesForFile */
            foreach ($invLinesForFile as $invLine) {
                foreach ($csLines as $index => $csLine) {
                    //Проверяем совпадение по сумме Amount
                    if ($csLine['Amount'] == $invLine['Amount'] && $csLine['CurrencyISO'] == $this->_data['CurrencyISO']) {
                        //Нашли совпадение, обновим строку кост-шита
                        //запишем ID строки в спец массив, чтобы в конце сделать обновление одним запросом
                        $csLinesIdsToBeUpdated[] = $csLine['ID'];
                        //Уберём стркоу Cost-sheet'а из списка $csLines, чтобы при следующем проходе по следующей
                        //строке счёта уже не натыкаться на обновлённую $csLine
                        unset($csLines[$index]);
                    }
                }
            }
        }

        //Если после прохода по всем Cost-sheet'ам накопился массив из ID каких-то строк, обновим эти строки в базе!
        if ($csLinesIdsToBeUpdated) {
            Db::query(
                "UPDATE csincome
                 SET InvNum = '" . Db::esc($this->_data['Name']) . "'
                 WHERE ID IN (" . implode(',', $csLinesIdsToBeUpdated) . ")"
            );
        }
    }

    /**
     * Обновляет все кеширующе поля связанные с изменением или созданием счёта
     * - кеширующие поля внутри линий счёта
     * - кеширующие поля по остаткам и задолженностям внутри самого счёта
     * - суммы по файлам в таблице cinvoice_file_amount
     * - GP по файлам
     * - критерии по файлам
     *
     * @param array $oldData опционально можно передать массив старых файлов связанных с этим счётом до обновления
     * таким образом будут пересчитаны GP и критерии и по старым файлам тоже. Это полезно, когда при сохранении счёта
     * какие-то строки удаляются
     *
     * @param Msg $msgBuff опционально можно передать объект-буффер логов. Это используется при сохранении счёта через
     * веб интерфейс.
     * @param bool $updateShipmentsGpAndCriteria иногда нужно отключать автоматическое обновление GP по всем файлам,
     * если, например, создаётся несколько счетов подряд через Massaction - в этом случае GP достаточно обновить по всем
     * файлам в самом конце, а не после создания каждого счёта
     * @param string $dbLogMsg
     */
    public function updateCacheAfterSubmit(
        array $oldData,
        Msg $msgBuff = null,
        $updateShipmentsGpAndCriteria = true,
        $dbLogMsg = ''
    ): void {
        if (isset($oldData['FileNumbers'])) {
            $oldFileNumbers = $oldData['FileNumbers'];
        } else {
            $oldFileNumbers = [];
        }

        // Если имя счета изменилось, запустим метод переименования и в платежах, и в Cost-sheet`ах
        if ($this['Name'] === '' && !Constant::isOneCCompany($this['CompanyID'])) {
            $this->autogenerateName();
        }

        if (isset($oldData['Name']) && (string)$oldData['Name'] !== (string)$this['Name']) {
            $this->renameAfterInsertUpdate($oldData['Name'], $this['Name']);
        }

        //Обновим дату DueDate, но только если она пуста. Если она уже заполнена - возможно, это правильное значение введённое пользователем
        $this->autocalculateInvoiceDueDate($msgBuff, $dbLogMsg);

        //Обновим поле cinvoice.Amount на основе подтаблицы с линиями = sum(cinvoiceline.Amount)
        $this->updateAmount($dbLogMsg);

        $this->updateAmountInUsd();

        //Обновим поля поля CacheLastPaymentDate, CachePaymentsReference, CachePaymentsTotalAmount, CacheOutstandingAmount
        //Эти поля связаны с оплатами и казалось бы они не должны измениться при сохранении счёта
        //Однако, они могут измениться в двух случаях:
        //1. у счёта изменилась валюта: влияет на CachePaymentsTotalAmount
        //2. или у счёиа измениолась сумма: влияет на CacheOutstandingAmount
        if (!isset($oldData['Amount'], $oldData['CurrencyISO'])
            || $oldData['Amount'] != $this->_data['Amount']
            || $oldData['CurrencyISO'] != $this->_data['CurrencyISO']
        ) {
            Finance::updateInvoiceCacheClearingFields('C', $this->getId());
        }

        //Обновим таблицу cinvoice_file_amount в которой каждому счёту+файлу сопоставлена сумма
        $this->updateAmountPerFile();
        $this->updateAmountPerFileCurrencyRate();

        //Вызовем специальный метод, который попробует угадать какие строки из Cost-sheet'ов соответсвуют этому счёту
        //и добавим номера инвойсов в поле InvNum в Cost-sheet
        //Добавляем в Cost-sheet, только если это не удалённый счёт
        $this->guessAndUpdateCostSheetsInvNum();

        if ($updateShipmentsGpAndCriteria) {
            //После обновления кеширующей таблицы cinvoice_file_amount, можно обновить и GP по всем файлам,
            //которые участвовали при сохранении данного счёта
            $this->updateShipmentsGpAndCriteria($oldFileNumbers, $dbLogMsg);
        }

        //Обновим сумму открытых счетов клиента фигурирующего в данном счёте
        $this->updateCustomerCacheOpenAndOverdueInvoicesAmount();
    }

    public function updateCustomerCacheOpenAndOverdueInvoicesAmount(): void
    {
        $customerObj = new CustomerObj();
        $customerObj->setId($this->_data['CustomerID']);
        if ($customerObj->loadData()) {
            $customerObj->updateCacheOpenAndOverdueInvoicesAmount();
        }
    }

    /**
     * Записывает в поле Name занчение поля ID - нужно для счетов создаваемых в базе от офшорных компаний
     */
    public function autogenerateName(): void
    {
        $name = $this->getId();
        if ($this['CompanyID'] == Constant::OC_MAXVEL) {
            $name = "PLS-$name";
        }
        $this->updateDb(['Name' => $name], 'Auto');
    }

    public function updateCacheAfterDelete(array $oldFileNumbers, $dbLogMsg)
    {
        //Вызовем метод предка, чтобы обновить стандартные поля для объектов унаследованных от InvoiceObjAbstract
        parent::updateCacheAfterDelete($oldFileNumbers, $dbLogMsg);
        //Обновим сумму открытых счетов клиента фигурирующего в данном счёте
        $this->updateCustomerCacheOpenAndOverdueInvoicesAmount();
    }

    private function updateAmountInUsd(): void
    {
        if ($this->_data['CurrencyISO'] === 'USD') {
            $this->updateDb(
                ['AmountInUSD' => $this->_data['Amount']],
                'Автоматическое обновление на основе суммы счёта'
            );
        }
    }
}
