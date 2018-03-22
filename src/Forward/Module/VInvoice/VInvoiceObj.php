<?php
namespace Forward\Module\VInvoice;

use Forward\Application;
use Forward\Classes\Finance;
use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Forward\ModuleBase\Invoices\InvoiceObjAbstract;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoney;
use Light\Msg;
use Light\User;

class VInvoiceObj extends InvoiceObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('vinvoice');

        $companyField = GeneralFields::companyId();
        if (!User::hasSpecialAccess(Application::SA_VINVOICE_ALLOW_ALL_COMPANYID)) {
            //Если у пользователя нет доступа ко всем компаниям в модуле VInvoice,
            //то ограничим доступ одной компанией
            $companyField->setParam('options', "SELECT ID, Name, IF(ID=1,1,0) AS Active FROM ourcompany ORDER BY OrderID");
        }

        $this->addFields(

            CF::id(),

            InvoiceFields::vName()->setParam('editMandatory', true),

            $companyField,

            GeneralFields::vendorId(true, false), //editMandatory => true, filter => false

            GeneralFields::vendorAccountId(true), //editMandatory => true

            InvoiceFields::docType(),

            F::createSelect(
                'Status',
                [
                    'options' => Finance::$vinvoiceStatuses,
                    'empty' => false,
                    'sqlSelectMandatory' => true
                ]
            ),

            InvoiceFields::vStatusIco(),

            F::createMoney('Amount', ['modes' => F::MODE_READONLY | F::MODE_LOG]),

            CF::currency('', ['default' => 'USD']),

            CF::comments(['rows' => 2]),

            InvoiceFields::receivedDate(),

            InvoiceFields::docDate(true),

            InvoiceFields::dueDate(),

            F::createDate('NotOnCheckingDate', [
                'name' => 'Checked Date',
                'modes' => F::MODE_LIST | F::MODE_VIEW | F::MODE_LOG | F::MODE_SUBMIT
            ]),

            F::createInt('Overdue', [
                'name' => 'Overdue',
                'nameTable' => 'Overdue<br>(days)',
                'sqlSelectRequires' => 'Status',
                'tableAlign' => 'center',
                'sqlSelect' => "IF(Status IN (" . implode(',', Finance::$vinvoiceOpenStatuses) . ")
                                   , DATEDIFF(CURDATE(), DueDate)
                                   , -999)",
                //Определим функцию, которая в зависимости от значения Overdue окрасит ячеку таблицы в класс bad или warning
                'viewCssClass' => '\\Forward\\Classes\\Finance::getOverdueViewCssClass',
                //Определим функцию, которая очистит ячейку Overdue, если статус счёта CLEARD или CREDITNOTED
                'getHtmlFunction' => function ($value, $data, $outHtml, $forTable) {
                    if (isset($data['Status'])) {
                        $status = $data['Status'];
                        if (!isset(Finance::$vinvoiceOpenStatuses[$status])) {
                            $outHtml = '';
                        }
                    }

                    return $outHtml;
                }
            ])

        );

        $this->addStandrtFields();
    }

    /**
     * Обновляет все кеширующе поля связанные с изменением или созданием счёта
     * - кеширующие поля по остаткам и задолженностям внутри самого счёта
     * - суммы по файлам в таблице vinvoice_file_amount
     * - GP по файлам
     * - критерии по файлам
     *
     * @param array $oldData содержащит массив старых файлов 'FileNumbers' связанных с этим счётом до обновления
     * таким образом будут пересчитаны GP и критерии и по старым файлам тоже. Это полезно, когда при сохранении счёта
     * какие-то строки удаляются
     *
     * @param Msg $msgBuff опционально можно передать объект-буффер логов. Это используется при сохранении счёта через
     * веб интерфейс.
     * @param bool $updateShipmentsGpAndCriteria
     * @param string $dbLogMsg
     */
    public function updateCacheAfterSubmit(
        array $oldData,
        Msg $msgBuff = null,
        $updateShipmentsGpAndCriteria = true,
        $dbLogMsg = ''
    )
    {

        if (isset($oldData['FileNumbers'])) {
            $oldFileNumbers = $oldData['FileNumbers'];
        } else {
            $oldFileNumbers = [];
        }

        //Если имя счета изменилось, запустим метод переименования и в платежах, и в Cost-sheet`ах
        if (isset($oldData['Name']) && (string)$oldData['Name'] !== (string)$this['Name']) {
            $this->renameAfterInsertUpdate($oldData['Name'], $this['Name']);
        }

        //Обновим дату DueDate, но только если она пуста. Если она уже заполнена - возможно, это правильное значение введённое пользователем
        $this->autocalculateInvoiceDueDate($msgBuff, $dbLogMsg);

        //Обновим поле vinvoice.Amount на основе подтаблицы с линиями = sum(vinvoiceline.Amount)
        $this->updateAmount($dbLogMsg);

        //Обновим поля поля CacheLastPaymentDate, CachePaymentsReference, CachePaymentsTotalAmount, CacheOutstandingAmount
        //Эти поля связаны с оплатами и казалось бы они не должны измениться при сохранении счёта
        //Однако, они могут измениться в двух случаях:
        //1. у счёта изменилась валюта: влияет на CachePaymentsTotalAmount
        //2. или у счёиа измениолась сумма: влияет на CacheOutstandingAmount
        Finance::updateInvoiceCacheClearingFields('V', $this->getId());

        //Обновим таблицу vinvoice_file_amount в которой каждому счёту+файлу сопоставлена сумма
        $this->updateAmountPerFile();
        $this->updateAmountPerFileCurrencyRate();

        //Вызовем специальный метод, который попробует угадать какие строки из Cost-sheet'ов соответсвуют этому счёту
        //и добавим номера инвойсов в поле InvNum в Cost-sheet
        //Добавляем в Cost-sheet, только если это не удалённый счёт
        if ($this->_data['Status'] !== Finance::INVOICESTATUS_DELETED) {
            $this->guessAndUpdateCostSheetsInvNum($msgBuff);
        }

        if ($updateShipmentsGpAndCriteria) {

            //После обновления кеширующей таблицы vinvoice_file_amount, можно обновить и GP по всем файлам,
            //которые участвовали при сохранении данного счёта
            $this->updateShipmentsGpAndCriteria($oldFileNumbers, $dbLogMsg);
        }
    }

    private function updateAmount($dbLogMsg = '')
    {
        $amount = Db::getOne("SELECT SUM(FinalAmount) FROM vinvoiceline WHERE VInvoiceID = @i", $this->getId());
        $this->updateDb(['Amount' => $amount], $dbLogMsg);
    }

    /**
     * Этот метод обновляет Cost-sheet'ы по всем файлам относящимся к текущему счёту
     * Метод пытается заполнить поле InvNum в блоке Estimated Expenses в тех строках Cost-sheet'а, где InvNum пусто
     * Алгоритм пытается сравнить строки Cost-sheet'а со строками счёта по суммам Amount
     *
     * Метод вызывается при сохранении инвойса в модуле VInvoice через вызов updateAllCache
     * @param Msg $msgBuff
     */
    private function guessAndUpdateCostSheetsInvNum(Msg $msgBuff)
    {
        //Загрузим информацию по всем строкам счёта
        $invLines = Db::getAll("SELECT FileNumber, FinalAmount FROM vinvoiceline
                               WHERE FileNumber != 0 AND VInvoiceID = " . $this->getId()
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
            $csLines = Db::getAll("SELECT ID
                                       , Amount
                                       , CurrencyISO
                                       , InvNum
                                   FROM csexpenses
                                   WHERE ShipmentID = " . $shipmentId . " AND InvNum = ''");
            if (!$csLines) {
                continue;
            }

            //Пройдём по каждой строке в исходном счёте по данному файлу (массив $invLinesForFile) и попробуем найти соответсвие в $csLines
            /** @var array $invLinesForFile */
            foreach ($invLinesForFile as $invLine) {

                //Пройдём по всем строкам кост-шита и найдём те, которые совпадают по сумме и по валюте с текущей сторокой счёта
                foreach ($csLines as $index => $csLine) {

                    //Проверяем совпадение по сумме Amount
                    if ($csLine['Amount'] == $invLine['FinalAmount'] && $csLine['CurrencyISO'] == $this->_data['CurrencyISO']) {
                        //Нашли совпадение, запишем в массив совпавших строк

                        //Запишем ID найденной строки в массив на обновление
                        $csLinesIdsToBeUpdated[] = $csLine['ID'];
                        //Обязательно удалим найденную строку из исходного массива $csLine, чтбоы при следующем проходе
                        //по следующей строке счёта уже не делать сравнение с этой строкой Cost-sheet'а
                        unset($csLines[$index]);
                    }
                }
            }
        }

        //Если после прохода по всем Cost-sheet'ам накопился массив из ID каких-то строк, обновим эти строки в базе!
        if ($csLinesIdsToBeUpdated) {
            Db::query(
                "UPDATE csexpenses
                       SET InvNum = '" . Db::esc($this->_data['Name']) . "'
                       WHERE ID IN (" . implode(',', $csLinesIdsToBeUpdated) . ")"
            );

            if ($msgBuff) {
                $msgBuff->addInfo(
                    'Номер счёта ' . htmlspecialchars($this->_data['Name']) . ' был автоматически простален в ' . count(
                        $csLinesIdsToBeUpdated
                    ) . ' строк Cost-sheet Expenses'
                );
            }
        }
    }
}
