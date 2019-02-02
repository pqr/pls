<?php

namespace Light\Field;

use Light\Config;
use Light\Field\FieldAbstract as F;
use Light\Incoterms;
use Light\Misc;
use Light\User;

class CommonFields
{
    const VAT_EMPTY = -2;
    const VAT_BEZNDS = -1;
    const VAT_0 = 0;
    const VAT_18 = 18;
    const VAT_20 = 20;

    const VAT_OPTIONS = [
        self::VAT_EMPTY => '',
        self::VAT_BEZNDS => 'Без НДС',
        self::VAT_0 => '0%',
        self::VAT_18 => '18%',
        self::VAT_20 => '20%',
    ];

    public static function getFullVatPercent(string $date): int
    {
        if ($date < '2019-01-01') {
            return self::VAT_18;
        }
        return self::VAT_20;
    }

    public static function id(array $params = []): FieldInt
    {
        $defaultParams = ['modes' => F::MODE_LIST, 'tableAlign' => 'left', 'tableWidthHtml' => 24];
        $params = array_merge($defaultParams, $params);

        return F::createInt('ID', $params);
    }

    public static function color(array $params = []): FieldColor
    {
        $defaultParams = [
            'name' => Config::$lang === 'ru' ? 'Цвет' : 'Color',
            'modes' => F::MODE_ALL & ~F::MODE_LIST,
            'sqlSelectMandatory' => true
        ];
        $params = array_merge($defaultParams, $params);
        return F::createColor('Color', $params);
    }

    public static function active(array $params = []): FieldSelect
    {
        $defaultParams = [
            'name' => Config::$lang === 'ru' ? 'Активный?' : 'Active?',
            'tableAlign' => 'center',
            'empty' => false,
            'editWidth' => 'small',
            'tableWidthExcel' => 10,
            'options' => [
                1 => ['text' => Config::$lang === 'ru' ? 'Активный' : 'Active', 'cssClass' => 'label-active'],
                0 => ['text' => Config::$lang === 'ru' ? 'Неактивный' : 'Inactive', 'cssClass' => 'label-inactive']
            ]
        ];

        return F::createSelect('Active', $params + $defaultParams);
    }

    public static function validFrom(array $params = []): FieldDate
    {
        $defaultParams = [
            'name' => Config::$lang === 'ru' ? 'Дата начала' : 'Valid From',
        ];
        $params = array_merge($defaultParams, $params);
        return F::createDate('ValidFrom', $params);
    }

    public static function validUntil(array $params = []): FieldDate
    {
        $defaultParams = [
            'name' => Config::$lang === 'ru' ? 'Дата окончания' : 'Valid Until',
            'getDataFromRequestFunction' => function ($value) {
                //Если пользователь не ввёл даты и засабмитил пустое поле Valid Until, первратим его в 2099-01-01
                if ($value !== null && !$value) {
                    $value = '2099-01-01';
                }

                return $value;
            }
        ];
        $params = array_merge($defaultParams, $params);
        return F::createDate('ValidUntil', $params);
    }

    /**
     * @deprecated не удобно то, что при использовании этой функции в месте вызова не видно полное имя поля - невозможно найти поиском по тексту
     * @param string $prefix
     * @param array $params
     * @return FieldMoney
     */
    public static function amount($prefix = '', array $params = []): FieldMoney
    {
        $fieldKey = $prefix . 'Amount';
        $defaultParams = [];
        if ($prefix !== '') {
            $defaultParams['name'] = $prefix . ' Amount';
        }

        $params = array_merge($defaultParams, $params);

        return F::createMoney($fieldKey, $params);
    }

    /**
     * не удобно то, что при использовании этой функции в месте вызова не видно полное имя поля - невозможно найти поиском по тексту
     * @param string $prefix
     * @param array $params
     * @return FieldMoneyWithCurrency
     * @deprecated
     */
    public static function amountWithCurrency($prefix = '', array $params = []): FieldMoneyWithCurrency
    {
        $fieldKey = $prefix . 'Amount';
        $defaultParams = [];
        if ($prefix !== '') {
            $defaultParams['name'] = $prefix . ' Amount';
        }

        $params = array_merge($defaultParams, $params);

        return F::createMoneyWithCurrency($fieldKey, $params);
    }

