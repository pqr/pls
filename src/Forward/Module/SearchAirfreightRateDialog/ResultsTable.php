<?php

namespace Forward\Module\SearchAirfreightRateDialog;

use Forward\Classes\Constant;
use Forward\Field\GeneralFields;
use Forward\Module\AirfreightRate\AirfreightRate;
use Forward\Module\AirfreightSurcharge\AirfreightSurcharge;
use Light\Currency;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Link;
use Light\Table;
use Light\Traits\TableFilterable;
use Light\User;

class ResultsTable extends Table
{
    use TableFilterable;

    public function __construct()
    {
        parent::__construct('SearchAirfreightRateDialogResultsTable');

        $this->tableReloadUrl = '?a=SearchAirfreightRateDialog&action=ajaxSearch';

        $this->proceedFilters();
        $chargeableWeightFilterValue = $this->getFilterValue('ChargeableWeight');

        $this->getFieldsetFirst()->addField(F::createHtml('Rb')
            ->setName('')
            ->setGetHtmlFunction(function ($value, array $data) {
                $value = json_encode($data);
                return '<input type="radio" name="rb" value="' . htmlspecialchars($value) . '" class="rb">';
            }));

        $this->addFields(
            GeneralFields::vendorId(false, false),
            GeneralFields::departureIata(),
            GeneralFields::arrivalIata(),
            GeneralFields::airlineAlphaCode()->setOptions("SELECT AlphaCode, AlphaCode FROM airline")->setTableAlign('center'),
            GeneralFields::transitTime(),
            CF::validFrom(),
            CF::validUntil()
        );

        if ($chargeableWeightFilterValue) {
            $this->addField(F::createMoney('FreightAmount',
                ['name' => 'Freight Amount', 'uom' => 'USD', 'sqlSelect' => '']));
        }

        $this->addField(F::createHtml('Surcharges')
            ->setName('Locals')
            ->setGetHtmlFunction(function ($value, array $data) {
                $outHtml = [];

                if ($data['FSCAmount'] != 0) {
                    $outHtml[] = '<span title="За кг ' . $data['FSCAmount'] . ' ' . $data['CurrencyISO'] . '">FSC</span>';
                }

                if ($data['SSCAmount'] != 0) {
                    $outHtml[] = '<span title="За кг ' . $data['SSCAmount'] . ' ' . $data['CurrencyISO'] . '">SSC</span>';
                }

                foreach ($value as $surcharge) {
                    $title = $surcharge['Name'] . ": ";

                    $amountsTitle = [];
                    if ($surcharge['BaseAmount'] !== '0.00') {
                        $amountsTitle[] = 'Base ' . $surcharge['BaseAmount'];
                    }
                    if ($surcharge['MinAmount'] !== '0.00') {
                        $amountsTitle[] = 'Min ' . $surcharge['MinAmount'];
                    }
                    if ($surcharge['MaxAmount'] !== '0.00') {
                        $amountsTitle[] = 'Max ' . $surcharge['MaxAmount'];
                    }
                    if ($surcharge['PerKgAmount'] !== '0.00') {
                        $amountsTitle[] = 'Per Kg ' . $surcharge['PerKgAmount'];
                    }
                    $title .= implode(', ', $amountsTitle);
                    $title .= ' ' . $surcharge['CurrencyISO'];

                    if ($surcharge['Comments']) {
                        $title .= ' (' . $surcharge['Comments'] . ')';
                    }

                    $html = '<span title="' . htmlspecialchars($title) . '">'
                        . htmlspecialchars($surcharge['Name'])
                        . '</span>';

                    //Если у пользователя есть доступ к модулю редактирования Airfreight Surcharges, то сделаем ссылку!
                    if (User::hasReadAccess(AirfreightSurcharge::class)) {
                        $html = Link::create(AirfreightSurcharge::class, $html)
                            ->view($surcharge['ID'])
                            ->blank()
                            ->getHtml();
                    }

                    $outHtml[] = $html;
                }
                return implode(', ', $outHtml);
            }));

        if ($chargeableWeightFilterValue) {
            $this->addField(F::createMoney('TotalAmount', ['name' => 'Total incl.Surcharges', 'uom' => 'USD']));
        }

        $this->addField(F::createText('Comments'));

        //Если у пользователя есть доступ к модулю Airfreight Rates, то добавим ссылку на просмотр этой ставки
        if (User::hasReadAccess('AirfreightRate')) {
            $this->getFieldsetLast()->addField(
                F::createHtml('AirfreightRateLink')
                    ->setName('')
                    ->setGetHtmlFunction(fn($value, array $data) => Link::create(AirfreightRate::class, 'View')
                        ->view($data['ID'])
                        ->blank()
                        ->getHtml()));
        }

        //Сразу загружаем данные
        $query = "SELECT * 
                  FROM airfreightrate 
                  WHERE " . $this->getFiltersSqlWhere() . " 
                  ORDER BY ID DESC 
                  LIMIT 50";

        $this->tableData = Db::getAll($query);

        //Загрузим из базы Surcharges - они будут добавлены внутрь массива $this->tableData в колонку Surcharges
        $this->loadSurcharges();

        //Если на экран выведено поле TotalAmount, то нужно посчитать этот TotalAmount
        if ($chargeableWeightFilterValue) {
            $interval = Constant::getWeightInterval($chargeableWeightFilterValue);
            $amountFieldKey = $interval ? 'Amount' . $interval : 'AmountN';
            foreach ($this->tableData as $i => $rowData) {
                $this->tableData[$i]['FreightAmount'] = $this->calculateFreightAmount($rowData,
                    $chargeableWeightFilterValue, $amountFieldKey);
                $this->tableData[$i]['TotalAmount'] = $this->calculateTotalAmount($rowData,
                    $chargeableWeightFilterValue, $amountFieldKey);
            }
        }
    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;

        $this->addFilters(
            GeneralFields::vendorId(false, true),
            GeneralFields::airlineAlphaCode(),
            GeneralFields::departureIata(),
            GeneralFields::arrivalIata(),

            // поля incoterms нет в таблице airfreightrate - на него не нужно накладывать where условия в sql запросе
            CF::incoterms()
                ->setEmpty(false)
                ->setSqlWhere(''),

            F::createDate('ValidDate')->setName('Validity')->setSqlWhere("ValidFrom <= '{value}' AND ValidUntil >= '{value}'"),

            CF::chargeableWeight()
                ->setSqlWhere('')
        );

        $this->filtersLayout = [
            [
                'VendorID',
                'AirlineAlphaCode',
                'ValidDate'
            ],
            [

                'DepartureIATA',
                'ArrivalIATA',
                'Incoterms',
                'ChargeableWeight',
            ]
        ];
    }

