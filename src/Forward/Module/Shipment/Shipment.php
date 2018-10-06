<?php

namespace Forward\Module\Shipment;

use Forward\Application;
use Forward\Classes\BuAccess;
use Forward\Classes\CompanyChild;
use Forward\Classes\Constant;
use Forward\Classes\CustomerAccess;
use Forward\Classes\Finance;
use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Forward\Classes\ShipmentDownloadDocs;
use Forward\Field\GeneralFields;
use Forward\Field\ShipmentFields;
use Forward\Module\Shipment\CostSheet\CostSheetObj;
use Forward\Module\Shipment\CostSheet\CostSheetRouter;
use Forward\Module\Shipment\CostSheet\Subtables\Base\IncomeExpensesObjAbstract;
use Forward\Module\Shipment\CostSheet\Subtables\ExpensesObj;
use Forward\Module\Shipment\CostSheet\Subtables\IncomeObj;
use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldMoney;
use Light\Field\FieldMoneyWithCurrency;
use Light\File;
use Light\FilesFolder;
use Light\Img;
use Light\InvStatus;
use Light\Link;
use Light\Mailer;
use Light\Misc;
use Light\ModuleBase\ModuleAbstract;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;
use Light\Session;
use Light\Template;
use Light\User;
use Light\XlsWriter;

class Shipment extends ModuleAbstract
{
    private static $_responsibleRole = [
        'ResponsibleSales' => 'Sales',
        'ResponsibleCS' => 'Customer Service',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->folders = true;

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->textSaveBtn = 'Shipment';
        $this->tableDefaultOrderBy = 'shipment.ID DESC';
        $this->tableLayouts['Default'] =
            'Name|FileClosed|Month|MAWBNumber|DepartureIATA|ArrivalIATA|CustomerID|ETD|ETA|ATA';

        $this->allowDelete = User::hasSpecialAccess(Application::SA_SHIPMENT_DELETE);

        $this->registerSubtable('Shippers');
    }

    protected function initList()
    {

        $getSqlSelectInvoices = function ($firstLetter) {
            $firstLetterLower = strtolower($firstLetter);
            $customerOrVendor = $firstLetter === 'C' ? 'Customer' : 'Vendor';
            $customerOrVendorLower = strtolower($customerOrVendor);
            return "SELECT GROUP_CONCAT(CONCAT(i.Name, '|', cv.Name, '|', i.Status) ORDER BY i.DocDate, i.ID SEPARATOR '\\n') 
                    FROM {$firstLetterLower}invoice_file_amount fa, {$firstLetterLower}invoice i 
                        LEFT JOIN $customerOrVendorLower cv ON i.{$customerOrVendor}ID = cv.ID
                    WHERE 
                        i.ID = fa.{$firstLetter}InvoiceID 
                        AND fa.FileNumber = shipment.Name";
        };

        $getHtmlFunctionInvoices = function ($value, array $data, $outHtml, $forTable, $field) {
            if (strpos($value, '|') !== false) {
                [$invoice, $contragent, $status] = explode('|', $value, 3);
                /** @var FieldAbstract $field */
                if ($field->key === 'CustomerInvoices') {
                    $statusIco = Finance::getCInvoiceStatusIcon($status);
                } else {
                    $statusIco = Finance::getVInvoiceStatusIcon($status);
                }
                $outHtml = $statusIco . ' ' . htmlspecialchars($invoice) . ' <small class="gray">(' . htmlspecialchars($contragent) . ')</small>';
            }

            return $outHtml;
        };

        $getExcelFunctionInvoices = function ($value, array $data, $out) {
            if (strpos($value, '|') !== false) {
                list($invoice, $contragent) = explode('|', $value, 2);
                $out = $invoice . ' (' . $contragent . ')';
            }

            return $out;
        };

        $getSqlSelectInvoicesAmounts = function ($firstLetter) {
            $firstLetterLower = strtolower($firstLetter);
            return "SELECT GROUP_CONCAT(i.Amount ORDER BY DocDate, ID SEPARATOR '\\n') FROM {$firstLetterLower}invoice i, {$firstLetterLower}invoice_file_amount fa WHERE i.ID = fa.{$firstLetter}InvoiceID AND fa.FileNumber = shipment.Name";
        };


        $this->addFields(
            F::createString('CustomerInvoices', [
                'name' => 'Customer Invoices',
                'tableWidthExcel' => 20,
                'groupconcat' => true,
                'sqlSelect' => $getSqlSelectInvoices('C'),
                'getHtmlFunction' => $getHtmlFunctionInvoices,
                'getExcelFunction' => $getExcelFunctionInvoices
            ]),
            F::createMoney('CustomerInvoicesAmounts', [
                'name' => 'Customer Invoices Amounts',
                'groupconcat' => true,
                'zeroEmpty' => true,
                'sqlSelect' => $getSqlSelectInvoicesAmounts('C')
            ]),
            F::createString('VendorInvoices', [
                'name' => 'Vendor Invoices',
                'tableWidthExcel' => 20,
                'groupconcat' => true,
                'sqlSelect' => $getSqlSelectInvoices('V'),
                'getHtmlFunction' => $getHtmlFunctionInvoices,
                'getExcelFunction' => $getExcelFunctionInvoices
            ]),
            F::createMoney('VendorInvoicesAmounts', [
                'name' => 'Vendor Invoices Amounts',
                'groupconcat' => true,
                'zeroEmpty' => true,
                'sqlSelect' => $getSqlSelectInvoicesAmounts('V')
            ]),

            $this->getListVatMountField(12),
            $this->getListVatMountField(18)


        );
    }

    protected function initFieldsFromSubtables()
    {
        parent::initFieldsFromSubtables();

        // К полю Shipper добавим параметр, который будет обрезать длинные названия
        $this->getField('CacheShippersName')->setViewMaxWidth300(true);
    }


    private function getListVatMountField($vatPercent)
    {
        return F::createMoney("VAT{$vatPercent}Amount", [
            'name' => "Expenses VAT $vatPercent%",
            'title' => "Сумма приходящаяся на НДС $vatPercent% во входящих счетах Vendor Invoices (на основе столбца 'VAT Amount' в строках счёта + сумма из строки в счёте с текстом услуги 'НДС $vatPercent%')",
            'sqlSelect' => "IFNULL((SELECT sum(VATAmount) FROM vinvoiceline WHERE VATPercent = $vatPercent AND FileNumber = shipment.Name), 0) + IFNULL((SELECT sum(FinalAmount) FROM vinvoiceline WHERE FileNumber = shipment.Name AND (Name LIKE 'НДС $vatPercent%' OR Name LIKE 'VAT $vatPercent%')), 0)",
            'zeroEmpty' => true,
            'zeroEmptyExcel' => true
        ]);
    }

    protected function initCopyModes()
    {
        $this->addCopyMode(
            'Copy',
            [
                'CompanyID',
                'TrafficID',
                'ResponsibleCS',
                'ResponsibleSales',
                'CustomerID',
                'CustomerAccountID',
                'ConsigneeID',
                'Consignee',
                'AirlineAlphaCode',
                'PlaceOfReceipt',
                'DepartureIATA',
                'DepartureLocationName',
                'ArrivalIATA',
                'ArrivalLocationName',
                'Destination',
                'Vessel',
                'VoyageNbr',
                'PODTerminalID',
                'ETD',
                'ETA',
                'ContainerType',
                'VendorID',
                'NatureOfGoods',
                'Incoterms',
                'IncotermsLocation'
            ],
            null
        );
    }

