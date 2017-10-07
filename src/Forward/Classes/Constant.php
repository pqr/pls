<?php
namespace Forward\Classes;

use Forward\Application;
use Light\A;
use Light\Db;

class Constant
{
    const USERGROUP_FIN = 1;
    const USERGROUP_CS = 2;
    const USERGROUP_SALES = 3;

    const RESPONSIBLEROLE_CS = 2;
    const RESPONSIBLEROLE_SALES = 3;

    const OC_PLS = 1;
    const OC_PLS_USD = 13;
    const OC_GTS = 2;
    const OC_PLS_OOO = 4;
    const OC_PGL_OOO = 5;
    const OC_ACB = 7;
    const OC_PLS12_OOO = 12;
    const OC_PGL11_OOO = 11;

    const TRAFFIC_OCEAN = 7;

    const BU_MOW = '1';
    const BU_LED = '2';

    public static $onecCompany = [
        Constant::OC_PLS_OOO => [
            'onecUrl' => 'http://192.168.55.2/pls',
            'ftpSubfolder' => 'pls'
        ],
        Constant::OC_PGL_OOO => [
            'onecUrl' => 'http://192.168.55.2/pgl',
            'ftpSubfolder' => 'pgl'
        ],
        Constant::OC_PLS12_OOO => [
            'onecUrl' => 'http://192.168.55.2/pls12',
            'ftpSubfolder' => 'pls12'
        ],
        Constant::OC_PGL11_OOO => [
            'onecUrl' => 'http://192.168.55.2/pgl11',
            'ftpSubfolder' => 'pgl11'
        ]
    ];

    public static $standardFolders = [
        '0 Заявка',
        '1 Накладная',
        '2 Входящий счёт',
        '3 Акт без подписи',
        '4 Акт подписанный',
        '5 Акт от нерезидента',
    ];

    public static function isOneCCompany(?int $companyId)
    {
        return isset(self::$onecCompany[$companyId]);
    }

    /**
     * Возвращает массив с шаблонными переменными для вывода информации о компании в различные печатные формы
     * @param int $companyId
     * @param int $bankId
     * @return array
     */
    public static function getCompanyTplData($companyId, $bankId)
    {
        $company = Db::getRow(
            "SELECT Name, Address FROM ourcompany WHERE ID = @i",
            $companyId
        );
        $bank = Db::getRow(
            "SELECT BeneficiaryBank, CorrespondentBank FROM ourcompanybank WHERE ID = @i",
            $bankId
        );
        return [
            'OurCompanyName_raw' => str_replace(['(USD)', '(EUR)'], '', $company['Name']),
            'OurCompanyAddress_raw' => $company['Address'],
            'OurCompanyBeneficiaryBank_raw' => $bank['BeneficiaryBank'] ?? '',
            'OurCompanyCorrespondentBank_raw' => $bank['CorrespondentBank'] ?? ''
        ];
    }

    public static function generageQuotationName(
        $date,
        $departureIata,
        $arrivalIata,
        $chargeableWeight,
        $airlineAplhaCode
    ) {
        $parts = [
            (new \DateTime($date))->format('dmy'),
            $departureIata . $arrivalIata
        ];

        if ($chargeableWeight) {
            $interval = Constant::getWeightInterval($chargeableWeight);
            $parts[] = $interval ? 'Q' . $interval : 'MIN';
        }

        $parts[] = $airlineAplhaCode;

        return implode('/', $parts);
    }

    public static function getWeightInterval($weight)
    {
        return A::find([2000, 1000, 750, 500, 300, 200, 100, 45], function($interval) use ($weight) {
            return $weight >= $interval;
        });
    }

    public static function getDefaultAddons()
    {
        //Загрузим из system_params
        $addons = Application::getParam('DefaultAddons');
        if ($addons) {
            $addons = json_decode($addons, true);
        }

        if (!is_array($addons)) {
            $addons = [];
        }

        return $addons;
    }
}
