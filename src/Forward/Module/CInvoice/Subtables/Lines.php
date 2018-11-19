<?php

namespace Forward\Module\CInvoice\Subtables;

use Forward\Application;
use Forward\Classes\CInvoiceCommon;
use Forward\Classes\Constant;
use Forward\Module\ItemList\ItemList;
use Light\Db;
use Light\Link;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;
use Light\User;

class Lines extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('cinvoiceline');

        $this->allowTableLayouts = false;

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->tableLinkModule = 'Shipment';
        $this->tableLinkFieldKeys = ['FileNumber', 'ContainerName'];
        $this->tableLinkIdFieldKey = 'ShipmentID';
        $this->tableLinkUrlParams = ['func' => 'cs', 'fromInvNum' => $masterObj['Name']];

        $this->_viewTableLayout = 'FileNumber|Name|UOMID|Quantity|Price|Amount';
        $this->_editTableLayout = $this->_viewTableLayout;

        $this->_tablePrintTotalRecordsFound = true;
        $this->tableTotal = ['Name' => 'Total'];

        $this->_cacheFieldsKeys = ['FileNumber'];
        $this->_cacheOnlyUniqueValues = true;

        $this->getField('Amount')->setParam('nameTable', "Amount in " . $masterObj['CurrencyISO']);

        if (Constant::isOneCCompany($masterObj['CompanyID'])) {
            $this->getField('Name')->setGetHtmlFunction(function ($value, array $data, $outHtml) {
                if (!isset($data['ID'])) {
                    // Это строка Total
                    return $outHtml;
                }

                // Попробуем найти перевод и приклеить к описанию услуги
                $translation = Db::getRow("SELECT ID, NameRu FROM list_chargedescription WHERE Name = @s LIMIT 1",
                    $value);

                if ($translation && $translation['NameRu'] !== '') {

                    $outHtml .= '<br><small>' . htmlspecialchars($translation['NameRu']) . '</small>';

                } elseif (User::hasWriteAccess('ItemList')) {

                    $linkText = 'Перевести';
                    $link = Link::create(ItemList::class, $linkText);
                    if ($translation) {

                        $link->edit($translation['ID']);

                    } else {

                        $link->params(['addnew' => 1, 'Name' => $value])->blank();

                    }
                    $outHtml .= '<br><small>' . $link . '</small>';

                }
                return $outHtml;
            });
        }
    }

    protected function getExcelFilename()
    {
        return 'Customer Invoice ' . $this->_masterObj['Name'] . ' details';
    }

    protected function getExcelWorksheetName()
    {
        return 'Customer Invoice Details';
    }

    protected function getDataQuery()
    {
        $query = "SELECT cil.*
                      , s.ID AS ShipmentID
                  FROM cinvoiceline AS cil
                      LEFT JOIN shipment s ON s.Name = cil.FileNumber
                  WHERE cil.CInvoiceID = " . $this->_masterObj->getId() . "
                      AND cil.CInvoiceID != 0
                  ORDER BY cil.ID";

        return $query;
    }

    public function getHtmlEdit()
    {
        $this->getField('Amount')->setParam(
            'nameTable',
            'Amount in <span id="cinvoice-currency">' . $this->_masterObj['CurrencyISO'] . '</span>'
        );

        if (isset($_GET['fromCostSheet'])) {

            //Добавим строки в подтаблицу, если в GET запросе есть ключевой параметр fromCostSheet - значит попали сюда
            //из Cost-sheet'а нажатием кнопки Create Customer Invoice
            $shipmentId = (int)$_GET['fromCostSheet'];
            $csLineIds = explode(',', $_GET['csLineIds']);
            if (!Constant::isOneCCompany($this->_masterObj['CompanyID'])) {
                // Для обычных компаний (не ООО) берём все строки из Cost-sheet
                $linesData = CInvoiceCommon::getLinesDataFromCostSheet($shipmentId, $csLineIds);
            } else {
                // Для ООО компаний сворачиваем строки в одну строку Транспортно-экспедиторское обслуживание по маршруту: ...
                $linesData = CInvoiceCommon::getLinesDataFromCostSheetPlsPgl($shipmentId, $csLineIds);
            }
            $this->setAdditionalData($linesData);

        } elseif (isset($_GET['fromShipmentsMassaction'])) {

            //Если в GET есть ключевой параметр fromShipmentsMassaction - значит попали сюда из диалогового окна
            //Shipments Massaction "Создать Customer Invoice"
            //В этом случае в POST лежат id строк из кост-шитов на основе которых делаем счёт
            /** @var array $csLineIdsPerShipment */
            $csLineIdsPerShipment = $_POST['csLineIdsPerShipment'];

            $linesData = [];
            //Пройдём в цикле по массиву из POST - внутри он содержит вложенные массивы с ID конкретных строк в Cost-sheet'ах
            foreach ($csLineIdsPerShipment as $shipmentId => $csLineIds) {
                //Накапливаем $linesData
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $linesData = array_merge(
                    $linesData,
                    CInvoiceCommon::getLinesDataFromCostSheet($shipmentId, $csLineIds)
                );
            }

            $this->setAdditionalData($linesData);

        }

        return parent::getHtmlEdit();
    }

}
