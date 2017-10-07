<?php

namespace Forward\Autolaunch;

use Forward\Autolaunch\ImportFrom1C\ImportFrom1CAbstract;
use Forward\Module\CPayment\CPaymentObj;
use Forward\Module\CPayment\Subtables\CInvoices;
use Forward\Module\CPayment\Subtables\CInvoicesObj;
use Light\Config;
use Light\Db;
use Light\Field\FieldDate;
use Light\Link;
use Light\Mailer;
use Light\Msg;

class ImportCPaymentsFrom1C extends ImportFrom1CAbstract
{
    protected static $_title = 'Импортировать оплаты Customer Payments из 1С (через FTP сервер)';
    protected static $_description = 'Проверяет наличие файлов с информацией об оплате из 1С на FTP сервере и загружает в Customer Payment';

    public function __construct()
    {
        parent::__construct();

        $this->folder = 'cpayment';
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
            $this->_msgBuff->addSkipp('Оплата до 1 ноября 2016, пропускаем');
            return self::RESULT_NOTCHANGED;
        }

        $cpaymentObj = new CPaymentObj();

        // Попробуем найти оплату в базе
        Db::begin();
        $existCPaymentId = Db::getOne("SELECT ID FROM cpayment WHERE CompanyID = $companyId AND OneCGUID = @s FOR UPDATE",
            $json['GUID']);
        // Если оплата найдена в базе, сразу загрузим её, пригодится
        if ($existCPaymentId) {
            $cpaymentObj->setId($existCPaymentId);
            $cpaymentObj->loadData();
        }

        if ($json['IsDeleted']) {
            // Если пришел флаг IsDeleted, это
            if (!$existCPaymentId) {
                // Этой оплаты и не было в нашей базе, ничего делать и не нужно, просто вернём результат
                Db::commit();
                return self::RESULT_NOTFOUND;
            }

            // Удаляем только в том случае, если оплата действительно была найдена в базе
            $oldInvoiceIds = $cpaymentObj->getInvoiceIds();
            $cpaymentObj->deleteDb('Удаление из 1С');
            $cinvoicesSubtable = new CInvoices($cpaymentObj);
            $cinvoicesSubtable->deleteAll();

            // Обновим кэш по оплате
            $cpaymentObj->updateCacheAfterDelete($oldInvoiceIds, 'Удаление из 1С');
            Db::commit();

            $this->_msgBuff->addInfo('Оплата ' . $cpaymentObj->getDescriptionHtml() . ' удалена из базы, т.к. из 1С пришла пометка на удаление');
            return self::RESULT_DELETED;


        }

        $fileLink = Link::module(htmlspecialchars($filenameForLog),
            ['ps' => 'file', 'file' => $filenameForLog], 'ImportCPaymentFrom1CLog', null, 'class=note');

        $dbLogMsg = "Импорт из 1С <small>$fileLink</small>";

        // Подготовим буфер конкретно для данного json файла с оплатой, чтобы потом записать
        // подробные логи в dblog к этой оплате
        $msgBuff = new Msg();

        // Если дошли до этого момента, значит в JSON структуре из 1С не было пометки на удаление
        // Подготовим массив с данными для записи в базу
        $cpaymentUpdateData = [
            'CompanyID' => $companyId,
            'CustomerAccountID' => (int)$json['CustomerAccountID'],
            'Name' => $json['Name'] . ' (from 1С)',
            'PaymentDate' => $paymenDate,
            'Amount' => $json['Amount'],
            'CurrencyISO' => $json['CurrencyISO'],
            'Comments' => $json['Comments']
        ];

        // Загрузим клиентов относящихся к этому CustomerAccountID, если там ровно один клиент - его и используем
        $customerIds = Db::getCol("SELECT CustomerID FROM customer_customeraccount WHERE CustomerAccountID = @i", $json['CustomerAccountID']);
        if (count($customerIds) === 1) {
            $cpaymentUpdateData['CustomerID'] = reset($customerIds);
        }

        if ($existCPaymentId) {

            // Счёт существует в базе, сохраним данные до обновления, пригодятся для пересчёта кэша
            $oldPaymentData = $cpaymentObj->getData();
            $oldPaymentData['InvoiceIds'] = $cpaymentObj->getInvoiceIds();

            $affectedRows = $cpaymentObj->updateDb($cpaymentUpdateData, $dbLogMsg);


        } else {

            $oldPaymentData = [];
            $cpaymentUpdateData['OneCGUID'] = $json['GUID'];
            $cpaymentObj->insertDb($cpaymentUpdateData, $dbLogMsg);
            $affectedRows = 1;

        }

