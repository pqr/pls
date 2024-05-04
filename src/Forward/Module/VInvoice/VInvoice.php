<?php

namespace Forward\Module\VInvoice;

use Forward\Application;
use Forward\Classes\CompanyChild;
use Forward\Classes\Constant;
use Forward\Classes\Finance;
use Forward\Classes\InvoicesCommon;
use Forward\Field\GeneralFields;
use Forward\Field\InvoiceFields;
use Forward\ForwardConfig;
use Forward\Module\VInvoice\Subtables\Lines;
use Forward\Module\VInvoice\Subtables\Responsibles;
use Forward\Module\VInvoice\Subtables\VPayments;
use Forward\ModuleBase\Invoices\InvoiceModuleAbstract;
use Light\ApplicationAbstract;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\File;
use Light\FilesFolder;
use Light\Link;
use Light\Mailer;
use Light\Misc;
use Light\ModuleBase\MassactionUtils;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;
use Light\Template;
use Light\User;

class VInvoice extends InvoiceModuleAbstract
{
    protected $textSaveBtn = 'Vendor Invoice';
    public $tableLinkFieldKeys = ['VendorID'];
    public $tableLayoutDefault = 'VendorID|Name|Amount|CurrencyISO|StatusIco|CacheResponsibleUserID|ReceivedDate|DocDate|DueDate|CacheLastPaymentDate|Comments';

    public function __construct()
    {
        parent::__construct();

        //Если у пользователя нет прав на изменения поля Status, исправим набор options
        $statusOptions = Finance::$vinvoiceStatuses;
        if (!User::hasSpecialAccess(Application::SA_VINVOICE_ALLOW_STATUS_ACCEPT)) {
            $statusOptions[Finance::INVOICESTATUS_UNDERDISPUTE]['Active'] = 0;
            $statusOptions[Finance::INVOICESTATUS_ACCEPTED]['Active'] = 0;
        }
        if (!User::hasSpecialAccess(Application::SA_VINVOICE_ALLOW_STATUS_CLEAR)) {
            $statusOptions[Finance::INVOICESTATUS_PARTCLEARED]['Active'] = 0;
            $statusOptions[Finance::INVOICESTATUS_CLEARED]['Active'] = 0;
            $statusOptions[Finance::INVOICESTATUS_CREDITNOTED]['Active'] = 0;
            $statusOptions[Finance::INVOICESTATUS_DELETED]['Active'] = 0;
        }
        $this->getField('Status')->setParam('options', $statusOptions);
    }

    #[\Override]
    protected function initSubtables()
    {
        $this->addSubtable(Responsibles::class);
        $this->addSubtable(Lines::class);
        $this->addSubtableReadonly(VPayments::class);
    }

    #[\Override]
    protected function initMassactions()
    {
        if (User::hasSpecialAccess(Application::SA_VINVOICE_ALLOW_STATUS_ACCEPT)) {
            $this->addMassaction('MarkAccepted', 'Accepted', 'invopenaccepted');
        }

        if (User::hasWriteAccess('VPayment')) {
            $this->addMassaction('CreateVendorPayment', 'Create Vendor Payment', 'invcleared');
        }

        $this->addMassaction('Download', 'Скачать файлы единым zip архивом', '', '', true);
    }

    #[\Override]
    protected function initCssJs()
    {
        parent::initCssJs();

        if ($this->isStateEdit() || $this->isStateAddNew()) {
            if (User::hasSpecialAccess(Application::SA_VINVOICE_ALLOW_ALL_COMPANYID)) {
                CompanyChild::initJsCompanyChildFromVendor();
            }
        }
    }

