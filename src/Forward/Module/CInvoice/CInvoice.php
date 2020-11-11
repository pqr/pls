<?php

namespace Forward\Module\CInvoice;

use Forward\Application;
use Forward\Classes\BuAccess;
use Forward\Classes\CInvoiceCommon;
use Forward\Classes\Constant;
use Forward\Classes\CustomerAccess;
use Forward\Classes\Finance;
use Forward\Classes\InvoicesCommon;
use Forward\Classes\ShipmentCriteria\ShipmentCriteria;
use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Forward\ForwardConfig;
use Forward\Module\CInvoice\Massactions\CreateCPayments;
use Forward\Module\CInvoice\PrintForms\CInvoicePrintForm;
use Forward\Module\CInvoice\Subtables\CPayments;
use Forward\Module\CInvoice\Subtables\Lines;
use Forward\Module\CPayment\CPayment;
use Forward\Module\Shipment\Shipment;
use Forward\ModuleBase\Invoices\InvoiceModuleAbstract;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Light\Currency;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\File;
use Light\FilesFolder;
use Light\Ico;
use Light\ModuleBase\MassactionUtils;
use Light\Msg;
use Light\ObjAbstract;
use Light\PrintForm\PrintForm;
use Light\Response;
use Light\Session;
use Light\Template;
use Light\User;

class CInvoice extends InvoiceModuleAbstract
{
    protected $textSaveBtn = 'Customer Invoice';
    public $tableLayoutDefault = 'Name|CustomerAccountID|Amount|CurrencyISO|DocType|StatusIco|DocDate|DueDate|Overdue|Comments';

    protected function initSubtables()
    {
        $this->addSubtable(Lines::class);
        $this->addSubtableReadonly(CPayments::class);
    }

    protected function initMassactions()
    {
        if (User::hasWriteAccess(CPayment::class)) {
            $this->addMassaction('CreateCPayment', 'Create Customer Payments', 'invcleared');
        }

        $this->addMassactionMassUpdate();
    }

    protected function initPrintForms()
    {
        $this->addPrintForm(new CInvoicePrintForm('Word', 'Word', PrintForm::FORMAT_DOC));
        $this->addPrintForm(new CInvoicePrintForm('WordStamp', 'Word with stamp', PrintForm::FORMAT_DOC));
        $this->addPrintForm(new CInvoicePrintForm('PDF', 'PDF', PrintForm::FORMAT_PDF));
        $this->addPrintForm(new CInvoicePrintForm('PDFStamp', 'PDF with stamp', PrintForm::FORMAT_PDF));
    }