    /**
     * @param string $prefix
     * @param array $params
     * @param string $postfix
     * @return \Light\Field\FieldSelect
     */
    public static function currency($prefix = '', array $params = [], $postfix = ''): FieldSelect
    {
        $fieldKey = $prefix . 'CurrencyISO' . $postfix;
        $defaultParams = [
            'tableAlign' => 'center',
            'tableWidthHtml' => 60,
            'empty' => false,
            'copy' => true,
            'options' => "SELECT Name AS K, Name FROM currency ORDER BY OrderID, Name",
            'editCssClass' => 'select-currency'
        ];

        if (!isset($params['name'])) {

            $name = Config::$lang === 'ru' ? 'Валюта' : 'Currency';
            $nameTable = Config::$lang === 'ru' ? 'Валюта' : 'Curr.';

            $defaultParams += [
                'name' => $name,
                'nameTable' => $nameTable,
                'title' => $name,
                'nameTableSettings' => $name,
            ];

            if ($prefix != '') {
                $defaultParams['name'] = $prefix . ' ' . $defaultParams['name'];
                $defaultParams['title'] = $prefix . ' ' . $defaultParams['title'];
                $defaultParams['nameTable'] = $prefix . ' ' . $defaultParams['nameTable'];
                $defaultParams['nameTableSettings'] = $prefix . ' ' . $defaultParams['nameTableSettings'];
            }
        }

        $params = array_merge($defaultParams, $params);

        return F::createSelect($fieldKey, $params);
    }

    public static function comments(array $params = []): FieldText
    {
        $params += [
            'ajax' => true,
            'name' => Config::$lang === 'ru' ? 'Комментарии' : 'Comments',
        ];
        return F::createText('Comments', $params);
    }

    public static function orderId(): FieldInt
    {
        return F::createInt('OrderID', [
            'name' => Config::$lang === 'ru' ? 'Порядок' : 'Order ID',
            'editAlign' => 'left',
            'tableAlign' => 'center',
            'tableWidthHtml' => 50
        ]);
    }

    public static function grossWeight(): FieldFloat
    {
        return F::createFloat('GrossWeight', [
            'name' => 'Gross Weight',
            'nameTable' => "Gross\nWeight (kg)",
            'tableWidthHtml' => 62,
            'editCssClass' => 'input-weight',
            'uom' => 'kg',
            'total' => true,
        ]);
    }

    public static function netWeight()
    {
        return F::createFloat(
            'NetWeight',
            [
                'name' => 'Net Weight',
                'nameTable' => "Net\nWeight (kg)",
                'tableWidthHtml' => 62,
                'editCssClass' => 'input-weight',
                'uom' => 'kg',
                'total' => true,
            ]
        );
    }

    public static function chargeableWeight(): FieldFloat
    {
        return F::createFloat(
            'ChargeableWeight',
            [
                'name' => 'Chargeable Weight',
                'tableWidthHtml' => 62,
                'editCssClass' => 'input-weight',
                'uom' => 'kg',
                'total' => true,
            ]
        );
    }

    public static function width(): FieldInt
    {
        return F::createInt('Width', ['uom' => 'cm']);
    }

    public static function height(): FieldInt
    {
        return F::createInt('Height', ['uom' => 'cm']);
    }

    public static function length(): FieldInt
    {
        return F::createInt('Length', ['uom' => 'cm']);
    }

    public static function quantity(): FieldInt
    {
        return F::createInt('Quantity', [
            'name' => Config::$lang === 'ru' ? 'Кол-во' : 'Quantity',
        ]);
    }

