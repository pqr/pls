<?php
namespace Forward\Module\Shipment\CostSheet;

use Forward\Application;
use Forward\Module\SearchAirfreightRateDialog\SearchAirfreightRateDialog;
use Forward\Module\Shipment\CostSheet\Subtables\CommonDataProvider;
use Forward\Module\Shipment\ShipmentObj;
use Light\Css;
use Light\Currency;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Js;
use Light\Response;
use Light\Template;
use Light\User;

/**
 * Этот класс имеет один публичный метод proceed() внутри которого проверяются различные GET и POST параметры
 * и происходит вызов других proceed методов, чтобы отрендрить нужную страницу.
 *
 * Это класс-роутер - он оперирует с запросом, но не реализует никакой бизнес логики загрузки/сохранения кост-шита
 * Вся бизенс-логика сосредоточена в классе CostSheetObj
 */
class CostSheetRouter
{
    /**
     * Метод формирует переменные для шаблона Cost-sheet'а - для его шапки с общей информацией по перевозке
     * Выносим в отдельный метод, т.к. используется в двух местах: при первичном рендринге страницы Cost-sheet и
     * при рендринге статичной версии в History
     * @return array
     */
    public static function getCsHeadTplData(ShipmentObj $shipmentObj)
    {
        //Чтобы работала кнопка Color, ей нужно задать правильный source
        $shipmentObj->getField('Color')->setParam(
            'source',
            '?a=Shipment&id=' . $shipmentObj->getId() . '&func=changeField'
        );

        //Заполним массив tdata данными из перевозки - нужно отобразить над Cost-sheet'ами
        $tdata = $shipmentObj->getTplData(F::MODE_VIEW);

        //Для сообщения "Attention: prepaid customer"
        $tdata['isPrepaidCustomer'] = false;
        if ($shipmentObj['CustomerID']) {
            $creditAmount = Db::getOne("SELECT CreditAmount FROM customer WHERE ID = " . (int)$shipmentObj['CustomerID']);
            if ($creditAmount == 0) {
                $tdata['isPrepaidCustomer'] = true;
            }
        }

        return $tdata;
    }

    /**
     * При входе в раздел Cost-sheet нужно целую страницу
     * @return string
     */
    private static function proceedView(ShipmentObj $shipmentObj)
    {
        Js::add('Module/Shipment/CostSheet/CostSheet.js');
        Css::add('CostSheet.css');

        SearchAirfreightRateDialog::initCssJs();

        //Передадим в JavaScript окружение текущие значения GP - пригодятся для режима редактирования
        $dataForSearchAirfreigthRate = [
            'VendorID' => $shipmentObj->getVendorID(),
            'DepartureIATA' => $shipmentObj->getDepartureIATA(),
            'ArrivalIATA' => $shipmentObj->getArrivalIATA(),
            'AirlineAlphaCode' => $shipmentObj->getAirlineAlphaCode(),
            'Incoterms' => $shipmentObj->getIncoterms(),
            'ETD' => $shipmentObj->getETD(),
            'ChargeableWeight' => $shipmentObj->getChargeableWeight()
        ];

        Response::addJsData(
            [
                'shipmentId' => $shipmentObj->getId(),
                'egp' => $shipmentObj->getCacheEGPinUSD(),
                'gp' => $shipmentObj->getCacheGPinUSD(),
                'deviation' => $shipmentObj->getCacheGPDeviationinUSD(),
                'QuotationID' => $shipmentObj->getQuotationID(), //понадобится для заполнения диалогового окна Fill from Quotation / Standart Rate
                'DataForSearchAirfreigthRate' => $dataForSearchAirfreigthRate //понадобится для запуска диалогового окна Search Airfreight Rate
            ]
        );

        //Загрузим курсы валют на текущий день и проинициализируем их в JS массиве
        Currency::putJsRates();

        //Добавим в JS окружение суммы счетов (Customer Invocies и Vendor Invoices)
        //Для этого подготовим вспомагательную фукнцию, которая из массива счетов сформирует ассоциативный массив ID => AmountOnShipment
        $mapInvIdAmountOnShipment = function (array $invoices) {
            $out = [];
            foreach ($invoices as $invData) {
                $out[$invData['ID']] = [
                    'CurrencyISO' => $invData['CurrencyISO'],
                    'Amount' => (float)$invData['AmountOnShipment']
                ];
            }

            return $out;
        };

        Response::addJsData(
            [
                'invoicesAmounts' => [
                    'income' => $mapInvIdAmountOnShipment(
                        CommonDataProvider::getCInvoices($shipmentObj['Name'])
                    ),
                    'expenses' => $mapInvIdAmountOnShipment(
                        CommonDataProvider::getVInvoices($shipmentObj['Name'])
                    )
                ]
            ]
        );

        $headHtml = Template::parseTpl('CostSheet/CostSheetHead.tpl.php', self::getCsHeadTplData($shipmentObj));

        $cs = new CostSheetObj($shipmentObj);
        $csHtml = $cs->getHtmlView();

        return $headHtml . $csHtml;
    }