    #[\Override]
    protected function initFilters()
    {
        $statusOptions = Finance::$vinvoiceStatuses;
        //Добавим дополнительные опции для фильтра
        $statusOptions['Manual'] = ['text' => 'Manual Selection', 'optgroup' => 'Mixed'];
        $statusOptions['Open'] = ['text' => 'Open', 'optgroup' => 'Mixed'];
        $statusOptions['NotDeleted'] = ['text' => 'Not Deleted', 'optgroup' => 'Mixed'];

        $this->addFilters(

            GeneralFields::filterCompanyId(),
            GeneralFields::vendorId(),
            GeneralFields::vendorAccountId(),
            F::createText('Reference', [
                    'sqlWhere' => fn($value) => Db::getSqlWhereSeparatedByComa($value, 'Name', 0, true, false),
                ]
            ),
            F::createText(
                'FileNumbers',
                [
                    'name' => 'File Numbers',
                    'sqlWhere' => function ($value) {
                        $query = "EXISTS(SELECT *
                                 FROM vinvoice_file_amount vf
                                 WHERE vf.VInvoiceID = vinvoice.ID
                                     AND " . Db::getSqlWhereSeparatedByComa($value, 'vf.FileNumber', -1, false, true) .
                            ")";

                        return $query;
                    },
                ]
            ),
            F::createText(
                'MAWBNumbers',
                [
                    'name' => 'MAWB Numbers',
                    'sqlWhere' => function ($value) {
                        $value = str_replace([' ', '-'], '', $value);
                        $query = "EXISTS(SELECT *
                                 FROM vinvoiceline vl
                                 WHERE vl.VInvoiceID = vinvoice.ID
                                     AND " . Db::getSqlWhereSeparatedByComa(
                                $value,
                                "REPLACE(REPLACE(vl.MAWBNumber, ' ', ''), '-', '')",
                                11,
                                true
                            ) . ")";

                        return $query;
                    },
                ]
            ),
            CF::currency(),

            InvoiceFields::overdue('V'),

            F::createSelect(
                'Responsible',
                [

                    'options' => "SELECT ID, Name
                            FROM `user`
                            WHERE EXISTS (SELECT * FROM vinvoice_responsible vr WHERE vr.UserID = `user`.ID)
                            ORDER BY Name",
                    'sqlWhere' => "EXISTS (SELECT VInvoiceID
                                  FROM vinvoice_responsible vr
                                  WHERE vr.VInvoiceID = vinvoice.ID
                                      AND vr.UserID ='{value}')",
                ]
            ),

            F::createSelect('HasVAT', [
                'name' => 'VAT (НДС)',
                'options' => [1 => 'Есть НДС'],
                'sqlWhere' => "EXISTS(SELECT * FROM vinvoiceline WHERE VInvoiceID = vinvoice.ID AND (VATAmount !=0 OR Name = 'НДС'))",
            ]),

