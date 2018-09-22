<?php

namespace Forward\Module\Customer\StandartRateDialog;

use Forward\Classes\Constant;
use Forward\Field\RateFields;
use Forward\Module\Customer\CustomerObj;
use Forward\Module\Customer\Subtables\StandartRatesObj;
use Light\ApplicationAbstract;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Link;
use Light\Msg;
use Light\Response;
use Light\Template;

class StandartRateDialog
{
    /**
     * Не обычный метод для подтаблицы - здесь происходит роутинг ajax действий с записями подтаблицы
     * @param CustomerObj $customerObj
     */
    public static function proceed(CustomerObj $customerObj)
    {
        switch ($_GET['action']) {
            case "new":
                self::proceedNew($customerObj);
                break;

            case "edit":
                self::proceedEdit($customerObj);
                break;

            case "applyaddon":
                self::proceedShowApplyAddonDialog($customerObj);
                break;

            case "submit":
                self::proceedSubmit($customerObj);
                break;

            case "del":
                self::proceedDel($customerObj);
                break;

            default:
                Response::abort(400, 'Unknown action ' . $_GET['action']);
        }
    }

    /**
     * При ajax запросе при нажатии ссылки New Standart Rate - диалоговое окно
     * @param CustomerObj $customerObj
     */
    private static function proceedNew(CustomerObj $customerObj)
    {
        $srObj = new StandartRatesObj();
        $tdata = self::getTplData($srObj, $customerObj);

        $bodyHtml = Template::parseTpl('StandartRateDialog/StandartRateDialog.tpl.php', $tdata);
        Response::showDialog($bodyHtml, 'Save', null, 'Cancel', null, 1080, '90%', 10);
    }

    private static function proceedEdit(CustomerObj $customerObj)
    {
        $srObj = self::loadObjAndCheck($_GET['srid'], $customerObj->getId());
        $tdata = self::getTplData($srObj, $customerObj);

        $bodyHtml = Template::parseTpl('StandartRateDialog/StandartRateDialog.tpl.php', $tdata);
        Response::showDialog($bodyHtml, 'Save', null, 'Cancel', null, 1080, '90%', 10);
    }

    private static function getTplData(StandartRatesObj $srObj, CustomerObj $customerObj)
    {
        $tdata = $srObj->getTplData(F::MODE_EDIT);
        $tdata['formActionUrl'] = Link::create('Customer')
            ->view($customerObj->getId())
            ->func('StandartRateDialog')
            ->params([
                'action' => 'submit',
                'srid' => $srObj->getId()
            ])
            ->getHref();

        $buyingSurcharges = new BuyingSurcharges($srObj);
        $tdata['BuyingSurchargesHtml'] = $buyingSurcharges->getHtmlEdit();

        $sellingSurcharges = new SellingSurcharges($srObj);
        $tdata['SellingSurchargesHtml'] = $sellingSurcharges->getHtmlEdit();

        $tdata['fields'] = $srObj->getFieldset()->getFieldsForFormJs();

        return $tdata;
    }

    private static function proceedSubmit(CustomerObj $customerObj)
    {
        $srId = (int)$_GET['srid'];
        if ($srId) {
            //Сохраняем уже существующий тариф
            $srObj = self::loadObjAndCheck($srId, $customerObj->getId());
        } else {
            //Создаём новый тариф
            $srObj = new StandartRatesObj();
        }

        $srObj->setMasterIdForDbLog($customerObj->getId());
        $fieldsData = $srObj->getFieldset()->getDataFromRequest($_POST);

        if ($fieldsData['Name'] === "") {
            $fieldsData['Name'] = Constant::generageQuotationName($fieldsData['ValidFrom'],
                $fieldsData['DepartureIATA'], $fieldsData['ArrivalIATA'], 0, $fieldsData['AirlineAlphaCode']);
        }

        if ($srId) {
            //Сохраняем уже существующий тариф
            $srObj->updateDb($fieldsData);
        } else {
            //Создаём новый тариф
            $fieldsData['CustomerID'] = $customerObj->getId();
            $srObj->insertDb($fieldsData);
        }

        $dummyMsgBuff = new Msg();
        $buyingSurcharges = new BuyingSurcharges($srObj);
        $buyingSurcharges->proceedSubmit($dummyMsgBuff);

        $sellingSurcharges = new SellingSurcharges($srObj);
        $sellingSurcharges->proceedSubmit($dummyMsgBuff);

        //Отправляем инструкцию на клиент, чтобы перезагрузилась таблица со стандартными тарифами
        Response::sendHeaderJsReloadTable('StandartRates', ['StandartRates_findid' => $srObj->getId()]);
        ApplicationAbstract::terminate();
    }

