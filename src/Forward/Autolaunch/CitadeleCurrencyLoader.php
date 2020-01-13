<?php
namespace Forward\Autolaunch;

use Forward\Module\CurrencyRateCitadele\CurrencyRateCitadeleObj;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Misc;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Light\Table;

class CitadeleCurrencyLoader extends AutolaunchAbstract
{
    protected static $title = 'Update Currency from Citadele';
    protected static $description = '<a href="http://www.citadele.ee/ru/rates/" target="_blank">http://www.citadele.ee/ru/rates/</a>';

    protected function run(array $params)
    {
        $date = date('Y-m-d');

        $this->_msgBuff->addInfo(
            'Loading rates from http://www.citadele.ee/ru/rates/ on ' . FieldDate::beautify($date)
        );

        $html = file_get_contents('http://www.citadele.ee/ru/rates/');
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        $xpath = new \DOMXPath($doc);
        $tables = $xpath->query("//table[@class='tables']");

        $eurTableNode = $tables->item(0);
        $usdTableNode = $tables->item(1);

        //Загрузим данные по покупке-продаже EUR
        $eurRows = $this->getRowsFromTable($xpath, $eurTableNode);
        //Выделим столбец с валютами, оставив массив $eurRows только с самими курсами
        $currencies = [];
        foreach ($eurRows as $i => $rowData) {
            preg_match('/[A-Z]{3}/i', $rowData[0], $m);
            $currencyIso = $m[0];
            if ($currencyIso === 'RUR') {
                //На сайте citadele рубли обозначаются как RUR. Приведём к принятому у нас обозначению RUB
                $currencyIso = 'RUB';
            }
            $currencies[] = $currencyIso;
            //Удаляем первый столбец из текущей строки в таблице $eurRows
            array_shift($eurRows[$i]);
        }

        //Загрузим данные по покупке-продаже USD - здесь не будет первого столбца с идентификатором валюты, всё немного проще
        $usdRows = $this->getRowsFromTable($xpath, $usdTableNode);

        //Список валют, которые нужно обновлять
        $usedCurrencyIso = Db::getAssoc("SELECT Name, Name FROM currency");

        $inserted = 0;
        $updated = 0;
        $notChanged = 0;

        $logTableData = [];

        //Теперь идём по всем загруженным валютам проверяем, нужен ли нам этот курс? Если нужен, вставляем его в базу
        foreach ($currencies as $index => $currencyIso) {
            if (isset($usedCurrencyIso[$currencyIso])) {

                $rateObj = new CurrencyRateCitadeleObj();

                //Подходящая нам валюта!
                $fieldsData = [
                    'Name' => $currencyIso,
                    'ValidDate' => $date,
                    'BuyingForEUR' => (float)$eurRows[$index][0] ?: 1,
                    'SellingForEUR' => (float)$eurRows[$index][1] ?: 1,
                    'BuyingForUSD' => (float)$usdRows[$index][0] ?: 1,
                    'SellingForUSD' => (float)$usdRows[$index][1] ?: 1
                ];

                //На сайте http://www.citadele.ee/ru/rates/ для валют AUD, GBP, NZD указана обратная котировка за Доллар США.
                if ($currencyIso === 'AUD' || $currencyIso === 'GBP' || $currencyIso === 'NZD') {
                    $fieldsData['BuyingForUSD'] = 1 / $fieldsData['BuyingForUSD'];
                    $fieldsData['SellingForUSD'] = 1 / $fieldsData['SellingForUSD'];
                }

                //Попробуем найти этот курс в базе по набору ключевых полей
                $query = "SELECT ID
                          FROM currencyrate_citadele
                          WHERE Name = @s_currencyIso
                              AND ValidDate = @s_date";
                $id = Db::getOne($query, $currencyIso, $date);
                if ($id) {
                    //Обновим уже существующий курс в базе
                    $affectedRows = $rateObj->setId($id)->updateDb($fieldsData, 'Update from Citadele website');
                    if ($affectedRows) {
                        $updated++;
                        $status = 'Updated';
                    } else {
                        $notChanged++;
                        $status = 'Not changed';
                    }
                } else {
                    //Вставим новый крус в базу
                    $rateObj->insertDb($fieldsData, 'Update from Citadele website');
                    $inserted++;
                    $status = 'Inserted';
                }

                $logTableData[] = $fieldsData + ['Status' => $status];
            }
        }

        $logTable = new Table('CitadeleCurrencyLoaderLog');
        $logTable->addFields((new CurrencyRateCitadeleObj())->getFieldset());
        $logTable->createField('Status', F::TYPE_STRING);
        $logTable->setTableData($logTableData);

        $this->_msgBuff->addHtml($logTable->getHtmlTable());

        $msg = $inserted . ' new currency rates added';
        if ($inserted) {
            $this->_msgBuffShort->addSuccess($msg);
        } else {
            $this->_msgBuffShort->addInfo($msg);
        }

        if ($updated > 0) {
            $this->_msgBuffShort->addSuccess(
                $updated . ' currency rates updated'
            );
        }

        if ($notChanged > 0) {
            $this->_msgBuffShort->addSkipp(
                $notChanged . ' currency rates not changed'
            );
        }
    }

    private function getRowsFromTable(\DOMXPath $xpath, \DOMNode $tableNode)
    {
        $eurRows = $xpath->query("tr", $tableNode);
        $rowsData = [];
        $firstRowSkipped = false;
        foreach ($eurRows as $eurRow) {
            if (!$firstRowSkipped) {
                //Первая строка - это заголовок, пропускаем
                $firstRowSkipped = true;
                continue;
            }

            $rowData = [];
            /** @noinspection ForeachSourceInspection */
            foreach ($eurRow->childNodes as $eurRowChildNode) {
                /** @var \DOMNode $eurRowChildNode */
                if ($eurRowChildNode->nodeName === 'td') {
                    $rowData[] = trim($eurRowChildNode->textContent);
                }
            }

            $rowsData[] = $rowData;
        }

        return $rowsData;
    }

}