    public static function hasAccessWriteCostSheet(ShipmentObj $shipmentObj)
    {
        return !$shipmentObj['FileClosed'] || User::hasSpecialAccess(Application::SA_SHIPMENT_CHANGECLOSED);
    }

    /**
     * Возвращает HTML код для редактирования.
     * Поскольку редактируем две подтаблицы - возвращается json с двумя html блоками: Income и Expenses
     */
    private static function proceedAjaxEdit(ShipmentObj $shipmentObj): void
    {
        if (!self::hasAccessWriteCostSheet($shipmentObj)) {
            Response::abort(403, 'Нет прав на редактирование Cost-sheet в перевозках в статусе Blocked или Closed');
        }

        $cs = new CostSheetObj($shipmentObj);

        $out = [
            'IncomeHtml' => $cs->getHtmlEditIncome(),
            'ExpensesHtml' => $cs->getHtmlEditExpenses(),
        ];

        Response::json($out);
    }

    /**
     * Возвращает JSON данные для копирования из другого кост-шита
     */
    public static function proceedAjaxCopyFromCs(): void
    {
        //Узнаем ShipmentID по переданному номеру файла
        $out = [];
        $sid = Db::getOne("SELECT ID FROM shipment WHERE Name = @i", $_GET['FileNumber']);
        if ($sid) {
            //Перевозка найдена, загрузим все строки из её Cost-sheet'а
            $query = "SELECT Name
                          , Price
                          , Quantity
                          , CONCAT(CurrencyISO, ' ', Amount) AS Amount
                      FROM @n
                      WHERE ShipmentID = " . $sid;
            $out['income'] = Db::getAll($query, 'csincome');
            $out['expenses'] = Db::getAll($query, 'csexpenses');
        }

        Response::json($out);
    }

    /**
     * Обрабатывает сохранение одной из вкладок, после чего возвращает HTML код для просмотра изменённых данных
     * Поскольку возвращать HTML код нужно для как минимум двух подтаблиц Income и Excepnses - оборачиваем ответ в JSON
     * В дополнение к этому отправим в барузер новый HTML код для подтаблицы History, новый EGP и новый Deviation
     */
    private static function proceedAjaxSubmit(ShipmentObj $shipmentObj): void
    {
        if (!self::hasAccessWriteCostSheet($shipmentObj)) {
            Response::abort(403, 'Нет прав на редактирование Cost-sheet в перевозках в статусе Blocked или Closed');
        }

        $cs = new CostSheetObj($shipmentObj);
        $cs->saveFromPost();

        //Создаём объект заново, чтобы всё заново проинициализировалось и загрузилось из базы
        $shipmentObj->loadData();
        $cs = new CostSheetObj($shipmentObj);
        $out = [
            'IncomeHtml' => $cs->getHtmlViewIncome(),
            'ExpensesHtml' => $cs->getHtmlViewExpenses(),
            'EGP' => $shipmentObj['CacheEGPinUSD'],
            'Deviation' => $shipmentObj['CacheGPDeviationinUSD'],
            'HistoryHtml' => $cs->getHtmlHistoryTable(),
            'QuotationIDHtml' => $shipmentObj->getFieldHtml('QuotationID')
        ];

        Response::json($out);
    }

    private static function proceedShowHistory(ShipmentObj $shipmentObj): void
    {
        $histId = (int)$_GET['histid'];
        $cs = new CostSheetObj($shipmentObj);

        print $cs->getHtmlFromHistory($histId);
        Application::terminate();
    }

    /**
     * Диспетчер запроса - смотрит на GET параметры и запускает соотвествующие методы
     * В этот метод мы попадаем из Shipment::viewFuncCs(), т.к. в url присутсвуют параметры id=...&func=cs
     * @return null|string
     */
    public static function proceed(ShipmentObj $shipmentObj)
    {
        $content = null;

        if (isset($_GET['editcs'])) {

            self::proceedAjaxEdit($shipmentObj);

        } elseif (isset($_GET['submitcs'])) {

            self::proceedAjaxSubmit($shipmentObj);

        } elseif (isset($_GET['histid'])) {

            self::proceedShowHistory($shipmentObj);

        } else {

            //Рендрим страницу Cost-sheet'а в режиме просмотра
            //При этом нужно ещё отрендрить верхнее меню с табами и подменю, но из класса CostSheetRouter это не удастся
            //сделать т.к. все нужные методы находятся уровнем выше в ModuleAbstract
            //Поэтому вернём в качестве результатов работы proceedView() HTML код трёх кост-шитов, а ModuleAbstract уже
            //отрендрит его
            $content = self::proceedView($shipmentObj);
        }

        return $content;
    }
} 