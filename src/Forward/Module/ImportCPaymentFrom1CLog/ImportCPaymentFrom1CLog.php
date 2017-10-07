<?php

namespace Forward\Module\ImportCPaymentFrom1CLog;


use Forward\Classes\Constant;
use Forward\ForwardConfig;
use Light\ApplicationAbstract;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\Misc;
use Light\Request;
use Light\Response;
use Light\Table;
use Light\Traits\TableExcelExportable;
use Light\Traits\TableFilterable;

class ImportCPaymentFrom1CLog extends Table
{
    use TableFilterable, TableExcelExportable;

    public function __construct()
    {
        parent::__construct('ImportCPaymentFrom1CLog');


        $this->addFields(

            F::createHtml('File', [
                'viewCssClass' => 'small',
                'tableWidthExcel' => 60
            ]),

            F::createSelect('CustomerAccountID', [
                'name' => 'Customer Account',
                'options' => 'customeraccount',
                'tableWidthExcel' => 40

            ]),

            F::createString('Name'),

            F::createDate('PaymentDate'),

            F::createMoney('Amount'),

            F::createString('Comments', ['tableWidthExcel' => 80]),

            F::createText('Lines', [
                'name' => 'Ошибки',
                'viewCssClass' => 'bad'
            ]),

            F::createHtml('CPaymentID', [
                'name' => 'Есть в базе?',
                'getHtmlFunction' => function ($value) {
                    if ($value) {
                        $outHtml = Link::moduleViewBlank('Есть', $value, null, 'CPayment');

                        $hasCInvoice = Db::getOne("SELECT CInvoiceID FROM cpayment_cinvoice WHERE CPaymentID = @i",
                            $value);
                        if (!$hasCInvoice) {
                            $outHtml .= '<br><small class="bad">без привязки к счёту</small>';
                        }
                        return $outHtml;
                    }

                    return 'Нет';
                },
                'viewCssClass' => function ($value) {
                    return $value ? '' : 'bad';
                }
            ])

        );

        $this->initFilters();
        $this->proceedFilters();
        if ($this->getFilterValue('CompanyID') === '%') {
            $this->_tableMsgIfEmpty = 'Выберете компанию';
        } else {
            $this->_tableMsgIfEmpty = 'Нет файлов';
            $this->loadData();
        }
    }

    protected function initFilters()
    {
        $companyIdsSql = implode(',', array_keys(Constant::$onecCompany));

        $this->addFilter(

            F::createSelect('CompanyID', [
                'name' => 'Our Company',
                'options' => "SELECT ID, Name FROM ourcompany WHERE ID IN($companyIdsSql) ORDER  BY OrderID, Name"
            ])

        );
    }


