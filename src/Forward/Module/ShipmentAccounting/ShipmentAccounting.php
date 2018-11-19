<?php

namespace Forward\Module\ShipmentAccounting;

use Forward\Application;
use Forward\Classes\Constant;
use Forward\Classes\ShipmentDownloadDocs;
use Forward\Field\GeneralFields;
use Forward\Field\ShipmentFields;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldMoney;
use Light\File;
use Light\FilesFolder;
use Light\Img;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

/**
 * Created by PhpStorm.
 * User: petr
 * Date: 11.07.17
 * Time: 11:04
 */
class ShipmentAccounting extends ModuleAbstract
{
    protected $folders = true;
    protected $allowAdd = false;
    protected $allowEdit = false;
    protected $allowLog = false;
    protected $allowDelete = false;

    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('shipment');

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
        $this->tableLayoutDefault =
            'Name|MAWBNumber|ShipmentRequestNo|CompanyID|CustomerID|CustomerInvoices|CActs|VendorID|DepartureIATA|ArrivalIATA|ATA';

        foreach (Constant::$standardFolders as $i => $_) {
            $this->tableLayoutDefault .= "|File$i";
        }

        $this->tableLayoutDefault .= '|DocsSentToAccountingDate';

        $onecCompanyIdsSql = implode(',', array_keys(Constant::$onecCompany));

        $this->addFields(

            ShipmentFields::name(),

            F::createString('MAWBNumber', [
                'name' => 'Накладная',
                'nobr' => true,
                'tableWidthExcel' => 15,
            ]),

            ShipmentFields::shipmentRequestNo(),

            F::createSelect('CompanyID', [
                'name' => 'Наша компания',
                'options' => 'ourcompany',
                'tableWidthExcel' => 20,
            ]),

            F::createSelect('CustomerID', [
                'name' => 'Клиент',
                'options' => 'customer',
                'tableWidthExcel' => 30,
            ]),

            F::createSelect('CustomerAccountID', [
                'name' => 'Плательщик',
                'options' => 'customer',
                'tableWidthExcel' => 30,
            ]),

            F::createString('CustomerInvoices', [
                'name' => 'Исходящий счёт',
                'tableWidthExcel' => 35,
                'groupconcat' => true,
                'sqlSelect' => "SELECT GROUP_CONCAT(CONCAT(ci.Name, '|', ci.DocDate, '|', ci.Amount, '|', ci.CurrencyISO) ORDER BY ci.DocDate, ci.ID SEPARATOR '\\n') 
                    FROM cinvoice_file_amount cfa, cinvoice ci 
                    WHERE 
                        ci.ID = cfa.CInvoiceID 
                        AND cfa.FileNumber = shipment.Name
                        AND ci.CompanyID IN ($onecCompanyIdsSql)",
                'getHtmlFunction' => function ($value) {
                    if (strpos($value, '|') === false) {
                        return '';
                    }

                    [$invName, $docDate, $invAmount, $currencyIso] = explode('|', $value);
                    return htmlspecialchars($invName)
                        . ' <small class="gray"> от '
                        . FieldDate::beautify($docDate)
                        . '</small>'
                        . ' <small>на '
                        . FieldMoney::beautify($invAmount)
                        . ' ' . $currencyIso
                        . '</small>';

                },
                'getExcelFunction' => function ($value, $out) {
                    if (strpos($value, '|') === false) {
                        return '';
                    }

                    [$invName, $docDate, $invAmount, $currencyIso] = explode('|', $value);
                    return "$invName от $docDate на $invAmount $currencyIso";
                }
            ]),

            ShipmentFields::cacts(),

            F::createSelect('VendorID', [
                'name' => 'Субподрядчик',
                'options' => 'vendor',
                'tableWidthExcel' => 35,
            ]),

            GeneralFields::departureIata(),

            GeneralFields::arrivalIata(),

            F::createDate('ATA')

        );

        $this->addFields(...ShipmentFields::fileFields());

