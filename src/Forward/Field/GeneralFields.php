<?php

namespace Forward\Field;

use Forward\Application;
use Forward\Classes\BuAccess;
use Forward\Classes\Constant;
use Forward\Classes\CustomerAccess;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\Template;
use Light\User;

class GeneralFields
{
    public static function companyId()
    {
        return F::createSelect('CompanyID', [
            'name' => 'Our Company',
            'tableWidthExcel' => 30,
            'options' => "SELECT ID, Name, Active FROM ourcompany ORDER BY OrderID",
            'empty' => false,
            'linkModule' => 'OurCompany'
        ]);
    }

    public static function filterCompanyId()
    {
        return F::createSelect('CompanyID', [
            'name' => 'Our Company',
            'options' => "SELECT ID, Name, IF(Active, '', 'Inactive') as optgroup FROM ourcompany ORDER BY OrderID",
        ]);
    }

    public static function bankId()
    {
        return F::createSelectChild('BankID', [
            'name' => 'Bank',
            'tableWidthExcel' => 30,
            'options' => "SELECT ID, Name FROM ourcompanybank ORDER BY CompanyID, ID",
            'parentFieldKeys' => 'CompanyID',
            'relations' => "SELECT ID AS BankID, CompanyID FROM ourcompanybank",
        ]);
    }

    public static function trafficId()
    {
        return F::createSelect('TrafficID', [
            'name' => 'Traffic',
            'options' => "SELECT ID, Name FROM traffic ORDER BY OrderID",
            'empty' => false
        ]);
    }

    public static function customerId($editMandatory = false, $filter = true)
    {
        //Выпадающий список клиентов нужно ограничить доступом на уровне bu, поэтому сразу скачаем весь список
        //и пробежимся по нему исключая тех, к которым у текущего пользователя нет доступа
        if ($filter) {
            $select = "IF(Active, '', 'Inactive') as optgroup";
        } else {
            $select = "Active";
        }
        $query = "SELECT ID, Name, " . $select . " FROM customer ORDER BY Active DESC, Name";
        $options = Db::getAssoc($query);

        if (!BuAccess::hasAccessAll()) {
            $allowedCustomerIds = BuAccess::getCustomerIds();
            foreach ($options as $customerId => &$option) {
                //Скроем или оставим видимым данного клиента на основе того, есть ли его ID среди допустимых
                $option['Active'] = in_array($customerId, $allowedCustomerIds);
            }
            unset($option);
        }

        $noAccessCustomerIds = CustomerAccess::getNoAccessCustomerIds();
        if ($noAccessCustomerIds) {
            foreach($noAccessCustomerIds as $noAccessCustomerId) {
                if (isset($options[$noAccessCustomerId])) {
                    $options[$noAccessCustomerId]['Active'] = 0;
                }
            }
        }

        return F::createSelect('CustomerID', [
            'name' => 'Customer',
            'tableWidthExcel' => 30,
            'options' => $options,
            'linkModule' => 'Customer',
            'editMandatory' => $editMandatory,
            'select2' => true,
        ]);
    }

    public static function customerAccountId($editMandatory = false)
    {
        $options = Db::getAssoc(
            "SELECT ID, Name AS text, IF(Active, '', 'Inactive') AS optgroup
             FROM customeraccount
             ORDER BY Active DESC, Name"
        );

        if (!BuAccess::hasAccessAll()) {
            $allowedCustomerAccountIds = BuAccess::getCustomerAccountIds();
            foreach ($options as $customerAccountId => &$option) {
                //Скроем или оставим видимым данный аккаунт на основе того, есть ли его ID среди допустимых
                $option['Active'] = in_array($customerAccountId, $allowedCustomerAccountIds);
            }
            unset($option);
        }

        $noAccessCustomerAccountIds = CustomerAccess::getNoAccessCustomerAccountIds();
        if ($noAccessCustomerAccountIds) {
            foreach($noAccessCustomerAccountIds as $noAccessCustomerAccountId) {
                if (isset($options[$noAccessCustomerAccountId])) {
                    $options[$noAccessCustomerAccountId]['Active'] = 0;
                }
            }
        }

        return F::createSelectChild('CustomerAccountID', [
            'name' => 'Customer Account',
            'tableWidthExcel' => 30,
            'linkModule' => 'CustomerAccount',
            'select2' => true,
            'empty' => true,
            'options' => $options,
            'parentFieldKeys' => 'CustomerID',
            'relations' => "SELECT CustomerAccountID, CustomerID FROM customer_customeraccount",
            'editMandatory' => $editMandatory
        ]);
    }

