<?php

namespace Forward\ModuleBase\Payments;

use Forward\Classes\Finance;
use Light\Traits\FirstLetterTrait;
use Forward\Module\CInvoice\CInvoiceObj;
use Forward\Module\VInvoice\VInvoiceObj;
use Light\Db;
use Light\Msg;
use Light\ObjAbstract;

abstract class PaymentObjAbstract extends ObjAbstract
{
    use FirstLetterTrait;

    public function getInvoiceIds()
    {
        $firstLetter = $this->getFirstLetter();
        $firstLetterLower = strtolower($firstLetter);
        $query = "SELECT " . $firstLetter . "InvoiceID
                  FROM " . $firstLetterLower . "payment_" . $firstLetterLower . "invoice
                  WHERE " . $firstLetter . "PaymentID = @i
                      AND " . $firstLetter . "PaymentID != 0";
        return Db::getCol($query, $this->getId());
    }

    public function updateCacheAfterSubmit(
        array $oldData,
        Msg $msgBuff = null,
        $updateShipmentsGpAndCriteria = true,
        $dbLogMsg = ''
    ): void {
        if (isset($oldData['InvoiceIds'])) {
            $oldInvoiceIds = $oldData['InvoiceIds'];
        } else {
            $oldInvoiceIds = [];
        }

        //Загрузим новые ID счетов
        $newInvoiceIds = $this->getInvoiceIds();

        //Объединим оба массива с ID счетов
        $allInvoiceIds = array_filter(array_unique(array_merge($oldInvoiceIds, $newInvoiceIds)));
        if ($allInvoiceIds) {
            $this->updateInvoicesCache($allInvoiceIds);
        }

        //После обновления кеширующей таблицы cinvoice_file_amount или vinvoice_file_amount
        //нужно обновить и EGP, GP, Deivation, Criteria и Customer Credit по всем перевозкам
        if ($updateShipmentsGpAndCriteria) {
            $this->updateFilesCache($allInvoiceIds, $dbLogMsg);
        }

        $this->updateCacheOverpay();
    }

    /**
     * Обновляет кеши указанных счетов. Вызывается из updateCacheAfterSubmit() и из updateCacheAfterDelete()
     * В случае вызова из updateCacheAfterSubmit() получаем также второй параметр - массив счетов по которым нужно
     * обновить статус Open / Part Cleared, Cleared
     */
    protected function updateInvoicesCache(array $invoicesIds)
    {
        $firstLetter = $this->getFirstLetter();
        //Пройдёмся по всем счетам, обнвляя их кеш
        foreach ($invoicesIds as $invId) {
            //Обновим кеширующие поля связанные с оплатой и оставшейся задолженностью
            //CacheLastPaymentDate, CachePaymentsReference, CachePaymentsTotalAmount, CacheOutstandingAmount
            Finance::updateInvoiceCacheClearingFields($firstLetter, $invId);

            //Автоматически переведём счёт в статус Part Cleared, Cleared или Open Accepted на основе сумм оплат
            Finance::autoClearInvoice($firstLetter, $invId);

            //Обновим таблицу сinvoice_file_amount или vinvoice_file_amount в которой каждому счёту+файлу сопоставлена сумма
            //Внутри используется дата CacheLastPaymentDate, которая могла измениться в предыдущей строке
            $invObj = $firstLetter === 'C' ? new CInvoiceObj() : new VInvoiceObj();
            $invObj->setId($invId);
            if ($invObj->loadData()) {
                $invObj->updateCacheAfterPaymentSubmitOrDelete();
            }
        }
    }

    protected function updateFilesCache($invoicesIds, $dbLogMsg)
    {
        if (!$invoicesIds) {
            return;
        }

        $firstLetter = $this->getFirstLetter();
        $firstLetterLower = strtolower($firstLetter);

        $fileNumbers = Db::getCol(
            "SELECT FileNumber
             FROM {$firstLetterLower}invoice_file_amount vfa
             WHERE {$firstLetter}InvoiceID IN (@a)",
            $invoicesIds
        );

        if (!$fileNumbers) {
            return;
        }

        Finance::updateShipmentsGpAndCriteria($fileNumbers, $dbLogMsg, $firstLetter);
    }

    public function updateCacheOverpay()
    {
        $paymentAmount = $this->_data['Amount'];
        $firstLetter = $this->getFirstLetter();
        $firstLetterLower = strtolower($firstLetter);
        $clearingAmount = Db::getOne(
            "SELECT SUM(ClearingAmountInPaymentCurrency)
             FROM {$firstLetterLower}payment_{$firstLetterLower}invoice
             WHERE {$firstLetter}PaymentID = " . $this->getId()
        );

        $overpayAmount = $paymentAmount - $clearingAmount;
        return $this->updateDb(['CacheOverpayAmount' => $overpayAmount]);
    }

    public function updateCacheAfterDelete(array $oldInvoiceIds, $dbLogMsg): void
    {
        $this->updateInvoicesCache($oldInvoiceIds);
        $this->updateFilesCache($oldInvoiceIds, $dbLogMsg);
    }
}