    protected function initFilters()
    {
        $this->addFilters(

            GeneralFields::filterCompanyId(),
            GeneralFields::customerId(false, true),
            GeneralFields::customerAccountId(),
            F::createText('InvoiceNumbers', [
                'name' => 'Invoice No',
                'sqlWhere' => function ($value) {
                    return Db::getSqlWhereSeparatedByComa($value, 'cinvoice.Name', -1, false, false);
                },
            ]),
            F::createText('FileNumbers', [
                'name' => 'File Numbers',
                'sqlWhere' => function ($value) {
                    $query = "EXISTS (SELECT *
                                  FROM cinvoice_file_amount cf
                                  WHERE cf.CInvoiceID = cinvoice.ID
                                      AND " . Db::getSqlWhereSeparatedByComa($value, 'cf.FileNumber', -1, false, true) .
                        ")";

                    return $query;
                },
            ]),
            F::createText('ShippingDocuments', [
                'name' => 'Shipping Documents',
                'sqlWhereFormatFunction' => function ($value) {
                    return str_replace(' ', '', str_replace('-', '', $value));
                },
                'sqlWhere' => function ($value) {
                    return Db::getSqlWhereSeparatedByComa(
                        $value,
                        'REPLACE(cinvoice.ShippingDocument, "-", "")',
                        0,
                        true,
                        false
                    );
                },
            ]),
            CF::currency(),
            InvoiceFields::filterStatus(
                Finance::$cinvoiceStatuses,
                [
                    'Manual' => ['text' => 'Manual Selection', 'optgroup' => 'Mixed'],
                    'Open' => ['text' => 'Open', 'optgroup' => 'Mixed'],
                ]
            ),
            InvoiceFields::overdue('C'),

            F::createSelect('ResponsibleSales', [
                'name' => 'Sales',
                'options' => "SELECT ID, Name FROM user WHERE ID IN (SELECT DISTINCT UserID FROM customer_responsible WHERE Role = 'Sales') ORDER BY Name",
                'sqlWhere' => "EXISTS (SELECT * FROM customer_responsible cr WHERE cr.CustomerID = cinvoice.CustomerID AND cr.UserID = {value} AND cr.Role = 'Sales')",
            ])

        );

        //Добавим больше фильтров по статусам - по каждому статусу отдельный чекбокс
        foreach (Finance::$cinvoiceStatuses as $status => $info) {
            $this->addFilter(F::createCheckbox("Status$status", $info['text']));
        }

        $this->addFiltersDatesFromUntil('DocDate', ['name' => 'Doc Date']);

        $this->filtersLayout = [

            [

                'CompanyID',
                'CustomerID',
                'CustomerAccountID',
                'InvoiceNumbers',
                'FileNumbers',
                'ShippingDocuments',
            ],
            [
                'CurrencyISO',
                'DocDate',
                'Overdue',
                'ResponsibleSales',
                'Status' => function () {
                    return Template::html('Status')
                        . '<div><table id="statuses-checkboxes-table"'
                        . ((is_numeric(Template::value('Status')) || Template::value('Status') == '%') ? ' class="display-none"' : '')
                        . '><tr valign="top"><td>'
                        . '<div class="invopen">'
                        . Template::html('Status' . Finance::INVOICESTATUS_OPEN)
                        . ' ' . Template::label('Status' . Finance::INVOICESTATUS_OPEN)
                        . '</div>'
                        . '<div class="invpartcleared">'
                        . Template::html('Status' . Finance::INVOICESTATUS_PARTCLEARED)
                        . ' ' . Template::label('Status' . Finance::INVOICESTATUS_PARTCLEARED)
                        . '</div>'
                        . '<div class="invcleared">'
                        . Template::html('Status' . Finance::INVOICESTATUS_CLEARED)
                        . ' ' . Template::label('Status' . Finance::INVOICESTATUS_CLEARED)
                        . '</div>'
                        . '<div class="invcreditnoted">'
                        . Template::html('Status' . Finance::INVOICESTATUS_CREDITNOTED)
                        . ' ' . Template::label('Status' . Finance::INVOICESTATUS_CREDITNOTED)
                        . '</div>'
                        . '<div class="invdeleted">'
                        . Template::html('Status' . Finance::INVOICESTATUS_DELETED)
                        . ' ' . Template::label('Status' . Finance::INVOICESTATUS_DELETED)
                        . '</div>'
                        . '</td></tr></table></div>';
                },
            ],

        ];
    }

    protected function validateUnique(array $dataFromPost)
    {
    }

