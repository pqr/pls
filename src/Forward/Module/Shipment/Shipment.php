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
use Forward\Module\Customer\Customer;
use Forward\Module\DocsInventory\DocsInventory;
use Forward\Module\Shipment\CostSheet\CostSheetObj;
use Forward\Module\Shipment\CostSheet\CostSheetRouter;
use Forward\Module\Shipment\CostSheet\Subtables\Base\IncomeExpensesObjAbstract;
use Forward\Module\Shipment\CostSheet\Subtables\ExpensesObj;
use Forward\Module\Shipment\CostSheet\Subtables\IncomeObj;
use Forward\Module\Shipment\PrintForms\MAWBExcel;
use Forward\Module\Shipment\Subtables\Shippers;
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
use Light\Ico;
use Light\InvStatus;
use Light\Link;
use Light\Mailer;
use Light\ModuleBase\ModuleAbstract;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;
use Light\Session;
use Light\Template;
use Light\Translate;
use Light\User;
use Light\XlsWriter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;

class Shipment extends ModuleAbstract
{
    private static $_responsibleRole = [
        'ResponsibleSales' => 'Sales',
        'ResponsibleCS' => 'Customer Service',
    ];

    public $folders = true;
    protected $textSaveBtn = 'Shipment';
    public $tableLayoutDefault =
        'Name|FileClosed|Month|MAWBNumber|DepartureIATA|ArrivalIATA|CustomerID|ETD|ETA|ATA';

    public $tableLayouts = [
        'FBL' => 'CompanyID|MAWBNumber|Pieces|ChargeableWeight|Volume|NatureOfGoods|DepartureIATA|ArrivalIATA|Comments',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
        $this->allowDelete = User::hasSpecialAccess(Application::SA_SHIPMENT_DELETE);
    }

    #[\Override]
    protected function initPrintForms()
    {
        $this->addPrintForm(new MAWBExcel());
    }


    #[\Override]
    protected function initSubtables()
    {
        $this->addSubtable(Shippers::class);
    }

    #[\Override]
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
            if (str_contains($value, '|')) {
                [$invoice, $contragent, $status] = explode('|', $value, 3);
                /** @var FieldAbstract $field */
                if ($field->key === 'CustomerInvoices') {
                    $statusIco = Finance::getCInvoiceStatusIcon($status);
                } else {
                    $statusIco = Finance::getVInvoiceStatusIcon($status);
                }
                $outHtml = $statusIco.' '.htmlspecialchars($invoice).' <small class="gray">('.htmlspecialchars(
                        $contragent,
                    ).')</small>';
            }