    public static function volume(): FieldFloat
    {
        return F::createFloat('Volume', [
            'name' => Config::$lang === 'ru' ? 'Объём' : 'Volume',
            'tableWidthHtml' => 62,
            'editCssClass' => 'input-weight',
            'uom' => Config::$lang === 'ru' ? 'м<sup>3</sup>' : 'm<sup>3</sup>',
            'precision' => 3,
            'total' => true,
        ]);
    }

    public static function pieces(): FieldInt
    {
        return F::createInt('Pieces', [
            'name' => Config::$lang === 'ru' ? 'Мест' : 'Pieces',
            'total' => true
        ]);
    }

    public static function address($fieldKey = 'Address'): FieldText
    {
        return F::createText($fieldKey, [
            'name' => Config::$lang === 'ru' ? 'Адрес' : 'Address',
            'clickable' => true,
            'ajax' => true
        ]);
    }

    public static function ets(): FieldDate
    {
        return F::createDate('ETS', ['title' => 'Estimated Time of Ship']);
    }

    public static function etd(): FieldDate
    {
        return F::createDate('ETD', ['title' => 'Estimated Time of Departure']);
    }

    public static function eta(): FieldDate
    {
        return F::createDate('ETA', ['title' => 'Estimated Time of Arrival']);
    }

    public static function ata(): FieldDate
    {
        return F::createDate('ATA', ['title' => 'Actual Time of Arrival']);
    }

    public static function teu(): FieldInt
    {
        return F::createInt('TEU', ['tableAlign' => 'center', 'total' => true]);
    }

    public static function synonyms(): FieldText
    {
        return F::createText('Synonyms', [
            'name' => Config::$lang === 'ru' ? 'Синонимы' : 'Synonyms',
        ]);
    }

    public static function email(): FieldText
    {
        return F::createText('Email', [
            'name' => 'E-mail',
            'editCssClass' => 'email',
            'clickable' => true,
            'tableWidthHtml' => 126,
            'rows' => 2,
        ]);
    }

    public static function phone($fieldKey = 'Phone', array $params = []): FieldText
    {
        $defaultParams = [
            'name' => Config::$lang === 'ru' ? 'Телефон' : 'Phone',
            'editCssClass' => 'phone',
            'tableWidthHtml' => 126,
            'rows' => 2,
        ];

        return F::createText($fieldKey, $params + $defaultParams);
    }

    public static function checkbox(string $name = "ids"): FieldHtml
    {
        return F::createHtml('Checkbox', [
            'nameTable' => '<input type="checkbox" class="cb-all">',
            'getHtmlFunction' => function ($value, array $data) use ($name) {
                return '<input type="checkbox" class="cb" value="' . $data['ID'] . '" name="' . $name . '[]">';
            }
        ]);
    }

    public static function filterCreatedBy()
    {
        return F::createSelect('CreatedBy', [
            'name' => Config::$lang === 'ru' ? 'Автор' : 'Author',
            'select2' => true,
            'editWidth' => 'default',
            'options' => "SELECT ID, Name, IF(Active, '', 'Inactive') AS optgroup FROM user ORDER BY Active DESC, Name",
            'empty' => false
        ]);
    }

    public static function filterModuleId()
    {
        return F::createSelect('ModuleID', [
            'name' => Config::$lang === 'ru' ? 'Модуль' : 'Module',
            'select2' => true,
            'editWidth' => 'default',
            'options' => "SELECT ID, ModuleName FROM dblog_module ORDER BY ModuleName",
            'empty' => true
        ]);
    }

    public static function filterTableId()
    {
        return F::createSelect('TableID', [
            'name' => Config::$lang === 'ru' ? 'Таблица' : 'Table',
            'select2' => true,
            'editWidth' => 'default',
            'options' => "SELECT ID, TableName FROM dblog_table ORDER BY TableName",
            'empty' => true
        ]);
    }

