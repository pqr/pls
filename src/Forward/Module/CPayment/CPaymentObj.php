<?php
namespace Forward\Module\CPayment;

use Forward\ModuleBase\Payments\PaymentObjAbstract;
use Forward\Field\GeneralFields;
use Forward\Field\PaymentFields;
use Forward\Module\Customer\CustomerObj;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Msg;
use Light\Field\FieldAbstract as F;

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

            PaymentFields::overpayCleared()

        );
    }

    /**
     * @param array $oldData нужны старые данные на случай смены клиента
     * @param Msg $msgBuff
     * @param bool $updateShipmentsGpAndCriteria
     * @param array|null $invoiceIdsAutoClear массив из ID счетов, которые нужно автоматически закрыть с помощью Finance::autoClearInvoice()
     * если этот массив не передан вообще, то все счета будут автоматически закрываться
     * @param string $dbLogMsg
     */
    public function updateCacheAfterSubmit(
        array $oldData,
        Msg $msgBuff = null,
        $updateShipmentsGpAndCriteria = true,
        $invoiceIdsAutoClear = null,
        $dbLogMsg = ''
    ) {
        $this->autoname();

        //Вызываем стандартный метод в классе предке (в абстрактном PaymentObjAbstract)
        parent::updateCacheAfterSubmit($oldData, $msgBuff, $updateShipmentsGpAndCriteria, $invoiceIdsAutoClear, $dbLogMsg);

        //Если изменился клиент, сначала обновим задолженность старого клиента
        if (isset($oldData['CustomerID']) && $oldData['CustomerID'] != $this->_data['CustomerID']) {
            $customerObj = new CustomerObj();
            $customerObj->setId($oldData['CustomerID']);
            $customerObj->loadData();
            $customerObj->updateCacheOpenPaymentsAmount();
            $customerObj->updateCacheOpenAndOverdueInvoicesAmount();
        }

        //Обновим сумму открытых переплат и задолженностей текущего клиента
        $customerObj = new CustomerObj();
        $customerObj->setId($this->_data['CustomerID']);
        $customerObj->loadData();
        $customerObj->updateCacheOpenPaymentsAmount();
        $customerObj->updateCacheOpenAndOverdueInvoicesAmount();

    }

    /**
     * Автоматически заполняет поле Reference у текущей оплаты на основе номеров счетов (через пробел)
     * но только если reference пуст
     */
    private function autoname()
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

    public function updateCacheAfterDelete(array $oldInvoiceIds, $dbLogMsg)
    {
        parent::updateCacheAfterDelete($oldInvoiceIds, $dbLogMsg);

        //Обновим сумму открытых переплат и задолженностей текущего клиента
        $customerObj = new CustomerObj();
        $customerObj->setId($this->_data['CustomerID']);
        $customerObj->loadData();
        $customerObj->updateCacheOpenPaymentsAmount();
        $customerObj->updateCacheOpenAndOverdueInvoicesAmount();
    }
}
