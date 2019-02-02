<?php

namespace Forward\Module\Shipment\CostSheet;

use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Forward\Classes\ShipmentGp;
use Forward\Module\Shipment\CostSheet\Subtables\CInvoices;
use Forward\Module\Shipment\CostSheet\Subtables\Expenses;
use Forward\Module\Shipment\CostSheet\Subtables\Income;
use Forward\Module\Shipment\CostSheet\Subtables\VInvoices;
use Forward\Module\Shipment\Shipment;
use Forward\Module\Shipment\ShipmentObj;
use Light\Css;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldMoney;
use Light\FilesFolder;
use Light\Link;
use Light\Msg;
use Light\Table;
use Light\Template;
use Light\User;

/**
 * Класс, который ответчает за рендринг, сохранение и ajax обновление Cost-sheet'а
 */
class CostSheetObj
{
    private $_shipmentObj;

    private $_income;
    private $_expenses;

    public function __construct(ShipmentObj $shipmentObj)
    {
        $this->_shipmentObj = $shipmentObj;

        $this->_income = new Income($shipmentObj);
        $this->_expenses = new Expenses($shipmentObj);
    }

    public function getHtmlView()
    {
        $tdata = $this->getTplData();

        //Добавим подтаблицу с историей изменений
        $tdata['History'] = $this->getHtmlHistoryTable();

        return Template::parseTpl('CostSheet/CostSheetTab.tpl.php', $tdata);
    }

    public function getHtmlEditIncome()
    {
        return $this->_income->getHtmlEdit();
    }

    public function getHtmlEditExpenses()
    {
        return $this->_expenses->getHtmlEdit();
    }

    public function getHtmlViewIncome()
    {
        return $this->_income->getHtmlView();
    }

    public function getHtmlViewExpenses()
    {
        return $this->_expenses->getHtmlView();
    }

    protected function getTplData()
    {
        $tdata = [];

        $tdata['EGP_value'] = $this->_shipmentObj['CacheEGPinUSD'];
        $tdata['EGP'] = FieldMoney::beautify($tdata['EGP_value']);

        $tdata['GP_value'] = $this->_shipmentObj['CacheGPinUSD'];
        $tdata['GP'] = FieldMoney::beautify($tdata['GP_value']);

        $tdata['Deviation_value'] = $this->_shipmentObj['CacheGPDeviationinUSD'];
        $tdata['Deviation'] = FieldMoney::beautify($tdata['Deviation_value']);

        //Добавим подтаблицы Estimated Income и Estimated Expenses
        $tdata['Income'] = $this->_income->getHtmlView();
        $tdata['Expenses'] = $this->_expenses->getHtmlView();

        //Добавим подтаблицы со счетами
        $cinvoices = new CInvoices($this->_shipmentObj, false);
        $tdata['CInvoices'] = $cinvoices->getHtmlTable();
        $vinvoices = new VInvoices($this->_shipmentObj, false);
        $tdata['VInvoices'] = $vinvoices->getHtmlTable();

        return $tdata;
    }

    public function getHtmlHistoryTable()
    {
        $query = "SELECT *
                  FROM cshistory
                  WHERE ShipmentID = @i
                  ORDER BY ID DESC";
        $historyData = Db::getAll($query, $this->_shipmentObj->getId());
        if (!$historyData) {
            return '';
        }

        //Подогтовим таблицу с историей изменения Cost-sheet'а
        $table = new Table('CostSheetHistory');
        $table->allowTablePrintHeadBlock = false;
        $table->getFieldset()->addFieldsCreatedBy();
        $table->addFields(
            F::createMoney('EGPinUSD', [
                'name' => 'EGP (USD)',
                'cssClassNegative' => 'bad'
            ])
        );

        $table->getFieldsetLast()->addField(
            F::createHtml('View', [
                'name' => '',
                'getHtmlFunction' => function ($value, $data) {
                    return Link::create(Shipment::class, 'View')
                        ->view($this->_shipmentObj->getId())
                        ->func('cs')
                        ->blank()
                        ->params(['histid' => $data['ID']])
                        ->getHtml();
                }
            ])
        );

        $table->setTableData($historyData);

        $outHtml = '<h2>History</h2>';
        $outHtml .= $table->getHtmlTable();

        return $outHtml;
    }

