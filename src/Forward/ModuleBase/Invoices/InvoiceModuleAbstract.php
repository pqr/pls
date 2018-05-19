<?php
namespace Forward\ModuleBase\Invoices;

use Forward\Classes\Finance;
use Forward\Classes\Traits\FirstLetterTrait;
use Light\Db;
use Light\Js;
use Light\Misc;
use Light\ModuleBase\ModuleAbstract;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;

abstract class InvoiceModuleAbstract extends ModuleAbstract
{
    use FirstLetterTrait;

    public function __construct()
    {
        parent::__construct();

        $this->folders = true;
        $this->tableDefaultOrderBy = 'ID DESC';
    }

    protected function initCssJs()
    {
        parent::initCssJs();

        if ($this->isStateList()) {

            Js::add('ModuleBase/Invoices/InvoiceList.js');

        } elseif ($this->isStateAddNew() || $this->isStateEdit()) {

            Js::add('ModuleBase/Invoices/InvoiceEdit.js');

        }
    }


    /**
     * Переопределяем стандартный метод, чтобы помимо данных из шапки сохранить ещё старые ID привязанных счетов
     * @param ObjAbstract $obj
     * @return array
     */
    public function getOldDataBeforeSubmitOrDelete(ObjAbstract $obj)
    {
        $oldData = parent::getOldDataBeforeSubmitOrDelete($obj);

        /** @var $obj InvoiceObjAbstract */
        $oldData['FileNumbers'] = $obj->getFileNumbresFromCacheTable();

        return $oldData;
    }

    protected function afterSubmit(ObjAbstract $obj, array $dataFromPost, $affectedRows, array $oldData, Msg $msgBuff, $fromMultipleUpdat)
    {
        //Обновим все кеширюущие поля связанные с изменением или созданием счёта
        //Дополнительно передадим в метод обновлящий кеш специальный буфер для логов
        /** @var $obj InvoiceObjAbstract */
        $msgBuff = new Msg();
        $dbLogMsg = 'Save ' . $this->getCustomerOrVendor() . ' Invoice ' . $obj->getDescriptionHtml();
        $obj->updateCacheAfterSubmit($oldData, $msgBuff, true, $dbLogMsg);
        $msgBuff->putIntoSessionFlash();
    }

    protected function afterDelete(ObjAbstract $obj, array $oldData)
    {
        /** @var $obj InvoiceObjAbstract */
        $dbLogMsg = 'Delete ' . $this->getCustomerOrVendor() . ' Invoice ' . $obj->getDescriptionHtml();
        $obj->updateCacheAfterDelete($oldData['FileNumbers'], $dbLogMsg);
    }

    protected function getTableRowDataParams(array $rowData, $rowNum)
    {
        //У Customer Invoices и Vendor Invoices статус удалённого счёта совпадает - это статус номер 4
        if (isset($rowData['Status']) && $rowData['Status'] == Finance::INVOICESTATUS_DELETED) {
            return ['cssClass' => 'deleted'];
        }

        return [];
    }

    public function proceedSplitPerWeight()
    {

        $fileNumbers = array_filter(array_map('intval', explode("\n", $_POST['fileNumbers'])));
        $subtableData = $_POST['subtableData'];

        // Для начала загрузим все номера перевозки из базы
        $shipments = Db::getAssoc("SELECT Name, MAWBNumber, ChargeableWeight FROM shipment WHERE Name IN (@a)", $fileNumbers);

        // Расчитаем пропорции на основе весов в том порядке, в котором переданы fileNumbers
        $propportions = [];
        foreach ($fileNumbers as $fileNumber) {
            $propportions[] = $shipments[$fileNumber]['ChargeableWeight'] ?? 0;
        }

        $firstLetter = $this->getFirstLetter();

        // Теперь разобьём суммы из шаблонов услуг из $subtableData по пропорциям
        // Разбивать будем суммы из полей Price, Amount, VATAmount, FinalAmount
        foreach ($subtableData as &$oldRow) {
            $oldRow['PricePerFile'] = Misc::splitMoneyInProroption((float)$oldRow['Price'], $propportions);
            $oldRow['AmountPerFile'] = Misc::splitMoneyInProroption((float)$oldRow['Amount'], $propportions);
            if ($firstLetter === 'V') {
                $oldRow['VATAmountPerFile'] = Misc::splitMoneyInProroption((float)$oldRow['VATAmount'], $propportions);
                $oldRow['FinalAmountPerFile'] = Misc::splitMoneyInProroption((float)$oldRow['FinalAmount'], $propportions);
            }
        }
        unset($oldRow);


        // Теперь разобьём шаблонные строки на несколько строк (по файлам)
        $newSubtableData = [];
        foreach ($subtableData as $oldRow) {

            foreach ($fileNumbers as $fileIndex => $fileNumber) {

                if ($firstLetter === 'C') {
                    $newSubtableData[] = [
                        'FileNumber' => $fileNumber,
                        'Name' => $oldRow['Name'],
                        'UOMID' => $oldRow['UOMID'],
                        'Quantity' => $oldRow['Quantity'],
                        'Price' => $oldRow['PricePerFile'][$fileIndex],
                        'Amount' => $oldRow['AmountPerFile'][$fileIndex]
                    ];
                } else {
                    $newSubtableData[] = [
                        'MAWBNumber' => $shipments[$fileNumber]['MAWBNumber'] ?? '',
                        'FileNumber' => $fileNumber,
                        'Name' => $oldRow['Name'],
                        'UOMID' => $oldRow['UOMID'],
                        'Quantity' => $oldRow['Quantity'],
                        'Price' => $oldRow['PricePerFile'][$fileIndex],
                        'Amount' => $oldRow['AmountPerFile'][$fileIndex],
                        'VATPercent' => $oldRow['VATPercent'],
                        'VATAmount' => $oldRow['VATAmountPerFile'][$fileIndex],
                        'FinalAmount' => $oldRow['FinalAmountPerFile'][$fileIndex]
                    ];
                }

            }
        }

        Response::json($newSubtableData);
    }

}