        // Теперь обновим все строки
        $existLineIds = Db::getCol("SELECT ID FROM cpayment_cinvoice WHERE CPaymentID = @i", $cpaymentObj->getId());

        if (isset($json['Lines']) && $json['Lines']) {
            $affectedRows += $this->importLinesFromJson(
                $json['Lines'],
                $cpaymentObj,
                $existLineIds,
                $msgBuff,
                $dbLogMsg
            );

            // Обновим кэш по подтаблице
            $cinvoicesSubtable = new CInvoices($cpaymentObj);
            $cinvoicesSubtable->updateCache();

            // Обновим кэш по объекту CPayment
            $cpaymentObj->updateCacheAfterSubmit($oldPaymentData);
        }

        Db::commit();

        if ($existCPaymentId) {

            $result = $affectedRows ? self::RESULT_UPDATED : self::RESULT_NOTCHANGED;

        } else {

            $result = self::RESULT_INSERTED;
        }

        // Отправим Email ответсвенному Customer Service по перевозке, если у оплата с переплатой или недоплатой
        $this->sendEmailOverpay($cpaymentObj);

        // Запишем в логи этого Autolaunch результат обработки текущего json
        $cpaymentLink = Link::moduleViewBlank($cpaymentObj->getDescriptionHtml(), $cpaymentObj->getId(), null,
            'CPayment');
        switch ($result) {
            case self::RESULT_INSERTED:
                $msgBuff->addSuccess("$cpaymentLink: оплата добавлена из 1С<br>$fileLink");
                break;

            case self::RESULT_UPDATED:
                $msgBuff->addInfo("$cpaymentLink: оплата обновлена из 1С<br>$fileLink");
                break;

            case self::RESULT_NOTCHANGED:
                $msgBuff->addSkipp("$cpaymentLink: оплата не изменилась<br>$fileLink");
                break;
        }

        $cpaymentObj->writeDbLogText($msgBuff->getHtml(), '1С', '1С');
        $this->_msgBuff->addMessagess($msgBuff->getAll());

