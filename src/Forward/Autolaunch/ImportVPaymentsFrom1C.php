<?php

namespace Forward\Autolaunch;

use Forward\Autolaunch\ImportFrom1C\ImportFrom1CAbstract;
use Forward\Module\VendorAccount\VendorAccountObj;
use Forward\Module\VPayment\Subtables\VInvoices;
use Forward\Module\VPayment\Subtables\VInvoicesObj;
use Forward\Module\VPayment\VPaymentObj;
use Light\Db;
use Light\Field\FieldDate;
use Light\Link;

class ImportVPaymentsFrom1C extends ImportFrom1CAbstract
{
    protected static $_title = 'Импортировать оплтаы Vendor Payments из 1С (через FTP сервер)';
    protected static $_description = 'Проверяет наличие файлов с информацией об оплате из 1С на FTP сервере и загружает в Vendor Payment';

    public function __construct()
    {
        parent::__construct();

        $this->folder = 'vpayment';
    }

    protected function importFromJson(array $json, int $companyId, string $filenameForLog)
    {
        if (!isset($json['GUID'])) {
            $this->_msgBuff->addError('Не передан параметр GUID');
            return self::RESULT_INCORRECT;
        }

        if (!$json['GUID']) {
            $this->_msgBuff->addError('Параметр GUID пуст в файле');
            return self::RESULT_INCORRECT;
        }

        $paymenDate = FieldDate::parse($json['PaymentDate']);
        if (!$paymenDate) {
            // Если дата по каким-то прична не пришла из 1С, то примем текущий день
            $paymenDate = date('Y-m-d');
        }
        if ($paymenDate < '2016-11-01') {
            $this->_msgBuff->addSkipp('Оплата до 19 декабря 2016, пропускаем');
            return self::RESULT_NOTCHANGED;
        }

        $vpaymentObj = new VPaymentObj();

        // Попробуем найти оплату в базе
        Db::begin();
        $existVPaymentId = Db::getOne("SELECT ID FROM vpayment WHERE CompanyID = $companyId AND OneCGUID = @s FOR UPDATE",
            $json['GUID']);
        // Если оплата найдена в базе, сразу загрузим её, пригодится
        if ($existVPaymentId) {
            $vpaymentObj->setId($existVPaymentId);
            $vpaymentObj->loadData();
        }

        if ($json['IsDeleted']) {
            // Если пришел флаг IsDeleted, это
            if (!$existVPaymentId) {
                // Этой оплаты и не было в нашей базе, ничего делать и не нужно, просто вернём результат
                Db::commit();
                return self::RESULT_NOTFOUND;
            }

            // Удаляем только в том случае, если оплата действительно была найдена в базе
            $oldInvoiceIds = $vpaymentObj->getInvoiceIds();
            $vpaymentObj->deleteDb('Удаление из 1С');
            $vinvoicesSubtable = new VInvoices($vpaymentObj);
            $vinvoicesSubtable->deleteAll();

            // Обновим кэш по оплате
            $vpaymentObj->updateCacheAfterDelete($oldInvoiceIds, 'Удаление из 1С');
            Db::commit();

            $this->_msgBuff->addInfo('Оплата ' . $vpaymentObj->getDescriptionHtml() . ' удалена из базы, т.к. из 1С пришла пометка на удаление');
            return self::RESULT_DELETED;


        }

        $dbLogMsg = 'Импорт из 1С <small>' . htmlspecialchars($filenameForLog) . '</small>';

        // Если дошли до этого момента, значит в JSON структуре из 1С не было пометки на удаление
        // Подготовим массив с данными для записи в базу
        $vpaymentUpdateData = [
            'CompanyID' => $companyId,
            'PaymentDate' => $paymenDate,
            'Amount' => $json['Amount'],
            'CurrencyISO' => $json['CurrencyISO'],
            'Comments' => $json['Comments']
        ];

        // Поле Name для Vendor Payment состоит из номеров счетов и имени вендора
        $name = $json['Vendor'];
        if (isset($json['Lines'])) {
            $name = implode(', ', array_unique(array_column($json['Lines'], 'VInvoiceName')))
                . ' ' . $name;
        }
        $name .= ' (from 1С)';
        $vpaymentUpdateData['Name'] = $name;

        // Попробуем определить VendorAccountID на основе текстового описания Vendor из 1С
        $vendorAccountId = Db::getOne("SELECT ID FROM vendoraccount WHERE OneCName = @s", $json['Name']);

        // Загрузим все счета, которые упоминаются в Lines
        $vinvoices = [];
        $vinvoiceQuery = "SELECT ID, VendorID, VendorAccountID, CurrencyISO, Amount FROM vinvoice WHERE Name = @s AND DocDate = @s";
        if ($vendorAccountId) {
            $vinvoiceQuery .= " AND VendorAccountID = " . (int)$vendorAccountId;
        }

        if (isset($json['Lines'])) {
            foreach ($json['Lines'] as $lineFromJson) {
                $vinvoice = Db::getAll($vinvoiceQuery, $lineFromJson['VInvoiceName'], $lineFromJson['VInvoiceDate']);
                if (count($vinvoice) === 0) {
                    $this->_msgBuff->addError('Не найден счёт ' . htmlspecialchars($lineFromJson['VInvoiceName']) . ' от ' . FieldDate::beautify($lineFromJson['VInvoiceDate']));
                } elseif (count($vinvoice) > 1) {
                    $this->_msgBuff->addError('Найдено несколько счетов с номером ' . htmlspecialchars($lineFromJson['VInvoiceName']) . ' от ' . FieldDate::beautify($lineFromJson['VInvoiceDate']));
                } else {
                    $vinvoices[$lineFromJson['VInvoiceName'] . '|' . $lineFromJson['VInvoiceDate']] = reset($vinvoice);
                }
            }
        }

        // После того как загрузили все счета, попробуем определить VendorID
        $possibleVendorIds = array_unique(array_filter(array_column($vinvoices, 'VendorID')));
        if (count($possibleVendorIds) === 1) {
            $vpaymentUpdateData['VendorID'] = reset($possibleVendorIds);
        } else {
            $this->_msgBuff->addError('Не удалось определить Vendor');
        }

        // Если Vendor Account не был определён на основе текстового имени, то попробуем определить его на основе счетов
        if (!$vendorAccountId) {
            $possibleVendorAccountIds = array_unique(array_filter(array_column($vinvoices, 'VendorAccountID')));
            if (count($possibleVendorAccountIds) === 1) {
                $vpaymentUpdateData['VendorAccountID'] = reset($possibleVendorAccountIds);
                // Заодно сохраним в базе русское наименование
                $vendorAccountObj = new VendorAccountObj();
                $vendorAccountObj->setId($vpaymentUpdateData['VendorAccountID']);
                $vendorAccountObj->updateDb(['OneCName' => $json['Vendor']],
                    'Обновление из 1С при обновлении Vendor Payment');
            } else {
                $this->_msgBuff->addError('Не удалось определить Vendor Account');
            }
        }

        if ($existVPaymentId) {

            // Счёт существует в базе, сохраним данные до обновления, пригодятся для пересчёта кэша
            $oldPaymentData = $vpaymentObj->getData();
            $oldPaymentData['InvoiceIds'] = $vpaymentObj->getInvoiceIds();

            $affectedRows = $vpaymentObj->updateDb($vpaymentUpdateData, $dbLogMsg);


        } else {

            $oldPaymentData = [];
            $vpaymentUpdateData['OneCGUID'] = $json['GUID'];
            $vpaymentObj->insertDb($vpaymentUpdateData, $dbLogMsg);
            $affectedRows = 1;

        }

        $lineObjBase = new VInvoicesObj();
        $lineObjBase->setMasterIdForDbLog($vpaymentObj->getId());

        // Теперь обновим все строки
        $existLineIds = Db::getCol("SELECT ID FROM vpayment_vinvoice WHERE VPaymentID = @i", $vpaymentObj->getId());

        if (isset($json['Lines'])) {
            foreach ($json['Lines'] as $indexFromJson => $lineFromJson) {

                // Чтобы правильно расчитать поле ClearingAmountInInvoiceCurrency и RateToInvoiceCurrency нам нужно
                // загрузить счёт из базы
                $vinvoice = $vinvoices[$lineFromJson['VInvoiceName'] . '|' . $lineFromJson['VInvoiceDate']] ?? [];

                $lineUpdateData = [
                    'VPaymentID' => $vpaymentObj->getId(),
                    'VInvoiceID' => $vinvoice['ID'] ?? '0',
                    'VInvoiceName' => $lineFromJson['VInvoiceName'],
                    'ClearingAmountInPaymentCurrency' => $lineFromJson['Amount']
                ];

                if (!$vinvoice) {
                    // Если счёт не был найден в нашей базе, то используем сумму из Json файла
                    $lineUpdateData['ClearingAmountInInvoiceCurrency'] = $lineFromJson['Amount'];
                    $lineUpdateData['RateToInvoiceCurrency'] = 1;

                } else {

                    // Если же счёт был найден в нашей базе, то сравним валюту оплаты и валюту счёта
                    if ($vinvoice['CurrencyISO'] === $vpaymentObj['CurrencyISO']) {
                        // Валюты одинаковы, значит подставим сумму из json и курс 1
                        $lineUpdateData['ClearingAmountInInvoiceCurrency'] = $lineFromJson['Amount'];
                        $lineUpdateData['RateToInvoiceCurrency'] = 1;
                    } else {
                        // Валюты разные, подставим полную сумму счёта и вычислим курс на лету
                        $lineUpdateData['ClearingAmountInInvoiceCurrency'] = $vinvoice['Amount'];
                        $vinvoiceAmount = (float)$vinvoice['Amount'];
                        // Чтобы не было деления на 0, дополнительно проверим, что сумма счёта в базе не нулевая
                        $lineUpdateData['RateToInvoiceCurrency'] = $vinvoiceAmount ? round($lineFromJson['Amount'] / $vinvoiceAmount,
                            4) : 1;
                    }
                }

                // Итак, мы сформировали массив $lineData для обновления и вставки в таблицу cpayment_cinvoice
                $lineObj = clone $lineObjBase;

                // Если в базе уже была строка под текущим порядковым номером, обновим её
                if (isset($existLineIds[$indexFromJson])) {
                    $existLineId = $existLineIds[$indexFromJson];
                    $lineObj->setId($existLineId);
                    $affectedRows += $lineObj->updateDb($lineUpdateData, $dbLogMsg);
                } else {
                    $lineObj->insertDb($lineUpdateData, $dbLogMsg);
                    $affectedRows++;
                }

            }
        }

        // После того как вставили или обновили новые строки пришедшие в json файле, удалим лишние старые строки из базы
        $jsonLinesCount = isset($json['Lines']) ? count($json['Lines']) : 0;
        $existLinesCount = count($existLineIds);
        if ($existLinesCount > $jsonLinesCount) {
            for ($i = $jsonLinesCount; $i < $existLinesCount; $i++) {
                // Удаляем лишнюю строку из базы
                $lineObj = clone $lineObjBase;
                $lineObj->setId($existLineIds[$i]);
                $lineObj->loadData();
                $lineObj->deleteDb($dbLogMsg);
                $affectedRows++;
            }
        }

        // Обновим кэш по подтаблице
        $vinvoicesSubtable = new VInvoices($vpaymentObj);
        $vinvoicesSubtable->updateCache();

        // Обновим кэш по объекту CPayment
        $vpaymentObj->updateCacheAfterSubmit($oldPaymentData);

        Db::commit();


        if ($existVPaymentId) {

            $result = $affectedRows ? self::RESULT_UPDATED : self::RESULT_NOTCHANGED;

        } else {

            $result = self::RESULT_INSERTED;
        }

        // Запишем в логи этого Autolaunch результат обработки текущего json
        $link = Link::moduleViewBlank($vpaymentObj->getDescriptionHtml(), $vpaymentObj->getId(), null, 'VPayment');
        switch ($result) {
            case self::RESULT_INSERTED:
                $this->_msgBuff->addSuccess($link . ': оплата добавлена');
                break;

            case self::RESULT_UPDATED:
                $this->_msgBuff->addInfo($link . ': оплата обновлена');
                break;

            case self::RESULT_NOTCHANGED:
                $this->_msgBuff->addSkipp($link . ': оплата не изменилась');
                break;
        }

        return $result;

    }

}
