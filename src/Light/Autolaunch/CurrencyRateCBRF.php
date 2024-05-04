<?php

namespace Light\Autolaunch;

use Light\Currency;
use Light\Dates;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Misc;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Light\Module\CurrencyRate\CurrencyRateObj;

class CurrencyRateCBRF extends AutolaunchAbstract
{
    protected static $_title = 'Обновить курсы валют ЦБРФ';
    protected static $_description = 'Обновляет курсы валют с сайта ЦБРФ';

    #[\Override]
    public function initFieldset(): void
    {
        $this->addFields(
            F::createDate('ValidDate', [
                'name' => 'Дата',
                'default' => (new \DateTime('+1 day'))->format('Y-m-d')
            ])
        );
    }

    #[\Override]
    protected function run(array $params)
    {
        if (isset($GLOBALS['argv'][2]) && $GLOBALS['argv'][2] === 'today') {
            $params['ValidDate'] = date('Y-m-d');
        } elseif (!$params['ValidDate']) {
            $params['ValidDate'] = '+1 day';
        }

        $dateTimeRequest = new \DateTime($params['ValidDate']);
        $msg = 'Запрошены курсы на дату ' . $dateTimeRequest->format('d.m.Y');
        $this->_msgBuff->addInfo($msg);
        $this->_msgBuffShort->addInfo($msg);

        $isSuccess = $this->loadRates($dateTimeRequest);
        if (!$isSuccess
            && $dateTimeRequest->format('Y-m-d') === date('Y-m-d')
            && Dates::isWorkingDay(date('Y-m-d'))
        ) {
            // Были запрошены курсы на сегодняшний день, и это рабочий день, но курсов почему-то нет!
            // сообщим в rollbar
            \Light\ErrorHandler::reportException(new \Exception($this->_msgBuff->getText()));
        }
    }

    private function loadRates(\DateTime $dateTimeRequest)
    {
        $url = 'http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $dateTimeRequest->format('d/m/Y');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $r = curl_exec($ch);
        if (!$r) {
            $msg = 'Нет ответа от сайта ' . $url;
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);
            return false;
        }

        try {
            /** @var \SimpleXMLElement $xmlObject */
            $xmlObject = simplexml_load_string($r);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);
            return false;
        }

        if (!$xmlObject) {
            $msg = 'Не удалось распарсить ответ:<br>' . $r;
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);
            return false;
        }

        $currencies = Db::getCol("SELECT Name FROM currency");

        $rates = [];

        $realDate = date('Y-m-d', strtotime((string)$xmlObject['Date']));

        // Если была запрошена загрузка курсов на сегодняшний день или в прошлом, но на сайте ЦБ нет такиех курсов
        // (например, это выходные), то принудительно выставляем realDate в запрошенную дату - таким образом происходит загрузка курсов на сегодня
        $todayDate = date('Y-m-d');
        $requestedDate = $dateTimeRequest->format('Y-m-d');
        if ($realDate !== $requestedDate && $requestedDate <= $todayDate) {
            $msg = 'По запрошенной дате ' . FieldDate::beautify($requestedDate) . ' сайт ЦБ вернул курсы на ' . FieldDate::beautify($realDate) . ', сохраняем в базу как курсы на запрошенную дату ' . FieldDate::beautify($requestedDate);
            $this->_msgBuff->addWarning($msg);
            $this->_msgBuffShort->addWarning($msg);
            $realDate = $requestedDate;
        } else {
            $msg = 'Получены курсы на ' . FieldDate::beautify($realDate);
            $this->_msgBuff->addInfo($msg);
            $this->_msgBuffShort->addInfo($msg);
        }

        $this->_msgBuff->addEmptyLine();

        $xmlValues = $xmlObject->xpath('Valute');
        foreach ($xmlValues as $info) {
            $iso = (string)$info->xpath('CharCode')[0];
            if (!in_array($iso, $currencies, true)) {
                // Этой валюты нет в нашем справочнике валют, пропускаем её парсинг
                continue;
            }
            $nominal = (int)$info->xpath('Nominal')[0];
            $rate = str_replace(',', '.', $info->xpath('Value')[0]);

            $rates[$iso]['RateToRUB'] = $rate / $nominal;
        }

        //Узнаем все валюты, которы должны обновляться с помощью текущего провайдера
        $inserted = 0;
        $updated = 0;
        foreach ($rates as $currencyIso => $rateInfo) {

            $fieldsData = [
                'CurrencyISO' => $currencyIso,
                'RateToRUB' => $rateInfo['RateToRUB'],
                'ValidDate' => $realDate
            ];

            $rateObj = new CurrencyRateObj();

            //Попробуем найти этот курс в базе по набору ключевых полей
            $id = Db::getOne(
                "SELECT ID FROM currencyrate WHERE CurrencyISO = @s AND ValidDate = @s"
                , $currencyIso
                , $realDate
            );

            if ($id) {

                //Обновим уже существующий курс в базе
                $affectedRows = $rateObj->setId($id)->updateDb($fieldsData, 'Automatic update');
                if ($affectedRows) {
                    $updated++;
                }

            } else {

                //Вставим новый крус в базу
                $rateObj->insertDb($fieldsData, 'Automatic update');
                $inserted++;

            }

            $this->_msgBuff->addInfo($currencyIso . ' ' . $rateInfo['RateToRUB']);
        }

        Currency::updateCurrencyRateTodayAfterSend();

        $this->_msgBuff->addEmptyLine();

        if ($inserted) {
            $msg = 'Добавлено: ' . $inserted;
            $this->_msgBuff->addSuccess($msg);
            $this->_msgBuffShort->addSuccess($msg);
        }

        if ($updated) {
            $msg = 'Обновлено: ' . $updated;
            $this->_msgBuff->addSuccess($msg);
            $this->_msgBuffShort->addSuccess($msg);
        }

        if ($updated + $inserted === 0) {
            $msg = 'Нет изменений';
            $this->_msgBuff->addSkipp($msg);
            $this->_msgBuffShort->addSkipp($msg);
        }

        return true;
    }
}
