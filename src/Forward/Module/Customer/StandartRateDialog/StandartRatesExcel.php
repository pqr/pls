<?php


namespace Forward\Module\Customer\StandartRateDialog;


use Forward\Classes\Constant;
use Forward\Field\GeneralFields;
use Forward\Field\RateFields;
use Light\A;
use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\Table;
use Light\Traits\TableExcelExportable;
use Light\XlsWriter;

class StandartRatesExcel extends Table
{
    use TableExcelExportable;

    public function __construct(int $customerId, array $rateIds)
    {
        parent::__construct();

        $this->addFields(
            F::createString('AirlineAlphaCode', ['name' => 'Carrier', 'tableAlign' => 'center']),
            GeneralFields::departureIata(),
            CF::incoterms(),
            RateFields::minAmount('Selling'),
            RateFields::amount100('Selling'),
            RateFields::amount100('Selling'),
            RateFields::amount300('Selling'),
            RateFields::amount500('Selling'),
            RateFields::amount1000('Selling'),
            CF::currency('Selling')->setNameTable('Currency'),
            GeneralFields::arrivalIata(),
            F::createText('LocalCharges', ['name' => 'Local charges']),
            CF::validFrom(),
            CF::validUntil(),
            F::createText('CommentsCustomer', ['name' => 'Comments'])
        );

        $this->loadData($customerId, $rateIds);
    }

    private function loadData(int $customerId, array $rateIds)
    {
        $data = Db::getAll(
            "SELECT *
            FROM customer_standartrates
            WHERE CustomerID = $customerId
               AND ID IN (@a)
            ORDER BY ID"
            , $rateIds
        );

        $localCharges = Db::getAll(
            "SELECT * 
            FROM customer_standratrates_sellingsurcharges
            WHERE CustomerStandartRateID IN (@a)
            ORDER BY ID"
            , $rateIds
        );
        $localChargesPerRateId = A::groupBy($localCharges, 'CustomerStandartRateID');

        // Дополнительно приклеим LocalCharges
        foreach ($data as &$row) {
            $rateId = $row['ID'];
            if (!isset($localChargesPerRateId[$rateId])) {
                continue;
            }

            $lines = [];
            foreach ($localChargesPerRateId[$rateId] as $localCharge) {
                $line = $localCharge['Name'] . ' ' . $localCharge['CurrencyISO'] . ': ';
                $baseParts = [];
                if ($localCharge['BaseAmount'] != 0) {
                    $baseParts[] = $localCharge['BaseAmount'] . ' per shipment';
                }
                if ($localCharge['PerKgAmount'] != 0) {
                    $baseParts[] = $localCharge['PerKgAmount'] . ' per kg';
                }
                $line .= implode(' + ', $baseParts);

                $minMaxParts = [];
                if ($localCharge['MinAmount'] != 0) {
                    $minMaxParts[] = 'Min: ' . $localCharge['MinAmount'];
                }
                if ($localCharge['MaxAmount'] != 0) {
                    $minMaxParts[] = 'Max: ' . $localCharge['MaxAmount'];
                }
                if ($minMaxParts) {
                    $line .= ' (' . implode('; ', $minMaxParts) . ')';
                }
                $lines[] = $line;
            }

            $row['LocalCharges'] = implode("\n", $lines);
        }
        unset($row);

        $this->_tableData = $data;
    }

    /**
     * Переопределяем метод, чтобы подставить таблицу в шаблон
     * @param string $customerName
     * @param int $companyId
     * @param string $lang
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function sendStandartRatesExcel(string $customerName, int $companyId, string $lang)
    {
        if ($companyId === Constant::OC_PGL_OOO || $companyId === Constant::OC_PGL11_OOO) {
            $companyName = 'pgl';
        } else {
            $companyName = 'pls';
        }

        if ($lang != 'ru' && $lang != 'en') {
            $lang = 'en';
        }

        $template = __DIR__ . "/standartrates_{$companyName}_{$lang}.xlsx";
        $xls = new XlsWriter($template);
        $xls->insertTable(1, 6, $this);
        $xls->replaceData(['CustomerName' => $customerName]);

        $filename = "Standart Rates for $customerName (" . date('Y-m-d') . ").xlsx";
        $xls->send($filename);
    }


}
