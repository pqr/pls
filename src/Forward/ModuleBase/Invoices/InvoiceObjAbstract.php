<?php
namespace Forward\ModuleBase\Invoices;

use Forward\Classes\Finance;
use Light\Currency;
use Light\Traits\FirstLetterTrait;
use Forward\Field\InvoiceFields;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Msg;
use Light\ObjAbstract;

/**
 * Общий класс для CInvoiceObj и VInvoiceObj
 */
abstract class InvoiceObjAbstract extends ObjAbstract
{
    use FirstLetterTrait;

    protected function addStandrtFields()
    {
        $this->addFields(
            [
                InvoiceFields::cacheFirstPaymentDate(),
                InvoiceFields::cacheLastPaymentDate(),
                InvoiceFields::cachePaymentsReference(),
                InvoiceFields::cachePaymentsTotalAmount(),
                InvoiceFields::cacheOutstandingAmount(),
                F::createCheckbox(
                    'CopyRequestReceived',
                    [
                        'name' => 'Копия Заявки',
                        'title' => 'Копия Заявки получена',
                        'nameTable' => 'КЗ',
                        'nameTableSettings' => 'Копия Заявки получена',
                        'ajax' => true
                    ]
                ),
                F::createCheckbox(
                    'CopyInvoiceReceived',
                    [
                        'name' => 'Копия Счёта',
                        'title' => 'Копия Счёта получена',
                        'nameTable' => 'КС',
                        'nameTableSettings' => 'Копия Счёта получена',
                        'ajax' => true
                    ]
                ),
                F::createCheckbox(
                    'CopyFakturaReceived',
                    [
                        'name' => 'Копия Счёт-Фактуры',
                        'title' => 'Копия Счёт-Фактуры получена',
                        'nameTable' => 'КСФ',
                        'nameTableSettings' => 'Копия Счет-Фактуры получена',
                        'ajax' => true
                    ]
                ),
                F::createCheckbox(
                    'OriginalRequestReceived',
                    [
                        'name' => 'Оригинал Заявки',
                        'title' => 'Оригинал Заявки получен',
                        'nameTable' => 'ОЗ',
                        'nameTableSettings' => 'Оригинал Заявки получен',
                        'ajax' => true
                    ]
                ),
                F::createCheckbox(
                    'OriginalInvoiceReceived',
                    [
                        'name' => 'Оригинал Счёта',
                        'title' => 'Оригинал Счёта получен',
                        'nameTable' => 'ОС',
                        'nameTableSettings' => 'Оригинал Счёта получен',
                        'ajax' => true
                    ]
                ),
                F::createCheckbox(
                    'OriginalFakturaReceived',
                    [
                        'name' => 'Оригинал Счёт-Фактуры',
                        'title' => 'Оригинал Заявки получен',
                        'nameTable' => 'ОСФ',
                        'nameTableSettings' => 'Оригинал Счёт-Фактуры получен',
                        'ajax' => true
                    ]
                ),
                F::createCheckbox(
                    'CopyActReceived',
                    [
                        'name' => 'Копия Акта',
                        'title' => 'Копия Акта получена',
                        'nameTable' => 'КА',
                        'nameTableSettings' => 'Копия Акта получена',
                        'ajax' => true
                    ]
                ),
                F::createCheckbox(
                    'OriginalActReceived',
                    [
                        'name' => 'Оригинал Акта',
                        'title' => 'Оригинал Акта получен',
                        'nameTable' => 'ОА',
                        'nameTableSettings' => 'Оригинал Акта получен',
                        'ajax' => true
                    ]
                )

            ]
        );
    }

    abstract public function updateCacheAfterSubmit(
        array $oldData,
        Msg $msgBuff = null,
        $updateShipmentsGpAndCriteria = true,
        $dbLogMsg = ''
    );

    /**
     * Метод вызывается из модулей CPayment и VPayment для каждого привязанного счёта, чтобы обновить нужные
     * кешируюище поля внутри счёта
     */
    public function updateCacheAfterPaymentSubmitOrDelete(): void
    {
        //Прежде всего нужно обновить поля AmountinUSD, AmountinRUB, RateToUSD, RateToRUB в таблицах
        //cinvoice_file_amount и vinvoice_file_amount, т.к. перечёт в другую валюту делается по курсу на дату оплаты
        //(после сохранения оплаты эта дата могла измениться)
        $this->updateAmountPerFileCurrencyRate();
    }