    private function loadSurcharges(): void
    {
        $baseQuery = "SELECT ID, Name, Comments, BaseAmount, MinAmount, MaxAmount, PerKgAmount, CurrencyISO
                      FROM airfreightsurcharge
                      WHERE 1";

        //Сначала наложим общие sql where условия не зависящие от конкретной строки
        $validDateSqlWhere = $this->getFilterSqlWhere('ValidDate');
        if ($validDateSqlWhere) {
            $baseQuery .= " AND " . $validDateSqlWhere;
        }

        $incotermsValue = $this->getFilterValue('Incoterms');
        if ($incotermsValue !== '%') {
            $baseQuery .= " AND (Incoterms = '' OR Incoterms = '" . Db::esc($incotermsValue) . "')";
        }

        //Накладываем условия специфичные для найденной строки
        foreach ($this->tableData as $rowNum => $rowData) {
            $query = $baseQuery;

            //Добавим условия на совпадение Vendor, Airline и Location с текущей строкой
            $query .= " AND (VendorID = 0 OR VendorID = " . $rowData['VendorID'] . ")";
            $query .= " AND (AirlineAlphaCode = '' OR AirlineAlphaCode = '" . $rowData['AirlineAlphaCode'] . "')";
            $query .= " AND (IATA = '' OR IATA = '" . $rowData['DepartureIATA'] . "' OR IATA = '" . $rowData['ArrivalIATA'] . "')";

            $this->tableData[$rowNum]['Surcharges'] = Db::getAll($query);
        }
    }

    private function calculateFreightAmount($rowData, $chargeableWeight, $amountFieldKey)
    {
        //Какую цену применить как основную?
        $freightAmount = $rowData[$amountFieldKey] * $chargeableWeight;

        //Если итоговая сумма меньше меньшего - нужно использовать минимальную сумму
        if ($freightAmount < $rowData['MinAmount']) {
            $freightAmount = $rowData['MinAmount'];
        }

        $freightAmountInUsd = Currency::convertValue($freightAmount, $rowData['CurrencyISO'], 'USD');

        return $freightAmountInUsd;
    }

    private function calculateTotalAmount(array $rowData, $chargeableWeight, $amountFieldKey)
    {
        //Какую цену применить как основную?
        $totalAmount = $rowData[$amountFieldKey] * $chargeableWeight;

        //Если итоговая сумма меньше меньшего - нужно использовать минимальную сумму
        if ($totalAmount < $rowData['MinAmount']) {
            $totalAmount = $rowData['MinAmount'];
        }

        //Добавим FSC и SSC
        $totalAmount += ($rowData['FSCAmount'] * $chargeableWeight);
        $totalAmount += ($rowData['SSCAmount'] * $chargeableWeight);

        $totalAmount = Currency::convertValue($totalAmount, $rowData['CurrencyISO'], 'USD');

        //Теперь добавим все надбавки
        /** @noinspection ForeachSourceInspection */
        foreach ($rowData['Surcharges'] as $surcharge) {
            $base = (double)$surcharge['BaseAmount'];
            $price = (double)$surcharge['PerKgAmount'];
            $surchAmount = $base + $price * $chargeableWeight;

            $minAmount = (double)$surcharge['MinAmount'];
            $maxAmount = (double)$surcharge['MaxAmount'];

            if ($surchAmount < $minAmount) {
                $surchAmount = $minAmount;
            } elseif ($maxAmount > 0 && $surchAmount > $maxAmount) {
                $surchAmount = $maxAmount;
            }

            $surchAmountInUsd = Currency::convertValue($surchAmount, $surcharge['CurrencyISO'], 'USD');

            $totalAmount += $surchAmountInUsd;
        }

        return $totalAmount;
    }
}
