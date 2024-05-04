<?php

namespace Forward\Classes;

use Forward\Application;
use Light\A;
use Light\Db;
use Light\User;

class Constant
{
    public const USERGROUP_FIN = 1;
    public const USERGROUP_CS = 2;
    public const USERGROUP_SALES = 3;

    public const RESPONSIBLEROLE_CS = 2;
    public const RESPONSIBLEROLE_SALES = 3;

    public const OC_PLS = 1;
    public const OC_PLS_USD = 13;
    public const OC_GTS = 2;
    public const OC_PLS_OOO = 4;
    public const OC_PGL_OOO = 5;
    public const OC_ACB = 7;
    public const OC_PLS12_OOO = 12;
    public const OC_PGL11_OOO = 11;
    public const OC_PF14_OOO = 14;
    public const OC_MAXVEL = 16;
    public const OC_PLS17_OOO = 17;
    public const OC_PGL18_OOO = 18;
    public const OC_PRO_AIR_OOO = 19;
    public const OC_PRO_AIR_SPB = 21;

    public const TRAFFIC_OCEAN = 7;
    public const TRAFFIC_FINANCE = 11;

    public const BU_MOW = '1';
    public const BU_LED = '2';

    public const ACTONDATETYPE_ATA = 1;
    public const ACTONDATETYPE_CPAYMENT = 2;

    public const USER_PETR = 28;
    public const USER_OLGA = 29;
    public const USER_ALEXANDRA = 25;

    public const USER_START_DATE = [
        self::USER_PETR => '2022-07-01',
        self::USER_OLGA => '2022-08-01',
        self::USER_ALEXANDRA => '2024-01-01',
    ];

    public static $onecCompany = [
        Constant::OC_PLS_OOO => 'pls',
        Constant::OC_PGL_OOO => 'pgl',
        Constant::OC_PLS12_OOO => 'pls12',
        Constant::OC_PGL11_OOO => 'pgl11',
        Constant::OC_PF14_OOO => 'pf14',
        Constant::OC_PLS17_OOO => 'pls17',
        Constant::OC_PGL18_OOO => 'pgl18',
        Constant::OC_PRO_AIR_OOO => 'uma',
        Constant::OC_PRO_AIR_SPB => 'airspb',
    ];

    public const FOLDER_REQUEST = 0;
    public const FOLDER_REQUEST_SIGNED = 1;
    public const FOLDER_CINVOICE = 2;
    public const FOLDER_MAWB = 3;
    public const FOLDER_VINVOICE = 4;
    public const FOLDER_CACT = 5;
    public const FOLDER_CACT_SIGNED = 6;
    public const FOLDER_CACT_NOTRESIDENT = 7;

    public static $standardFolders = [
        '0 Заявка',
        '1 Подписанная заявка',
        '2 Счёт',
        '3 Накладная', // 1 Накладная
        '4 Входящий счёт', // 2 Входящий счёт
        '5 Акт без подписи', // 3 Акт без подписи
        '6 Акт подписанный', // 4 Акт подписанный
        '7 Акт от нерезидента', // 5 Акт от нерезидента
    ];

    public static function getCreatedDateSqlWhere(): string
    {
        $startDate = self::USER_START_DATE[User::getId()] ?? null;
        if (!$startDate) {
            return 'TRUE';
        }

        return "CreatedDateTime >= '$startDate'";
    }

    public static function getStarndartFolderCAct()
    {
        return self::$standardFolders[self::FOLDER_CACT];
    }

    public static function getStandartFolderReqeust()
    {
        return self::$standardFolders[self::FOLDER_REQUEST];
    }

    public static function isOneCCompany(?int $companyId)
    {
        return isset(self::$onecCompany[$companyId]);
    }

    /**
     * Возвращает массив с шаблонными переменными для вывода информации о компании в различные печатные формы
     *
     * @param int $companyId
     * @param int $bankId
     *
     * @return array
     */
    public static function getCompanyTplData($companyId, $bankId)
    {
        $company = Db::getRow(
            "SELECT Name, Address FROM ourcompany WHERE ID = @i",
            $companyId,
        );
        $bank = Db::getRow(
            "SELECT BeneficiaryBank, CorrespondentBank FROM ourcompanybank WHERE ID = @i",
            $bankId,
        );

        return [
            'OurCompanyName_raw' => self::getCompanyNameBeforePartnthesis($company['Name'] ?? ''),
            'OurCompanyAddress_raw' => $company['Address'] ?? '',
            'OurCompanyBeneficiaryBank_raw' => $bank['BeneficiaryBank'] ?? '',
            'OurCompanyCorrespondentBank_raw' => $bank['CorrespondentBank'] ?? '',
        ];
    }

    public static function getCompanyNameBeforePartnthesis(string $companyName): string
    {
        $parPos = strpos($companyName, '(');
        if ($parPos) {
            $companyName = trim(substr($companyName, 0, $parPos));
        }

        return $companyName;
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
            $departureIata.$arrivalIata,
        ];

        if ($chargeableWeight) {
            $interval = Constant::getWeightInterval($chargeableWeight);
            $parts[] = $interval ? 'Q'.$interval : 'MIN';
        }

        $parts[] = $airlineAplhaCode;

        return implode('/', $parts);
    }

    public static function getWeightInterval($weight)
    {
        return A::find(
            [2000, 1000, 750, 500, 300, 200, 100, 45],
            fn($interval) => $weight >= $interval,
        );
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