    protected function validateMandatory(array $dataFromPost)
    {
        parent::validateMandatory($dataFromPost);

        if ($dataFromPost['CustomerAccountID'] == 0) {
            $query = "SELECT count(*) FROM customer_customeraccount WHERE CustomerID = " .
                (int)$dataFromPost['CustomerID'];
            $count = Db::getOne($query);
            if ($count > 0) {
                $this->riseSaveErorr('Empty Customer Account', 'CustomerAccountID');
            }
        }

        if (!$this->_obj->getId()) {
            if ($dataFromPost['CompanyID'] == Constant::OC_PGL_OOO) {
                $this->riseSaveErorr('от старой компании PGL OOO счета больше не выставляются, используйте PGL OOO new',
                    'CompanyID');
            } elseif ($dataFromPost['CompanyID'] == Constant::OC_PLS_OOO) {
                $this->riseSaveErorr('от старой компании PLS OOO счета больше не выставляются, используйте PLS OOO new',
                    'CompanyID');
            }
        }

        if ($this->_obj->getId()) {
            // Это не новый счёт, это обновление существующего
            $oldCompanyId = $this->_obj['CompanyID'];
            $newCompanyId = $dataFromPost['CompanyID'];

            if ($oldCompanyId != $newCompanyId && Constant::isOneCCompany($oldCompanyId)) {
                // По рублёвым компаниям нельзя менять компанию, чтобы не запуталась синхронизация с 1С
                // Запрещаем, только если уже синхронизировано (для этого проверим наличие даты OneCDocDate)
                $onecDocDate = Db::getOne("SELECT OneCDocDate FROM cinvoice WHERE ID = @i", $this->_obj->getId());
                if ($onecDocDate && $onecDocDate !== '0000-00-00') {
                    // Счёт уже выгружался в 1С, значит запрещяем менять компанию
                    $this->riseSaveErorr('Счёт уже был выгружен в 1С, изменить нашу компанию теперь нельзя.<br>Если счёт был выпущен ошибочно, то его нужно удалить в 1С (при этом базе автоматически появится статус "Deleted") и выпустить новый счёт от другой нашей компании.',
                        'CompanyID');
                }
            }
        }

        /*
        if ($this->_obj->getId() && $dataFromPost['Name'] === '') {
            $this->riseSaveErorr('Empty Inv. No', 'Name');
        }
        */
    }

    protected function getTplData()
    {
        // Проверим, есть ли зафиксированная дата OneCDocDate?
        $onecDocDate = Db::getOne("SELECT OneCDocDate FROM cinvoice WHERE ID = @i", $this->_obj->getId());
        // Если есть зафикисированная дата OneCDocDate, значит этот счёт попал под синхронизацию с 1С и мы не можем
        // менять его номер
        if ($onecDocDate && $onecDocDate !== '0000-00-00') {
            $this->_obj->getField('Name')->setModes(F::MODE_READONLY);
        }

        $tdata = parent::getTplData();

        // Подгрузим курс даты последней оплаты для режима View
        if ($this->isStateView()) {

            $query = "SELECT RateToInvoiceCurrency
                      FROM cpayment_cinvoice cpi, cpayment cp
                      WHERE cpi.CInvoiceID = @i_cinvoiceId
                          AND cp.PaymentDate = @s_cinvoiceCacheLastPaymentDate
                      LIMIT 1";
            $currRate = Db::getOne($query, $this->_obj->getId(), $this->_obj['CacheLastPaymentDate']);
            $tdata['LastPaymentCurrRate'] = $currRate;
        }

        return $tdata;
    }