    public static function consigneeId()
    {
        $options = Db::getAssoc("SELECT ID, Name, CustomerID FROM customerconsignee ORDER BY Name");

        if (!BuAccess::hasAccessAll()) {
            $allowedCustomerIds = BuAccess::getCustomerIds();
            foreach ($options as &$option) {
                //Скроем или оставим видимым данный аккаунт на основе того, есть ли его ID среди допустимых
                $option['Active'] = in_array($option['CustomerID'], $allowedCustomerIds);
            }
            unset($option);
        }

        $noAccessCustomerIds = CustomerAccess::getNoAccessCustomerIds();
        if ($noAccessCustomerIds) {
            foreach ($options as &$option) {
                if (in_array($option['CustomerID'], $noAccessCustomerIds)) {
                    $option['Active'] = 0;
                }
            }
            unset($option);
        }

        $options = [-1 => ['Name' => 'Совпадает с Customer Account', 'cssClass' => 'skipp']] + $options;

        return F::createSelectChild('ConsigneeID', [
            'name' => 'Consignee',
            'tableWidthExcel' => 30,
            'empty' => true,
            'select2' => true,
            'options' => $options,
            'parentFieldKeys' => 'CustomerID',
            'relations' => "SELECT ID AS ConsigneeID, CustomerID FROM customerconsignee"
        ]);
    }

    public static function vendorId($editMandatory = false, $filter = true)
    {
        if ($filter) {
            $select = "IF(Active, '', 'Inactive') as optgroup";
        } else {
            $select = "Active";
        }
        $field = F::createSelect('VendorID', [
            'name' => 'Vendor',
            'tableWidthExcel' => 30,
            'options' => "SELECT ID, Name, " . $select . " FROM vendor ORDER BY Active DESC, Name",
            'linkModule' => 'Vendor',
            'select2' => true,
        ]);

        if ($editMandatory) {
            $field->setEditMandatory('не выборан Vendor');
        }

        return $field;
    }

    public static function vendorAccountId($editMandatory = false)
    {
        $field = F::createSelectChild('VendorAccountID', [
            'name' => 'Vendor Account',
            'tableWidthExcel' => 30,
            'linkModule' => 'VendorAccount',
            'select2' => true,
            'options' => "SELECT ID, Name AS text, IF(Active, '', 'Inactive') AS optgroup
                        FROM vendoraccount
                        ORDER BY Active DESC, Name",
            'parentFieldKeys' => 'VendorID',
            'relations' => "SELECT VendorAccountID, VendorID FROM vendor_vendoraccount"
        ]);

        if ($editMandatory) {
            $field->setEditMandatory('не выборан Vendor Account');
        }

        return $field;
    }

    public static function locationIata($fieldKey, array $params = [])
    {
        $defaultParams = [
            'name' => 'Location IATA',
            'nameTable' => "Location\n(IATA)",
            'tableAlign' => 'center',
            'tableWidthHtml' => 40,
            'tableWidthExcel' => 10,
            'editMaxLength' => 3,
            F::PARAM_AC_SOURCE => '?a=Core&ac=SearchLocationIata',
            F::PARAM_AC_ONSELECTJSFUNCTION => 'Forward.onLocationIATASelect',
            F::PARAM_AC_RESULTFORMATTERJSFUNCTION => 'Forward.acLocationIATANameFormatter',
            'upper' => true,
            'sqlWhere' => "{fieldKey} = '{value}'"
        ];

        $params = array_merge($defaultParams, $params);

        return F::createString($fieldKey, $params);
    }

    public static function locationName($fieldKey, array $params = [])
    {
        $defaultParams = [
            'name' => 'Location',
            'nameTable' => "Location",
            'tableWidthExcel' => 10,
            F::PARAM_AC_SOURCE => '?a=Core&ac=SearchLocationName',
            F::PARAM_AC_ONSELECTJSFUNCTION => 'Forward.onLocationNameSelect',
            F::PARAM_AC_RESULTFORMATTERJSFUNCTION => 'Forward.acLocationIATANameFormatter',
            'upper' => true,
            'sqlWhere' => "{fieldKey} = '{value}'"
        ];

        $params = array_merge($defaultParams, $params);

        return F::createString($fieldKey, $params);
    }

    public static function departureIata()
    {
        return GeneralFields::locationIata(
            'DepartureIATA',
            [
                'name' => 'Departure IATA',
                'nameTable' => "Departure\n(IATA)"
            ]
        );
    }

    public static function departureLocationName()
    {
        return GeneralFields::locationName(
            'DepartureLocationName',
            [
                'name' => 'Departure',
                'nameTable' => 'Departure'
            ]
        );
    }

    public static function arrivalIata()
    {
        return GeneralFields::locationIata(
            'ArrivalIATA',
            [
                'name' => 'Arrival IATA',
                'nameTable' => "Arrival\n(IATA)"
            ]
        );
    }

