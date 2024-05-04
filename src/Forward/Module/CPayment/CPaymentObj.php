<?php

namespace Forward\Module\CPayment;

use Forward\Field\GeneralFields;
use Forward\Field\PaymentFields;
use Forward\Module\Customer\CustomerObj;
use Forward\Module\Shipment\ShipmentObj;
use Forward\ModuleBase\Payments\PaymentObjAbstract;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Msg;

class CPaymentObj extends PaymentObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('cpayment');

        $this->addFields(

            PaymentFields::name(),

            GeneralFields::companyId(),

            GeneralFields::customerId(true, false), //editMandatory = true

            GeneralFields::customerAccountId(false), //editMandatory = true

            F::createMoney('Amount'),

            CF::currency(),

            PaymentFields::paymentDate(),

            CF::comments(),

            PaymentFields::cacheOverpayAmount(),

            PaymentFields::overpayCleared(),

            F::createDate('PrintedDate', [
                'name' => 'Акт отправлен на печать',
                'ajax' => true
            ]),

            PaymentFields::shipmentFileField(3),
            PaymentFields::shipmentFileField(4)


        );
    }

    /**
     * @param array $oldData нужны старые данные на случай смены клиента
     * @param Msg $msgBuff
     * @param bool $updateShipmentsGpAndCriteria
     * если этот массив не передан вообще, то все счета будут автоматически закрываться
     * @param string $dbLogMsg
     */
    public function updateCacheAfterSubmit(
        array $oldData,
        Msg $msgBuff = null,
        $updateShipmentsGpAndCriteria = true,
        $dbLogMsg = ''
    ): void {
        $this->autoname();

        // Вызываем стандартный метод в классе предке (в абстрактном PaymentObjAbstract)
        parent::updateCacheAfterSubmit($oldData, $msgBuff, $updateShipmentsGpAndCriteria, $dbLogMsg);

        $this->updateCustomerStatistic($oldData);

        $this->updateShipmentsDocsPrintedDate($oldData, $msgBuff);

    }

    /**
     * Автоматически заполняет поле Reference у текущей оплаты на основе номеров счетов (через пробел)
     * но только если reference пуст
     */
    private function autoname(): void
    {
        if ($this->_data['Name'] === '') {

            $query = "SELECT CInvoiceName
                      FROM cpayment_cinvoice
                      WHERE CPaymentID=" . $this->getId();
            $cinvoiceNames = array_filter(Db::getCol($query));
            $newName = implode(' ', $cinvoiceNames);
            $this->updateDb(['Name' => $newName], 'Automatic');
        }
    }

    private function updateCustomerStatistic(array $oldData): void
    {
        // Если изменился клиент, сначала обновим задолженность старого клиента
        if (isset($oldData['CustomerID']) && $oldData['CustomerID'] != $this->_data['CustomerID']) {
            $customerObj = new CustomerObj();
            $customerObj->setId($oldData['CustomerID']);
            $customerObj->loadData();
            $customerObj->updateCacheOpenPaymentsAmount();
            $customerObj->updateCacheOpenAndOverdueInvoicesAmount();
        }

        // Обновим сумму открытых переплат и задолженностей текущего клиента
        $customerObj = new CustomerObj();
        $customerObj->setId($this->_data['CustomerID']);
        $customerObj->loadData();
        $customerObj->updateCacheOpenPaymentsAmount();
        $customerObj->updateCacheOpenAndOverdueInvoicesAmount();
    }

    public function updateCacheAfterDelete(array $oldInvoiceIds, $dbLogMsg): void
    {
        parent::updateCacheAfterDelete($oldInvoiceIds, $dbLogMsg);

        //Обновим сумму открытых переплат и задолженностей текущего клиента
        $customerObj = new CustomerObj();
        $customerObj->setId($this->_data['CustomerID']);
        $customerObj->loadData();
        $customerObj->updateCacheOpenPaymentsAmount();
        $customerObj->updateCacheOpenAndOverdueInvoicesAmount();
    }

    private function updateShipmentsDocsPrintedDate(array $oldData, ?Msg $msgBuff): void
    {
        $oldPrintedDate = $oldData['PrintedDate'] ?? '0000-00-00';
        $newPrintedDate = $this->_data['PrintedDate'];

        if ($oldPrintedDate !== $newPrintedDate && $newPrintedDate !== '0000-00-00') {
            // Нужно обновить поле shipment.DocsPrintedDate
            $shipmentIds = Db::getCol(
                "SELECT DISTINCT s.ID
                FROM shipment s
                    JOIN cinvoice_file_amount cfa ON cfa.FileNumber = s.Name
                    JOIN cpayment_cinvoice cpi ON cpi.CInvoiceID = cfa.CInvoiceID

                WHERE cpi.CPaymentID = @i"
                , $this->getId()
            );
            if ($shipmentIds) {
                $shipmentObjBase = new ShipmentObj();
                $updateData = ['DocsPrintedDate' => $newPrintedDate];
                $dbLogMsg = 'Обновление даты печати документов на основе оплаты ' . $this->getLinkViewDialog();
                foreach ($shipmentIds as $shipmentId) {
                    $shipmentObj = clone $shipmentObjBase;
                    $shipmentObj->setId($shipmentId);
                    $affectedRows = $shipmentObj->updateDb($updateData, $dbLogMsg);
                    if ($affectedRows && $msgBuff) {
                        $msgBuff->addInfo('В перевозке ' . $shipmentObj->getLinkView() . ' обновлена дата печати оригиналов, новое значение: ' . FieldDate::beautify($newPrintedDate));
                    }
                }
            }
        }
    }
}
