<?php

namespace Forward\Classes\ImportFromOneC\Importers;


use Forward\Module\CPayment\CPaymentObj;
use Forward\Module\CPayment\Subtables\CInvoices;
use Forward\Module\CPayment\Subtables\CInvoicesObj;
use Light\Config;
use Light\Db;
use Light\Field\FieldDate;
use Light\Mailer;

class ImportCPayment extends ImporterAbstract
{
    public function import(array $data): int
    {
        if (!$this->validateData($data)) {
            return 0;
        }

        $paymenDate = FieldDate::parse($data['PaymentDate']);
        if ($paymenDate && $paymenDate < '2016-11-01') {
            $this->msgBuff->addSkipp('Оплата до 1 ноября 2016, пропускаем');
            return 0;
        }

        $companyId = (int)$data['CompanyID'];
        $cpaymentObj = new CPaymentObj();

        // Попробуем найти оплату в базе
        Db::begin();
        $existCPaymentId = Db::getOne("SELECT ID FROM cpayment WHERE CompanyID = $companyId AND OneCGUID = @s FOR UPDATE",
            $data['GUID']);
        // Если оплата найдена в базе, сразу загрузим её, пригодится
        if ($existCPaymentId) {
            $cpaymentObj->setId($existCPaymentId);
            $cpaymentObj->loadData();
        }

        if ($data['IsDeleted']) {
            // Если пришел флаг IsDeleted, это
            if (!$existCPaymentId) {
                // Этой оплаты и не было в нашей базе, ничего делать и не нужно, просто вернём результат
                Db::commit();
                return 0;
            }

            // Удаляем только в том случае, если оплата действительно была найдена в базе
            $oldInvoiceIds = $cpaymentObj->getInvoiceIds();
            $cpaymentObj->deleteDb('Удаление из 1С');
            $cinvoicesSubtable = new CInvoices($cpaymentObj);
            $cinvoicesSubtable->deleteAll();

            // Обновим кэш по оплате
            $cpaymentObj->updateCacheAfterDelete($oldInvoiceIds, 'Удаление из 1С');
            Db::commit();

            $this->msgBuff->addInfo('Оплата ' . $cpaymentObj->getDescriptionHtml() . ' удалена из базы, т.к. из 1С пришла пометка на удаление');
            return $cpaymentObj->getId();
        }

        $dbLogMsg = "Импорт из 1С " . $this->getImportLink();

        // Если дошли до этого момента, значит в JSON структуре из 1С не было пометки на удаление
        // Подготовим массив с данными для записи в базу
        $cpaymentData = [
            'CompanyID' => $companyId,
            'CustomerAccountID' => (int)$data['CustomerAccountID'],
            'Name' => $data['Name'] . ' (from 1С)',
            'Amount' => $data['Amount'],
            'CurrencyISO' => $data['CurrencyISO'],
            'Comments' => $data['Comments']
        ];

        // Загрузим клиентов относящихся к этому CustomerAccountID, если там ровно один клиент - его и используем
        $customerIds = Db::getCol("SELECT CustomerID FROM customer_customeraccount WHERE CustomerAccountID = @i",
            $data['CustomerAccountID']);
        if (count($customerIds) === 1) {
            $cpaymentData['CustomerID'] = reset($customerIds);
        }

        if ($existCPaymentId) {

            // Счёт существует в базе, сохраним данные до обновления, пригодятся для пересчёта кэша
            $oldPaymentData = $cpaymentObj->getData();
            $oldPaymentData['InvoiceIds'] = $cpaymentObj->getInvoiceIds();

            // При обновлении обновляем PaymentDate, только если на самом деле дата была указана
            if ($paymenDate) {
                $cpaymentData['PaymentDate'] = $paymenDate;
            }

            $cpaymentObj->updateDb($cpaymentData, $dbLogMsg);


        } else {

            $oldPaymentData = [];
            $cpaymentData['OneCGUID'] = $data['GUID'];

            // При вставке, если в 1С не была указана дата оплаты, то принимаем сегодняшний день
            $cpaymentData['PaymentDate'] = $paymenDate ?: date('Y-m-d');

            $cpaymentObj->insertDb($cpaymentData, $dbLogMsg);

        }

        if (isset($data['Lines']) && $data['Lines']) {
            $this->importLinesFromJson(
                $data['Lines'],
                $cpaymentObj,
                $dbLogMsg
            );

            // Обновим кэш по подтаблице
            $cinvoicesSubtable = new CInvoices($cpaymentObj);
            $cinvoicesSubtable->updateCache();

            // Обновим кэш по объекту CPayment
            $cpaymentObj->updateCacheAfterSubmit($oldPaymentData);
        }

        Db::commit();

        // Отправим Email ответсвенному Customer Service по перевозке, если у оплата с переплатой или недоплатой
        $this->sendEmailOverpay($cpaymentObj);

        $cpaymentObj->writeDbLogText($this->msgBuff->getHtml(), '1С', '1С');
        return $cpaymentObj->getId();

    }

    protected function validateData(array $data)
    {
        $result = parent::validateData($data);

        if (!isset($data['GUID'])) {
            $this->msgBuff->addError('Отсуствует обязательное поле GUID');
            $result = false;
        } else {
            if ($data['GUID'] === '') {
                $this->msgBuff->addError('Поле GUID не может быть пустым');
                $result = false;
            }
        }
        if (!isset($data['CustomerAccountID'])) {
            $this->msgBuff->addError('Отсуствует обязательное поле CustomerAccountID');
            $result = false;
        } else {
            if (!is_numeric($data['CustomerAccountID'])) {
                $this->msgBuff->addError('Поле CustomerAccountID должно быть числом получено "' . htmlspecialchars($data['Amount']) . '"');
                $result = false;
            }
        }

        return $result;
    }


