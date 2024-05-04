<?php

namespace Forward\Module\Quotation;

use Forward\Application;
use Forward\Classes\BuAccess;
use Forward\Classes\CompanyChild;
use Forward\Classes\Constant;
use Forward\Classes\CustomerAccess;
use Forward\Field\GeneralFields;
use Forward\Field\QuotationFields;
use Forward\Module\Quotation\Subtables\Cargo;
use Forward\Module\Quotation\Subtables\Expenses;
use Forward\Module\Quotation\Subtables\Income;
use Forward\Module\SearchAirfreightRateDialog\SearchAirfreightRateDialog;
use Forward\Module\Shipment\Shipment;
use Light\ApplicationAbstract;
use Light\Currency;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Ico;
use Light\Link;
use Light\ModuleBase\ModuleAbstract;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;
use Light\User;
use Light\XlsWriter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class Quotation extends ModuleAbstract
{
    private const EXCEL_CARGO_STARTROW = 15;
    private const EXCEL_DETAILS_STARTROW = 20;

    protected $textSaveBtn = 'Quotation';
    public $folders = true;

    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            CF::id(),
            QuotationFields::name(),
            GeneralFields::companyId(),
            GeneralFields::customerId(false, false),
            GeneralFields::vendorId(false, false),
            GeneralFields::responsibleSalesId(),
            GeneralFields::responsibleCsId(),
            GeneralFields::natureOfGoods(),
            GeneralFields::departureIata(),
            GeneralFields::departureLocationName(),
            GeneralFields::arrivalIata(),
            GeneralFields::arrivalLocationName(),
            CF::validFrom(),
            CF::validUntil(),
            CF::currency('', ['default' => 'USD']),
            CF::grossWeight(),
            CF::chargeableWeight(),
            QuotationFields::commentsInternal(),
            QuotationFields::commentsCustomer(),
            QuotationFields::commentsLost(),
            GeneralFields::airlineAlphaCode(),
            GeneralFields::transitTime(),
            CF::incoterms(),
            GeneralFields::incotermsLocation(),
            QuotationFields::status(),
            F::createInt('MergedQuotationID', [
                'name' => 'Merged Quotation ID',
                'editAlign' => 'left',
                'zeroEmpty' => true,
                'modes' => F::MODE_ALL & ~F::MODE_LIST,
                'getHtmlFunction' => function ($value, array $data, $outHtml, $forTable, $field) {
                    if ($value) {
                        $outHtml = Link::create(Quotation::class, $outHtml)->view($value)->getHtml();
                    }

                    return $outHtml;
                },
            ]),
            QuotationFields::cacheGp(),
            F::createFloat('DensityKgPerM3', [
                'name' => 'Density',
                'uom' => 'kg/m<sup>3</sup>',
                'modes' => F::MODE_READONLY,
                'sqlSelect' => '',
                'sqlSelectRequires' => 'GrossWeight',
                'calcFunction' => function ($value, array $data) {
                    if (!isset($data['ID'])) {
                        return 0;
                    }

                    $totalVolume = (float)Db::getOne(
                        "SELECT sum(Volume) FROM quotationcargo WHERE QuotationID = @i",
                        $data['ID']
                    );
                    if (!$totalVolume) {
                        return 0;
                    }

                    return round($data['GrossWeight'] / $totalVolume);
                },
            ]),
            F::createFloat('DensityM3PerTon', [
                'name' => 'Density',
                'uom' => 'm<sup>3</sup>/ton',
                'modes' => F::MODE_READONLY,
                'sqlSelect' => '',
                'sqlSelectRequires' => 'GrossWeight',
                'calcFunction' => function ($value, array $data) {
                    if (!isset($data['ID'])) {
                        return 0;
                    }

                    if ($data['GrossWeight'] == 0) {
                        return 0;
                    }

                    $totalVolume = (float)Db::getOne(
                        "SELECT sum(Volume) FROM quotationcargo WHERE QuotationID = @i",
                        $data['ID']
                    );
                    return round($totalVolume / $data['GrossWeight'] * 1000, 2);
                },
            ]),
            F::createSelect('CargoUOM', [
                'name' => 'cm or inch?',
                'options' => ['cm' => 'cm', 'inch' => 'inch'],
                'default' => 'cm',
                'empty' => false,
            ])

        );

    }

    protected function initSubtables()
    {
        $this->addSubtable(Cargo::class);
        $this->addSubtable(Income::class);
        $this->addSubtable(Expenses::class);
    }

    protected function initMassactions()
    {
        $this->addMassactionMassUpdate();
    }

    protected function initCopyModes()
    {
        $allFieldKeys = $this->getFieldset()->getKeys();
        unset($allFieldKeys[array_search('Name', $allFieldKeys)], $allFieldKeys[array_search('Status', $allFieldKeys)]);

        $this->addCopyMode('Copy', $allFieldKeys);
    }

    protected function initCssJs()
    {
        parent::initCssJs();
        if ($this->isStateEdit() || $this->isStateAddNew()) {
            CompanyChild::initJsCompanyChildFromCustomer();
            SearchAirfreightRateDialog::initCssJs();
            Currency::putJsRates();
        }
    }

    protected function initFilters()
    {

        $this->addFilters(

            F::createString('FilterName', [
                'name' => 'ID / Text',
                'title' => 'ID / Description / Comments',
                'sqlWhere' => "ID = '{value}' OR Name LIKE '%{value}%' OR CommentsInternal LIKE '%{value}%' OR CommentsCustomer LIKE '%{value}%'",
            ]),
            F::createSelect('CustomerID', [
                'name' => 'Customer',
                'options' => function () {
                    $options = Db::getAssoc(
                        "SELECT ID, Name, IF(Active, '', 'Inactive') AS optgroup
                                 FROM customer
                                 WHERE ID IN (SELECT DISTINCT(CustomerID) FROM quotation)
                                 ORDER BY Active DESC, Name"
                    );

                    if (!BuAccess::hasAccessAll()) {
                        $allowedCustomerIds = BuAccess::getCustomerIds();
                        foreach ($options as $customerId => &$option) {
                            //Скроем или оставим видимым данного клиента на основе того, есть ли его ID среди допустимых
                            $option['Active'] = in_array($customerId, $allowedCustomerIds);
                        }
                    }

                    return $options;
                },
            ]),
            F::createSelect('Responsible', [

                'options' => function () {
                    //Подготовим массив $responsible с разбивкой пользователей по ролям для фильтров
                    $responsibleRole = [
                        'ResponsibleSalesID' => 'Sales',
                        'ResponsibleCSID' => 'Customer Service',
                    ];
                    $options = [];
                    foreach ($responsibleRole as $roleField => $roleDescription) {
                        //Добавим пустое значение в каждую группу
                        $options[$roleField . '_0'] = ['text' => '', 'optgroup' => $roleDescription];

                        $users = Db::getAssoc(
                            "SELECT DISTINCT(quotation." . $roleField .
                            "), user.Name FROM quotation, user WHERE user.ID = quotation." . $roleField .
                            " ORDER BY user.Name"
                        );
                        foreach ($users as $id => $name) {
                            $options[$roleField . '_' . $id] = [
                                'text' => $name,
                                'optgroup' => $roleDescription,
                            ];
                        }
                    }

                    return $options;
                },
                'sqlWhere' => function ($value) {
                    //Фильтр по Responsible состоит из двух частей разделённых знаком подчёркивания: Имя поля в базе () и значение поля
                    [$role, $userId] = explode('_', $value);
                    if ($role === 'ResponsibleSalesID' || $role === 'ResponsibleCSID') {
                        return $role . " = " . (int)$userId;
                    }

                    return '';
                },
            ]),
            GeneralFields::departureIata(),
            GeneralFields::departureLocationName(),
            GeneralFields::arrivalIata(),
            GeneralFields::arrivalLocationName(),
            GeneralFields::airlineAlphaCode()

        );

        $statusField = QuotationFields::status();
        $statusOptions = $statusField->options;
        $statusOptions['draft-pending'] = ['text' => 'Draft or Pending', 'optgroup' => 'Mixed'];
        $statusOptions['win-lost'] = ['text' => 'Win or Lost', 'optgroup' => 'Mixed'];
        $statusField->setParam('options', $statusOptions);
        $statusField->setParam(
            'sqlWhere',
            function ($value) {
                if ($value === 'draft-pending') {
                    return "Status = 'draft' OR Status = 'pending'";
                }

                if ($value === 'win-lost') {
                    return "Status = 'win' OR Status = 'lost'";
                }

                return "Status = '{value}'";

            }
        );

        //$statusField->setParam('default', 'draft-pending');

        $this->addFilter($statusField);

        $this->addFiltersDatesFromUntil('Valid', ['name' => 'Validity', 'asInterval' => true]);

        $this->filtersLayout = [
            [

                'FilterName',
                'CustomerID',
                'Responsible',
                'Status',
            ],
            [
                'Valid',
            ],
            [
                'AirlineAlphaCode',
                'DepartureLocationName' => GeneralFields::filterTemplateLocation('Departure'),
                'ArrivalLocationName' => GeneralFields::filterTemplateLocation('Arrival'),
            ],
        ];
    }

    protected function getAccessSqlWhere(): string
    {
        return BuAccess::getSqlWhereCustomerId()
            . " AND " . CustomerAccess::getSqlWhere()
            . " AND " . Constant::getCreatedDateSqlWhere();
    }

    protected function validateUnique(array $dataFromRequest)
    {
    }

    protected function validateMandatory(array $dataFromPost)
    {
        if ($dataFromPost['Status'] === 'lost') {
            $this->getField('CommentsLost')->setEditMandatory('Fill Comments Lost');
        }

        parent::validateMandatory($dataFromPost);
    }

    protected function initViewSubmenu()
    {
        //Кнопка экспорта в Excel
        //$this->addSubmenuItem('Export to Excel', Img::sprite('excel'), 'printExcel');
        $this->addSubmenuLinkViewFunc(Ico::excel() . ' Export to Excel (En)', 'Excel')->params(['lang' => 'en']);
        $this->addSubmenuLinkViewFunc(Ico::excel() . ' Export to Excel (Ru)', 'Excel')->params(['lang' => 'ru']);

        //Добавим кнопку просмотра шипмента, если эта котровка привязана к каой-то перевозке
        $shipmentId = Db::getOne("SELECT ID FROM shipment WHERE QuotationID = " . $this->_obj->getId());
        if ($shipmentId) {

            $this->addSubmenuLink(
                Link::create(Shipment::class, Ico::search() . ' View Shipment')
                    ->view($shipmentId)
            );

        } elseif ($this->_obj['Status'] === 'win') {

            $this->addSubmenuLink(
                Link::create(Shipment::class, Ico::plus() . ' Create Shipment')
                    ->params(['addnew' => 1, 'qid' => $this->_obj->getId()])
            );

        }
    }

    protected function afterSubmit(
        ObjAbstract $obj,
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff,
        $fromMultipleUpdat
    ) {
        //Обновим Name у котировки, если пользователь не указал его сам
        if ($obj['Name'] === '') {
            $createdDate = Db::getOne("SELECT CreatedDateTime FROM quotation WHERE ID = @i", $obj->getId());
            $newName = Constant::generageQuotationName($createdDate, $obj['DepartureIATA'],
                $obj['ArrivalIATA'], $obj['ChargeableWeight'], $obj['AirlineAlphaCode']);
            $obj->updateDb(['Name' => $newName], 'Autogenerated');
        }

        $income =
            (double)Db::getOne("SELECT SUM(Amount) FROM quotationincome WHERE QuotationID = " . $this->_obj->getId());
        $expenses =
            (double)Db::getOne("SELECT SUM(Amount) FROM quotationexpenses WHERE QuotationID = " . $this->_obj->getId());
        Db::query("UPDATE quotation SET CacheGP = " . ($income - $expenses) . " WHERE ID = " . $this->_obj->getId());
    }


    /* Старый форма в виде заявки
    protected function proceedPrintExcel()
    {
        $xls = new XlsWriter('Forward/Module/Quotation/quotation.xlsx');

        $excelData = $this->_obj->getExcelData();

        //Добавим общие данные по грузу
        $query = "SELECT sum(Pieces) AS TotalQuantity
                    , sum(Volume) AS TotalVolume
                  FROM quotationcargo
                  WHERE QuotationID = " . $this->_obj->getId();

        $excelData += Db::getRow($query) + ['TotalQuantity' => 0, 'TotalVolume' => 0];

        //Добавим Selling Amount
        $excelData['SellingAmount'] = Db::getOne(
            "SELECT sum(Amount) FROM quotationincome WHERE QuotationID = " . $this->_obj->getId()
        );

        //Добавим детальное описание груза
        $cargo = Db::getAll("SELECT * FROM quotationcargo WHERE QuotationID = " . $this->_obj->getId());
        //Поскольку в шаблоне зафиксировано 6 строк под описание груза - определим все 6 строк в цикле
        for ($i = 0; $i < 6; $i++) {
            if (isset($cargo[$i])) {
                $excelData['q' . $i] = $cargo[$i]['Pieces'];
                $excelData['w' . $i] = $cargo[$i]['Width'];
                $excelData['h' . $i] = $cargo[$i]['Height'];
                $excelData['l' . $i] = $cargo[$i]['Length'];
            } else {
                $excelData['q' . $i] = '';
                $excelData['w' . $i] = '';
                $excelData['h' . $i] = '';
                $excelData['l' . $i] = '';
            }
        }

        $xls->replaceData($excelData);

        $xls->send('Quotation ' . $this->_obj->getId() . '.xlsx');

    }
    */

    protected function viewGetExcel()
    {
        $lang = $_GET['lang'] ?? 'en';
        if ($lang !== 'ru' && $lang !== 'en') {
            $lang = 'en';
        }

        if ($this->_obj['CompanyID'] == Constant::OC_PLS
            || $this->_obj['CompanyID'] == Constant::OC_PLS_USD
            || $this->_obj['CompanyID'] == Constant::OC_PLS_OOO
            || $this->_obj['CompanyID'] == Constant::OC_PLS12_OOO
        ) {
            // Шаблоны различаются логотипом
            $companySuffix = 'pls';
        } else {
            $companySuffix = 'pgl';
        }

        $xls = new XlsWriter(__DIR__ . "/quotation_{$companySuffix}_{$lang}.xlsx");
        $sheet = $xls->getSpreadsheet()->getActiveSheet();

        $excelData = $this->_obj->getExcelData();

        //Добавим общие данные по грузу
        $query = "SELECT sum(Pieces) AS TotalQuantity
                    , sum(Volume) AS TotalVolume
                  FROM quotationcargo
                  WHERE QuotationID = " . $this->_obj->getId();

        $excelData += Db::getRow($query) + ['TotalQuantity' => 0, 'TotalVolume' => 0];

        $xls->replaceData($excelData);

        // Добавим адрес компании
        // Задача от 2017-01-15: убрать адрес компании из шаблона
        /*
        $address = Db::getOne("SELECT Address FROM ourcompany WHERE ID = " . (int)$this->_obj['CompanyID']);
        if ($address) {
            $addressLines = explode("\n", $address);
            $rowNum = 2;
            foreach ($addressLines as $addressLine) {
                $sheet->setCellValueExplicit(
                    'A' . $rowNum,
                    $addressLine,
                    DataType::TYPE_STRING
                );

                $rowNum++;
            }
        }
        */

        //Добавим детальное описание груза
        $cargo = Db::getAll("SELECT * FROM quotationcargo WHERE QuotationID = " . $this->_obj->getId());
        $insertedRows = 0;
        $rowNum = self::EXCEL_CARGO_STARTROW;
        foreach ($cargo as $cargoLine) {
            if ($rowNum > self::EXCEL_CARGO_STARTROW) {
                //При вставке первой строки cargo новую строку в Excel НЕ добавляем
                //при последующих строках ($rowNum > self::EXCEL_CARGO_STARTROW), нужно добавлять новые строки
                $sheet->insertNewRowBefore($rowNum);
                $insertedRows++;
            }
            $sheet->setCellValueExplicit('C' . $rowNum, $cargoLine['Length'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('D' . $rowNum, $cargoLine['Width'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('E' . $rowNum, $cargoLine['Height'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('F' . $rowNum, $cargoLine['Pieces'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('G' . $rowNum, $cargoLine['Volume'], DataType::TYPE_NUMERIC);
            $rowNum++;
        }

        //Вставим Total по упаковкам в виде формул
        if ($cargo) {
            $sheet->setCellValueExplicit(
                'F' . $rowNum,
                '=SUM(F' . self::EXCEL_CARGO_STARTROW . ':F' . ($rowNum - 1) . ')',
                DataType::TYPE_FORMULA
            );
            $sheet->setCellValueExplicit(
                'G' . $rowNum,
                '=SUM(G' . self::EXCEL_CARGO_STARTROW . ':G' . ($rowNum - 1) . ')',
                DataType::TYPE_FORMULA
            );
        }

        //Добавим детали самой котировки
        $details = Db::getAll("SELECT * FROM quotationincome WHERE QuotationID = " . $this->_obj->getId());
        //Если котировка на русском языке, нужно сделать перевод
        if ($lang === 'ru') {
            $chargelinesRu = Db::getAssoc("SELECT LOWER(Name), NameRu FROM list_chargedescription WHERE NameRu != ''");

            foreach ($details as $i => $detailLine) {
                $nameLower = mb_strtolower($detailLine['Name'], 'UTF-8');
                if (isset($chargelinesRu[$nameLower])) {
                    $details[$i]['Name'] = $chargelinesRu[$nameLower];
                }
            }
        }

        $detailsStartRow = self::EXCEL_DETAILS_STARTROW + $insertedRows; //сдвиг на $insertedRows из-за таблицы с описанием груза
        $rowNum = $detailsStartRow;
        foreach ($details as $detailLine) {
            if ($rowNum > $detailsStartRow) {
                //При вставке первой строки details новую строку в Excel НЕ добавляем
                //при последующих строках ($rowNum > self::EXCEL_DETAILS_STARTROW), нужно добавлять новые строки
                $sheet->insertNewRowBefore($rowNum);
            }
            $sheet->mergeCells('A' . $rowNum . ':C' . $rowNum);
            $sheet->setCellValueExplicit('A' . $rowNum, $detailLine['Name'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rowNum, $detailLine['Price'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('E' . $rowNum, $detailLine['Per'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit(
                'F' . $rowNum,
                $detailLine['MinAmount'],
                DataType::TYPE_NUMERIC
            );
            $sheet->setCellValueExplicit('G' . $rowNum, $detailLine['Amount'], DataType::TYPE_NUMERIC);
            $rowNum++;
        }

        //Вставим Total суммам в виде формул
        if ($details) {
            $sheet->setCellValueExplicit(
                'G' . $rowNum,
                '=SUM(G' . $detailsStartRow . ':G' . ($rowNum - 1) . ')',
                DataType::TYPE_FORMULA
            );
        }

        //Вставим комментарии с разбиением по строкам в Excel
        if ($this->_obj['CommentsCustomer']) {
            $xls->writeTextPerRows(1, $rowNum + 2, $this->_obj['CommentsCustomer']);
        }

        //Имя файла - это имя клиента + описание котировки
        $fileName = '#' . $this->_obj->getId() . ' ' . strip_tags(
                $this->_obj->getFieldHtml('CustomerID')
            ) . ' ' . str_replace('/', '-', $this->_obj['Name']) . '.xlsx';


        // Сначала сохраним файл в папке
        $filepathAbs = $this->getFolderAbsolute() . '/' . $fileName;
        $xls->save($filepathAbs);

        // Затем отправим пользотвателю
        $xls->send($fileName);

        //обязательно делаем terminate, чтобы движок не пытался всё это засунуть в Master.tpl.php
        ApplicationAbstract::terminate();

    }

    /**
     * Метод вызывается с формы редактирования котировки через ajax запрос при клике на ссылке "Apply Addon"
     * Нужно вывести диалог с подходящей суммой Addon для данного клиента (с учётом веса и валюты).
     * Далее пользователь может поменять сумму в диалоговом окне и нажав кнопку кнопку Ok в диалоге эта сумма будет добавлена
     * к Airfreight строке в доходной части
     */
    public static function proceedApplyAddonDialog(): void
    {
        //Подгрузим нужное значение Addon - либо из карточки клиента, либо из DefaultAddons
        $addon = '';
        if ($_GET['CurrencyISO'] === 'USD' || $_GET['CurrencyISO'] === 'EUR') {
            $interval = Constant::getWeightInterval($_GET['ChargeableWeight']);
            if ($interval === null) {
                $interval = 'N';
            }

            $addonKey = "Addon{$_GET['CurrencyISO']}Amount{$interval}";

            //Проверим карточку клиента
            $customerInfo = Db::getRow(
                "SELECT UseDefaultAddon, $addonKey AS Addon
                 FROM customer
                 WHERE ID = @i",
                $_GET['CustomerID']
            );

            if ($customerInfo && !$customerInfo['UseDefaultAddon']) {
                $addon = $customerInfo['Addon'];
            } else {
                $defaultAddons = Constant::getDefaultAddons();
                if (isset($defaultAddons[$addonKey])) {
                    $addon = $defaultAddons[$addonKey];
                }
            }
        }
        $body = '<input type="text" class="input-mini addon-amount input-float" value="' . $addon . '"> ' . $_GET['CurrencyISO'] . ' per Kg';
        Response::showDialog($body, 'Apply', 'Quotation.onApplyAddonDialogSubmit');
    }
}