    private function loadData()
    {
        $companyId = $this->getFilterValue('CompanyID');
        if ($companyId === '%') {
            return;
        }

        $ftpSubfolder = Constant::$onecCompany[$companyId]['ftpSubfolder'];
        $ftpFolder = ForwardConfig::$onecFtpFolderBase . "/cpayment/$ftpSubfolder/archive";
        if (!is_dir($ftpFolder)) {
            return;
        }

        $data = [];

        foreach (scandir($ftpFolder) as $archiveFolder) {
            if ($archiveFolder[0] === '.') {
                continue;
            }

            foreach (scandir("$ftpFolder/$archiveFolder") as $file) {
                if ($file[0] === '.') {
                    continue;
                }

                $filepathAbs = "$ftpFolder/$archiveFolder/$file";
                $json = json_decode(str_replace("\xEF\xBB\xBF", '', file_get_contents($filepathAbs)), true);

                $key = "$archiveFolder/$file";
                $fileLink = Link::module($file,
                    ['ps' => 'file', 'file' => "$ftpSubfolder/archive/$archiveFolder/$file"], null, null, 'class=note');
                $data[$key] = [
                    'File' => "$archiveFolder<span class=note>/<br>$fileLink</span>",
                    'CustomerAccountID' => $json['CustomerAccountID'],
                    'Name' => $json['Name'] ?? '',
                    'PaymentDate' => $json['PaymentDate'] ?? '',
                    'Amount' => $json['Amount'] ?? '',
                    'Comments' => $json['Comments'] ?? '',
                    'Lines' => json_encode($json['Lines'] ?? '', JSON_PRETTY_PRINT)
                ];

                if (isset($json['Name']) && $json['Name']) {
                    $name1 = $json['Name'];
                    $name2 = $name1 . ' (from 1С)';
                    $data[$key]['CPaymentID'] = Db::getOne("SELECT ID FROM cpayment WHERE CompanyID = @i AND (Name = @s OR Name = @s)",
                        $companyId,
                        $name1, $name2);
                }

                $hasError = false;

                if (!isset($json['Lines']) || !$json['Lines']) {
                    $data[$key]['Lines'] = 'Нет разбивки по счетам';
                    $hasError = true;
                } else {

                    if (mb_stripos($json['Comments'], 'допл') === false) {

                        $data[$key]['Lines'] = 'В разбивке ' . count($json['Lines']) . ' '
                            . Misc::getNumberEnd(count($json['Lines']), ['счёт', 'счёта', 'счетов']) . '.';
                        // Проверим наличие всех указанных счетов
                        $cinvoicesAmount = 0;
                        $currency = 'RUB';
                        $cinvoiceNames = [];
                        foreach ($json['Lines'] as $line) {

                            $cinvoiceInfo = Db::getRow("SELECT Name, Amount, CurrencyISO FROM cinvoice WHERE ID = @i",
                                $line['CInvoiceID']);
                            if (!$cinvoiceInfo) {
                                $data[$key]['Lines'] .= "\nНе удалось найти счёт №" . $line['CInvoiceName'] . ' от ' . $line['CInvoiceDate'] . "\n";
                                $hasError = true;
                            } else {
                                $cinvoicesAmount += $cinvoiceInfo['Amount'];
                                $cinvoiceNames[] = $cinvoiceInfo['Name'];
                                if ($cinvoiceInfo['CurrencyISO'] !== 'RUB') {
                                    $currency = $cinvoiceInfo['CurrencyISO'];
                                }
                            }
                        }
                        if ($cinvoicesAmount) {
                            $rate = $json['Amount'] / $cinvoicesAmount;
                            if ($json['Amount'] != $cinvoicesAmount && ($rate < 50 || $rate > 90)) {
                                $hasError = true;
                                $data[$key]['Lines'] .= "\nСумма оплаты (" . $json['Amount'] . ') не бьётся с суммой ' . (count($cinvoiceNames) > 1 ? 'счетов' : 'счеёта')
                                    . ' №' . implode(', №', $cinvoiceNames)
                                    . ' (' . $cinvoicesAmount . ' ' . $currency . ')';
                                if ($currency !== 'RUB') {
                                    $data[$key]['Lines'] .= ': курс ' . round($rate, 2);
                                }
                            }
                        }
                    }

                }

                if (!$hasError) {
                    $data[$key]['Lines'] = '';
                }

            }

        }

        krsort($data);
        $this->_tableData = array_values($data);
    }

    public function __invoke()
    {
        $moduleName = "История загрузок оплат (Customer Payments) из 1С";

        if (Request::isXls()) {

            $this->sendExcel("$moduleName.xlsx");
            ApplicationAbstract::terminate();

        }

        if (Request::isAjax()) {

            print $this->getHtmlTableForAjax();
            ApplicationAbstract::terminate();

        }

        Response::setPageTitle($moduleName);
        Response::setPageHeadHtml($moduleName);

        print $this->getFiltersHtml();
        print $this->getHtmlTable();

    }

    public static function proceedFile()
    {
        $file = $_GET['file'];
        if (strpos($file, '..')) {
            Response::abort(403);
        }

        $filepathAbs = ForwardConfig::$onecFtpFolderBase . "/cpayment/$file";
        $filename = pathinfo($filepathAbs, PATHINFO_BASENAME);
        Response::streamFileFromFilesystem($filepathAbs, $filename);

        ApplicationAbstract::terminate();
    }

}