    protected function proceedAddNew()
    {
        if (isset($_GET['fromCostSheet'])) {

            //Переход на режим Add New состоялся из Cost-sheet'а где несколько строк были выделены галочками
            $shipmentId = (int)$_GET['fromCostSheet'];
            //Загрузим шапку из указанного файла
            $this->_obj->mergeData(CInvoiceCommon::getDataFromShipment($shipmentId));
            //Компанию CompanyID и валюту счёта определим на основе первой отмеченной галочки в кост-шите
            $csLineIds = explode(',', $_GET['csLineIds']);
            $this->_obj->mergeData(
                InvoicesCommon::getCurrencyFromCsLines($csLineIds, 'csincome')
            );

        } elseif (isset($_GET['fromShipmentsMassaction'])) {

            //Переход на режим Add New cостоялся из Shipments Massaction "Создать Customer Invoice"
            //В этом случае в POST лежат id строк из кост-шитов на основе которых делаем счёт
            /** @var array $csLineIdsPerShipment */
            $csLineIdsPerShipment = $_POST['csLineIdsPerShipment'];

            //Для начала выделим два отдельных массива: массив номеров перевозок и массив номеров строк в кост-шитах
            $shipmentIds = [];
            $allCsLineIds = [];
            foreach ($csLineIdsPerShipment as $shipmentId => $csLineIds) {
                $shipmentIds[] = $shipmentId;
                //Добавим в массив $csLineIds только не нулевые галочки
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $allCsLineIds = array_merge($allCsLineIds, array_filter($csLineIds));
            }

            //Загрузим шапку из первого файла
            $this->_obj->mergeData(CInvoiceCommon::getDataFromShipment($shipmentIds[0]));
            //Некоторые поля в шапке заполняются отдельно, т.к. могут быть основаны на нескольких перевозках
            $this->_obj->mergeData(CInvoiceCommon::getPolPodBlFromShipments($shipmentIds));
            //Компанию CompanyID и валюту счёта определим на основе первой отмеченной галочки в кост-шите
            $this->_obj->mergeData(
                InvoicesCommon::getCurrencyFromCsLines($allCsLineIds, 'csincome')
            );
        }

        if ($this->_obj['CompanyID']) {
            $this->_obj['BankID'] = Db::getOne("SELECT ID FROM ourcompanybank WHERE CompanyID = @i ORDER BY ID LIMIT 1",
                $this->_obj['CompanyID']);
        } else {
            $this->_obj['CompanyID'] = Constant::OC_PLS_USD;
            $this->_obj['BankID'] = Constant::OC_PLS_USD;
        }

        parent::proceedAddNew();
    }

    protected function massactionCreateCPayment(array $ids)
    {
        $errors = CreateCPayments::check($ids);

        if ($errors) {
            MassactionUtils::sendErrorDialog($errors);
            return;
        }

        CreateCPayments::showPaymentDateDialog($ids);

    }

    public static function proceedCreateCPayments()
    {
        $cinvocieIds = explode(',', $_POST['ids']);
        $paymentDate = FieldDate::parse($_POST['PaymentDate']);
        CreateCPayments::create($cinvocieIds, $paymentDate);
    }

    public static function proceedGetCustomerAccountInvoiceTo()
    {
        if (!isset($_GET['CustomerAccountID'])) {
            Response::abort('400', 'Mandatory GET parameter CustomerAccountID missing');
        }

        print Db::getOne(
            "SELECT CONCAT(Name, '\\n', InvoiceAddress) AS InvoiceTo
             FROM customeraccount
             WHERE ID = @i",
            $_GET['CustomerAccountID']
        );
    }

    protected function afterMultipleUpdateSubmit(array $updatedObjects, array $dataFromPost, Msg $msgBuff)
    {
        if ($updatedObjects) {
            $updatedIds = array_map(
                function ($cinvObj) {
                    /** @var CInvoiceObj $cinvObj */
                    return $cinvObj->getId();
                },
                $updatedObjects
            );

            //По всем реально обновлённым счетам обновим критерии в файлах
            $fileNumbers = Db::getCol(
                "SELECT FileNumber FROM cinvoice_file_amount WHERE CInvoiceID IN (@a)",
                $updatedIds
            );
            ShipmentCriteria::updateRelatedCInvByFileNumbers($fileNumbers, 'Customer Invoices Multiple Update');
        }
    }

    public static function proceedTry()
    {
        //Проверим на наличие параметра try - попытка открыть конкретного отправителя по имени
        $id = Db::getOne("SELECT ID FROM cinvoice WHERE Name = '" . Db::esc($_GET['try']) . "'");
        if ($id) {
            //Если отправитель был найден - перенаправляем на его карточку
            Response::redirect('CInvoice', ['id' => $id]);
        }

        //Иначе подставим имя отправителя в форму поиска в режиме List
        $filterValues = ['FilterName' => $_GET['try']];
        Response::redirect('CInvoice', ['filterValues' => json_encode($filterValues)]);
    }

    protected function getAccessSqlWhere(): string
    {
        return BuAccess::getSqlWhereCustomerId() . " AND " . CustomerAccess::getSqlWhere();
    }

