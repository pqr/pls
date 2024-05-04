<?php
namespace Forward\ModuleBase\Payments;

use Forward\Application;
use Forward\Classes\Finance;
use Light\Traits\FirstLetterTrait;
use Light\Css;
use Light\Db;
use Light\Js;
use Light\ModuleBase\ModuleAbstract;
use Light\Msg;
use Light\ObjAbstract;
use Light\User;

/**
 * Обший предо для классов модулей CPayment и VPayment - много похожих вещей!
 */
abstract class PaymentModuleAbstract extends ModuleAbstract
{
    use FirstLetterTrait;

    public $folders = true;

    public function __construct()
    {
        parent::__construct();
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

    }

    #[\Override]
    protected function initCssJs()
    {
        parent::initCssJs();

        if ($this->isStateAddNew() || $this->isStateEdit()) {
            Js::add('ModuleBase/Payments/PaymentEdit.js');
            Css::add('PaymentEdit.css');
        }
    }

    #[\Override]
    protected function validateUnique(array $dataFromPost)
    {
    }

    /**
     * Вспомагательная функция - используется для Autocomplete поиска по vinvoice.ID или по vinvoice.Name
     * @param string $keyField
     * @param string $value
     * @return array
     */
    private static function getInvoicesForAjax($keyField, $value)
    {
        $parts = explode("\\", static::class);
        if (end($parts) === 'CPayment') {
            $customerOrVendor = 'Customer';
        } else {
            $customerOrVendor = 'Vendor';
        }

        $value = trim($value);

        $customerOrVendorId = (int)$_GET[$customerOrVendor . 'ID'];
        $accountId = (int)$_GET[$customerOrVendor . 'AccountID'];
        $invoiceTable = $customerOrVendor === 'Customer' ? 'cinvoice' : 'vinvoice';

        $query = "SELECT $keyField AS value
                      , ID
                      , Name
                      , Status
                      , Amount AS InvoiceAmount
                      , CacheOutstandingAmount AS InvoiceOutstandingAmount
                      , CurrencyISO AS InvoiceCurrencyISO
                  FROM $invoiceTable
                  WHERE {$customerOrVendor}ID = @i
                      AND {$customerOrVendor}AccountID = @i";

        if ($keyField === 'ID') {
            $query .= " AND ID = @i";
        } else {
            $query .= " AND Name LIKE @l2";
        }

        $data = [];
        $res = Db::query($query, $customerOrVendorId, $accountId, $value);
        if ($res->num_rows) {
            //Есть результаты - добавим их в выходной массив
            while ($row = $res->fetch_assoc()) {
                //По ходу добавления в выходной массив нужно заполнить StatusHtml - иконка статуса счёта
                $row['StatusHtml'] = $customerOrVendor === 'Customer'
                    ? Finance::getCInvoiceStatusIcon($row['Status'])
                    : Finance::getVInvoiceStatusIcon($row['Status']);
                $data[] = $row;
            }
        } else {
            //Если результатов нет, то в клиентскую часть нужно отрпавить одну строку
            //а JavaScript разберётся, что это признак "нет результатов" и выведет соответсвующее сообщение
            //Возвращает текущее значение value с ключём искомого поля $keyField - это нужно, чтобы на стороне клиента
            //очистить в строке все поля, кроме искомого (см. VPaymentEdit.js: VPayment.acInvoiceResultFormatter
            $data[] = [$keyField => $value, 'value' => $value];
        }

        return $data;
    }

    public static function acSearchInvoiceById($value)
    {
        return self::getInvoicesForAjax('ID', $value);
    }

    public static function acSearchInvoiceByName($value)
    {
        return self::getInvoicesForAjax('Name', $value);
    }

    /**
     * Переопределяем стандартный метод, чтобы помимо данныех из шапки сохранить ещё старые ID привязанных счетов
     * @param ObjAbstract $obj
     * @return array
     */
    #[\Override]
    public function getOldDataBeforeSubmitOrDelete(ObjAbstract $obj)
    {
        $oldData = parent::getOldDataBeforeSubmitOrDelete($obj);

        /** @var $obj PaymentObjAbstract */
        $oldData['InvoiceIds'] = $obj->getInvoiceIds();

        return $oldData;
    }

    #[\Override]
    protected function afterSubmit(
        ObjAbstract $obj,
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff,
        $fromMultipleUpdat
    ) {
        //Обновим все кеширюущие поля связанные с изменением или созданием оплаты
        /** @var $obj PaymentObjAbstract */
        $dbLogMsg = 'Save ' . $this->getCustomerOrVendor() . ' Payment ' . $obj->getDescriptionHtml();
        $obj->updateCacheAfterSubmit($oldData, $msgBuff, true, $dbLogMsg);
    }

    #[\Override]
    protected function afterDelete(ObjAbstract $obj, array $oldData)
    {
        /** @var $obj PaymentObjAbstract */
        $dbLogMsg = 'Delete ' . $this->getCustomerOrVendor() . ' Payment ' . $obj->getDescriptionHtml();
        $obj->updateCacheAfterDelete($oldData['InvoiceIds'], $dbLogMsg);
    }
}