        $this->addField(ShipmentFields::docsSentToAccountingDate());

    }

    protected function initFilters()
    {
        $compnayIdsSql = implode(',', array_keys(Constant::$onecCompany));
        $customerIdsSql = Db::getOne("SELECT GROUP_CONCAT(DISTINCT CustomerID) FROM shipment WHERE CompanyID IN ($compnayIdsSql)");
        $customerAccountIdsSql = Db::getOne("SELECT GROUP_CONCAT(DISTINCT CustomerAccountID) FROM shipment WHERE CompanyID IN ($compnayIdsSql)");
        $consigneeIdsSql = Db::getOne("SELECT GROUP_CONCAT(DISTINCT ConsigneeID) FROM shipment WHERE CompanyID IN ($compnayIdsSql)");
        $vendorIdsSql = Db::getOne("SELECT GROUP_CONCAT(DISTINCT VendorID) FROM shipment WHERE CompanyID IN ($compnayIdsSql)");

        $this->addFilters(

            ShipmentFields::filterFileNumbers(),

            ShipmentFields::filterMawbs()->setName('Накладные'),

            GeneralFields::filterCompanyId()
                ->setName('Наша компания')
                ->setOptions("SELECT ID, Name FROM ourcompany WHERE ID IN ($compnayIdsSql) ORDER BY OrderID, ID"),
            GeneralFields::customerId()
                ->setName('Клиент')
                ->setOptions("SELECT ID, Name FROM customer WHERE ID IN ($customerIdsSql) ORDER BY Name"),

            GeneralFields::customerAccountId()
                ->setName('Плательщик')
                ->setOptions("SELECT ID, Name FROM customeraccount WHERE ID IN ($customerAccountIdsSql) ORDER BY Name"),
            GeneralFields::consigneeId()
                ->setName('Получатель')
                ->setOptions("SELECT ID, Name FROM customerconsignee WHERE ID IN ($consigneeIdsSql) ORDER BY Name"),

            GeneralFields::vendorId()
                ->setName('Субподрядчик')
                ->setOptions("SELECT ID, Name FROM vendor WHERE ID IN ($vendorIdsSql) ORDER BY Name")

        );

        foreach (Constant::$standardFolders as $i => $standardFolder) {
            $this->addFilter(F::createYesNo("File$i", [
                'name' => $standardFolder,
                'sqlWhere' => "1"
            ]));
        }

        $this->addFiltersDatesFromUntil('ATA', ['name' => 'ATA']);

        $this->filtersLayout = [
            [

                'FileNumbers',
                'MAWBNumbers',
                'CompanyID',
                'CustomerID',
                'CustomerAccountID',
                'ConsigneeID',
                'VendorID',
            ],
            [
                'ATA',
            ]
        ];

        foreach (Constant::$standardFolders as $i => $standardFolder) {
            $this->filtersLayout[1][] = "File$i";
        }
    }

    protected function initMassactions()
    {
        $this->addMassaction('DownloadDocs', 'Собрать полный комплект документов', '', '', null, true);
        $this->addMassaction('DownloadShipmentRequestsXlsx', 'Скачать заявки в Excel', '', '', null, true);

        foreach (Constant::$standardFolders as $i => $standardFolder) {
            $this->addMassaction("DownloadDocs$i", $standardFolder, '', '', null, true);
        }
    }

    protected function massactionDownloadDocs($ids)
    {
        ShipmentDownloadDocs::download($ids, true);
    }

    protected function massactionDownloadShipmentRequestsXlsx($ids)
    {
        ShipmentDownloadDocs::download($ids, true, ShipmentDownloadDocs::ONLY_SHIPMENT_REQUESTS_XLSX);
    }

    protected function massactionDownloadDocs0($ids)
    {
        ShipmentDownloadDocs::download($ids, true, 0);
    }

    protected function massactionDownloadDocs1($ids)
    {
        ShipmentDownloadDocs::download($ids, true, 1);
    }

    protected function massactionDownloadDocs2($ids)
    {
        ShipmentDownloadDocs::download($ids, true, 2);
    }

    protected function massactionDownloadDocs3($ids)
    {
        ShipmentDownloadDocs::download($ids, true, 3);
    }

    protected function massactionDownloadDocs4($ids)
    {
        ShipmentDownloadDocs::download($ids, true, 4);
    }

    protected function massactionDownloadDocs5($ids)
    {
        ShipmentDownloadDocs::download($ids, true, 5);
    }

    public function getFiltersSqlWhere()
    {
        // Ограничим доступ только к накладным относящимся к рублёвым компаниям
        $sqlWhere = parent::getFiltersSqlWhere();
        $sqlWhere .= " AND CompanyID IN (" . implode(',', array_keys(Constant::$onecCompany)) . ")";

        $fileFilters = [];
        foreach (Constant::$standardFolders as $i => $standardFolder) {
            $fileFilterValue = $this->getFilterValue("File$i");
            if ($fileFilterValue !== '%') {
                $fileFilters[$i] = $fileFilterValue;
            }
        }

        if ($fileFilters) {
            $shipmentOkIds = [];
            // Найдём ID всех перевозок удовлетворяющих фильтрам без учёта папок
            // после чего применим фильтры по папкам
            $queryBase = "SELECT ID, Name FROM shipment WHERE $sqlWhere";
            $shipments = Db::getAssoc($queryBase);
            foreach ($shipments as $shipmentId => $shipmentName) {
                $sub = floor($shipmentId / 1000);
                $folderRelative = "Shipment/$sub/$shipmentId";
                // Пройдём по всем активным фильтрам
                $shipmentOk = true;
                foreach ($fileFilters as $i => $filterValue) {
                    $notEmpty = FilesFolder::isNotEmpty("$folderRelative/" . File::decode(Constant::$standardFolders[$i]));
                    $correct = ($notEmpty && $filterValue == 1 || !$notEmpty && $filterValue == 0);
                    if (!$correct) {
                        // Этот шипмент нам не подходит
                        $shipmentOk = false;
                        break;
                    }
                }
                if ($shipmentOk) {
                    // Если после всех проверок перевозка осталась в статусе $shipmentOk, тогда добавляем её в массив
                    $shipmentOkIds[] = $shipmentId;
                }
            }

            if ($shipmentOkIds) {
                $sqlWhere .= " AND ID IN (" . implode(',', $shipmentOkIds) . ")";
            } else {
                $sqlWhere .= " AND FALSE";
            }
        }

        return $sqlWhere;
    }

    protected function initViewSubmenu()
    {
        $this->addSubmenuItemViewFunc('Комплект документов', Img::icon('file-archive-o'), 'DownloadDocs', [], true);
    }

    protected function viewGetDownloadDocs()
    {
        ShipmentDownloadDocs::download([$this->_obj->getId()], true);
    }

}