    /**
     * При ajax запросе на удаление какого-то тарифа
     * @param CustomerObj $customerObj
     */
    private static function proceedDel(CustomerObj $customerObj)
    {
        $srObj = self::loadObjAndCheck($_GET['srid'], $customerObj->getId());

        if (isset($_GET['confirm'])) {
            //Запрос пришел из диалогового окна подтверждающего удаление - реально удаляем!
            $srObj->deleteDb();

            //Удалим также данные из подтаблиц
            Db::deleteRow('customer_standratrates_buyingsurcharges', 'CustomerStandartRateID', $srObj->getId());
            Db::deleteRow('customer_standratrates_sellingsurcharges', 'CustomerStandartRateID', $srObj->getId());

            //Отправляем инструкцию на клиент, чтобы перезагрузилась таблица со стандартными тарифами
            Response::sendHeaderJsReloadTable('StandartRates');
            ApplicationAbstract::terminate();
        }

        $formActionUrl = Link::create('Customer')
            ->view($customerObj->getId())
            ->func('StandartRateDialog')
            ->params([
                'action' => 'del',
                'srid' => $srObj->getId(),
                'confirm' => 1
            ])
            ->ajax()
            ->getHref();
        $bodyHtml = '<form action="' . $formActionUrl . '">Delete <strong>' . $srObj->getDescriptionHtml() . '</strong>?</form>';

        Response::showDialog($bodyHtml, 'Delete');
    }

    /**
     * Пробует загрузить объект стравки из базы.
     * Если обхекта не найдено, значит какой-то странный id пришел в ajax запросе - вернём простой 404
     * @param int $srid
     * @param int $customerId
     * @return StandartRatesObj
     */
    private static function loadObjAndCheck($srid, $customerId)
    {
        //Проверим существование объекта в базе, попробовав его загрузить
        $srObj = new StandartRatesObj();
        $srObj->setId($srid);
        if (!$srObj->loadData()) {
            Response::abort(404);
        }

        //Также надо сделать провеку, что запрошенный srid относится к текущему клиенту
        if ($srObj['CustomerID'] != $customerId) {
            Response::abort(403);
        }

        $srObj->setMasterIdForDbLog($customerId);

        return $srObj;
    }

    private static function proceedShowApplyAddonDialog(CustomerObj $customerObj)
    {
        //Подготовим форму с полями для ввода addon
        $currencyIso = $_GET['CurrencyISO'];
        $fieldset = new Fieldset(
            RateFields::minAmount('Addon')->setUom($currencyIso),
            RateFields::amountN('Addon')->setUom($currencyIso),
            RateFields::amount45('Addon')->setUom($currencyIso),
            RateFields::amount100('Addon')->setUom($currencyIso),
            RateFields::amount200('Addon')->setUom($currencyIso),
            RateFields::amount300('Addon')->setUom($currencyIso),
            RateFields::amount500('Addon')->setUom($currencyIso),
            RateFields::amount750('Addon')->setUom($currencyIso),
            RateFields::amount1000('Addon')->setUom($currencyIso),
            RateFields::amount2000('Addon')->setUom($currencyIso)
        );

        //Какие аддоны применять? Зависит от объекта Customer
        //если стоит галочка UseDefaultAddon, то используем стандартные аддоны
        //в противном случае используем аддоны прописаные в карточке этого клиента
        if ($customerObj['UseDefaultAddon']) {
            //Загрузим стандартные адоны
            $addonsSource = Constant::getDefaultAddons();
        } else {
            $addonsSource = $customerObj->getData();
        }

        $data = [];
        if ($currencyIso == 'USD' || $currencyIso == 'EUR') {
            foreach ($fieldset->getKeys() as $fieldKey) {
                $customerFieldKey = str_replace('Addon', 'Addon' . $currencyIso,
                    $fieldKey); //Вставляем валюту в ключ поля
                $data[$fieldKey] = $addonsSource[$customerFieldKey];
            }
        }

        $tdata = $fieldset->getTplData($data, F::MODE_EDIT);
        $body = Template::parseTpl('StandartRateDialog/ApplyAddonDialog.tpl.php', $tdata);

        Response::showDialog($body, 'Apply', 'Customer.onApplyAddonDialogSubmit');
    }
} 