    public static function arrivalLocationName()
    {
        return GeneralFields::locationName(
            'ArrivalLocationName',
            [
                'name' => 'Arrival',
                'nameTable' => 'Arrival',
            ]
        );
    }

    public static function incoterms()
    {
        return F::createSelect(
            'Incoterms',
            [
                'name' => 'Incoterms',
                'options' => "SELECT Name AS K, Name FROM incoterm ORDER BY OrderID, Name",
                'empty' => true,
                'tableAlign' => 'center'
            ]
        );
    }

    public static function incotermsLocation()
    {
        return CommonFields::incotermsLocation()
            ->setUpper(true)
            ->setAcSource('?a=Core&ac=SearchIncotermsLocation');
    }

    public static function airlineAlphaCode()
    {
        return F::createSelect('AirlineAlphaCode', [
            'name' => 'Airline',
            'tableWidthExcel' => 20,
            'editWidth' => 'default',
            'select2' => true,
            'options' => "SELECT AlphaCode, CONCAT(Name, ' (', AlphaCode, ')') AS Name FROM airline WHERE AlphaCode != '' ORDER BY Name"
        ]);
    }

    public static function fileNumber($checkByShipmentId = false)
    {
        $field = F::createString('FileNumber', [
            'name' => 'File Number',
            'title' => 'Shipment',
            'copy' => true,
            'groupconcatTruncate' => true,
            'groupconcat' => true,
            'editCssClass' => 'file-no input-int input-left',
            'tableAlign' => 'center',
            'tableWidthHtml' => 68,
            'tableWidthExcel' => 20,
            F::PARAM_AC_SOURCE => '?a=Core&ac=SearchShipment',
            F::PARAM_AC_ONSELECTJSFUNCTION => 'Forward.onFileNumberSelect',
            F::PARAM_AC_RESULTFORMATTERJSFUNCTION => 'Forward.acMawbFileResultFormatter'
        ]);

        //Если передан специальный параметр, добавим метод проверяющий по наличию поля ShipmentID
        //что этот файл существует в базе
        if ($checkByShipmentId) {
            $field->setParam(
                'viewCssClass',
                function ($value, array $data) {
                    if (array_key_exists('ShipmentID', $data) && empty($data['ShipmentID'])) {
                        return 'bad';
                    }

                    return '';
                }
            );

            $field->setParam(
                'viewTitle',
                function ($value, array $data, $forTable) {
                    if (array_key_exists('ShipmentID', $data) && empty($data['ShipmentID'])) {
                        return 'Shipment not found';
                    }

                    return '';
                }
            );
        }

        return $field;
    }

    public static function mawbNumber()
    {
        return F::createString('MAWBNumber', [
            'name' => 'MAWB',
            'copy' => true,
            'editCssClass' => 'mawb-no',
            'tableWidthHtml' => 105,
            'tableWidthExcel' => 25,
            'source' => '?a=Core&ac=SearchMawb',
            'config' => [
                'onSelectFunction' => 'Forward.onMAWBSelect',
                'resultFormatterFunction' => 'Forward.acMawbFileResultFormatter'
            ]
        ]);
    }

    public static function containerWeight()
    {
        return F::createFloat('ContainerWeight', [
            'name' => "Container\nWeight (kg)",
            'title' => 'Container Weight (kg)'
        ]);
    }

    public static function containerVolume()
    {
        return F::createFloat('ContainerVolume', [
            'name' => "Container\nVolume (m3)",
            'title' => 'Container Volume (m3)'
        ]);
    }

    public static function quantity()
    {
        return F::createFloat('Quantity', [
            'name' => 'Qty.',
            'default' => 1,
            'copy' => true,
            'editWidth' => 'mini',
            'tableWidthHtml' => 50,
            'tableAlign' => 'center',
            /*
            'getDataFromRequestFunction' => function ($value) {
                if ($value !== null) {
                    //Округлим в большую сторону до 0.5
                    $floor = floor($value);
                    $decimal = $value - $floor;
                    if ($decimal > 0) {
                        if ($decimal <= 0.5) {
                            $value = $floor + 0.5;
                        } else {
                            $value = $floor + 1;
                        }
                    }
                }

                return $value;
            }
            */
        ]);
    }

    public static function consigneeReference()
    {
        return F::createString('ConsigneeReference', [
            'name' => 'Consignee Ref.',
            'title' => 'Consignee Reference'
        ]);
    }