        return $result;

    }

    /**
     * Эта функция вызывается только если из 1С пришла не пустая оплата
     * (массив Lines в JSON не пуст), чтобы не стересть случайно вручную разнесённые оплаты
     * @param array $linesFromJson
     * @param CPaymentObj $cpaymentObj
     * @param array $existLineIds
     * @param Msg $msgBuff
     * @param string $dbLogMsg
     * @return int
     */
    private function importLinesFromJson(
        array $linesFromJson,
        CPaymentObj $cpaymentObj,
        array $existLineIds,
        Msg $msgBuff,
        string $dbLogMsg
    ) {
        $affectedRows = 0;

        $lineObjBase = new CInvoicesObj();
        $lineObjBase->setMasterIdForDbLog($cpaymentObj->getId());

        foreach ($linesFromJson as $indexFromJson => $lineFromJson) {

            // Чтобы правильно расчитать поле ClearingAmountInInvoiceCurrency и RateToInvoiceCurrency нам нужно
            // загрузить счёт из базы
            $cinvoice = $this->loadCInvoice($lineFromJson, $cpaymentObj['CompanyID'], $msgBuff);

            $lineUpdateData = [
                'CPaymentID' => $cpaymentObj->getId(),
                'CInvoiceID' => $cinvoice['ID'] ?? $lineFromJson['CInvoiceID'],
                'CInvoiceName' => $cinvoice['Name'] ?? $lineFromJson['CInvoiceName'],
                'ClearingAmountInPaymentCurrency' => $lineFromJson['Amount'],
                'ClearingAmountInInvoiceCurrency' => $lineFromJson['AmountInCurrency'] ?? $lineFromJson['Amount'],
                // до 29.06.2017 поля AmountInCurrency не было в json, теперь добавляем
                'RateToInvoiceCurrency' => $lineFromJson['CurrencyRate'] ?? 1
                // до 29.06.2017 поля CurrencyRate не было в json, теперь добавляем
            ];

            if ($cinvoice) {

                // Если нашли счёт в базе, то заодно обновим поле CustomerAccountID и CustomerID в самой оплате
                $cpaymentUpdateData = [];
                if (!$cpaymentObj['CustomerID']) {
                    $cpaymentUpdateData['CustomerID'] = $cinvoice['CustomerID'];
                }
                if (!$cpaymentObj['CustomerAccountID']) {
                    $cpaymentUpdateData['CustomerAccountID'] = $cinvoice['CustomerAccountID'];
                }
                if ($cpaymentUpdateData) {
                    // Собрали какие-то новые данные для обновления оплаты
                    $affectedRows += $cpaymentObj->updateDb($cpaymentUpdateData,
                        $dbLogMsg . ' - обновление на основе счёта ' . htmlspecialchars($cinvoice['Name']));
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

        // Удаление лишних строк из базы делать не будем,
        // т.к. пользоатель мог сам доразнести оплату в базе, не хотим это потерять

        return $affectedRows;

    }

    private function loadCInvoice(array $lineFromOneC, int $companyId, Msg $msgBuff)
    {
        if ($lineFromOneC['CInvoiceID']) {
            // Если в json строке передан конкретный ID счёта в нашей базе - загрузим его
            return Db::getRow("SELECT * FROM cinvoice WHERE ID = @i", $lineFromOneC['CInvoiceID']);
        }

        // Если же в json строке нет конкретного ID в нашей базе, то осуществим поиск по номеру и дате
        $docDate = new \DateTime($lineFromOneC['CInvoiceDate']);
        $possibleInvoices = Db::getAll("SELECT * 
             FROM cinvoice 
             WHERE Name = @s 
                AND DocDate = @s 
                AND CompanyID = @i",
            $lineFromOneC['CInvoiceName'],
            $docDate->format('Y-m-d'),
            $companyId);

        if (count($possibleInvoices) === 1) {
            // Нашелся ровно один подходящий счёт - его и возвращаем
            return $possibleInvoices[0];
        }

        if (count($possibleInvoices) > 1) {
            // Если же нашлось несколько подходящих счетов, то выведем сообщение и вернём пустой массив
            $msgBuff->addWarning('От 1С не пришел CInvoiceID, а по номеру (' . htmlspecialchars($lineFromOneC['CInvoiceName']) . ') и дате (' . htmlspecialchars($lineFromOneC['CInvoiceDate']) . ') счёта нашлось несколько результатов');
            return [];
        }

        $msgBuff->addWarning('От 1С не пришел CInvoiceID, а по номеру (' . htmlspecialchars($lineFromOneC['CInvoiceName']) . ') и дате (' . htmlspecialchars($lineFromOneC['CInvoiceDate']) . ') счёт не найден');
        return [];

    }

    private function sendEmailOverpay(CPaymentObj $cpaymentObj)
    {
        $cpaymentId = $cpaymentObj->getId();
        // Снчала проверим, есть ли строки у этой оплаты. Если строк нет, ничего проверять не будем
        $cinvoicesIds = Db::getCol("SELECT CInvoiceID FROM cpayment_cinvoice WHERE CPaymentID = $cpaymentId");
        if (!$cinvoicesIds) {
            return;
        }

        $overpayAmount = $cpaymentObj['CacheOverpayAmount'];
        if ($overpayAmount == 0) {
            return;
        }
        $overpay = $overpayAmount > 0 ? 'Переплата' : 'Недоплата';

        $customerAccountName = $cpaymentObj->getFieldText('CustomerAccountID');
        $paymentName = $cpaymentObj['Name'];
        $paymentDate = $cpaymentObj->getFieldText('PaymentDate');
        $paymentCurrencyIso = $cpaymentObj['CurrencyISO'];

        $subject = "$overpay $overpayAmount $paymentCurrencyIso от $customerAccountName в платёжке №ё$paymentName $paymentDate";

        $paymentLink = '<a href="'.Config::$localUrl.'/?a=CPayment&id='.$cpaymentId.'">№'.htmlspecialchars($paymentName).' '. $paymentDate.'</a>';
        $customerAccountNameHtml = htmlspecialchars($customerAccountName);
        $body = "$overpay $overpayAmount $paymentCurrencyIso от $customerAccountNameHtml в платёжке $paymentLink";

        // Узнаем всех ответсвенных Customer Service
        $fileNumbers = Db::getCol("SELECT FileNumber FROM cinvoice_file_amount WHERE CInvoiceID IN (@a)", $cinvoicesIds);
        $respoisbleCsIds = Db::getCol("SELECT ResponsibleCS FROM shipment WHERE Name IN (@a)", $fileNumbers);
        $responsibleEmails = Db::getCol("SELECT DISTINCT Email FROM user WHERE Email != '' AND ID IN (@a)", $respoisbleCsIds);
        if (!$responsibleEmails) {
            return;
        }

        Mailer::sendMail(
            Config::$mailFrom,
            $responsibleEmails,
            null,
            $subject,
            Mailer::wrapBody($body),
            null,
            $this->_msgBuff,
            'petr.myazin@gmail.com'
        );

    }

}