    protected function hasWriteAccessRightToObj(ObjAbstract $obj)
    {
        //Нужно запретить доступ на редактирование счёта если в нём есть закрытые файлы
        if (User::hasSpecialAccess(Application::SA_SHIPMENT_CHANGECLOSED)) {
            //У пользователя специальные права на редактирование всех перевозк - возвращаем true
            return true;
        }

        //Если же у пользователя нет специальных прав, проверим, есть ли закрытые файлы?
        $closedFiles = Db::getOne(
            "SELECT count(*)
             FROM shipment s, cinvoice_file_amount cfa
             WHERE s.Name = cfa.FileNumber
                AND s.FileClosed = 1
                AND cfa.CInvoiceID = " . $obj->getId()
        );

        if ($closedFiles) {
            //Если был найден хотя бы один закрытый файл, возвращаем false
            return false;
        }

        return true;
    }

    protected function initViewSubmenu()
    {
        if (Constant::isOneCCompany($this->_obj['CompanyID'])) {
            $this->addSubmenuLinkViewFunc(Ico::uploadCloud() . ' Отправить в 1С', 'SendOneC')
                ->post();
        }
    }

    protected function getDataForOneC(CInvoiceObj $cinvObj, $onecDocDate)
    {
        $data = [
            'ID' => (int)$cinvObj->getId(),
            'CompanyID' => (int)$cinvObj['CompanyID'],
            'CustomerAccountID' => (int)$cinvObj['CustomerAccountID'],
            'Comments' => $cinvObj['Comments'],
            'DocDate' => $onecDocDate && $onecDocDate !== '0000-00-00' ? $onecDocDate : $cinvObj['DocDate'],
            'DueDate' => $cinvObj['DueDate'],
            'Amount' => (float)$cinvObj['Amount'],
            'CurrencyISO' => $cinvObj['CurrencyISO'],
            'PaymentCurrencyISO' => $cinvObj['PaymentCurrencyISO'],
            'ShippingDocument' => $cinvObj['ShippingDocument'],
        ];

        if ($cinvObj['RatePercent'] !== '' && $cinvObj['RatePercent'] !== '0%') {
            $data['CurrencyISO'] .= '+' . $cinvObj['RatePercent'];
        }

        $lines = [];
        foreach (Db::getAll("SELECT * FROM cinvoiceline WHERE CInvoiceID = @i", $cinvObj->getId()) as $lineData) {

            // Попробуем найти перевод услуги в справочнике услуг
            $nameRu = Db::getOne("SELECT NameRu FROM list_chargedescription WHERE Name = @s LIMIT 1",
                $lineData['Name']);

            $lines[] = [
                'Name' => $nameRu ?: $lineData['Name'],
                'Quantity' => (float)$lineData['Quantity'],
                'Price' => (float)$lineData['Price'],
                'Amount' => (float)$lineData['Amount'],
            ];
        }

        $data['Lines'] = $lines;

        return $data;
    }