    protected function initCssJs()
    {
        parent::initCssJs();
        if ($this->isStateEdit() || $this->isStateAddNew()) {
            CompanyChild::initJsCompanyChildFromCustomer();
        }
    }

    protected function initMassactions()
    {
        $this->addMassactionMultipleUpdate();
        $this->addMassaction('DownloadDocs', 'Собрать комплект документов', '', '', null, true);
    }

    protected function massactionDownloadDocs($ids)
    {
        ShipmentDownloadDocs::download($ids, false);
    }

    protected function initViewSubmenu()
    {
        $subject = ApplicationAbstract::getParam('EmailNoticeSubject');
        $body = ApplicationAbstract::getParam('EmailNoticeBody');

        //Пройдём по всем полям объекта и попробуем сделать подстановку в subject и body
        foreach ($this->_obj->getFieldset() as $field) {
            if (isset($this->_obj[$field->key])) {
                $value = $field->getExcel($this->_obj[$field->key], $this->_obj->getData());
                $subject = str_replace('{' . $field->key . '}', $value, $subject);
                $body = str_replace('{' . $field->key . '}', $value, $body);
            }
        }

        //$link = 'http://connect.track-trace.com/for/pls/aircargo/' . $this->_obj['MAWBNumber'];
        $link = 'http://quicktracing.com/api/air.php?token=tVRtbXgXHxE71UJTTkbPOE0qZrj6sMYF&awb=' . rawurlencode($this->_obj['MAWBNumber']);
        $subject = str_replace('{TrackTraceLink}', $link, $subject);
        $body = str_replace('{TrackTraceLink}', $link, $body);

        //Загрузим email и имя контактной персоны клиента, на который отправлять уведомление
        $customerInfo = Db::getRow(
            "SELECT ContactPersonName, NoticeEmail FROM customer WHERE ID = " . $this->_obj['CustomerID']
        );
        if ($customerInfo) {
            $contactPerson = $customerInfo['ContactPersonName'];
            $email = $customerInfo['NoticeEmail'];
        } else {
            $contactPerson = '';
            $email = '';
        }

        $body = str_replace('{ContactPersonName}', $contactPerson, $body);

        $href = 'mailto:' . $email . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);
        if (strlen($href) > 2040) {
            $href = substr($href, 0, 2040);
        }

        $this->_submenuItemsTplData[] = [
            'html' => '<a href="' . $href . '" id="emailnotice-link">' . Img::iconMail() . ' Email Notice</a>'
        ];