            return $outHtml;
        };

        $getExcelFunctionInvoices = function ($value, array $data, $out) {
            if (str_contains($value, '|')) {
                [$invoice, $contragent] = explode('|', $value, 2);
                $out = $invoice.' ('.$contragent.')';
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
                'getExcelFunction' => $getExcelFunctionInvoices,
            ]),
            F::createMoney('CustomerInvoicesAmounts', [
                'name' => 'Customer Invoices Amounts',
                'groupconcat' => true,
                'zeroEmpty' => true,
                'sqlSelect' => $getSqlSelectInvoicesAmounts('C'),
            ]),
            F::createString('VendorInvoices', [
                'name' => 'Vendor Invoices',
                'tableWidthExcel' => 20,
                'groupconcat' => true,
                'sqlSelect' => $getSqlSelectInvoices('V'),
                'getHtmlFunction' => $getHtmlFunctionInvoices,
                'getExcelFunction' => $getExcelFunctionInvoices,
            ]),
            F::createMoney('VendorInvoicesAmounts', [
                'name' => 'Vendor Invoices Amounts',
                'groupconcat' => true,
                'zeroEmpty' => true,
                'sqlSelect' => $getSqlSelectInvoicesAmounts('V'),
            ]),

            $this->getListVatMountField(12),
            $this->getListVatMountField(18),
        );

        $this->addFields(
            $this->fieldFolderFilesList(),
        );
    }

    #[\Override]
    protected function initFieldsFromSubtables()
    {
        parent::initFieldsFromSubtables();

        // К полю Shipper добавим параметр, который будет обрезать длинные названия
        $this->getField('CacheShippersName')->setViewMaxWidth(300);
    }

    private function getListVatMountField($vatPercent)
    {
        return F::createMoney("VAT{$vatPercent}Amount", [
            'name' => "Expenses VAT $vatPercent%",
            'title' => "Сумма приходящаяся на НДС $vatPercent% во входящих счетах Vendor Invoices (на основе столбца 'VAT Amount' в строках счёта + сумма из строки в счёте с текстом услуги 'НДС $vatPercent%')",
            'sqlSelect' => "IFNULL((SELECT sum(VATAmount) FROM vinvoiceline WHERE VATPercent = $vatPercent AND FileNumber = shipment.Name), 0) + IFNULL((SELECT sum(FinalAmount) FROM vinvoiceline WHERE FileNumber = shipment.Name AND (Name LIKE 'НДС $vatPercent%' OR Name LIKE 'VAT $vatPercent%')), 0)",
            'zeroEmpty' => true,
            'zeroEmptyExcel' => true,
        ]);
    }

    #[\Override]
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
                'IncotermsLocation',
            ],
            null,
        );
    }

    #[\Override]
    protected function initCssJs()
    {
        parent::initCssJs();
        if ($this->isStateEdit() || $this->isStateAddNew()) {
            CompanyChild::initJsCompanyChildFromCustomer();
        }
    }

    #[\Override]
    protected function initMassactions()
    {
        $this->addMassactionMassUpdate();
        $this->addMassaction('DownloadDocs', 'Собрать комплект документов', '', '', true);
        $this->addMassaction('CreateDocsInventory', 'Создать опись переданных док-ов');
    }

    protected function massactionDownloadDocs($ids)
    {
        $msgBuff = new Msg();
        $sd = ShipmentDownloadDocs::create($msgBuff);
        $resultFilepath = $sd->generateZip($ids, false);
        if (!$resultFilepath) {
            print $msgBuff->getHtml();
            Response::disableMainmenu();

            return;
        }

        Response::streamFileFromFilesystem($resultFilepath);
    }

    protected function massactionCreateDocsInventory($ids)
    {
        Response::redirect(DocsInventory::class, ['addnew' => 1, 'fromShipmentIds' => implode(',', $ids)]);
    }

    #[\Override]
    protected function initViewSubmenu()
    {
        $subject = ApplicationAbstract::getParam('EmailNoticeSubject');
        $body = ApplicationAbstract::getParam('EmailNoticeBody');

        //Пройдём по всем полям объекта и попробуем сделать подстановку в subject и body
        foreach ($this->_obj->getFieldset() as $field) {
            if (isset($this->_obj[$field->key])) {
                $value = $field->getExcel($this->_obj[$field->key], $this->_obj->getData());
                $subject = str_replace('{'.$field->key.'}', $value, (string) $subject);
                $body = str_replace('{'.$field->key.'}', $value, (string) $body);
            }
        }

        //$link = 'http://connect.track-trace.com/for/pls/aircargo/' . $this->_obj['MAWBNumber'];
        $link = 'http://quicktracing.com/api/air.php?token=tVRtbXgXHxE71UJTTkbPOE0qZrj6sMYF&awb='.rawurlencode(
                (string) $this->_obj['MAWBNumber'],
            );
        $subject = str_replace('{TrackTraceLink}', $link, (string) $subject);
        $body = str_replace('{TrackTraceLink}', $link, (string) $body);

        //Загрузим email и имя контактной персоны клиента, на который отправлять уведомление
        $customerInfo = Db::getRow(
            "SELECT ContactPersonName, NoticeEmail FROM customer WHERE ID = ".$this->_obj['CustomerID'],
        );
        if ($customerInfo) {
            $contactPerson = $customerInfo['ContactPersonName'];
            $email = $customerInfo['NoticeEmail'];
        } else {
            $contactPerson = '';
            $email = '';
        }

        $body = str_replace('{ContactPersonName}', $contactPerson, $body);

        $href = 'mailto:'.$email.'?subject='.rawurlencode($subject).'&body='.rawurlencode($body);
        if (strlen($href) > 2040) {
            $href = substr($href, 0, 2040);
        }

        $this->addSubmenuLink(
            Link::create(null, Ico::mail().' Email Notice')
                ->externalUrl($href)
                ->cssClass('emailnotice-link'),
        );

        if (Constant::isOneCCompany($this->_obj['CompanyID'])) {
            $this->addSubmenuLinkViewFunc(Ico::download().' Заявка', 'ShipmentRequestExcel')->post();
            $this->addSubmenuLinkViewFunc(Ico::i('file-archive-o').' Комплект документов', 'DownloadDocs');
            $this->addSubmenuLinkViewFunc(Ico::i('file-archive-o').' Документы на печать', 'DownloadDocsForPrint');
        }
    }


    #[\Override]
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
                    'sqlWhere' => fn ($value) => Db::getSqlWhereSeparatedByComa($value, 'shipment.Name', -1, false, true, 1),
                ],
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
                            $options[$roleField.'_0'] = ['text' => '', 'optgroup' => $roleDescription];

                            $users = Db::getAssoc(
                                "SELECT DISTINCT(shipment.".$roleField.
                                "), user.Name FROM shipment, user WHERE user.ID = shipment.".$roleField.
                                " ORDER BY user.Name",
                            );
                            foreach ($users as $id => $name) {
                                $options[$roleField.'_'.$id] = [
                                    'text' => $name,
                                    'optgroup' => $roleDescription,
                                ];
                            }
                        }

                        return $options;
                    },
                    'sqlWhere' => function ($value) use ($responsibleRole) {
                        //Фильтр по Responsible состоит из двух частей разделённых знаком подчёркивания: Имя поля в базе () и значение поля
                        $responsibleFilterValue = explode('_', $value);
                        if (isset($responsibleRole[$responsibleFilterValue[0]])) {
                            return "`".Db::esc($responsibleFilterValue[0])."` = ".
                                (int) $responsibleFilterValue[1];
                        }

                        return '';
                    },
                ],
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
                                if ($filtersValues['Criterion'.$crId]) {
                                    $checkboxFound = true;
                                    $query .=
                                        " AND EXISTS (SELECT ShipmentID FROM shipmentcriterion WHERE ShipmentID = shipment.ID AND CriterionID = ".
                                        $crId.")";
                                }

                                if (!$checkboxFound) {
                                    $query .= " AND 0";
                                }
                            }
                        } elseif ($value === 'LogicOR') {
                            $crIds = [];
                            foreach (ShipmentCriteria::getSelectOptions() as $crId => $_) {
                                if ($filtersValues['Criterion'.$crId]) {
                                    $crIds[] = $crId;
                                }
                            }

                            if ($crIds) {
                                $query .=
                                    " AND EXISTS (SELECT ShipmentID FROM shipmentcriterion WHERE ShipmentID = shipment.ID AND CriterionID IN (".
                                    implode(', ', $crIds)."))";
                            } else {
                                $query .= " AND 0";
                            }
                        } else {
                            $query .=
                                " AND EXISTS (SELECT ShipmentID FROM shipmentcriterion WHERE ShipmentID = shipment.ID AND CriterionID = ".
                                (int) $value.")";
                        }

                        return $query;
                    },
                ],
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
                },
            ]),
            F::createMonth('MonthUntil', [
                'name' => 'Month Until',
                'yearFrom' => $monthYearFrom,
                'yearUntil' => $monthYearUntil,
                'sqlWhere' => function ($value, $filtersValues, $sqlWhere) {
                    $sqlWhere = str_replace('{fieldKey}', 'Month', $sqlWhere);

                    return str_replace('=', '<=', $sqlWhere);
                },
            ]),
            CF::color(),

            ShipmentFields::flightNo()->setPlaceholder('No'),

            ShipmentFields::flightDate()->setPlaceholder('Date'),

            F::createSelect('HasVAT', [
                'name' => 'VAT (НДС)',
                'options' => [1 => 'Есть НДС'],
                'sqlWhere' => "EXISTS(SELECT * FROM vinvoiceline WHERE FileNumber = shipment.Name AND (VATAmount !=0 OR Name = 'НДС'))",
            ]),

        );

        $this->addFiltersDatesFromUntil('ETD', ['name' => 'ETD']);
        $this->addFiltersDatesFromUntil('ETA', ['name' => 'ETA']);
        $this->addFiltersDatesFromUntil('ATA', ['name' => 'ATA']);
        $this->addFiltersDatesFromUntil('ReadyForCarriageDate', ['name' => 'Ready Carr.']);
        $this->addFiltersDatesFromUntil(ShipmentFields::docsPrintedDate());


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
                'CriterionID',
            ],
            [
                'Color' => fn () => '<div class="color-container color'.Template::value('Color').'">'
                    .Template::html('Color')
                    .'</div>',
                'BUIDsFilter',
                'FileClosed',

                'MonthFrom' => fn () => Template::html('MonthFrom').' - '.Template::html('MonthUntil'),
                'ReadyForCarriageDate',
                'ETD',
                'ETA',
                'ATA',
                'DocsPrintedDate',
                'FlightNo' => fn () => Template::html('FlightNo').' '.Template::html('FlightDate'),
                'HasVAT',
            ],
        ];
    }

    #[\Override]
    protected function getDataFromPost()
    {
        $dataFromRequest = parent::getDataFromPost();

        //Чтобы правильно записать поле MAWB Number, нужно определить traffic.Type
        if (isset($dataFromRequest['TrafficID'])) {
            $trafficType = Db::getOne("SELECT Type FROM traffic WHERE ID=".(int) $dataFromRequest['TrafficID']);

            if ($trafficType === 'avia') {
                if (isset($_POST['MAWBNumber1'], $_POST['MAWBNumber2'])) {
                    $dataFromRequest['MAWBNumber'] = str_replace(
                        ' ',
                        '',
                        trim((string) $_POST['MAWBNumber1']).'-'.trim((string) $_POST['MAWBNumber2']),
                    );
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
                    $dataFromRequest['Month'] = substr((string) $this->_obj['CreatedDateTime'], 0, 7).'-01';
                }
            } else {
                //Для других трафиков в качестве месяца используется ETD
                if ($dataFromRequest['ETD'] !== '') {
                    $dataFromRequest['Month'] = substr((string) $dataFromRequest['ETD'], 0, 7).'-01';
                }
            }
        }

        return $dataFromRequest;
    }

    #[\Override]
    protected function validateUnique(array $dataFromRequest)
    {
        if ($dataFromRequest['MAWBNumber'] != '') {
            //Проверим, что номер MAWB не повторялся за последний год
            //Проверим существование такого имени в базе
            $query = "SELECT ID
                  FROM shipment
                  WHERE MAWBNumber='".Db::esc($dataFromRequest['MAWBNumber'])."'
                      AND CreatedDateTime >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            if ($this->_obj->getId()) {
                $query .= " AND ID != ".$this->_obj->getId();
            }

            if ($id = Db::getOne($query)) {
                $this->riseSaveErorr(
                    'MAWB '.htmlspecialchars((string) $dataFromRequest['MAWBNumber']).' already exists, '.
                    Link::create(Shipment::class, 'view...')
                        ->view($id)
                        ->blank()
                        ->getHtml(),
                    'MAWBNumber',
                );
            }
        }
    }

    #[\Override]
    protected function validateMandatory(array $dataFromRequest)
    {
        if (isset($dataFromRequest['TrafficID']) && $dataFromRequest['TrafficID'] == Constant::TRAFFIC_FINANCE) {
            return;
        }

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
                'NatureOfGoods',
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
                && !Db::getOne("SELECT ID FROM cinvoiceline WHERE FileNumber = ".$this->_obj['Name']." LIMIT 1")
                && !Db::getOne("SELECT ID FROM vinvoiceline WHERE FileNumber = ".$this->_obj['Name']." LIMIT 1")
            ) {
                $this->riseSaveErorr(
                    'At least one Customer Invoice and one Vendor Invoices should be assigned to the shipment',
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
        $customerId = (int) $dataFromRequest['CustomerID'];
        if ($customerId) {
            //Загрузим поле Our Company из клиента
            $customerCompanyId = Db::getOne("SELECT CompanyID FROM customer WHERE ID = @i", $customerId);
            if (!$customerCompanyId) {
                $msg = 'В целях наполнения базы невозможно сохранить перевозку, пока в карточке клиента не заполнено поле Our Company.';
                $msg .= '<br>'.Link::create(Customer::class, 'Отредактировать карточку клиента')
                        ->view($customerId)
                        ->blank()
                        ->getHtml();
                $this->riseSaveErorr($msg);
            }
        }

        // Если пользователь вручную заполнил поле ShipmentRequestNo, то проверим, что это значение не выходит далеко впедёр от максимального
        if (isset($dataFromRequest['ShipmentRequestNo'], $dataFromRequest['CompanyID'], $dataFromRequest['ReadyForCarriageDate'])
            && $dataFromRequest['CustomerAccountID']
            && Constant::isOneCCompany($dataFromRequest['CompanyID'])
        ) {
            // 2024-05-04 По дате готовности груза
            $shipmentRequestDate = self::getShipmentRequestDate($dataFromRequest);
            $shipmentRequestYearShort = substr($shipmentRequestDate, 2, 2);
            $maxShipmentRequestNo = $this->getMaxShipmentRequestNo(
                $dataFromRequest['CompanyID'],
                $dataFromRequest['CustomerAccountID'],
                $shipmentRequestDate,
            );

            if ($maxShipmentRequestNo && $dataFromRequest['ShipmentRequestNo'] - $maxShipmentRequestNo > 1) {
                $msg = 'Номер заявки <strong class="bad">'
                    .$dataFromRequest['ShipmentRequestNo'].'-'.$shipmentRequestYearShort
                    .'</strong> выглядит слишком большим,'
                    .' последний использовавшийся в этом году номер: <strong>'
                    .$maxShipmentRequestNo.'-'.$shipmentRequestYearShort
                    .'</strong>,'
                    .' проверьте правильность заполнения';

                $this->riseSaveWarning($msg);
            }
        }
    }

    #[\Override]
    protected function getTplData()
    {
        $tdata = parent::getTplData();

        //Добавим переменную TrafficType, чтобы скрыть или отобразить блок Ocean
        $tdata['TrafficType'] = Db::getOne("SELECT Type FROM traffic WHERE ID = ".(int) $this->_obj['TrafficID']);
        if (!$this->isStateView()) {
            //Разделим поле MAWB на два поля для ввода
            $mawb1 = '';
            $mawb2 = '';

            if (isset($this->_obj['MAWBNumber'])) {
                $parts = explode('-', (string) $this->_obj['MAWBNumber']);
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
                    'editMaxLength' => 3,
                ],
            )->getHtmlInput($mawb1);

            $tdata['MAWBNumber2'] = F::create(
                'MAWBNumber2',
                F::TYPE_STRING,
                [
                    'editCssClass' => 'input-int',
                    'editMaxLength' => 8,
                ],
            )->getHtmlInput($mawb2);
        }

        //Для сообщения "Attention: prepaid customer"
        $tdata['isPrepaidCustomer'] = false;
        if (isset($this->_obj['CustomerID']) && $this->_obj['CustomerID']) {
            $creditAmount = Db::getOne(
                "SELECT CreditAmount FROM customer WHERE ID = ".(int) $this->_obj['CustomerID'],
            );
            if ($creditAmount == 0) {
                $tdata['isPrepaidCustomer'] = true;
            }
        }

        $tdata['ShipmentRequestDate'] = self::getShipmentRequestDate($this->_obj->getData());

        //Criteria Block
        if ($this->_obj->getId()) {
            $crArr = ShipmentCriteria::getShipmentCriteriaArray($this->_obj->getId());
            if ($crArr) {
                $crBlock = '<ul>';
                foreach ($crArr as $crInfo) {
                    $crBlock .= '<li title="'.htmlspecialchars((string) $crInfo['Description']).'"'.
                        (isset($crInfo['cssClass']) ? ' class="'.$crInfo['cssClass'].'"' : '').'>'.
                        htmlspecialchars((string) $crInfo['text']).'</li>';
                }
                $crBlock .= '</ul>';
                $tdata['Criteria'] = $crBlock;
            }
        }

        return $tdata;
    }

    #[\Override]
    protected function initAddNewJsData()
    {
        parent::initAddNewJsData();
        $this->addAddNewEditPageParams();
    }

    #[\Override]
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
                      IFNULL((SELECT UserID FROM customer_responsible WHERE CustomerID = c.ID AND Role = '".Application::ROLE_SALES."' ORDER BY ID LIMIT 1), '') AS s,
                      IFNULL((SELECT UserID FROM customer_responsible WHERE CustomerID = c.ID AND Role = '".Application::ROLE_CS."' ORDER BY ID LIMIT 1), '') AS c
                  FROM customer c
                  ";
        $customersInfo = Db::getAssoc($query);

        Response::addJsData([
            'customersInfo' => $customersInfo,
            'trafficTypes' => Db::getAssoc("SELECT ID, Type FROM traffic"),
            'airlineCodes' => Db::getAssoc(
                "SELECT DigitalCode, AlphaCode FROM airline WHERE DigitalCode != 0 AND AlphaCode != ''",
            ),
        ]);
    }

    #[\Override]
    protected function initListJsData()
    {
        parent::initListJsData();

        $criteriaArr = ShipmentCriteria::getSelectOptions();
        Response::addJsData(['criteriaArr' => $criteriaArr]);
    }

    #[\Override]
    protected function getViewEditLogTabsmenuItems()
    {
        $menuItems = parent::getViewEditLogTabsmenuItems();
        $menuItems[] = [
            'text' => 'Cost-sheets',
            'url' => $this->_obj->getLinkView()->func('cs')->params($this->getModuleSessionUrlParams())->getHref(),
            'active' => isset($_GET['func']) && $_GET['func'] === 'cs',
        ];

        return $menuItems;
    }

    /**
     * Переопределяем стандартную функцию, чтобы не добавлять ID в конец
     *
     * @param string $stateText
     */
    protected function setPageHeadForObj($stateText = '')
    {
        $headText = 'Shipments - ';
        if ($stateText) {
            $headText .= $stateText.' ';
        }
        $headText .= '<strong>'.$this->_obj->getDescriptionHtml().'</strong>';
        Response::setPageHeadHtml($headText);
        Response::setPageTitle($headText);
    }

    /**
     * Возвращает массив данных из другого Cost-sheet'а для копирования
     *
     * @return array
     */
    public static function proceedLoadCs()
    {
        //Узнаем ShipmentID по переданному номеру файла
        $out = [];
        $sid = Db::getOne("SELECT ID FROM shipment WHERE Name = '".Db::esc($_GET['filenumber'])."'");
        if ($sid) {
            //Перевозка найдена, загрузим все строки из её Cost-sheet'а
            $out['income'] = Db::getAll(
                "SELECT Name, Amount, CurrencyISO FROM csincome WHERE ShipmentID = ".$sid,
            );

            $out['expenses'] = Db::getAll(
                "SELECT Name, Amount, CurrencyISO FROM csexpenses WHERE ShipmentID = ".$sid,
            );
        }

        return $out;
    }

    #[\Override]
    protected function hasWriteAccessRightToObj(ObjAbstract $obj)
    {
        // Узнаем статус перевозки, и, если статус Closed, прверим что у пользователя есть права на редактирование
        return $obj['FileClosed'] != ShipmentObj::SHIPMENT_CLOSED
            || User::hasSpecialAccess(Application::SA_SHIPMENT_CHANGECLOSED);
    }

    #[\Override]
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
                      WHERE ID = ".(int) $_GET['qid'];
            $quotationInfo = Db::getRow($query);
            if ($quotationInfo) {
                $this->_obj->mergeData($quotationInfo);
                $this->_obj['QuotationID'] = (int) $_GET['qid'];

                //Дополним поле Pieces и Volume
                $query = "SELECT sum(Pieces) AS Pieces
                              , sum(Volume) AS Volume
                          FROM quotationcargo
                          WHERE QuotationID = ".(int) $_GET['qid'];
                $pv = Db::getRow($query);
                if ($pv) {
                    $this->_obj->mergeData($pv);
                }
            }
        }
    }

    #[\Override]
    protected function proceedAddNew()
    {
        $this->loadFromQuotaionRequest();

        //При добавлении нового шипмента нужно ограничить выбор месяца этим месяцем + 2 следующих
        $yearFrom = (new \DateTime())->format('Y-m');
        $yearUntil = (new \DateTime())->modify('+3 month')->format('Y-m');
        $this->getField('Month')->setParams(['yearFrom' => $yearFrom, 'yearUntil' => $yearUntil]);

        parent::proceedAddNew();
    }

    #[\Override]
    protected function afterSubmit(
        ObjAbstract $obj,
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff,
        $fromMultipleUpdate,
    ) {
        if ($this->_justInserted) {
            // Если шипмент был создан из котировки (заполнено поле QuotationID), то нужно заполнить и Cost-sheet!
            if ($obj['QuotationID'] && $obj['QuotationID'] < 700000) {
                $newCsLines = $this->getCsLinesFromQuotation(
                    $obj['QuotationID'],
                    $obj['GrossWeight'],
                    $obj['ChargeableWeight'],
                    $obj['Pieces'],
                    $obj->getId(),
                );

                if ($newCsLines) {
                    $shipmentId = $obj->getId();
                    $quotationId = $obj['QuotationID'];

                    // Вспомагательная функция, которая вставляет строки Cost-sheet (вызывается два раза: один раз для доходов и один раз для расходов)
                    $insertCsLines = function (array $csLines, IncomeExpensesObjAbstract $csLineObjBase) use (
                        $shipmentId,
                        $quotationId,
                    ) {
                        foreach ($csLines as $csLineData) {
                            $csLineObj = clone $csLineObjBase;
                            $csLineObj->setMasterIdForDbLog($shipmentId);
                            $csLineObj->insertDb($csLineData, 'from Quotation '.$quotationId);
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
        } else {
            // Обновление уже существующей перевозки
            if (isset($oldData['ResponsibleCS'], $obj['ResponsibleCS']) && $oldData['ResponsibleCS'] != $obj['ResponsibleCS']) {
                $additionalText = 'New Customer Service <strong>'
                    .htmlspecialchars((string) Db::getOne("SELECT Name FROM user WHERE ID = @i", $obj['ResponsibleCS']))
                    .'</strong>';
                /** @var ShipmentObj $obj */
                $this->sendShipmentEmail($obj, $additionalText, $msgBuff);
            }
        }


        if (!$fromMultipleUpdate) {
            // Заготовим стандартные папки
            $folderRelative = $this->getFolderRelative();
            foreach (Constant::$standardFolders as $standardFolder) {
                FilesFolder::mkdir("$folderRelative/$standardFolder");
            }
        }

        $this->emailOnAtaChange($obj, $oldData, $msgBuff);

        /** @var ShipmentObj $obj */
        $obj->updateCacheAfterSubmit($oldData, $msgBuff);
    }

    /**
     * Автоматическое E-mail уведомление из базы на адрес бухгалтерии по тем перевозкам,
     * где просталена дата прилёта и если у клиента указана опция "Акт на дату прилёта".
     */
    private function emailOnAtaChange(ObjAbstract $obj, array $oldData, Msg $msgBuff): void
    {
        $oldAta = $oldData['ATA'] ?? '0000-00-00';
        $newAta = $obj['ATA'];

        if ($newAta !== '0000-00-00' && $newAta != $oldAta) {
            // Проверим настройки Customer Account
            $actOnDateType = Db::getOne(
                "SELECT ActOnDateType FROM customeraccount WHERE ID = @i",
                $obj['CustomerAccountID'],
            );
            if ($actOnDateType == Constant::ACTONDATETYPE_ATA) {
                $body = 'Изменилась дата ATA '.FieldDate::beautify($newAta).' по накладной '.$obj->getLinkView().' ('.$obj->getFieldText('CustomerAccountID').')';
                $subject = strip_tags($body);
                Mailer::create('accounting@proactive-ls.com', $subject, $body)
                    ->wrapBodyWithHtmlTemplate()
                    ->makeLinksAbsolute()
                    ->send($msgBuff);
            }
        }
    }

    private function sendShipmentEmail(ShipmentObj $obj, $additionalText, Msg $msgBuff): void
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
            .' Shipment '.$obj['Name']
            .' for '.$obj->getFieldText('CustomerID')
            .' '.$obj->getFieldText('DepartureIATA')
            .'->'.$obj->getFieldText('ArrivalIATA');

        $body = $newOrUpdate
            .' Shipment <a href="'.Config::$localUrl.'/?a=Shipment&id='.$obj->getId().'"><strong>'.$obj['Name'].'</strong></a>'
            .' for '.$obj->getFieldText('CustomerID')
            .' '.$obj->getFieldText('DepartureIATA')
            .'➔'.$obj->getFieldText('ArrivalIATA')
            .'<br><br>';

        if ($additionalText) {
            $body .= $additionalText.'<br><br>';
        }

        $body .= '<small>';
        $body .= $this->_justInserted ? 'Created' : 'Updated';
        $body .= ' by '.htmlspecialchars((string) User::getName()).'</small>';

        Mailer::create($to, $subject, $body)
            ->send($msgBuff);
    }

    /**
     * Метод используется для получения массива строк для вставки в Cost-sheet на основе номера котировки.
     * Актуально, когда создаётся новая перевозка на основе котировки - в этом случае в afterInsert() нужно заполнить
     * CostSheet.
     * Также, если перевозка уже создана то в Cost-sheet можно нажать "Fill from Quotation" - этот метод загружает
     * данные из котировки и они возвращаются пользователю в ajax ответе для заполнения Cost-sheet в режиме Edit
     *
     * @param int $quotationId
     * @param int $grossWeight
     * @param int $chargeableWeight
     * @param int $pieces
     * @param int $shipmentId
     *
     * @return array
     */
    private function getCsLinesFromQuotation($quotationId, $grossWeight, $chargeableWeight, $pieces, $shipmentId): array
    {
        //Загрузим валюту котировки
        //Заодно проверим, существует ли такая котировка вообще?
        $quotationCurrencyIso = Db::getOne(
            "SELECT CurrencyISO FROM quotation WHERE ID = @i",
            $quotationId,
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
                $quotationId,
            );

            foreach ($quotationLines as $quotationLine) {
                //Подготовим массив данных для вставки в строку Cost-sheet'а
                //Для начала нужно расчитать Amount, который может зависеть от веса или числа мест
                $multiplier = match ($quotationLine['Per']) {
                    'GW' => $grossWeight,
                    'CW' => $chargeableWeight,
                    'Piece' => $pieces,
                    default => 1,
                };

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
                    'Amount' => $quotationCurrencyIso.' '.$amount,
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
     *
     * @param int   $srId
     * @param float $chargeableWeight
     * @param int   $shipmentId
     *
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
        $amountFieldKeySuffix = $interval ? 'Amount'.$interval : 'AmountN';

        // Подготовим выходной массив
        $outCsLines = ['income' => [], 'expenses' => []];

        foreach (['income' => 'Selling', 'expenses' => 'Buying'] as $incomeOrExpenses => $sellingOrBuying) {
            $amountFieldKey = $sellingOrBuying.$amountFieldKeySuffix;
            $price = $rate[$amountFieldKey];
            $quantity = $chargeableWeight;
            $amount = $price * $quantity;

            //Если итоговая сумма меньше меньшего - нужно использовать минимальную сумму
            if ($amount < $rate[$sellingOrBuying.'MinAmount']) {
                $amount = $rate[$sellingOrBuying.'MinAmount'];
                $price = $amount;
                $quantity = 1;
            }

            $outCsLines[$incomeOrExpenses][] = [
                'Name' => 'Airfreight',
                'ShipmentID' => $shipmentId,
                'Quantity' => $quantity,
                'Price' => $price,
                'Amount' => $rate[$sellingOrBuying.'CurrencyISO'].' '.$amount,
            ];

            //Добавим FSC и SSC
            foreach (['FSC', 'SSC'] as $fscOrSsc) {
                $price = $rate[$sellingOrBuying.$fscOrSsc.'Amount'];
                if ($price) {
                    $outCsLines[$incomeOrExpenses][] = [
                        'Name' => $fscOrSsc,
                        'ShipmentID' => $shipmentId,
                        'Quantity' => $chargeableWeight,
                        'Price' => $price,
                        'Amount' => $rate[$sellingOrBuying.'CurrencyISO'].' '.($price * $chargeableWeight),
                    ];
                }
            }

            //Теперь добавим все надбавки
            $surchargesTableName = "customer_standratrates_".strtolower($sellingOrBuying)."surcharges";
            $surcharges = Db::getAll(
                "SELECT *
                 FROM $surchargesTableName
                 WHERE CustomerStandartRateID = {$rate['ID']}",
            );

            foreach ($surcharges as $surcharge) {
                $base = (double) $surcharge['BaseAmount'];
                $price = (double) $surcharge['PerKgAmount'];
                $quantity = $chargeableWeight;
                $amount = $base + $price * $quantity;

                $minAmount = (double) $surcharge['MinAmount'];
                $maxAmount = (double) $surcharge['MaxAmount'];

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
                    'Amount' => $surcharge['CurrencyISO'].' '.$amount,
                ];
            }
        }

        return $outCsLines;
    }

    /**
     * Переопределяем стандартный метод, чтобы запретить удаление файлов к которым привязаны счета
     */
    #[\Override]
    protected function sendDeleteConfirmDialog()
    {
        //Найдём число привязанных счетов
        $cinvCount = (int) Db::getOne(
            "SELECT count(*) 
              FROM cinvoice_file_amount cfa, cinvoice ci 
              WHERE cfa.FileNumber = @i
                  AND cfa.CInvoiceID = ci.ID
                  AND ci.Status NOT IN (".InvStatus::getDeletedStatusesSql().")",
            $this->_obj['Name'],
        );
        $vinvCount = (int) Db::getOne(
            "SELECT count(*)
            FROM vinvoice_file_amount vfa, vinvoice vi
            WHERE vfa.FileNumber = @i
                AND vfa.VInvoiceID = vi.ID
                AND vi.Status NOT IN (".InvStatus::getDeletedStatusesSql().")",
            $this->_obj['Name'],
        );

        if ($cinvCount || $vinvCount) {
            $msg = "Can't delete shipment because ";
            $invMsg = [];
            if ($cinvCount) {
                $invMsg[] = $cinvCount." Customer Invoices";
            }
            if ($vinvCount) {
                $invMsg[] = $vinvCount." Vendor Invoices";
            }
            $msg .= implode(' and ', $invMsg);
            $msg .= " are linked to the shipment";

            Response::showDialog($msg);
        }

        //Если же инвойсов нет, то отправим стандартный подтверждающий удаление диалог
        parent::sendDeleteConfirmDialog();
    }

    public static function getShipmentRequestDate(array $shipmentData): string
    {
        $readyForCarriageDate = $shipmentData['ReadyForCarriageDate'] ?? null;

        return FieldDate::notEmpty($readyForCarriageDate) ? $readyForCarriageDate : date('Y-m-d');
    }

    private function getMaxShipmentRequestNo(
        int $companyId,
        int $customerAccountId,
        string $shipmentRequestDate,
        bool $forUpdate = false,
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

        return (int) Db::getOne($query, $customerAccountId);
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
        } elseif ($shipmentObj['CompanyID'] == Constant::OC_PF14_OOO) {
            $exceltemplate = 'ShipmentRequestPF14.xlsx';
        } elseif ($shipmentObj['CompanyID'] == Constant::OC_PLS17_OOO) {
            $exceltemplate = 'ShipmentRequestPLS17.xlsx';
        } elseif ($shipmentObj['CompanyID'] == Constant::OC_PRO_AIR_SPB) {
            $exceltemplate = 'ShipmentRequestProAir21.xlsx';
        } elseif ($shipmentObj['CompanyID'] == Constant::OC_PGL18_OOO) {
            $exceltemplate = 'ShipmentRequestPGL18.xlsx';
        } elseif ($shipmentObj['CompanyID'] == Constant::OC_PRO_AIR_OOO) {
            $exceltemplate = 'ShipmentRequest19.xlsx';
        } else {
            Session::flashError('Для компании '.$shipmentObj->getFieldHtml('CompanyID').' нет формы заявки');
            $this->redirectToView();
        }

        ini_set('display_errors', 0);

        $shipmentRequestDate = self::getShipmentRequestDate($shipmentObj->getData());

        if (!$shipmentObj['ShipmentRequestNo'] && $shipmentObj['CustomerAccountID']) {
            // В базе нет сохранённого номера заявки - сгенерируем автоматически.
            // Выбираем максимальный номер заявки в рамках этого года
            Db::begin();
            $lastNo = $this->getMaxShipmentRequestNo(
                $shipmentObj['CompanyID'],
                $shipmentObj['CustomerAccountID'],
                $shipmentRequestDate,
                true,
            );
            $newNo = $lastNo ? $lastNo + 1 : 1;
            $shipmentObj->updateDb(['ShipmentRequestNo' => $newNo], 'Автоматическое присвоение следующего номера');
            Db::commit();
        }

        // Сформируем путь до файла назначения и проверим, что этого файла ещё нет в папке
        $shipmentRequestYearShort = substr((string) $shipmentRequestDate, 2, 2);

        $excelData = $shipmentObj->getExcelData();

        $excelData['ShipmentRequestDate'] = self::getDateInText($shipmentRequestDate);
        $excelData['ShipmentRequestYearShort'] = $shipmentRequestYearShort;
        $excelData['ETA'] = FieldDate::beautify($shipmentObj['ETA']);

        // Загрузим номер контракта из карточки Customer Account
        $contractNo = Db::getOne(
            "SELECT ContractNo FROM customeraccount WHERE ID = @i",
            $shipmentObj['CustomerAccountID'],
        );
        // Заменим в номере контракта английское слово "from" на русское "от"
        $excelData['CustomerAccountContractNo'] = str_replace('from', 'от', (string) $contractNo);

        // Добавим адрес получателя
        if ($shipmentObj['ConsigneeID'] > 0) {
            // Если был задан конкретный Consignee в выпадающем списке на форме перевозки, то подтянем информацию из него
            $excelData += self::getAddressForShipmentRequest(
                'customerconsignee',
                $shipmentObj['ConsigneeID'],
                'Consignee',
            );
        } elseif ($shipmentObj['ConsigneeID'] == -1) {
            // Если же был выбран вариант "совпадает с Customer Account", тогда загрузим информацию из таблицы Customer Account
            // (но префикс для полей с данными по прежнему будет 'Consignee'
            $excelData += self::getAddressForShipmentRequest(
                'customeraccount',
                $shipmentObj['CustomerAccountID'],
                'Consignee',
            );
        }

        // Добавим адрес плательщика (Customer Account)
        $excelData += self::getAddressForShipmentRequest(
            'customeraccount',
            $shipmentObj['CustomerAccountID'],
            'CustomerAccount',
        );

        // Добавим адрес первого отправителя
        $firstShipper = Db::getRow(
            "SELECT Name, HAWBNumber FROM shipmentshipper WHERE ShipmentID = @i",
            $shipmentObj->getId(),
        );
        if ($firstShipper) {
            $excelData['HAWBNumber'] = $firstShipper['HAWBNumber'];
            if ($firstShipper['Name']) {
                $shipperId = Db::getOne("SELECT ID FROM list_shipper WHERE Name = @s", $firstShipper['Name']);
                if ($shipperId) {
                    $excelData += self::getAddressForShipmentRequest(
                        'list_shipper',
                        $shipperId,
                        'Shipper',
                    );
                }
            }
        }

        // Найдём первый счёт выставленный на клиента по этой перевозке и подставим его сумму в заявку
        $firstCInvoice = Db::getRow(
            "SELECT cfa.Amount, ci.CurrencyISO, ci.RatePercent, ci.PaymentCurrencyISO
             FROM cinvoice_file_amount cfa, cinvoice ci
             WHERE cfa.CInvoiceID = ci.ID
                 AND cfa.FileNumber = @s
             LIMIT 1",
            $shipmentObj['Name'],
        );

        if ($firstCInvoice) {
            $excelData['AmountAndCurrency'] = strip_tags(
                (string) FieldMoney::beautify(
                    $firstCInvoice['Amount'],
                    false,
                    $firstCInvoice['CurrencyISO'],
                ),
            );
            $excelData['AmountText'] = Translate::amountWordsRu(
                $firstCInvoice['Amount'],
                $firstCInvoice['CurrencyISO'],
            );

            $excelData['RateText'] = '';

            if ($firstCInvoice['CurrencyISO'] !== 'RUB' && $firstCInvoice['PaymentCurrencyISO'] === 'RUB') {
                $excelData['RateText'] = 'Оплата по курсу ЦБ РФ на день оплаты';
                if ($firstCInvoice['RatePercent'] !== '' && $firstCInvoice['RatePercent'] !== '0%') {
                    $excelData['RateText'] .= ' +'.$firstCInvoice['RatePercent'];
                }
            }
        }

        [$insuranceCurrency, $insuranceAmount] = FieldMoneyWithCurrency::getAsTouple($shipmentObj['InsuranceAmount']);
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

        $xls = new XlsWriter(__DIR__."/$exceltemplate");
        $xls->replaceData($excelData);

        $filenameWithoutExt = 'Заявка №'
            .$shipmentObj['ShipmentRequestNo']
            .'-'.$shipmentRequestYearShort
            .' '.$shipmentObj['MAWBNumber']
            .' ('.FieldDate::beautify($shipmentRequestDate).')';

        $filenameWithoutExt = File::getValidFilename($filenameWithoutExt);

        $folderAbs = $this->getFolderAbsolute();

        $filepathAbsWithStamp = "$folderAbs/$filenameWithoutExt.xlsx";

        // Затем отправим пользователю
        try {
            // Основной вариант с печатью и подписью сохраняем в формате PDF
            $xls->save($filepathAbsWithStamp);
            //Session::flashSuccess('Файл <strong>' . htmlspecialchars($filename) . '</strong> сохранён в папку перевозки');

        } catch (\Exception) {
            Session::flashError(
                'Не удалось сохранить файл <strong>'.htmlspecialchars(
                    $filenameWithoutExt.".pdf",
                ).'</strong> в папку перевозки, возможно, он открыт в Excel?',
            );
        }

        // Отдельную копию файла без печати и подписи нужно положить в папку "0 Заявка"
        $this->removeStampFromShipmentRequestExcel($xls);
        $requestFolder = Constant::getStandartFolderReqeust();

        // На всякий случай проверим, что папка "0 Заявка" создана
        $folderRelative = $this->getFolderRelative();
        FilesFolder::mkdir("$folderRelative/$requestFolder");

        $filepathAbsXlsx = "$folderAbs/$requestFolder/$filenameWithoutExt.xlsx";

        try {
            $xls->save($filepathAbsXlsx);
        } catch (\Exception) {
            Session::flashError(
                'Не удалось сохранить файл <strong>'.htmlspecialchars(
                    $filenameWithoutExt.".xlsx",
                ).'</strong> в папку "'.htmlspecialchars((string) $requestFolder).'", возможно, он открыт в Excel?',
            );
        }

        if (file_exists($filepathAbsWithStamp)) {
            // Отправим готовый файл на экран пользователю (отправляем тот, который PDF с печатью)
            Response::streamFileFromFilesystem($filepathAbsWithStamp);
        } else {
            $this->redirectToView();
        }
    }

    private function removeStampFromShipmentRequestExcel(XlsWriter $xls): void
    {
        // Также файл с заявкой без печати и подписи необходимо сохранить в папку 0. Заявка
        // Для этого сначала найдём все объекты изображений
        $worksheet = $xls->getSpreadsheet()->getActiveSheet();

        // Remove drawing from old \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
        $drawingCollection = $worksheet->getDrawingCollection();
        /** @var \ArrayIterator $iterator */
        $iterator = $drawingCollection->getIterator();

        $unsetOffset = [];
        while ($iterator->valid()) {
            /** @var BaseDrawing $drawing */
            $drawing = $iterator->current();
            $coordinates = $drawing->getCoordinates();
            if ($coordinates) {
                $row = Coordinate::coordinateFromString($coordinates)[1] ?? 0;
                if ($row >= 41) {
                    // Всё что ниже 41 строки считаем изображением печати
                    $unsetOffset[] = $iterator->key();
                }
            }
            $iterator->next();
        }

        foreach ($unsetOffset as $key) {
            $drawingCollection->offsetUnset($key);
        }
    }

    protected function viewGetDownloadDocs()
    {
        $sd = ShipmentDownloadDocs::create($this->flash);
        $ids = [$this->_obj->getId()];
        $resultFilepath = $sd->generateZip($ids, false);
        if (!$resultFilepath) {
            $this->redirectToView();

            return;
        }

        Response::streamFileFromFilesystem($resultFilepath);
    }

    /**
     * На экран Shipment добавить кнопку "Документы на печать" - должны
     * выгружаться документы из папок: 0.Заявка + 2. Счет + 5. Акт.
     */
    protected function viewGetDownloadDocsForPrint()
    {
        $sd = ShipmentDownloadDocs::create($this->flash);
        $ids = [$this->_obj->getId()];
        $resultFilepath = $sd->generateZip(
            $ids,
            false,
            [Constant::FOLDER_REQUEST, Constant::FOLDER_CINVOICE, Constant::FOLDER_CACT],
        );
        if (!$resultFilepath) {
            $this->redirectToView();

            return;
        }

        Response::streamFileFromFilesystem($resultFilepath);
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
            ",
        );
    }

    private static function getDateInText(string $mysqlDate)
    {
        [$year, $month, $day] = explode('-', $mysqlDate);

        return (int) $day.' '.Translate::monthRu($month, true).' '.$year.' г.';
    }

    protected function viewPostCs()
    {
        $this->viewGetCs();
    }

    /**
     * Этот метод запускается, если в GET запросе есть парамеры id=...&func=cs - срабатывает метод движка
     * ModuleAbstract::proceedViewFunc() т.е. загружается сам объект перевозки в $this->_obj и управление передаётся
     * внутрь viewFuncCs() Здесь полностью делегируем дальнейший разбор url параметров в класс CostSheetRouter - он уже
     * определит, что нужно делать с текущим кост-шитом (отрендрить, сохранить или обновить через ajax)
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

            $tdata = [
                'content' => $content,
                'module' => $this->getModule(),
                'tabsmenuItems' => $this->getViewEditLogTabsmenuItems(),
                'submenuHtml' => $this->getCsSubmenuHtml(),
                'id' => $shipmentId,
                'ajaxChangeFieldUrl' => $this->getAjaxChangeFieldUrl(),
            ];

            Template::includeTpl('module/view.tpl.php', $tdata);
        }
    }

    private function getCsSubmenuHtml()
    {
        //Если у пользователя нет специальных прав на редактирование перевозки после закрытия и она закрыта - не будем добавлять кнопки
        /** @var ShipmentObj $shipmentObj */
        $shipmentObj = $this->_obj;
        if (CostSheetRouter::hasAccessWriteCostSheet($shipmentObj)) {
            $this->addSubmenuLink(
                Link::create(self::class, Ico::edit().' Edit Cost-sheet')
                    ->cssClass('cs-edit-btn'),
            );

            $this->addSubmenuLink(
                Link::create(self::class, Ico::upload().' Create Customer Invoice')
                    ->cssClass('cs-cinvoice-btn'),
            );

            $this->addSubmenuLink(
                Link::create(self::class, Ico::download().' Register Vendor Invoice')
                    ->cssClass('cs-vinvoice-btn'),
            );

            $this->addSubmenuLink(
                Link::create(self::class, Ico::copy().' Copy')
                    ->cssClass('cs-copy-btn display-none'),
            );

            $this->addSubmenuLink(
                Link::create(self::class, Ico::search().' Quotation / Standart Rate')
                    ->cssClass('cs-fillquotation-btn display-none'),
            );
        }

        return $this->getSubmenuHtml();
    }

    public static function proceedCopyFromCs(): void
    {
        CostSheetRouter::proceedAjaxCopyFromCs();
    }

    public function viewGetFillFromQuotation(): void
    {
        // Загрузим данные по переданной котировке
        if ($_GET['QuotationID'] < 700000) {
            $newCsLines = $this->getCsLinesFromQuotation(
                $_GET['QuotationID'],
                $this->_obj['GrossWeight'],
                $this->_obj['ChargeableWeight'],
                $this->_obj['Pieces'],
                $this->_obj->getId(),
            );
        } else {
            $newCsLines = $this->getCsLinesFromStandartRate(
                $_GET['QuotationID'],
                $this->_obj['ChargeableWeight'],
                $this->_obj->getId(),
            );
        }

        if (!$newCsLines) {
            // Возвращаем пустой массив, тогда на стороне JS будет понятно, что котировка не найдена
            Response::abort(404, 'Quotation '.$_GET['QuotationID'].' not found');
        }

        Response::json($newCsLines);
    }

    #[\Override]
    protected function getAccessSqlWhere(): string
    {
        return BuAccess::getSqlWhereBu()
            ." AND ".CustomerAccess::getSqlWhere()
            ." AND ".Constant::getCreatedDateSqlWhere();
    }
}