    /**
     * Метод вызывается из CostSheetRouter::proceedAjaxSubmit() при сохранении Cost-sheet'а пользователем
     * Метод берёт данные из POST и записывает в базу
     */
    public function saveFromPost()
    {
        $dummyMsgBuff = new Msg();
        $this->_income->proceedSubmit($dummyMsgBuff);
        $this->_expenses->proceedSubmit($dummyMsgBuff);

        //Если в POST запросе пришлос скрытое поле NewQuotationID - значит нужно обновить номер котировки в шипменте
        if (isset($_POST['NewQuotationID']) && $_POST['NewQuotationID']) {
            $this->_shipmentObj->updateDb(['QuotationID' => (int)$_POST['NewQuotationID']], 'Save Cost-sheet');
        }

        //Обновим EGP и Deviation внутри Shipment
        $this->updateShipmentCache();

        //Сохраним статичную версию кост-шита в папку
        $this->saveToHistory();
    }

    /**
     * Обновляет поля с EGP, Total, CSCount и проч...
     * @return void
     */
    public function updateShipmentCache()
    {
        //Обновляем GP, EGP и Deviation
        ShipmentGp::updateGpEgpDeviation($this->_shipmentObj['Name']);
        ShipmentGp::flush();

        //Обновляем критерии связанные с финансами
        ShipmentCriteria::updateRelatedInv($this->_shipmentObj->getId(), 'Cost-sheet Save');
        ShipmentCriteria::flush();

        //Обновим кол-во CS
        $fieldsData['CacheCSCount'] = $this->_shipmentObj['CacheCSCount'] + 1;

        //Занесём обновлённые данные в базу, при этом shipmentObj загрузится заново - это полезно после всех обновлений выше
        $this->_shipmentObj->updateDb($fieldsData, 'Cost-sheet Save');
    }

    private function getHistoryFolder()
    {
        $shipmentId = $this->_shipmentObj->getId();
        $subfolder = floor($shipmentId / 1000);
        return 'costsheethistory/' . $subfolder . '/' . $shipmentId;
    }

    private function getHistoryFilepathAbs($histId)
    {
        $folder = $this->getHistoryFolder();
        $histId = (int)$histId;
        $filepath = $folder . '/' . $histId . '.html';
        return FilesFolder::getAbsolutePath($filepath);
    }

    public function saveToHistory()
    {
        //Сохраним в базу
        $fieldValues = [
            'CreatedBy' => User::getId(),
            'ShipmentID' => $this->_shipmentObj->getId(),
            'EGPinUSD' => $this->_shipmentObj['CacheEGPinUSD']
        ];

        $histId = Db::insertRow($fieldValues, 'cshistory');

        //Сохраним печатную версию в файл
        //Для начала получим общую информацию о перевозке для шапки
        $tdata = CostSheetRouter::getCsHeadTplData($this->_shipmentObj);
        //Добавим имя автора для шапки
        $tdata['CreatedBy_raw'] = User::getName();
        //Добавляем подтаблицы и различные переменные GP / EGP / Deviation
        $tdata += $this->getTplData();
        //Принудительно выключаем редактирование (чтобы не рендрить кнопку Save)

        Css::add('CostSheet.css');
        $printHtml = Template::parseTpl(LIGHT_DIR_PROJECT . '/Module/Shipment/CostSheet/CostSheetHistory.tpl.php',
            $tdata);
        //Преждем чем сохранять файл создадим папку под текущий shipment
        $folder = $this->getHistoryFolder();
        FilesFolder::mkdir($folder);
        //Теперь можем сохранить в эту папку файл с уникальным именем - $histId
        $filepathAbs = $this->getHistoryFilepathAbs($histId);
        file_put_contents($filepathAbs, $printHtml);
    }

    public function getHtmlFromHistory($histId)
    {
        //Попробуем найти файл с полной HTML вёрсткой этой версии Cost - sheet'а
        $filepathAbs = $this->getHistoryFilepathAbs($histId);
        if (is_file($filepathAbs)) {
            return file_get_contents($filepathAbs);
        }

        return 'Not found';
    }
}