    protected function viewPostSendOneC()
    {
        /** @var CInvoiceObj $cinvObj */
        $cinvObj = $this->_obj;

        if (!Constant::isOneCCompany($cinvObj['CompanyID'])) {
            Session::flashError('В 1С можно отправлять счета только от ООО ПЛС и ООО ПГЛ');
            $this->redirectToView();
            return;
        }

        // Проверим, есть ли зафиксированная дата OneCDocDate?
        $onecDocDate = Db::getOne("SELECT OneCDocDate FROM cinvoice WHERE ID = @i", $cinvObj->getId());

        if ($onecDocDate && $onecDocDate !== '0000-00-00') {
            // Если дата зафиксирована - то счёт обязательно должен быть с датой не больше зафиксированной!
            // (можно двигать дату назад, но нельзя двигать дату вперёд)
            if ($cinvObj['DocDate'] > $onecDocDate) {
                $errMsg = 'Этот счёт уже был выгружен в 1С с датой <strong>' . FieldDate::beautify($onecDocDate) . '</strong>.'
                    . ' Вы пытаетесь изменить дату в большую сторону на <strong>' . FieldDate::beautify($cinvObj['DocDate']) . '</strong>, это может привести к нарушению нумерации, выгрузка отменена.';
                Session::flashError($errMsg);
                $this->redirectToView();
                return;

            }
        } else {
            // Если же дата не была зафиксирована, то выгружать можно только на сегодняшний день или ранее (нельзя выгружать за будущую дату)
            // При этом в счёте не должно быть номреа счёта. Если же пользователь пытается выргузить какой-то счёт с номером
            // но при этом даты OneCDocDate нет, значит этот счёт не попал под синхронизацию - это один из старых счетов введённы в базу вручную
            if ($cinvObj['Name'] !== '') {

                $errMsg = 'Этот счёт был внесён в базу вручную и не подпадает под синхронизацию с 1С. Если выгрузить его в 1С, то там может появиться дубликат! Выгрузка отменена.';
                Session::flashError($errMsg);
                $this->redirectToView();
                return;

            } else {

                if ($cinvObj['DocDate'] > date('Y-m-d')) {
                    $errMsg = 'Нельзя создавать счета в 1С будущей датой (' . FieldDate::beautify($cinvObj['DocDate']) . '), это может привести к нарушению нумерации, выгрузка отменена.';
                    Session::flashError($errMsg);
                    $this->redirectToView();
                    return;
                }

            }
        }

        $dataForOneC = $this->getDataForOneC($cinvObj, $onecDocDate);

        $client = new Client(['verify' => false]);
        $path = Constant::$onecCompany[$cinvObj['CompanyID']];
        $url = 'https://' . ForwardConfig::$onecIp . ":" . ForwardConfig::$onecPort . "/$path/hs/CreateCustomerInvoice";
        Session::flashInfo('Отправляем данные на адрес ' . $url);

        try {

            $res = $client->post($url, ['json' => $dataForOneC]);

        } catch (RequestException $e) {

            $msg = 'Не удалось подключиться к серверу 1С.<br>' . $e->getMessage();
            Session::flashError($msg);
            $this->redirectToView();
            return;

        }

        $resBody = (string)$res->getBody();
        if (!$resBody) {
            Session::flashError('Не удалось прочитать ответ от 1С');
            $this->redirectToView();
        }

        $resData = json_decode($resBody, true);
        if (!$resData) {
            Session::flashError('Не удалось прочитать ответ от 1С');
            Session::flashError('<pre>' . htmlspecialchars($resBody) . '</pre>');
            $this->redirectToView();
            return;
        }

        if (isset($resData['Errors']) && $resData['Errors']) {
            Session::flashWarning('От 1С получен следующий ответ:<br>' . nl2br(htmlspecialchars($resData['Errors'])));
        }

        if (!isset($resData['DocNumber']) || !$resData['DocNumber']) {
            Session::flashError('От 1С не получен номер счёта');
            $this->redirectToView();
            return;
        }

        $this->_obj->updateDb(['Name' => $resData['DocNumber'], 'OneCDocDate' => $dataForOneC['DocDate']],
            'Номер счёта из 1С');
        Session::flashSuccess('Счёт зарегистрирован в 1С под номером <strong>' . htmlspecialchars($resData['DocNumber']) . '</strong>');

        $isDeleted = ($resData['IsDeleted'] ?? false) || strpos($resData['Errors'] ?? '', 'Помеченный на удаление');

        // Из 1С также может прийти флаг IsDeleted, который нужно применить к саттусу счёта
        if ($isDeleted && $this->_obj['Status'] != Finance::INVOICESTATUS_DELETED) {
            // Пришел флаг IsDeleted = true, но счёт находится в каком-то другом статусе - надо поменять статус в базе
            $this->_obj->updateDb(['Status' => Finance::INVOICESTATUS_DELETED],
                'При попытке отправить счёт в 1С пришел ответ с пометкой на удаление');
            Session::flashInfo('Счёт помечен как удалённый');
        } elseif (!$isDeleted && $this->_obj['Status'] == Finance::INVOICESTATUS_DELETED) {
            // Пришел флаг IsDeleted = false, но счёт находится в статусе Deleted - нужно вывести из этого статуса
            $this->_obj->updateDb(['Status' => Finance::INVOICESTATUS_OPEN],
                'При попытке отправить счёт в 1С пришел ответ со снятой пометкой на удаление');
            // Возможно, этот счёт должен быть Cleared на основе суммы оплат?
            Finance::autoClearInvoice('C', $cinvObj->getId());
            Session::flashInfo('Снята пометка на удаление');
        }

        $this->createFolder();
        $folderpathAbs = $this->getFolderAbsolute();

        if (empty($resData['Data'])) {

            Session::flashError('От 1С не получен PDF файл счёта');

        } else {

            $pdfFilename = File::getValidFilename($this->_obj['Name']) . '.pdf';
            $pdfFilepathAbs = "$folderpathAbs/$pdfFilename";
            $pdfDataBase64 = $resData['Data'];
            $pdfData = base64_decode($pdfDataBase64);
            if (file_put_contents($pdfFilepathAbs, $pdfData)) {
                Session::flashSuccess('Файл <strong>' . htmlspecialchars($pdfFilename) . '</strong> помещён в папку счёта');
            } else {
                Session::flashError('Не удалось поместить файл <strong>' . htmlspecialchars($pdfFilename) . '</strong> в папку счёта');
            }

        }

        if (empty($resData['ExcelData'])) {

            Session::flashError('От 1С не получен Excel файл счёта');

        } else {

            $excelFilename = File::getValidFilename($this->_obj['Name']) . '.xlsx';
            $excelFilepathAbs = "$folderpathAbs/$excelFilename";
            $excelDataBase64 = $resData['ExcelData'];
            $excelData = base64_decode($excelDataBase64);

            if (file_put_contents($excelFilepathAbs, $excelData)) {
                Session::flashSuccess('Файл <strong>' . htmlspecialchars($excelFilename) . '</strong> помещён в папку счёта');
            } else {
                Session::flashError('Не удалось поместить файл <strong>' . htmlspecialchars($excelFilename) . '</strong> в папку счёта');
            }

            // По просьбе Валерии копируем в папки перевозок Excel файл
            $cinvId = $this->_obj->getId();
            $shipments = Db::getAll("SELECT s.ID, s.Name FROM cinvoice_file_amount cfa, shipment s WHERE cfa.CInvoiceID = $cinvId AND cfa.FileNumber = s.Name");
            foreach ($shipments as ['ID' => $shipmentId, 'Name' => $fileNumber]) {
                // Перекладываем файл в папку внутри shipment
                $shipmentFolderRelative = FilesFolder::getRelativeModule(Shipment::class, $shipmentId);
                $standartCInvoiceFolder = Constant::$standardFolders[Constant::FOLDER_CINVOICE];
                $shipmentCInvoioceFolderRelative = "$shipmentFolderRelative/$standartCInvoiceFolder";
                FilesFolder::mkdir($shipmentCInvoioceFolderRelative);
                $result = file_put_contents(FilesFolder::getAbsolute($shipmentCInvoioceFolderRelative) . "/$excelFilename",
                    $excelData);
                if ($result) {
                    Session::flashSuccess('Файл <strong>' . htmlspecialchars($excelFilename) . '</strong> помещён в папку перевозки ' . $fileNumber . '/' . $standartCInvoiceFolder);
                } else {
                    Session::flashError('Не удалось сохранить файл <strong>' . htmlspecialchars($excelFilename) . '</strong> в папку перевозки ' . $fileNumber . '/' . $standartCInvoiceFolder);
                }
            }

        }

        $this->redirectToView();

    }

    public static function proceedGetRate()
    {
        if (!isset($_GET['date'], $_GET['currency'])) {
            Response::abort(400);
        }

        $date = FieldDate::parse($_GET['date']);
        $currency = $_GET['currency'];

        print Currency::getRate($currency, 'RUB', $date);
    }
}