            InvoiceFields::filterStatus(
                Finance::$vinvoiceStatuses,
                [
                    'Manual' => ['text' => 'Manual Selection', 'optgroup' => 'Mixed'],
                    'Open' => ['text' => 'Open', 'optgroup' => 'Mixed'],
                    'NotDeleted' => ['text' => 'Not Deleted', 'optgroup' => 'Mixed'],
                ]
            )
        );

        //Добавим больше фильтров по статусам - по каждому статусу отдельный чекбокс
        foreach (Finance::$vinvoiceStatuses as $status => $info) {
            $this->addFilter(F::createCheckbox('Status' . $status, ['name' => $info['text']]));
        }

        $this->addFiltersDatesFromUntil('ReceivedDate', ['name' => 'Received']);
        $this->addFiltersDatesFromUntil('DocDate', ['name' => 'Doc Date']);

        $this->filtersLayout = [
            [

                'CompanyID',
                'VendorID',
                'VendorAccountID',
                'Reference',
                'FileNumbers',
                'MAWBNumbers',
            ],
            [

                'CurrencyISO',
                'HasVAT',
                'ReceivedDate',
                'DocDate',
                'Overdue',
                'Responsible',
                'Status' => fn() => Template::html('Status')
                    . '<div><table id="statuses-checkboxes-table"'
                    . ((is_numeric(Template::value('Status')) || Template::value(
                            'Status'
                        ) == '%') ? ' class="display-none"' : '')
                    . '><tr valign="top"><td>'
                    . '<div class="invopen">'
                    . Template::html('Status' . Finance::INVOICESTATUS_OPEN)
                    . ' ' . Template::label('Status' . Finance::INVOICESTATUS_OPEN)
                    . '</div>'
                    . '<div class="invunderdispute">'
                    . Template::html('Status' . Finance::INVOICESTATUS_UNDERDISPUTE)
                    . ' ' . Template::label('Status' . Finance::INVOICESTATUS_UNDERDISPUTE)
                    . '</div>'
                    . '<div class="invaccepted">'
                    . Template::html('Status' . Finance::INVOICESTATUS_ACCEPTED)
                    . ' ' . Template::label('Status' . Finance::INVOICESTATUS_ACCEPTED)
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
                    . '</td></tr></table></div>',
            ],
        ];
    }

    #[\Override]
    protected function initList()
    {
        $this->addFields(
            $this->getListVatMountField(12),
            $this->getListVatMountField(18)
        );
    }

    private function getListVatMountField($vatPercent)
    {
        return F::createMoney("VAT{$vatPercent}Amount", [
            'name' => "VAT $vatPercent%",
            'title' => "Сумма приходящаяся на НДС $vatPercent% (на основе столбца 'VAT Amount' в строках счёта + сумма из строки в счёте с текстом услуги 'НДС $vatPercent%')",
            'sqlSelect' => "IFNULL((SELECT sum(VATAmount) FROM vinvoiceline WHERE VATPercent = $vatPercent AND VInvoiceID = vinvoice.ID), 0) + IFNULL((SELECT sum(FinalAmount) FROM vinvoiceline WHERE VInvoiceID = vinvoice.ID AND (Name LIKE 'НДС $vatPercent%' OR Name LIKE 'VAT $vatPercent%')), 0)",
            'zeroEmpty' => true,
            'zeroEmptyExcel' => true,
        ]);
    }

    #[\Override]
    protected function getDataFromPost()
    {
        $dataFromPost = parent::getDataFromPost();

        //Если новый статус инвойса это On Checkgin - стираем дату NoOnCheckingDate
        if ($dataFromPost['Status'] == Finance::INVOICESTATUS_OPEN) {
            $dataFromPost['NotOnCheckingDate'] = '0000-00-00';
        } else {
            //Иначе проверим как статус был это этого? Если он отсутсвовал или был 'On Chekcing' - проинициализируем дату
            if ($this->_obj->getId() == 0 || $this->_obj['Status'] == Finance::INVOICESTATUS_OPEN) {
                $dataFromPost['NotOnCheckingDate'] = date('Y-m-d');
            }
        }

        return $dataFromPost;
    }

    #[\Override]
    protected function validateUnique(array $dataFromPost)
    {
        //Проверим существование такого счёта для такого Vendor'а в базе
        $query = "SELECT ID
                  FROM vinvoice
                  WHERE Name = @s
                      AND VendorID = @i
                      AND CreatedDateTime >= '" . (new \DateTime("-3 month"))->format("Y-m-d") . "'";

        if ($this->_obj->getId()) {
            $query .= " AND ID != " . $this->_obj->getId();
        }

        if ($id = Db::getOne($query, $dataFromPost['Name'], $dataFromPost['VendorID'])) {
            $vendorName = strip_tags((string) $this->getField('VendorID')->getHtml($dataFromPost['VendorID']));
            $msg = 'Vendor Invoice с таким Reference <strong>' . htmlspecialchars((string) $dataFromPost['Name'])
                . '</strong> от перевозчика ' . $vendorName . ' уже есть в базе, '
                . Link::create(VInvoice::class, 'посмотреть...')
                    ->view($id)
                    ->blank()
                    ->getHtml();
            $fieldKey = 'Name';
            $hint = 'уже существует';
            $this->riseSaveWarning($msg, $fieldKey);
        }
    }

    protected function massactionMarkAccepted(array $ids)
    {
        $updated = 0;

        foreach ($ids as $id) {
            $vinvObj = new VInvoiceObj();
            $vinvObj->setId($id);
            if ($vinvObj->updateDb(['Status' => Finance::INVOICESTATUS_ACCEPTED], 'Multiple Update')) {
                $updated++;
            }
        }

        MassactionUtils::sendSuccessDialog(
            $updated . ' Vendor Invoices have been updated'
        );
    }

    protected function massactionCreateVendorPayment(array $ids)
    {
        $query = "SELECT VendorID
                          , VendorAccountID
                          , CompanyID
                          , CurrencyISO
                          , sum(CacheOutstandingAmount) AS SumAmount
                      FROM vinvoice
                      WHERE ID IN (" . implode(',', $ids) . ")
                      GROUP BY VendorID, VendorAccountID";
        $vendorInfo = Db::getAll($query);


        if (count($vendorInfo) === 0) {
            MassactionUtils::sendErrorDialog('Отмеченные счета не найдены в базе.');
            return;
        }

        if (count($vendorInfo) !== 1) {
            //Если кол-во комбинаций вендоров и аккаунтов больше 1, значит пользователь неверно выбрал входящие счета
            MassactionUtils::sendErrorDialog(
                'Выбранные счета относятся к несольким Vendor или Vendor Account.<br>
                 Чтобы создать Vendor Payment, счета должны относится к одному Vendor и Vendor Account.'
            );
            return;
        }

        //Всё отлично, необходимо перенаправить пользователя в модуль оплаты
        //Для этого сформируем url
        $urlParams = [
            'addnew' => 1,
            'VendorID' => $vendorInfo[0]['VendorID'],
            'VendorAccountID' => $vendorInfo[0]['VendorAccountID'],
            'CompanyID' => $vendorInfo[0]['CompanyID'],
            'CurrencyISO' => $vendorInfo[0]['CurrencyISO'],
            'Amount' => $vendorInfo[0]['SumAmount'],
        ];
        $postData = ['invoices' => implode(',', $ids)];
        Response::redirectWithPost('VPayment', $urlParams, $postData);
    }

    protected function massactionDownload(array $ids)
    {
        $vinvoices = Db::getAll("SELECT ID, CacheLinesMAWBNumber FROM vinvoice WHERE ID IN (@a)", $ids);
        if (!$vinvoices) {
            MassactionUtils::sendErrorDialog('Указанные счета не найдены');
        }

        // Создадим временную локальную папку, куда скопируем все файлы
        $tmpdir = LIGHT_DIR_TEMP . '/VInvoices' . date('Y-m-d_H_i_s') . '-' . User::getId();
        File::fs()->mkdir($tmpdir);

        $usedFilenames = [];
        foreach ($vinvoices as $vinvoice) {
            // Превратим номера накладных в строку через запятые
            $mawbNumbers = explode("\n", (string) $vinvoice['CacheLinesMAWBNumber']);
            $mawbNumbers = array_unique(array_filter(array_map('trim', $mawbNumbers)));
            $mawbNumbersForFile = File::getValidFilename(implode(',', $mawbNumbers));

            $folder = $this->getFolderRelative($vinvoice);
            foreach (FilesFolder::scandir($folder) as $file) {
                $fileForZip = $mawbNumbersForFile . ' ' . $file;

                // Добавим в начало файла номера накладных
                $filePathinfo = pathinfo($fileForZip);
                $index = 0;
                while (isset($usedFilenames[$fileForZip])) {
                    // Если файл с таким именем уже был добавлен в zip архив, прибавим индекс
                    $index++;
                    $fileForZip = $filePathinfo['filename'] . ' (' . $index . ').' . $filePathinfo['extension'];
                }

                $usedFilenames[$fileForZip] = true;
                $srcFilepath = FilesFolder::getAbsolute($folder) . '/' . $file;

                copy($srcFilepath, "$tmpdir/$fileForZip");
            }
        }

        $resultFilename = 'VendorInvocies ' . date('Y-m-d') . ' (' . count($vinvoices) . ').zip';
        $resultFilepath = $tmpdir . '/' . $resultFilename;

        $descriptorspec = [
            2 => ["pipe", "w"],
        ];
        $cmd = '7z';
        //Команда "a" означает добавить файл в архив
        $cmd .= ' a';
        //Укажем пароль
        $cmd .= ' -p' . ForwardConfig::$vinvoiceZipPass;
        //Укажем тип архива zip
        $cmd .= ' -tzip';
        //Укажем файл назначения
        $cmd .= ' "' . str_replace('\\', '/', $resultFilepath) . '"';
        //Укажем источник - что именно архивировать
        $cmd .= ' "' . str_replace('\\', '/', $tmpdir) . '/*"';

        $process = proc_open($cmd, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
        if (is_resource($process)) {
            $errText = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);
            if ($errText) {
                print 'Error:<br>';
                print mb_convert_encoding($errText, 'UTF-8', 'cp866');
            } else {
                Response::header('Content-Type: application/zip');
                Response::header('Content-Length: ' . filesize($resultFilepath));
                Response::header('Content-Disposition: attachment;filename="' . $resultFilename . '"');
                Response::header('Cache-Control: max-age=0');
                readfile($resultFilepath);
            }
        } else {
            print 'Error proc_open ' . $cmd;
        }

        ApplicationAbstract::terminate();
    }

    #[\Override]
    protected function afterSubmit(
        ObjAbstract $obj,
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff,
        $fromMultipleUpdat
    ) {
        //Если номер счёта пуст, то его нужно сгенерировать автоматически
        if (isset($oldData['Name']) && (string)$oldData['Name'] !== (string)$obj['Name']) {
            //Если пользователь в процессе редактирования счёта изменил поле Reference, то нужно изменить его и во многих других местах
            $this->renameAfterInsertUpdate($oldData['Name'], $obj['Name']);
        }

        parent::afterSubmit($obj, $dataFromPost, $affectedRows, $oldData, $msgBuff, $fromMultipleUpdat);

        /** @var VInvoiceObj $obj */
        if ($this->_justInserted) {
            $this->emailToAccountingIfNeeded($obj, $msgBuff);
        }

        // Автоматически создадим JSON файл для 1С
        $this->sendToOneC($obj);
    }

    /**
     * Если пользователь в процессе редактирования счёта изменил поле Reference, то нужно изменить его и во многих других
     * местах
     * @param string $oldVInvoiceName
     * @param string $newVInvoiceName
     */
    private function renameAfterInsertUpdate($oldVInvoiceName, $newVInvoiceName): void
    {
        if ((string)$oldVInvoiceName !== (string)$newVInvoiceName) {
            Db::query(
                "UPDATE vpayment_vinvoice
                 SET VInvoiceName = '" . Db::esc($newVInvoiceName) . "'
                 WHERE VInvoiceID = " . $this->_obj->getId()
            );

            //Переименуем в Cost-sheet'ах
            //Найдём все перевозки к которым относится данный счёт. Используем при этом табилцу vinvoiceline, а не
            //vinvoice_file_amount т.к. последняя ещё могла не обновиться
            $shipmentIds = Db::getCol(
                "SELECT DISTINCT s.ID
                 FROM vinvoiceline vil, shipment s
                 WHERE vil.FileNumber = s.Name
                     AND vil.VInvoiceID = " . (int)$this->_obj->getId()
            );

            if ($shipmentIds) {
                //Обновим Cost-sheet'ы
                $query = "UPDATE csexpenses cs
                      SET cs.InvNum = '" . Db::esc($newVInvoiceName) . "'
                      WHERE cs.InvNum = '" . Db::esc($oldVInvoiceName) . "'
                          AND cs.InvNum != ''
                          AND cs.ShipmentID IN (" . implode(',', $shipmentIds) . ")";

                Db::query($query);
            }
        }
    }

    #[\Override]
    protected function proceedAddNew()
    {
        if (isset($_GET['fromCostSheet'])) {
            //Переход на режим Add New состоялся из Cost-sheet'а где несколько строк были выделены галочками
            $shipmentId = (int)$_GET['fromCostSheet'];
            //Определим CompanyID
            $query = "SELECT CompanyID
                  FROM shipment
                      WHERE ID = @i";
            $this->_obj['CompanyID'] = Db::getOne($query, $shipmentId);

            //Определим валюту счёта и компанию
            //Сначала попробуем определить по первой отмеченной галочке
            $csLineIds = explode(',', (string) $_GET['csLineIds']);
            $this->_obj->mergeData(
                InvoicesCommon::getCurrencyFromCsLines($csLineIds, 'csexpenses')
            );

            if ($csLineIds) {
                //Посчитаем общую сумму регистрируемого счёта
                $query = "SELECT sum(Amount)
                          FROM csexpenses
                          WHERE ID IN (@a)";
                $this->_obj['Amount'] = Db::getOne($query, $csLineIds);
            }
        }

        parent::proceedAddNew();
    }

    public static function proceedTry(): void
    {
        $query = "SELECT ID FROM vinvoice WHERE Name = '" . Db::esc($_REQUEST['try']) . "'
                      AND EXISTS (SELECT VInvoiceID FROM vinvoiceline vc
                                  WHERE VInvoiceID = vinvoice.ID
                                      AND vc.FileNumber = '" . Db::esc($_REQUEST['filenumber']) . "')";

        $ids = Db::getCol($query);
        if (count($ids) === 1) {
            //Если инвойс был найден - редиректим на него
            Response::redirect('VInvoice', ['id' => $ids[0]]);
        } else {
            //Иначе подставим имя инвойса в форму поиска в режиме List
            $filterValues = [
                'Reference' => $_REQUEST['try'],
                'FileNumbers' => $_REQUEST['filenumber'],
            ];
            Response::redirect('VInvoice', ['filterValues' => json_encode($filterValues)]);
        }
    }

    #[\Override]
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
             FROM shipment s, vinvoice_file_amount vfa
             WHERE s.Name = vfa.FileNumber
                AND s.FileClosed = 1
                AND vfa.VInvoiceID = " . $obj->getId()
        );

        if ($closedFiles) {
            //Если был найден хотя бы один закрытый файл, возвращаем false
            return false;
        }

        return true;
    }

    private function sendToOneC(VInvoiceObj $vinvObj): void
    {
//        if (!Constant::isOneCCompany($vinvObj['CompanyID'])) {
//            return;
//        }
//
//        $vendorAccountName = Db::getOne("SELECT Name FROM vendoraccount WHERE ID = @i", $vinvObj['VendorAccountID']);
//
//        $cinvInfo = Db::getRow(
//            "SELECT s.CustomerAccountID
//                , ci.ID AS CInvoiceID
//                , ci.Name AS CInvoiceName
//                , ci.DocDate AS CInvoiceDocDate
//                , cfa.Amount AS CInvoiceAmount
//                , ci.CurrencyISO AS CInvoiceCurrencyISO
//             FROM vinvoiceline vil, shipment s
//                 LEFT JOIN cinvoice_file_amount cfa ON cfa.FileNumber = s.Name
//                 LEFT JOIN cinvoice ci ON ci.ID = cfa.CInvoiceID
//             WHERE vil.VInvoiceID = @i
//                 AND vil.FileNumber = s.Name
//             LIMIT 1"
//            , $vinvObj->getId()
//        );
//
//        $data = [
//            'ID' => $vinvObj->getId(),
//            'CompanyID' => $vinvObj['CompanyID'],
//            'VendorAccountID' => $vinvObj['VendorAccountID'],
//            'VendorAccountName' => $vendorAccountName,
//            'VendorAccountForPaymentID' => $vinvObj['VendorAccountID'],
//            'VendorAccountForPaymentName' => $vendorAccountName,
//            'CustomerAccountID' => $cinvInfo['CustomerAccountID'] ?? 0,
//            'CInvoiceID' => $cinvInfo['CInvoiceID'] ?? 0,
//            'CInvoiceName' => $cinvInfo['CInvoiceName'] ?? '',
//            'CInvoiceDocDate' => $cinvInfo['CInvoiceDocDate'] ?? '',
//            'CInvoiceAmount' => (float)($cinvInfo['CInvoiceAmount'] ?? 0),
//            'CInvoiceCurrencyISO' => $cinvInfo['CInvoiceCurrencyISO'] ?? '',
//            'DocDate' => $vinvObj['DocDate'],
//            'Name' => $vinvObj['Name'],
//            'Amount' => (float)$vinvObj['Amount'],
//            'CurrencyISO' => $vinvObj['CurrencyISO'],
//            'MAWB' => Db::getCol("SELECT DISTINCT MAWBNumber FROM vinvoiceline WHERE MAWBNumber != '' AND VInvoiceID = @i",
//                $vinvObj->getId()),
//            'Files' => []
//        ];
//
//        // Добавим файлы
//        $folder = $this->getFolderRelative($vinvObj->getData());
//        $folderpathAbs = FilesFolder::getAbsolute($folder);
//        foreach (FilesFolder::scandir($folder) as $file) {
//            $data['Files'][] = [
//                'Filename' => $file,
//                'Data' => base64_encode(file_get_contents(File::encode($folderpathAbs . '/' . $file)))
//            ];
//        }
//
//        $path = Constant::$onecCompany[$vinvObj['CompanyID']];
//        $ftpFolderpathAbs = ForwardConfig::$onecFtpFolderBase . "/vinvoice/$path";
//        File::mkdir($ftpFolderpathAbs);
//
//        // Сохраним json структуру в FTP папку
//        $ftpFilepathAbs = $ftpFolderpathAbs . '/' . $vinvObj->getId() . '.json';
//        file_put_contents($ftpFilepathAbs, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function emailToAccountingIfNeeded(VInvoiceObj $obj, Msg $msgBuff): void
    {
        $vendor = Db::getRow(
            "SELECT NotifyAccountingByEmail, Name FROM vendoraccount WHERE ID = @i",
            $obj['VendorAccountID']
        );
        if ($vendor['NotifyAccountingByEmail']) {
            /*
             * Прошу внести в карточку Vendor Accounts поле, при выборе которого, при внесении входящего счета в файл, база будет генерировать письмо в бухгалтерию.
            В этом письме нам нужна следующая информация:
            - наша компания (из Our Company);
            - вендор;
            - номер авианакладной или иного документа (коносамент);
            - номер счета;
            - дата счета.
             */

            $mawbs = Db::getOne(
                "SELECT GROUP_CONCAT(DISTINCT MAWBNumber) FROM vinvoiceline WHERE VInvoiceID = @i",
                $obj->getId()
            );

            $subject = 'Входщий счёт ' . $obj['Name'] . ' от ' . $vendor['Name'];
            $body = 'Новый входящий счёт (' . $obj->getLinkView('Посмотреть в базе >>')->blank()->absolute()->getHtml(
                ) . ')'
                . '<p><small>Our Company:</small> ' . $obj->getFieldText('CompanyID') . '</p>'
                . '<p><small>Vendor Account:</small> ' . $obj->getFieldText('VendorAccountID') . '</p>'
                . '<p><small>MAWB/BL:</small> ' . htmlspecialchars((string) $mawbs) . '</p>'
                . '<p><small>Номер счёта:</small> ' . $obj->getFieldText('Name') . '</p>'
                . '<p><small>Дата счёта:</small> ' . $obj->getFieldText('DocDate') . '</p>';

            Mailer::create('accounting@proactive-ls.com', $subject, $body)
                ->wrapBodyWithHtmlTemplate()
                ->send($msgBuff);
        }
    }

    #[\Override]
    protected function getAccessSqlWhere(): string
    {
        return Constant::getCreatedDateSqlWhere();
    }
}