    public function updateShipmentsGpAndCriteria(array $oldFileNumbers, string $dbLogMsg): void
    {
        $newFileNumbers = $this->getFileNumbresFromCacheTable();
        $allFileNumbers = array_filter(array_unique(array_merge($oldFileNumbers, $newFileNumbers)));
        Finance::updateShipmentsGpAndCriteria($allFileNumbers, $dbLogMsg, $this->getFirstLetter());
    }

    public function updateCacheAfterDelete(array $oldFileNumbers, string $dbLogMsg): void
    {
        $this->updateShipmentsGpAndCriteria($oldFileNumbers, $dbLogMsg);

        //Удалим данные из кэширующей таблицы cinvoice_file_amount/cinvoice_file_amount
        Db::query(
            "DELETE FROM {$this->getDbTable()}_file_amount
            WHERE {$this->getFirstLetter()}InvoiceID = @i",
            $this->getId()
        );
    }

    public function getFileNumbresFromCacheTable()
    {
        if ($this->getId() == 0) {
            return [];
        }

        return Db::getCol(
            "SELECT FileNumber
             FROM {$this->getDbTable()}_file_amount
             WHERE FileNumber != 0
             AND {$this->getFirstLetter()}InvoiceID = @i",
            $this->getId()
        );
    }

    /**
     * Обновляет поля cinvoice_file_amount.Amount или vinvoice_file_amount.Amount
     */
    public function updateAmountPerFile(): void
    {
        $firstLetter = $this->getFirstLetter();
        $firstLetterLower = strtolower($firstLetter);

        //Перед тем как вставлять новые данные со сгруппированными суммами удалим все старые данные по этому счёту
        Db::query(
            "DELETE FROM {$firstLetterLower}invoice_file_amount
            WHERE {$firstLetter}InvoiceID = @i",
            $this->getId()
        );

        $insertQuery = "REPLACE INTO {$firstLetterLower}invoice_file_amount
                            ({$firstLetter}InvoiceID,
                             FileNumber,
                             Amount
                            )
                        SELECT " . $this->getId() . "
                            , FileNumber
                            , SUM(" . ($firstLetter === 'C' ? '' : 'Final') . "Amount)
                        FROM {$firstLetterLower}invoiceline
                        WHERE {$firstLetter}InvoiceID = " . $this->getId() . "
                        GROUP BY FileNumber";

        Db::query($insertQuery);
    }

    /**
     * Обновляет поля связанные с пересчётом в валюты USD и RUB в таблицах cinvoice_file_amount и vinvoice_file_amount:
     * AmountinUSD, AmountinRUB, RateToUSD, RateToRUB
     * Курс пересчёта считается на дату оплаты (если счёт оплачен) или на дату самого счёта (если не оплачен) - т.е. курс
     * зависит от наличия оплат. При создании новых Customer Payment или Vendor Payment этот метод вызывается
     * для каждого счёта относящегося к оплате, чтобы обновить курсы (поэтому метод публичный)
     */
    public function updateAmountPerFileCurrencyRate(): void
    {
        //Сумму счёта нужно пересчитать в USD и в RUB для отчётности
        //В качестве даты пересчёта используем дату оплаты или дату счёта, если оплаты ещё не было
        $firstLetter = $this->getFirstLetter();

        $rateToUsd = 0;
        if ($this->_data['CacheLastPaymentDate'] !== '0000-00-00'
            && $this->_data['CacheLastPaymentDate'] !== null) {

            $convertDate = $this->_data['CacheLastPaymentDate'];

            //Самый простой вариант использовать дату оплаты для поиска курса ЦБ. Однако, возможно в этой оплате
            //указан какой-то особый курс в подтаблице CInvoices. Если же оплат несколько, нужно как-то усреднять
            //Пока сделаем по простому: если оплата ровно одна - используем её курс
            $firstLetterLower = strtolower($firstLetter);
            $payments = Db::getAll(
                "SELECT p.ID
                     , p.CurrencyISO
                     , pi.RateToInvoiceCurrency
                 FROM {$firstLetterLower}payment_{$firstLetterLower}invoice pi
                     , {$firstLetterLower}payment p
                 WHERE pi.{$firstLetter}PaymentID = p.ID
                    AND pi.{$firstLetter}InvoiceID = @i",
                $this->getId()
            );

            if (count($payments) == 1) {
                //Нашли ровно одну оплату - используем её курс!
                $theOnlyPayment = $payments[0];
                if ($theOnlyPayment['CurrencyISO'] === 'USD') {
                    $rateToUsd = $theOnlyPayment['RateToInvoiceCurrency'];
                }
            }

        } else {
            $convertDate = $this->_data['DocDate'];
        }

        if (!$rateToUsd) {
            if ($this->_data['CurrencyISO'] === 'USD') {
                $rateToUsd = 1;
            } else {
                $rateToUsd = Currency::getRate($this->_data['CurrencyISO'], 'USD', $convertDate);
            }
        }

        $query = "UPDATE " . $this->getDbTable() . "_file_amount
                  SET AmountinUSD = Amount * " . $rateToUsd . "
                  WHERE " . $this->getFirstLetter() . "InvoiceID = " . $this->getId();

        Db::query($query);
    }