    public static function filterFieldId()
    {
        return F::createSelect('FieldID', [
            'name' => Config::$lang === 'ru' ? 'Поле' : 'Field',
            'select2' => true,
            'editWidth' => 'default',
            'options' => "SELECT f.ID AS value, IF(f.FieldDescription = '', f.FieldKey, f.FieldDescription) AS text, t.TableName AS optgroup
                                      FROM dblog_field f, dblog_table t
                                      WHERE f.TableID = t.ID
                                      ORDER BY t.TableName, f.FieldDescription"
        ]);
    }

    public static function filterMasterId()
    {
        return F::createInt('MasterID', ['name' => 'Master ID', 'editAlign' => 'left', 'editWidth' => 'medium']);
    }

    public static function filterRecordId()
    {
        return F::createInt('RecordID', ['name' => 'Record ID', 'editAlign' => 'left', 'editWidth' => 'medium']);
    }

    public static function createdBy()
    {
        return F::createSelect('CreatedBy', [
            'name' => Config::$lang === 'ru' ? 'Автор' : 'Created By',
            'modes' => F::MODE_READONLY,
            'nobr' => true,
            'tableWidthExcel' => 18,
            'options' => 'user',
            'default' => User::getId(),
        ]);
    }

    public static function createdDateTime()
    {
        return F::createDateTime('CreatedDateTime', [
            'name' => Config::$lang === 'ru' ? 'Дата создания' : 'Created At',
            'modes' => F::MODE_READONLY,
        ]);
    }

    public static function incoterms()
    {
        return F::createSelect('Incoterms', [
            'name' => Config::$lang === 'ru' ? 'Условия поставки' : 'Incoterms',
            'options' => Incoterms::getOptions(),
            'tableAlign' => 'center',
            'editWidth' => 'small',
        ]);
    }

    public static function incotermsLocation()
    {
        return F::createString('IncotermsLocation', [
            'name' => Config::$lang === 'ru' ? 'Место забора' : 'Incoterms Location',
            'editWidth' => 'medium',
        ]);
    }

    public static function mawb()
    {
        return F::createString('MAWB', [
            'name' => 'MAWB',
            'getDataFromRequestFunction' => function ($value) {
                if ($value) {
                    $value = Misc::normalizeMawb((string)$value);
                }
                return $value;
            }
        ]);
    }

    public static function vatPercent()
    {
        return F::createSelect('VATPercent', [
            'name' => Config::$lang === 'ru' ? 'НДС %' : 'VAT %',
            'default' => self::VAT_BEZNDS,
            'options' => self::VAT_OPTIONS,
            'empty' => false,
            'copy' => true,
            'tableWidthHtml' => 59,
            'tableAlign' => 'center',
            'changeAll' => true,
            'getHtmlFunction' => function ($value, array $data, $outHtml, $forTable) {
                if ($forTable === 'total' && $value > 0) {
                    // Это похоже на total строку, т.е. нужно вывести $value как сумму
                    $outHtml = FieldMoney::beautify($value);
                }
                return $outHtml;
            }
        ]);
    }

    public static function vatAmount()
    {
        return F::createMoney('VATAmount', [
            'name' => Config::$lang === 'ru' ? 'Сумма НДС' : 'VAT Amount',
            'zeroEmpty' => true,
        ]);
    }

    public static function countryCode()
    {
        return F::createString('CountryCode', [
            'name' => Config::$lang === 'ru' ? 'Код страны' : 'Country Code',
            'editWidth' => 'mini',
            'editMaxLength' => 2,
            'tableAlign' => 'center',
            'upper' => true,
            F::PARAM_AC_SOURCE => '?a=Core&ac=SearchCountry',
            F::PARAM_AC_RESULTFORMATTERJSFUNCTION => '(item) => `${item.value} ${item.text}`'
        ]);
    }

    public static function filterExact($fieldKey = 'ExactReference')
    {
        $name = Config::$lang === 'ru' ? 'Точное совпадение' : 'Exact';
        return F::createCheckbox($fieldKey, ['name' => $name, 'sqlWhere' => '']);
    }

    public static function filterSearchById()
    {
        return F::createCheckbox('SearchByID', ['name' => 'ID', 'sqlWhere' => '']);
    }
}