    /**
     * Эта функция вызывается только если из 1С пришла не пустая оплата
     * (массив Lines в JSON не пуст), чтобы не стересть случайно вручную разнесённые оплаты
     * @param array $linesFromJson
     * @param CPaymentObj $cpaymentObj
     * @param string $dbLogMsg
     * @return int
     */
    private function importLinesFromJson(
        array $linesFromJson,
        CPaymentObj $cpaymentObj,
        string $dbLogMsg
    ) {
        $affectedRows = 0;

        $lineObjBase = new CInvoicesObj();
        $lineObjBase->setMasterIdForDbLog($cpaymentObj->getId());

        // Загрузим строки которые уже раньше были в этой оплате
        $existLineIds = Db::getCol("SELECT ID FROM cpayment_cinvoice WHERE CPaymentID = @i", $cpaymentObj->getId());

        // Заготовим отдельный массив ID строк, которые были затронуты этим импортом из 1С
        // (пригодится для удаления строк)
        $lineIdsFromOneC = [];

        foreach ($linesFromJson as $indexFromJson => $lineFromJson) {

            // Чтобы правильно расчитать поле ClearingAmountInInvoiceCurrency и RateToInvoiceCurrency нам нужно
            // загрузить счёт из базы
            $cinvoice = $this->loadCInvoice($lineFromJson, $cpaymentObj['CompanyID']);

            $lineUpdateData = [
                'CPaymentID' => $cpaymentObj->getId(),
                'CInvoiceID' => $cinvoice['ID'] ?? $lineFromJson['CInvoiceID'],
                'CInvoiceName' => $cinvoice['Name'] ?? $lineFromJson['CInvoiceName'],
                'ClearingAmountInPaymentCurrency' => $lineFromJson['Amount'],
                'ClearingAmountInInvoiceCurrency' => $lineFromJson['AmountInCurrency'],
                'RateToInvoiceCurrency' => $lineFromJson['CurrencyRate'],
                'FromOneC' => 1,
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
                $lineIdsFromOneC[] = $existLineId;
            } else {
                $insertedLineId = $lineObj->insertDb($lineUpdateData, $dbLogMsg);
                $lineIdsFromOneC[] = $insertedLineId;
                $affectedRows++;
            }

        }

        // Теперь удалим из оплаты те строки, которые не были затронуты этим обновлением из 1С
        // причём выберем из базы только те, которые когда-то изначально приходили или обновлялись из 1С
        // (вручную добавленные в конец оплаты строки трогать не будем)
        $toBeDeletedIds = Db::getCol(
            "SELECT ID 
            FROM cpayment_cinvoice
            WHERE CPaymentID = @i 
                AND FromOneC = 1 
                AND ID NOT IN(@a)"
            , $cpaymentObj->getId()
            , $lineIdsFromOneC
        );
        foreach ($toBeDeletedIds as $toBeDeletedId) {
            $lineObj = clone $lineObjBase;
            $lineObj->setId($toBeDeletedId);
            $lineObj->loadData();
            $lineObj->deleteDb($dbLogMsg);
        }

        return $affectedRows;

    }

    private function loadCInvoice(array $lineFromOneC, int $companyId)
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
            $this->msgBuff->addWarning('От 1С не пришел CInvoiceID, а по номеру (' . htmlspecialchars($lineFromOneC['CInvoiceName']) . ') и дате (' . htmlspecialchars($lineFromOneC['CInvoiceDate']) . ') счёта нашлось несколько результатов');
            return [];
        }

        $this->msgBuff->addWarning('От 1С не пришел CInvoiceID, а по номеру (' . htmlspecialchars($lineFromOneC['CInvoiceName']) . ') и дате (' . htmlspecialchars($lineFromOneC['CInvoiceDate']) . ') счёт не найден');
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

        $subject = "$overpay $overpayAmount $paymentCurrencyIso от $customerAccountName в платёжке №$paymentName $paymentDate";

        $paymentLink = '<a href="' . Config::$localUrl . '/?a=CPayment&id=' . $cpaymentId . '">№' . htmlspecialchars($paymentName) . ' ' . $paymentDate . '</a>';
        $customerAccountNameHtml = htmlspecialchars($customerAccountName);
        $body = "$overpay $overpayAmount $paymentCurrencyIso от $customerAccountNameHtml в платёжке $paymentLink";

        // Узнаем всех ответсвенных Customer Service
        $fileNumbers = Db::getCol("SELECT FileNumber FROM cinvoice_file_amount WHERE CInvoiceID IN (@a)",
            $cinvoicesIds);
        $respoisbleCsIds = Db::getCol("SELECT ResponsibleCS FROM shipment WHERE Name IN (@a)", $fileNumbers);
        $responsibleEmails = Db::getCol("SELECT DISTINCT Email FROM user WHERE Email != '' AND ID IN (@a)",
            $respoisbleCsIds);
        if (!$responsibleEmails) {
            return;
        }

        Mailer::create($responsibleEmails, $subject, $body)
            ->wrapBodyWithHtmlTemplate()
            ->send($this->msgBuff);

    }
}