    public static function buIds()
    {
        $defaultBu = '';
        if (User::hasResponsibleRole(Application::ROLE_BUMOW)) {
            $defaultBu = '|' . Constant::BU_MOW . '|';
        } elseif (User::hasResponsibleRole(Application::ROLE_BULED)) {
            $defaultBu = '|' . Constant::BU_LED . '|';
        }

        return F::createCheckboxSet('BUIDs', [
            'name' => 'Access BU',
            'options' => [Constant::BU_MOW => 'MOW', Constant::BU_LED => 'LED'],
            'default' => $defaultBu
        ]);
    }

    public static function buIdsFilter()
    {
        return F::createSelect('BUIDsFilter', [
            'name' => 'BU',
            'options' => [Constant::BU_MOW => 'MOW', Constant::BU_LED => 'LED'],
            'sqlWhere' => "BUIDs LIKE '%|{value}|%'"
        ]);
    }

    public static function natureOfGoods()
    {
        return F::createString('NatureOfGoods', ['name' => 'Nature of Goods', 'upper' => true]);
    }

    public static function transitTime()
    {
        return F::createString('TransitTime', [
            'name' => 'Transit Time',
            'editWidth' => 'mini',
            'uom' => 'days',
            'tableAlign' => 'center'
        ]);
    }

    public static function responsibleSalesId()
    {
        return F::createSelect('ResponsibleSalesID', [
            'name' => 'Sales',
            'options' => "SELECT ID, Name, IF(Active AND LOCATE('|" . Application::ROLE_SALES . "|', ResponsibleRoles), 1, 0) AS Active FROM user ORDER BY Name",
            'tableWidthExcel' => 24
        ]);
    }

    public static function responsibleCsId()
    {
        return F::createSelect('ResponsibleCSID', [
            'name' => 'Customer Service',
            'options' => "SELECT ID, Name, IF(Active AND LOCATE('|" . Application::ROLE_CS . "|', ResponsibleRoles), 1, 0) AS Active FROM user ORDER BY Name",
            'tableWidthExcel' => 24
        ]);
    }

    public static function ratePercent()
    {
        return F::createSelect('RatePercent', [
            'name' => 'CBRF Rate',
            'empty' => true,
            'options' => [
                '0%' => '+0%',
                '1%' => '+1%',
                '1,5%' => '+1,5%',
                '2%' => '+2%',
                '3%' => '+3%'
            ]
        ]);
    }

    public static function shipmentRequestZip()
    {
        return F::createString('ShipmentRequestZIP',
            ['name' => 'Индекс', 'nameTable' => 'Заявка: Индекс', 'editMaxLength' => 20, 'upper' => true]);
    }

    public static function shipmentRequestStreet()
    {
        return F::createString('ShipmentRequestStreet',
            ['name' => 'Улица', 'nameTable' => 'Заявка: Улица', 'upper' => true]);
    }

    public static function shipmentRequestCity()
    {
        return F::createString('ShipmentRequestCity',
            ['name' => 'Город', 'nameTable' => 'Заявка: Город', 'upper' => true]);
    }

    public static function shipmentRequestCountry()
    {
        return F::createString('ShipmentRequestCountry',
            ['name' => 'Страна', 'nameTable' => 'Заявка: Страна', 'upper' => true]);
    }

    public static function shipmentRequestPhone()
    {
        return F::createString('ShipmentRequestPhone',
            ['name' => 'Телефон', 'nameTable' => 'Заявка: Телефон', 'upper' => true]);
    }

    public static function cactName()
    {
        return F::createString('CActName', [
            'name' => 'Акт на клиента',
            'editWidth' => 'medium',
        ]);
    }

    public static function cactDate()
    {
        return F::createDate('CActDate', [
            'name' => 'Дата акта',
        ]);
    }

    public static function cactAmount()
    {
        return F::createMoney('CActAmount', [
            'name' => 'Сумма акта',
            'zeroEmpty' => true,
        ]);
    }

    /**
     * @param string $prefix либо пусто, либо Departure, либо Arrival
     * @return \Closure
     */
    public static function filterTemplateLocation(string $prefix)
    {
        return function () use ($prefix) {
            return '<span title="IATA">' . Template::html($prefix . 'IATA') . '</span>'
                . '<span title="Location">' . Template::html($prefix . 'LocationName') . '</span>';
        };
    }

    public static function docsAddress()
    {
        return F::createText('DocsAddress', [
            'name' => 'Адрес доставки документов',
        ]);
    }

    public static function deliveryMethod()
    {
        return F::createSelect('DeliveryMethod', [
            'name' => 'Способ отправки документов',
            'empty' => true,
            'tableWidthExcel' => 25,
            'options' => [
                1 => 'Отправка почтой',
                2 => 'Отправка курьером',
                3 => 'Клиент забирает самостоятельно',
            ]
        ]);
    }
}