    protected function autocalculateInvoiceDueDate(Msg $msgBuff = null, $dbLogMsg = '')
    {
        if ($this->_data['DueDate'] !== '0000-00-00') {
            //Если дата DueDate уже заполнена, автокалькуляцию не делаем
            return;
        }

        // Сначала определим дату от которой отсчитывать
        if ($this->_data['DocDate'] == '0000-00-00') {
            return;
        }

        $dateFrom = $this->_data['DocDate'];


        if ($this->getFirstLetter() == 'C') {
            $contrDbTable = 'customeraccount';
            $contrId = $this->_data['CustomerAccountID'];
        } else {
            $contrDbTable = 'vendor';
            $contrId = $this->_data['VendorID'];
        }

        // Загрузим информацию из карточки аккаунта
        $creditPeriod = (int)Db::getOne(
            "SELECT CreditPeriod
            FROM {$contrDbTable}
            WHERE ID = " . (int)$contrId
        );

        if (!$creditPeriod) {
            // Не смогли найти информацию по кредитным условиям в базе, автокалькуляцию не делаем
            return;
        }

        $dueDate = date('Y-m-d', strtotime("$dateFrom +$creditPeriod days"));

        // Обновляем в базе
        if ($dbLogMsg) {
            $dbLogMsg .= "\n";
        }
        $dbLogMsg .= 'Autocalculated';
        $this->updateDb(['DueDate' => $dueDate], $dbLogMsg);

        if ($msgBuff) {
            $msgBuff->addInfo('Due Date autocalculated ' . FieldDate::beautify($dueDate));
        }
    }

    /**
     * Если пользователь в процессе редактирования счёта изменил поле Reference, то нужно изменить его и во многих других
     * местах
     * @param string $oldName
     * @param string $newName
     */
    public function renameAfterInsertUpdate($oldName, $newName): void
    {
        $firstLetter = $this->getFirstLetter();
        $firstLetterLower = strtolower($firstLetter);

        //Обновим связи с оплатами
        Db::query(
            "UPDATE {$firstLetterLower}payment_{$firstLetterLower}invoice
             SET {$firstLetter}InvoiceName = @s_newName
             WHERE {$firstLetter}InvoiceID = @i_ID",
            $newName,
            $this->getId()
        );

        //Переименуем в Cost-sheet'ах
        //Найдём все перевозки к которым относится данный счёт. Используем при этом табилцу *invoiceline, а не
        //*invoice_file_amount т.к. последняя ещё могла не обновиться
        $shipmentIds = Db::getCol(
            "SELECT DISTINCT s.ID
             FROM {$firstLetterLower}invoiceline il, shipment s
             WHERE il.FileNumber = s.Name
                 AND il.{$firstLetter}InvoiceID = @i_ID",
            $this->getId()
        );

        $incomeOrExpenses = $firstLetter == 'C' ? 'income' : 'expenses';
        $cstable = 'cs' . $incomeOrExpenses;

        if ($shipmentIds) {
            //Обновим Cost-sheet'ы
            $query = "UPDATE $cstable cs
                      SET cs.InvNum = @s_newName
                      WHERE cs.InvNum = @s_oldName
                          AND cs.InvNum != ''
                          AND cs.ShipmentID IN (@a_shipmentIds)";

            Db::query($query, $newName, $oldName, $shipmentIds);
        }
    }
}