        if (Constant::isOneCCompany($this->_obj['CompanyID'])) {

            $this->addSubmenuItemViewFuncAjaxPost('Заявка', Img::iconDownload(), 'ShipmentRequestExcel');

            $this->addSubmenuItemViewFunc('Комплект документов', Img::icon('file-archive-o'), 'DownloadDocs', [], true);

        }

    }


    protected function initFilters()
    {
        //Узнаем самый ранний месяц и самый поздний год в поле shipment.Month для фильтра по месяцам
        $monthYearFrom = 2012;
        $monthYearUntil = Db::getOne("SELECT max(YEAR(Month)) FROM shipment");

        //Оъявляем локальную переменную для замыкания в фильтре Responsible
        $responsibleRole = self::$_responsibleRole;

        $this->addFilters(

            F::createText(
                'FileNumbers',
                [
                    'name' => 'File Numbers',
                    'sqlWhere' => function ($value) {
                        return Db::getSqlWhereSeparatedByComa($value, 'shipment.Name', -1, false, true, 1);
                    }
                ]
            ),
            ShipmentFields::filterMawbs(),
            GeneralFields::trafficId(),
            GeneralFields::filterCompanyId(),
            GeneralFields::customerId(),
            GeneralFields::customerAccountId(),
            GeneralFields::consigneeId(),
            F::createSelect(
                'Responsible',
                [

                    'options' => function () use ($responsibleRole) {
                        //Подготовим массив $responsible с разбивкой пользователей по ролям для фильтров
                        $options = [];
                        foreach ($responsibleRole as $roleField => $roleDescription) {
                            //Добавим пустое значение в каждую группу
                            $options[$roleField . '_0'] = ['text' => '', 'optgroup' => $roleDescription];

                            $users = Db::getAssoc(
                                "SELECT DISTINCT(shipment." . $roleField .
                                "), user.Name FROM shipment, user WHERE user.ID = shipment." . $roleField .
                                " ORDER BY user.Name"
                            );
                            foreach ($users as $id => $name) {
                                $options[$roleField . '_' . $id] = [
                                    'text' => $name,
                                    'optgroup' => $roleDescription
                                ];
                            }
                        }

                        return $options;
                    },
                    'sqlWhere' => function ($value) use ($responsibleRole) {
                        //Фильтр по Responsible состоит из двух частей разделённых знаком подчёркивания: Имя поля в базе () и значение поля
                        $responsibleFilterValue = explode('_', $value);
                        if (isset($responsibleRole[$responsibleFilterValue[0]])) {
                            return "`" . Db::esc($responsibleFilterValue[0]) . "` = " .
                                (int)$responsibleFilterValue[1];
                        }

                        return '';
                    }
                ]
            ),
            GeneralFields::buIdsFilter(),
            GeneralFields::departureIata(),
            GeneralFields::departureLocationName(),
            GeneralFields::arrivalIata(),
            GeneralFields::arrivalLocationName(),
            F::createString('Comments'),
            F::createSelect(
                'CriterionID',
                [
                    'name' => 'Criterion',
                    'options' => ShipmentCriteria::getSelectOptions(),
                    'sqlWhere' => function ($value, $filtersValues) {
                        $query = "1";
                        if ($value === 'LogicAND') {
                            //Проверим все чекбоксы по статусам на каждый из которых будем создавать подзапрос EXISTS
                            $checkboxFound = false;
                            foreach (ShipmentCriteria::getSelectOptions() as $crId => $_) {
                                if ($filtersValues['Criterion' . $crId]) {
                                    $checkboxFound = true;
                                    $query .=
                                        " AND EXISTS (SELECT ShipmentID FROM shipmentcriterion WHERE ShipmentID = shipment.ID AND CriterionID = " .
                                        $crId . ")";
                                }

                                if (!$checkboxFound) {
                                    $query .= " AND 0";
                                }
                            }
                        } elseif ($value === 'LogicOR') {
                            $crIds = [];
                            foreach (ShipmentCriteria::getSelectOptions() as $crId => $_) {
                                if ($filtersValues['Criterion' . $crId]) {
                                    $crIds[] = $crId;
                                }
                            }

                            if ($crIds) {
                                $query .=
                                    " AND EXISTS (SELECT ShipmentID FROM shipmentcriterion WHERE ShipmentID = shipment.ID AND CriterionID IN (" .
                                    implode(', ', $crIds) . "))";
                            } else {
                                $query .= " AND 0";
                            }
                        } else {
                            $query .=
                                " AND EXISTS (SELECT ShipmentID FROM shipmentcriterion WHERE ShipmentID = shipment.ID AND CriterionID = " .
                                (int)$value . ")";
                        }

                        return $query;
                    }
                ]
            ),
            ShipmentFields::fileClosed(),
            /*
            F::create(
                'Month',
                F::TYPE_MONTH,
                [
                    'yearFrom' => $yearFrom,
                    'yearUntil' => $yearUntil,
                    'filterFunction' => function ($value) {
                        return "DATE_FORMAT(shipment.Month, '%Y-%m') = '" . substr($value, 0, 7) . "'";
                    }
                ]
            ),
            */
            F::createMonth('MonthFrom', [
                'name' => 'Month',
                'yearFrom' => $monthYearFrom,
                'yearUntil' => $monthYearUntil,
                'sqlWhere' => function ($value, $filtersValues, $sqlWhere) {
                    $sqlWhere = str_replace('{fieldKey}', 'Month', $sqlWhere);
                    //Проверим, задан ли конечный месяц?
                    if ($filtersValues['MonthUntil']) {
                        //Задан конечный фильтр - делаем неравненство, чтобы получился интервал месяцев
                        $sqlWhere = str_replace('=', '>=', $sqlWhere);
                    }

                    return $sqlWhere;
                }
            ]),
            F::createMonth('MonthUntil', [
                'name' => 'Month Until',
                'yearFrom' => $monthYearFrom,
                'yearUntil' => $monthYearUntil,
                'sqlWhere' => function ($value, $filtersValues, $sqlWhere) {
                    $sqlWhere = str_replace('{fieldKey}', 'Month', $sqlWhere);
                    return str_replace('=', '<=', $sqlWhere);
                }
            ]),
            CF::color(),

            ShipmentFields::flightNo()->setPlaceholder('No'),

            ShipmentFields::flightDate()->setPlaceholder('Date'),

            F::createSelect('HasVAT', [
                'name' => 'VAT (НДС)',
                'options' => [1 => 'Есть НДС'],
                'sqlWhere' => "EXISTS(SELECT * FROM vinvoiceline WHERE FileNumber = shipment.Name AND (VATAmount !=0 OR Name = 'НДС'))"
            ])

        );

        $this->addFiltersDatesFromUntil('ETD', ['name' => 'ETD']);
        $this->addFiltersDatesFromUntil('ETA', ['name' => 'ETA']);
        $this->addFiltersDatesFromUntil('ATA', ['name' => 'ATA']);
        $this->addFiltersDatesFromUntil('ReadyForCarriageDate', ['name' => 'Ready Carr.']);

        $this->filtersLayout = [
            [

                'FileNumbers',
                'MAWBNumbers',
                'TrafficID',
                'CompanyID',
                'CustomerID',
                'CustomerAccountID',
                'ConsigneeID',
                'Responsible',
            ],
            [
                'DepartureLocationName' => GeneralFields::filterTemplateLocation('Departure'),
                'ArrivalLocationName' => GeneralFields::filterTemplateLocation('Arrival'),
                'Comments',
                'CriterionID'
            ],
            [
                'Color' => function () {
                    return '<div class="color-container color' . Template::value('Color') . '">'
                        . Template::html('Color')
                        . '</div>';
                },
                'BUIDsFilter',
                'FileClosed',

                'MonthFrom' => function () {
                    return Template::html('MonthFrom') . ' - ' . Template::html('MonthUntil');
                },
                'ReadyForCarriageDate',
                'ETD',
                'ETA',
                'ATA',
                'FlightNo' => function () {
                    return Template::html('FlightNo') . ' ' . Template::html('FlightDate');
                },
                'HasVAT'

            ]
        ];
    }

    protected function getDataFromPost()
    {
        $dataFromRequest = parent::getDataFromPost();

        //Чтобы правильно записать поле MAWB Number, нужно определить traffic.Type
        if (isset($dataFromRequest['TrafficID'])) {
            $trafficType = Db::getOne("SELECT Type FROM traffic WHERE ID=" . (int)$dataFromRequest['TrafficID']);

            if ($trafficType === 'avia') {
                if (isset($_POST['MAWBNumber1'], $_POST['MAWBNumber2'])) {
                    $dataFromRequest['MAWBNumber'] = str_replace(' ', '',
                        trim($_POST['MAWBNumber1']) . '-' . trim($_POST['MAWBNumber2']));
                    if ($dataFromRequest['MAWBNumber'] === '-') {
                        $dataFromRequest['MAWBNumber'] = '';
                    }
                }
            }
        }

        //нужно определить  Month
        if ($dataFromRequest['Month'] === '') {
            if ($dataFromRequest['TrafficID'] == 2) {
                if (!$this->_obj->getId()) {
                    $dataFromRequest['Month'] = date('Y-m-01');
                } else {
                    $dataFromRequest['Month'] = substr($this->_obj['CreatedDateTime'], 0, 7) . '-01';
                }
            } else {
                //Для других трафиков в качестве месяца используется ETD
                if ($dataFromRequest['ETD'] !== '') {
                    $dataFromRequest['Month'] = substr($dataFromRequest['ETD'], 0, 7) . '-01';
                }
            }
        }

        return $dataFromRequest;
    }

    protected function validateUnique(array $dataFromRequest)
    {
        if ($dataFromRequest['MAWBNumber'] != '') {
            //Проверим, что номер MAWB не повторялся за последний год
            //Проверим существование такого имени в базе
            $query = "SELECT ID
                  FROM shipment
                  WHERE MAWBNumber='" . Db::esc($dataFromRequest['MAWBNumber']) . "'
                      AND CreatedDateTime >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            if ($this->_obj->getId()) {
                $query .= " AND ID != " . $this->_obj->getId();
            }

            if ($id = Db::getOne($query)) {
                $this->riseSaveErorr(
                    'MAWB ' . htmlspecialchars($dataFromRequest['MAWBNumber']) . ' already exists, ' .
                    Link::create('Shipment', 'view...')
                        ->view($id)
                        ->blank()
                        ->getHtml(),
                    'MAWBNumber'
                );
            }
        }
    }

    protected function validateMandatory(array $dataFromRequest)
    {
        if ($dataFromRequest['FileClosed'] == ShipmentObj::SHIPMENT_CLOSED) {

            //Если пользователь пытается закрыть перевозку - добавим mandatory поля!
            //Они будут проверены при запуске стандартной проверки parent::validateMandatory($dataFromRequest); (ниже)
            $mandatoryFieldKeys = [
                'MAWBNumber',
                'ResponsibleSales',
                'ETD',
                'ATD',
                'ETA',
                'ATA',
                'Pieces',
                'ChargeableWeight',
                'GrossWeight',
                'Volume',
                'NatureOfGoods'
            ];

            if ($dataFromRequest['TrafficID'] == Constant::TRAFFIC_OCEAN) {
                $mandatoryFieldKeys[] = 'POLID';
                $mandatoryFieldKeys[] = 'PODID';
            } else {
                $mandatoryFieldKeys[] = 'DepartureLocationName';
                $mandatoryFieldKeys[] = 'ArrivalLocationName';
            }

            $this->getFieldset()->filterByKeys($mandatoryFieldKeys)->setParam('editMandatory', true);

            if (!empty($dataFromRequest['ATA']) && $dataFromRequest['ATA'] > date('Y-m-d')) {
                $this->riseSaveErorr('ATA in future', 'ATA');
            }

            // Проверим, что хотя бы один инвойс выставлен и получен по этому файлу
            if ($this->_obj['Name']
                && !Db::getOne("SELECT ID FROM cinvoiceline WHERE FileNumber = " . $this->_obj['Name'] . " LIMIT 1")
                && !Db::getOne("SELECT ID FROM vinvoiceline WHERE FileNumber = " . $this->_obj['Name'] . " LIMIT 1")
            ) {
                $this->riseSaveErorr(
                    'At least one Customer Invoice and one Vendor Invoices should be assigned to the shipment'
                );
            }
        }

        // Запускаем стандартную проверку обязательных полей
        parent::validateMandatory($dataFromRequest);

        // Если у пользователя есть опция сохранения без заполнения обязательных полей, то все накопленные критические ошибки
        // превратим в некритические
        if (User::hasSpecialAccess(Application::SA_SHIPMENT_SAVE_WITHOUT_MANDATORY)) {
            foreach ($this->_saveErrors as &$saveError) {
                $saveError['critical'] = false;
            }
        }

        // Задача из плана: При заведении файла нужно проверить карточку клиента - если OurCompany там не заполнен - не давать сохранить файл с соответвующим сообщением.
        $customerId = (int)$dataFromRequest['CustomerID'];
        if ($customerId) {
            //Загрузим поле Our Company из клиента
            $customerCompanyId = Db::getOne("SELECT CompanyID FROM customer WHERE ID = @i", $customerId);
            if (!$customerCompanyId) {
                $msg = 'В целях наполнения базы невозможно сохранить перевозку, пока в карточке клиента не заполнено поле Our Company.';
                $msg .= '<br>' . Link::create('Customer', 'Отредактировать карточку клиента')
                        ->view($customerId)
                        ->blank()
                        ->getHtml();
                $this->riseSaveErorr($msg);
            }
        }

        // Если пользователь вручную заполнил поле ShipmentRequestNo, то проверим, что это значение не выходит далеко впедёр от максимального
        if (isset($dataFromRequest['ShipmentRequestNo'])
            && $dataFromRequest['ShipmentRequestNo'] !== '0000-00-00'
            && $dataFromRequest['CustomerAccountID']
            && (Constant::isOneCCompany($dataFromRequest['CompanyID']))
        ) {

            $shipmentRequestDate = $this->getShipmentRequestDate($dataFromRequest['ETD']);
            $shipmentRequestYearShort = substr($shipmentRequestDate, 2, 2);
            $maxShipmentRequestNo = $this->getMaxShipmentRequestNo($dataFromRequest['CompanyID'],
                $dataFromRequest['CustomerAccountID'], $shipmentRequestDate);

            if ($maxShipmentRequestNo && $dataFromRequest['ShipmentRequestNo'] - $maxShipmentRequestNo > 1) {

                $msg = 'Номер заявки <strong class="bad">'
                    . $dataFromRequest['ShipmentRequestNo'] . '-' . $shipmentRequestYearShort
                    . '</strong> выглядит слишком большим,'
                    . ' последний использовавшийся в этом году номер: <strong>'
                    . $maxShipmentRequestNo . '-' . $shipmentRequestYearShort
                    . '</strong>,'
                    . ' проверьте правильность заполнения';

                $this->riseSaveWarning($msg);

            }

        }
    }

    protected function getTplData()
    {
        $tdata = parent::getTplData();

        //Добавим переменную TrafficType, чтобы скрыть или отобразить блок Ocean
        $tdata['TrafficType'] = Db::getOne("SELECT Type FROM traffic WHERE ID = " . (int)$this->_obj['TrafficID']);
        if (!$this->isStateView()) {

            //Разделим поле MAWB на два поля для ввода
            $mawb1 = '';
            $mawb2 = '';

            if (isset($this->_obj['MAWBNumber'])) {
                $parts = explode('-', $this->_obj['MAWBNumber']);
                $mawb1 = substr($parts[0], 0, 3);
                if (isset($parts[1])) {
                    $mawb2 = $parts[1];
                } elseif (mb_strlen($parts[0], 'UTF-8') > 3) {
                    $mawb2 = substr($parts[0], 3, 8);
                }
            }

            $tdata['MAWBNumber1'] = F::create(
                'MAWBNumber1',
                F::TYPE_STRING,
                [
                    'editCssClass' => 'input-int',
                    'editMaxLength' => 3
                ]
            )->getHtmlInput($mawb1);

            $tdata['MAWBNumber2'] = F::create(
                'MAWBNumber2',
                F::TYPE_STRING,
                [
                    'editCssClass' => 'input-int',
                    'editMaxLength' => 8
                ]
            )->getHtmlInput($mawb2);

        }

        //Для сообщения "Attention: prepaid customer"
        $tdata['isPrepaidCustomer'] = false;
        if (isset($this->_obj['CustomerID']) && $this->_obj['CustomerID']) {
            $creditAmount = Db::getOne(
                "SELECT CreditAmount FROM customer WHERE ID = " . (int)$this->_obj['CustomerID']
            );
            if ($creditAmount == 0) {
                $tdata['isPrepaidCustomer'] = true;
            }
        }

        if (isset($this->_obj['ETD'])) {
            $tdata['ShipmentRequestDate'] = $this->getShipmentRequestDate($this->_obj['ETD']);
        } else {
            $tdata['ShipmentRequestDate'] = date('Y-m-d');
        }

        //Criteria Block
        if ($this->_obj->getId()) {
            $crArr = ShipmentCriteria::getShipmentCriteriaArray($this->_obj->getId());
            if ($crArr) {
                $crBlock = '<ul>';
                foreach ($crArr as $crInfo) {
                    $crBlock .= '<li title="' . htmlspecialchars($crInfo['Description']) . '"' .
                        (isset($crInfo['cssClass']) ? ' class="' . $crInfo['cssClass'] . '"' : '') . '>' .
                        htmlspecialchars($crInfo['text']) . '</li>';
                }
                $crBlock .= '</ul>';
                $tdata['Criteria'] = $crBlock;
            }
        }

        return $tdata;
    }

    protected function initAddNewJsData()
    {
        parent::initAddNewJsData();
        $this->addAddNewEditPageParams();
    }

    protected function initEditJsData()
    {
        parent::initEditJsData();
        $this->addAddNewEditPageParams();
    }

    protected function addAddNewEditPageParams()
    {
        //Распечатаем связь между Customer и Consignee, чтобы при выборе Customer поменять Consignee
        $query = "SELECT c.ID,
                      IF(c.CreditAmount = 0, 1, 0) AS p,
                      IFNULL((SELECT UserID FROM customer_responsible WHERE CustomerID = c.ID AND Role = '" . Application::ROLE_SALES . "' ORDER BY ID LIMIT 1), '') AS s,
                      IFNULL((SELECT UserID FROM customer_responsible WHERE CustomerID = c.ID AND Role = '" . Application::ROLE_CS . "' ORDER BY ID LIMIT 1), '') AS c
                  FROM customer c
                  ";
        $customersInfo = Db::getAssoc($query);

        Response::addJsData([
            'customersInfo' => $customersInfo,
            'trafficTypes' => Db::getAssoc("SELECT ID, Type FROM traffic"),
            'airlineCodes' => Db::getAssoc(
                "SELECT DigitalCode, AlphaCode FROM airline WHERE DigitalCode != 0 AND AlphaCode != ''"
            )
        ]);
    }

    protected function initListJsData()
    {
        parent::initListJsData();

        $criteriaArr = ShipmentCriteria::getSelectOptions();
        Response::addJsData(['criteriaArr' => $criteriaArr]);
    }

    protected function getViewEditLogTabsmenuItems()
    {
        $menuItems = parent::getViewEditLogTabsmenuItems();
        $menuItems[] = [
            'text' => 'Cost-sheets',
            'url' => $this->_obj->getLinkView()->func('cs')->params($this->getModuleSessionUrlParams())->getHref(),
            'active' => isset($_GET['func']) && $_GET['func'] === 'cs'
        ];

        return $menuItems;
    }

    /**
     * Переопределяем стандартную функцию, чтобы не добавлять ID в конец
     * @param string $stateText
     */
    protected function setPageHeadForObj($stateText = '')
    {
        $headText = 'Shipments - ';
        if ($stateText) {
            $headText .= $stateText . ' ';
        }
        $headText .= '<strong>' . $this->_obj->getDescriptionHtml() . '</strong>';
        Response::setPageHeadHtml($headText);
        Response::setPageTitle($headText);
    }

    /**
     * Возвращает массив данных из другого Cost-sheet'а для копирования
     * @return array
     */
    public static function proceedLoadCs()
    {
        //Узнаем ShipmentID по переданному номеру файла
        $out = [];
        $sid = Db::getOne("SELECT ID FROM shipment WHERE Name = '" . Db::esc($_GET['filenumber']) . "'");
        if ($sid) {
            //Перевозка найдена, загрузим все строки из её Cost-sheet'а
            $out['income'] = Db::getAll(
                "SELECT Name, Amount, CurrencyISO FROM csincome WHERE ShipmentID = " . $sid
            );

            $out['expenses'] = Db::getAll(
                "SELECT Name, Amount, CurrencyISO FROM csexpenses WHERE ShipmentID = " . $sid
            );
        }

        return $out;
    }

    protected function hasWriteAccessRightToObj(ObjAbstract $obj)
    {
        // Узнаем статус перевозки, и, если статус Closed, прверим что у пользователя есть права на редактирование
        return $obj['FileClosed'] != ShipmentObj::SHIPMENT_CLOSED
            || User::hasSpecialAccess(Application::SA_SHIPMENT_CHANGECLOSED);
    }

    protected function hasWriteAccessRightToFolder(ObjAbstract $obj)
    {
        // Разрешаем загрузать в папку всем и всегда
        return true;
    }

    protected function loadFromQuotaionRequest()
    {
        if (isset($_GET['qid'])) {
            $query = "SELECT CustomerID
                        , CompanyID
                        , ResponsibleSalesID AS ResponsibleSales
                        , ResponsibleCSID AS ResponsibleCS
                        , CustomerID
                        , VendorID
                        , NatureOfGoods
                        , DepartureIATA
                        , DepartureLocationName
                        , ArrivalIATA
                        , ArrivalLocationName
                        , GrossWeight
                        , ChargeableWeight
                        , AirlineAlphaCode
                        , Incoterms
                        , IncotermsLocation
                        , CommentsInternal AS Comments
                      FROM quotation
                      WHERE ID = " . (int)$_GET['qid'];
            $quotationInfo = Db::getRow($query);
            if ($quotationInfo) {
                $this->_obj->mergeData($quotationInfo);
                $this->_obj['QuotationID'] = (int)$_GET['qid'];

                //Дополним поле Pieces и Volume
                $query = "SELECT sum(Pieces) AS Pieces
                              , sum(Volume) AS Volume
                          FROM quotationcargo
                          WHERE QuotationID = " . (int)$_GET['qid'];
                $pv = Db::getRow($query);
                if ($pv) {
                    $this->_obj->mergeData($pv);
                }
            }
        }
    }

    protected function proceedAddNew()
    {
        $this->loadFromQuotaionRequest();

        //При добавлении нового шипмента нужно ограничить выбор месяца этим месяцем + 2 следующих
        $yearFrom = (new \DateTime())->format('Y-m');
        $yearUntil = (new \DateTime())->modify('+3 month')->format('Y-m');
        $this->getField('Month')->setParams(['yearFrom' => $yearFrom, 'yearUntil' => $yearUntil]);

        parent::proceedAddNew();
    }

    protected function afterInsert(ObjAbstract $obj, array $dataFromPost)
    {
        //Если шипмент был создан из котировки (заполнено поле QuotationID), то нужно заполнить и Cost-sheet!
        if ($obj['QuotationID'] && $obj['QuotationID'] < 700000) {

            $newCsLines = $this->getCsLinesFromQuotation(
                $obj['QuotationID'],
                $obj['GrossWeight'],
                $obj['ChargeableWeight'],
                $obj['Pieces'],
                $obj->getId()
            );

            if ($newCsLines) {

                $shipmentId = $obj->getId();
                $quotationId = $obj['QuotationID'];

                // Вспомагательная функция, которая вставляет строки Cost-sheet (вызывается два раза: один раз для доходов и один раз для расходов)
                $insertCsLines = function (array $csLines, IncomeExpensesObjAbstract $csLineObjBase) use (
                    $shipmentId,
                    $quotationId
                ) {
                    foreach ($csLines as $csLineData) {
                        $csLineObj = clone $csLineObjBase;
                        $csLineObj->setMasterIdForDbLog($shipmentId);
                        $csLineObj->insertDb($csLineData, 'from Quotation ' . $quotationId);
                    }
                };

                //Вставляем полученные строки в базу
                $insertCsLines($newCsLines['income'], new IncomeObj());
                $insertCsLines($newCsLines['expenses'], new ExpensesObj());

                /** @var ShipmentObj $obj */
                $cs = new CostSheetObj($obj);
                //Обновляем EGP/Deviation и кеширующие поля Total Income и Total Expenses
                $cs->updateShipmentCache();
                //Сохраним первый (только что созданный Cost-sheet) в history
                $cs->saveToHistory();

            }
        }

        //При создании новой перевозки отправляем Email уведомление для Sales и для Customer Service
        /** @var ShipmentObj $obj */
        $this->sendShipmentEmail($obj, '', $this->flash);
    }

    protected function afterUpdate(
        ObjAbstract $obj,
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff,
        $fromMultipleUpdate
    ) {
        if ($oldData['ResponsibleCS'] != $obj['ResponsibleCS']) {
            $additionalText = 'New Customer Service <strong>'
                . htmlspecialchars(Db::getOne("SELECT Name FROM user WHERE ID = @i", $obj['ResponsibleCS']))
                . '</strong>';
            /** @var ShipmentObj $obj */
            $this->sendShipmentEmail($obj, $additionalText, $msgBuff);
        }
    }

    protected function afterSubmit(
        ObjAbstract $obj,
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff,
        $fromMultipleUpdate
    ) {
        if (!$fromMultipleUpdate) {
            // Заготовим стандартные папки
            $folderRelative = $this->getFolderRelativePath($obj->getData());
            foreach (Constant::$standardFolders as $standardFolder) {
                FilesFolder::mkdir("$folderRelative/$standardFolder");
            }
        }
    }

    private function sendShipmentEmail(ShipmentObj $obj, $additionalText, Msg $msgBuff)
    {
        //Отправим на дарес Sales, если создал НЕ Sales.
        $to = [];
        if ($obj['ResponsibleSales'] && $obj['ResponsibleSales'] != User::getId()) {
            $salesEmail = Db::getOne("SELECT Email FROM user WHERE ID = @i", $obj['ResponsibleSales']);
            if ($salesEmail) {
                $to[] = $salesEmail;
            }
        }

        if ($obj['ResponsibleCS'] && $obj['ResponsibleCS'] != User::getId()) {
            $csEmail = Db::getOne("SELECT Email FROM user WHERE ID = @i", $obj['ResponsibleCS']);
            if ($csEmail) {
                $to[] = $csEmail;
            }
        }

        if (!$to) {
            //Некому отправлять почту - завершаем работу метода
            return;
        }

        //Отправим Email уведомление для Customer Service и Sales при создании новой перевозки
        $newOrUpdate = $this->_justInserted ? 'New' : 'Update';
        $subject = $newOrUpdate
            . ' Shipment ' . $obj['Name']
            . ' for ' . $obj->getFieldText('CustomerID')
            . ' ' . $obj->getFieldText('DepartureIATA')
            . '->' . $obj->getFieldText('ArrivalIATA');

        $body = $newOrUpdate
            . ' Shipment <a href="' . Config::$localUrl . '/?a=Shipment&id=' . $obj->getId() . '"><strong>' . $obj['Name'] . '</strong></a>'
            . ' for ' . $obj->getFieldText('CustomerID')
            . ' ' . $obj->getFieldText('DepartureIATA')
            . '➔' . $obj->getFieldText('ArrivalIATA')
            . '<br><br>';

        if ($additionalText) {
            $body .= $additionalText . '<br><br>';
        }

        $body .= '<small>';
        $body .= $this->_justInserted ? 'Created' : 'Updated';
        $body .= ' by ' . htmlspecialchars(User::getName()) . '</small>';

        Mailer::create($to, $subject, $body)
            ->send($msgBuff);
    }

    /**
     * Метод используется для получения массива строк для вставки в Cost-sheet на основе номера котировки.
     * Актуально, когда создаётся новая перевозка на основе котировки - в этом случае в afterInsert() нужно заполнить
     * CostSheet.
     * Также, если перевозка уже создана то в Cost-sheet можно нажать "Fill from Quotation" - этот метод загружает
     * данные из котировки и они возвращаются пользователю в ajax ответе для заполнения Cost-sheet в режиме Edit
     * @param int $quotationId
     * @param int $grossWeight
     * @param int $chargeableWeight
     * @param int $pieces
     * @param int $shipmentId
     * @return array
     */
    private function getCsLinesFromQuotation($quotationId, $grossWeight, $chargeableWeight, $pieces, $shipmentId): array
    {
        //Загрузим валюту котировки
        //Заодно проверим, существует ли такая котировка вообще?
        $quotationCurrencyIso = Db::getOne(
            "SELECT CurrencyISO FROM quotation WHERE ID = @i",
            $quotationId
        );

        if (!$quotationCurrencyIso) {
            //Котировка не найдена
            return [];
        }

        $outCsLines = ['income' => [], 'expenses' => []];

        foreach (['income', 'expenses'] as $incomeOrExpenses) {

            $quotationLines = Db::getAll(
                "SELECT Name, Price, Per, MinAmount
                 FROM quotation{$incomeOrExpenses}
                 WHERE QuotationID = @i
                 ORDER BY ID",
                $quotationId
            );

            foreach ($quotationLines as $quotationLine) {

                //Подготовим массив данных для вставки в строку Cost-sheet'а
                //Для начала нужно расчитать Amount, который может зависеть от веса или числа мест
                switch ($quotationLine['Per']) {
                    case 'GW':
                        $multiplier = $grossWeight;
                        break;

                    case 'CW':
                        $multiplier = $chargeableWeight;
                        break;

                    case 'Piece':
                        $multiplier = $pieces;
                        break;

                    default:
                        $multiplier = 1;
                }

                $price = $quotationLine['Price'];
                $amount = $price * $multiplier;
                if ($amount < $quotationLine['MinAmount']) {
                    //Если сумма меньше минимальной, то сбрасываем сумму на минимальную
                    $amount = $quotationLine['MinAmount'];
                    //а кол-во и цену придётся сбросить таким образом, чтобы при дальнейшем редактировании Cost-sheet'а
                    //через JS калькуляция не изменилась
                    $multiplier = 1;
                    $price = $amount;
                }

                $outCsLines[$incomeOrExpenses][] = [
                    'Name' => $quotationLine['Name'],
                    'ShipmentID' => $shipmentId,
                    'Quantity' => $multiplier,
                    'Price' => $price,
                    'Amount' => $quotationCurrencyIso . ' ' . $amount
                ];
            }

        }

        return $outCsLines;
    }

    /**
     * Метод используется для получения массива строк для вставки в Cost-sheet на основе номера Standart Rate, которые
     * заполняются в карточке клиента.
     * Если перевозка уже создана то в Cost-sheet можно нажать "Fill from Quotation" - этот метод загружает
     * данные из ставки и они возвращаются пользователю в ajax ответе для заполнения Cost-sheet в режиме Edit
     * @param int $srId
     * @param float $chargeableWeight
     * @param int $shipmentId
     * @return array
     */
    private function getCsLinesFromStandartRate($srId, $chargeableWeight, $shipmentId)
    {
        // Загрузим саму ставку
        // Заодно проверим, существует ли такая ставка вообще?
        $rate = Db::getRow("SELECT * FROM customer_standartrates WHERE ID = @i", $srId);

        if (!$rate) {
            //Ставка не найдена
            return [];
        }

        $interval = Constant::getWeightInterval($chargeableWeight);
        //Какую цену применить как основную?
        $amountFieldKeySuffix = $interval ? 'Amount' . $interval : 'AmountN';

        // Подготовим выходной массив
        $outCsLines = ['income' => [], 'expenses' => []];

        foreach (['income' => 'Selling', 'expenses' => 'Buying'] as $incomeOrExpenses => $sellingOrBuying) {

            $amountFieldKey = $sellingOrBuying . $amountFieldKeySuffix;
            $price = $rate[$amountFieldKey];
            $quantity = $chargeableWeight;
            $amount = $price * $quantity;

            //Если итоговая сумма меньше меньшего - нужно использовать минимальную сумму
            if ($amount < $rate[$sellingOrBuying . 'MinAmount']) {
                $amount = $rate[$sellingOrBuying . 'MinAmount'];
                $price = $amount;
                $quantity = 1;
            }

            $outCsLines[$incomeOrExpenses][] = [
                'Name' => 'Airfreight',
                'ShipmentID' => $shipmentId,
                'Quantity' => $quantity,
                'Price' => $price,
                'Amount' => $rate[$sellingOrBuying . 'CurrencyISO'] . ' ' . $amount
            ];

            //Добавим FSC и SSC
            foreach (['FSC', 'SSC'] as $fscOrSsc) {
                $price = $rate[$sellingOrBuying . $fscOrSsc . 'Amount'];
                if ($price) {
                    $outCsLines[$incomeOrExpenses][] = [
                        'Name' => $fscOrSsc,
                        'ShipmentID' => $shipmentId,
                        'Quantity' => $chargeableWeight,
                        'Price' => $price,
                        'Amount' => $rate[$sellingOrBuying . 'CurrencyISO'] . ' ' . ($price * $chargeableWeight)
                    ];
                }
            }

            //Теперь добавим все надбавки
            $surchargesTableName = "customer_standratrates_" . strtolower($sellingOrBuying) . "surcharges";
            $surcharges = Db::getAll(
                "SELECT *
                 FROM $surchargesTableName
                 WHERE CustomerStandartRateID = {$rate['ID']}"
            );

            foreach ($surcharges as $surcharge) {
                $base = (double)$surcharge['BaseAmount'];
                $price = (double)$surcharge['PerKgAmount'];
                $quantity = $chargeableWeight;
                $amount = $base + $price * $quantity;

                $minAmount = (double)$surcharge['MinAmount'];
                $maxAmount = (double)$surcharge['MaxAmount'];

                if ($amount < $minAmount) {
                    $amount = $minAmount;
                    $price = $amount;
                    $quantity = 1;
                } elseif ($maxAmount > 0 && $amount > $maxAmount) {
                    $amount = $maxAmount;
                    $price = $amount;
                    $quantity = 1;
                } elseif ($base != 0) {
                    $price = $amount;
                    $quantity = 1;
                }

                $outCsLines[$incomeOrExpenses][] = [
                    'Name' => $surcharge['Name'],
                    'ShipmentID' => $shipmentId,
                    'Quantity' => $quantity,
                    'Price' => $price,
                    'Amount' => $surcharge['CurrencyISO'] . ' ' . $amount
                ];
            }
        }

        return $outCsLines;
    }

    /**
     * Переопределяем стандартный метод, чтобы запретить удаление файлов к которым привязаны счета
     */
    protected function sendDeleteConfirmDialog()
    {
        //Найдём число привязанных счетов
        $cinvCount = (int)Db::getOne(
            "SELECT count(*) 
              FROM cinvoice_file_amount cfa, cinvoice ci 
              WHERE cfa.FileNumber = @i
                  AND cfa.CInvoiceID = ci.ID
                  AND ci.Status NOT IN (" . InvStatus::getDeletedStatusesSql() . ")",
            $this->_obj['Name']
        );
        $vinvCount = (int)Db::getOne(
            "SELECT count(*)
            FROM vinvoice_file_amount vfa, vinvoice vi
            WHERE vfa.FileNumber = @i
                AND vfa.VInvoiceID = vi.ID
                AND vi.Status NOT IN (" . InvStatus::getDeletedStatusesSql() . ")",
            $this->_obj['Name']
        );

        if ($cinvCount || $vinvCount) {
            $msg = "Can't delete shipment because ";
            $invMsg = [];
            if ($cinvCount) {
                $invMsg[] = $cinvCount . " Customer Invoice" . Misc::getSSuffix($cinvCount);
            }
            if ($vinvCount) {
                $invMsg[] = $vinvCount . " Vendor Invoice" . Misc::getSSuffix($vinvCount);
            }
            $msg .= implode(' and ', $invMsg);
            $msg .= " " . Misc::getIsAreVerb($cinvCount + $vinvCount) . " linked to the shipment";

            Response::showDialog($msg);
        }

        //Если же инвойсов нет, то отправим стандартный подтверждающий удаление диалог
        parent::sendDeleteConfirmDialog();

    }

    private function getShipmentRequestDate(string $etd)
    {
        return $etd !== '0000-00-00' ? $etd : date('Y-m-d');
    }

    private function getMaxShipmentRequestNo(
        int $companyId,
        int $customerAccountId,
        string $shipmentRequestDate,
        bool $forUpdate = false
    ) {
        $shipmentRequestYear = substr($shipmentRequestDate, 0, 4);

        $query = "SELECT max(ShipmentRequestNo)
                  FROM shipment 
                  WHERE CustomerAccountID = @i
                       AND CompanyID = $companyId
                       AND YEAR(ETD) = '$shipmentRequestYear'";

        if ($forUpdate) {
            $query .= " FOR UPDATE";
        }

        return (int)Db::getOne($query, $customerAccountId);
    }

    protected function viewPostShipmentRequestExcel()
    {
        /** @var ShipmentObj $shipmentObj */
        $shipmentObj = $this->_obj;

        $exceltemplate = null;
        if ($shipmentObj['CompanyID'] == Constant::OC_PLS_OOO) {
            $exceltemplate = 'ShipmentRequestPLS.xlsx';
        } elseif ($shipmentObj['CompanyID'] == Constant::OC_PGL_OOO) {
            $exceltemplate = 'ShipmentRequestPGL.xlsx';
        } elseif ($shipmentObj['CompanyID'] == Constant::OC_PLS12_OOO) {
            $exceltemplate = 'ShipmentRequestPLS12.xlsx';
        } elseif ($shipmentObj['CompanyID'] == Constant::OC_PGL11_OOO) {
            $exceltemplate = 'ShipmentRequestPGL11.xlsx';
        } else {
            Session::flashError('Для компании ' . $shipmentObj->getFieldHtml('CompanyID') . ' нет формы заявки');
            $this->redirectToView();
        }

        $shipmentRequestDate = $this->getShipmentRequestDate($shipmentObj['ETD']);

        if (!$shipmentObj['ShipmentRequestNo'] && $shipmentObj['CustomerAccountID']) {

            // В базе нет сохранённого номера заявки - сгенерируем автоматически
            // Выбираем максимальный номер заявки в рамках этого года
            Db::begin();
            $lastNo = $this->getMaxShipmentRequestNo($shipmentObj['CompanyID'], $shipmentObj['CustomerAccountID'],
                $shipmentRequestDate, true);
            $newNo = $lastNo ? $lastNo + 1 : 1;
            $shipmentObj->updateDb(['ShipmentRequestNo' => $newNo], 'Автоматическое присвоение следующего номера');
            Db::commit();

        }

        // Сформируем путь до файла назначения и проверим, что этого файла ещё нет в папке
        $shipmentRequestYearShort = substr($shipmentRequestDate, 2, 2);
        $filename = 'Заявка №'
            . $shipmentObj['ShipmentRequestNo']
            . '-' . $shipmentRequestYearShort
            . ' ' . $shipmentObj['MAWBNumber']
            . ' (' . FieldDate::beautify($shipmentRequestDate) . ')'
            . '.xlsx';

        $filename = File::getValidFilename($filename);

        $filepathAbs = $this->getFolderAbsolutePath() . "/$filename";

        if (!isset($_GET['confirm']) && file_exists(File::encode($filepathAbs))) {

            $url = '?a=Shipment&id=' . $this->_obj->getId() . '&func=ShipmentRequestExcel&confirm=1#ajax';
            $dialogHtml = '<form action="' . $url . '" method="post">В папке уже существует файл <strong>'
                . htmlspecialchars($filename)
                . '</strong>, хотите его перезаписать?'
                . '</form>';

            Response::showDialog($dialogHtml, 'Перезаписать');
            return;

        }

        $excelData = $shipmentObj->getExcelData();


        $excelData['ShipmentRequestDate'] = self::getDateInText($shipmentRequestDate);
        $excelData['ShipmentRequestYearShort'] = $shipmentRequestYearShort;

        // Загрузим номер контракта из карточки Customer Account
        $contractNo = Db::getOne("SELECT ContractNo FROM customeraccount WHERE ID = @i",
            $shipmentObj['CustomerAccountID']);
        // Заменим в номере контракта английское слово "from" на русское "от"
        $excelData['CustomerAccountContractNo'] = str_replace('from', 'от', $contractNo);

        // Добавим адрес получателя
        if ($shipmentObj['ConsigneeID'] > 0) {
            // Если был задан конкретный Consignee в выпадающем списке на форме перевозки, то подтянем информацию из него
            $excelData += self::getAddressForShipmentRequest('customerconsignee', $shipmentObj['ConsigneeID'],
                'Consignee');
        } elseif ($shipmentObj['ConsigneeID'] == -1) {
            // Если же был выбран вариант "совпадает с Customer Account", тогда загрузим информацию из таблицы Customer Account
            // (но префикс для полей с данными по прежнему будет 'Consignee'
            $excelData += self::getAddressForShipmentRequest('customeraccount', $shipmentObj['CustomerAccountID'],
                'Consignee');
        }

        // Добавим адрес плательщика (Customer Account)
        $excelData += self::getAddressForShipmentRequest('customeraccount', $shipmentObj['CustomerAccountID'],
            'CustomerAccount');

        // Добавим адрес первого отправителя
        $firstShipper = Db::getRow("SELECT Name, HAWBNumber FROM shipmentshipper WHERE ShipmentID = @i",
            $shipmentObj->getId());
        if ($firstShipper) {
            $excelData['HAWBNumber'] = $firstShipper['HAWBNumber'];
            if ($firstShipper['Name']) {
                $shipperId = Db::getOne("SELECT ID FROM list_shipper WHERE Name = @s", $firstShipper['Name']);
                if ($shipperId) {
                    $excelData += self::getAddressForShipmentRequest('list_shipper', $shipperId,
                        'Shipper');
                }
            }
        }

        // Найдём первый счёт выставленный на клиента по этой перевозке и подставим его сумму в заявку
        $firstCInvoice = Db::getRow(
            "SELECT cfa.Amount, ci.CurrencyISO, ci.RatePercent
             FROM cinvoice_file_amount cfa, cinvoice ci
             WHERE cfa.CInvoiceID = ci.ID
                 AND cfa.FileNumber = @s
             LIMIT 1", $shipmentObj['Name']
        );

        if ($firstCInvoice) {
            $excelData['AmountAndCurrency'] = strip_tags(FieldMoney::beautify($firstCInvoice['Amount'], false,
                $firstCInvoice['CurrencyISO']));
            $excelData['AmountText'] = Misc::num2str($firstCInvoice['Amount'], $firstCInvoice['CurrencyISO']);

            if ($firstCInvoice['CurrencyISO'] !== 'RUB') {
                $excelData['RateText'] = 'Оплата по курсу ЦБ РФ на день оплаты';
                if ($firstCInvoice['RatePercent'] !== '' && $firstCInvoice['RatePercent'] !== '0%') {
                    $excelData['RateText'] .= ' +' . $firstCInvoice['RatePercent'];
                }
            } else {
                $excelData['RateText'] = '';
            }
        }

        list($insuranceCurrency, $insuranceAmount) = FieldMoneyWithCurrency::getAsTouple($shipmentObj['InsuranceAmount']);
        if ($insuranceAmount) {
            $excelData['InsuranceFlag'] = 'Да';
            $excelData['InsuranceText'] = 'Объявленная ценность груза для перевозки/для страховки:';
            $excelData['InsuranceAmount'] = $insuranceAmount;
            $excelData['InsuranceCurrencyISO'] = $insuranceCurrency;
        } else {
            $excelData['InsuranceFlag'] = 'Нет';
            $excelData['InsuranceText'] = '';
            $excelData['InsuranceAmount'] = '';
            $excelData['InsuranceCurrencyISO'] = '';
        }

        $xls = new XlsWriter(__DIR__ . "/$exceltemplate");
        $xls->replaceData($excelData);

        // Затем отправим пользотвателю
        try {
            $xls->save($filepathAbs);
            //Session::flashSuccess('Файл <strong>' . htmlspecialchars($filename) . '</strong> сохранён в папку перевозки');
            // Отправим готовый файл на экран пользователю
            $filepathRelative = $this->getFolderRelativePath() . "/$filename";
            FilesFolder::streamFile($filepathRelative);
        } catch (\Exception $e) {
            Session::flashError('Не удалось сохранить файл <strong>' . htmlspecialchars($filename) . '</strong> сохранён в папку перевозки, возможно, он открыт в Excel?');
            $this->redirectToView();
        }

        //обязательно делаем terminate, чтобы движок не пытался всё это засунуть в Master.tpl.php
        ApplicationAbstract::terminate();
    }

    protected function viewGetDownloadDocs()
    {
        ShipmentDownloadDocs::download([$this->_obj->getId()], false);
    }

    private static function getAddressForShipmentRequest(string $dbtable, int $id, string $fieldKeyPrefix)
    {
        return Db::getRow(
            "SELECT Name AS {$fieldKeyPrefix}Name
                , ShipmentRequestZIP as {$fieldKeyPrefix}ZIP
                , ShipmentRequestStreet as {$fieldKeyPrefix}Street
                , ShipmentRequestCity as {$fieldKeyPrefix}City
                , ShipmentRequestCountry as {$fieldKeyPrefix}Country
                , ShipmentRequestPhone as {$fieldKeyPrefix}Phone
              FROM $dbtable
              WHERE ID = $id
            "
        );
    }

    private static function getDateInText(string $mysqlDate)
    {
        list($year, $month, $day) = explode('-', $mysqlDate);
        return (int)$day . ' ' . Misc::getRusMonthName($month, true) . ' ' . $year . ' г.';
    }

    protected function viewPostCs()
    {
        $this->viewGetCs();
    }

    /**
     * Этот метод запускается, если в GET запросе есть парамеры id=...&func=cs - срабатывает метод движка ModuleAbstract::proceedViewFunc()
     * т.е. загружается сам объект перевозки в $this->_obj и управление передаётся внутрь viewFuncCs()
     * Здесь полностью делегируем дальнейший разбор url параметров в класс CostSheetRouter - он уже определит, что нужно
     * делать с текущим кост-шитом (отрендрить, сохранить или обновить через ajax)
     */
    protected function viewGetCs()
    {
        /** @var ShipmentObj $shipmentObj */
        $shipmentObj = $this->_obj;
        $content = CostSheetRouter::proceed($shipmentObj);

        //Если CostSheet роутер вернул некий контент - значит это был режим View - отображение всей страницы
        //Обернём вывод этого HTML контента в стандартный шаблон для ModuleAbstract
        if ($content) {

            $shipmentId = $this->_obj->getId();

            $this->setPageHeadForObj('Cost-sheet');
            $this->addModuleSessionNextPrevLinks($shipmentId, 'id', ['func' => 'cs']);
            $this->initCsSubmenuItems();

            $tdata = [
                'content' => $content,
                'tabsmenuItems' => $this->getViewEditLogTabsmenuItems(),
                'submenuItems' => $this->getSubmenuItemsTplData(),
                'id' => $shipmentId
            ];

            Template::includeTpl('module/view.tpl.php', $tdata);
        }
    }

    private function initCsSubmenuItems()
    {
        //Если у пользователя нет специальных прав на редактирование перевозки после закрытия и она закрыта - не будем добавлять кнопки
        /** @var ShipmentObj $shipmentObj */
        $shipmentObj = $this->_obj;
        if (!CostSheetRouter::hasAccessWriteCostSheet($shipmentObj)) {
            return;
        }

        $this->_submenuItemsTplData[] = [
            'html' => '<a href="#" class="cs-edit-btn">'
                . Img::iconEdit() . ' Edit Cost-sheet</a>'
        ];

        $this->_submenuItemsTplData[] = [
            'html' => '<a href="#" class="cs-cinvoice-btn">'
                . Img::iconUpload() . ' Create Customer Invoice</a>'
        ];

        $this->_submenuItemsTplData[] = [
            'html' => '<a href="#" class="cs-vinvoice-btn">'
                . Img::iconDownload() . ' Register Vendor Invoice</a>'
        ];

        $this->_submenuItemsTplData[] = [
            'html' => '<a href="#" id="cs-copy-btn" class="display-none">'
                . Img::iconCopy() . ' Copy</a>'
        ];

        $this->_submenuItemsTplData[] = [
            'html' => '<a href="#" id="cs-fillquotation-btn" class="display-none">'
                . Img::iconSearch() . ' Quotation / Standart Rate</a>' //
        ];
    }

    public static function proceedCopyFromCs()
    {
        CostSheetRouter::proceedAjaxCopyFromCs();
    }

    public function viewGetFillFromQuotation()
    {
        // Загрузим данные по переданной котировке
        if ($_GET['QuotationID'] < 700000) {
            $newCsLines = $this->getCsLinesFromQuotation(
                $_GET['QuotationID'],
                $this->_obj['GrossWeight'],
                $this->_obj['ChargeableWeight'],
                $this->_obj['Pieces'],
                $this->_obj->getId()
            );
        } else {
            $newCsLines = $this->getCsLinesFromStandartRate(
                $_GET['QuotationID'],
                $this->_obj['ChargeableWeight'],
                $this->_obj->getId()
            );
        }

        if (!$newCsLines) {
            // Возвращаем пустой массив, тогда на стороне JS будет понятно, что котировка не найдена
            Response::abort(404, 'Quotation ' . $_GET['QuotationID'] . ' not found');
        }

        Response::json($newCsLines);

    }

    protected function getAccessSqlWhere(): string
    {
        return BuAccess::getSqlWhereBu() . " AND " . CustomerAccess::getSqlWhere();
    }

}
