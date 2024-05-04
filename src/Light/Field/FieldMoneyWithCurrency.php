<?php

namespace Light\Field;

use Light\Db;
use Light\Field\CommonFields as CF;
use Light\Misc;

class FieldMoneyWithCurrency extends FieldMoney
{
    protected $defaultCurrencyIso = '';
    protected $currencyIsoFieldKey;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->tableWidthExcel = 18;
        $this->tableOrderBy = 'CAST(`' . $this->key . '` AS DECIMAL)';
    }

    /**
     * Возвращает имя поля Currency как оно в базе - нужно для select и для insert/update
     */
    public function getCurrencyIsoDbFieldKey()
    {
        if ($this->currencyIsoFieldKey) {
            return $this->currencyIsoFieldKey;
        }
        return str_replace('Amount', '', (string) $this->key) . 'CurrencyISO';
    }

    public function getSqlSelect()
    {
        $sqlSelect = parent::getSqlSelect();
        if ($sqlSelect === null && !$this->groupconcat) {
            $sqlSelect = "CONCAT(" . $this->getCurrencyIsoDbFieldKey() . ", ' ', " . $this->key . ")";
        }

        return $sqlSelect;
    }

    protected function calcGroupconcatSum($value)
    {
        $lines = explode("\n", (string) $value);

        $amountPerCurrency = Misc::calcAmountPerCurrency($lines);

        if (count($amountPerCurrency) === 1) {
            // Для оптимизации, в большенстве случаев валюта всего одна, сразу возвращаем
            $currencyIso = key($amountPerCurrency);
            $amount = reset($amountPerCurrency);
            return "$currencyIso $amount";
        }

        $outLines = [];
        foreach ($amountPerCurrency as $currencyIso => $amount) {
            $outLines[] = "$currencyIso $amount";
        }

        return implode("\n", $outLines);
    }

    public function getViewCssClass($value, array $data = [], $forTable = false, $excludeViewMaxWidth = false)
    {
        $viewCssClass = parent::getViewCssClass($value, $data, $forTable, $excludeViewMaxWidth);

        if ($value) {
            $parts = explode(' ', (string) $value);
            if (isset($parts[1])) {
                $amount = $parts[1];

                if ($amount > 0) {
                    $addCssClass = $this->cssClassPositive;
                } elseif ($amount < 0) {
                    $addCssClass = $this->cssClassNegative;
                } else {
                    $addCssClass = $this->cssClassZero;
                }

                if ($addCssClass) {
                    $viewCssClass .= ' ' . $addCssClass;
                }
            }
        }

        return $viewCssClass;
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        if ($value === false || $value === null) {
            return '';
        }

        [$currencyIso, $amount] = self::getAsTouple($value);

        if ($this->zeroEmpty && $amount == 0) {
            return '';
        }

        return FieldMoney::beautify($amount, $this->short, $currencyIso, $this->useSymbol);
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        [, $amount] = self::getAsTouple($value);

        $amount = (double)$amount;
        if ($amount == 0 && $this->zeroEmptyExcel) {
            return '';
        }

        return $value;
    }

    public static function getAsTouple($value): array
    {
        $amount = 0;
        $currencyIso = '';

        if ($value != '' && str_contains((string) $value, ' ')) {
            [$currencyIso, $amount] = explode(' ', (string) $value);
            $amount = (double)$amount;
        }

        return [$currencyIso, $amount];
    }

    public static function getAmount($value): float
    {
        $amount = 0;

        if ($value != '' && ($pos = strpos((string) $value, ' ')) !== false) {
            $amount = (double)substr((string) $value, $pos + 1);
        }

        return $amount;
    }

    public static function getCurrency($value): float
    {
        $amount = 0;

        if ($value != '' && ($pos = strpos((string) $value, ' ')) !== false) {
            $amount = (double)substr((string) $value, 0, $pos);
        }

        return $amount;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        [$currencyIso, $amount] = self::getAsTouple($value);

        $params = $this->initialParams;

        $amountParams = $params;
        $amountParams['editName'] = $this->editName . '[Amount]';
        $amountParams['editId'] = $this->editId . '-Amount';

        $currencyIsoParams = $params;
        $currencyIsoParams['editName'] = $this->editName . '[CurrencyISO]';
        $currencyIsoParams['editId'] = $this->editId . '-CurrencyISO';

        $outHtml = (new FieldMoney('', $amountParams))->getHtmlInput($amount, $data, $idPrefix);

        $outHtml .= " ";

        if (!$currencyIso && $this->defaultCurrencyIso) {
            $currencyIso = $this->defaultCurrencyIso;
        }

        $outHtml .= CF::currency('', $currencyIsoParams)
            ->getHtmlInput($currencyIso, $data, $idPrefix);

        $outHtml = '<span class="nobr">' . $outHtml . '</span>';

        return $outHtml;
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = null;
        if (isset($request[$this->key]['Amount'], $request[$this->key]['CurrencyISO'])) {

            $amount = (new FieldMoney('Amount'))->getDataFromRequest(['Amount' => $request[$this->key]['Amount']]);
            $currencyIso = CF::currency()->getDataFromRequest(['CurrencyISO' => $request[$this->key]['CurrencyISO']]);

            // Для вставки в базу нужны ключи как они определены в самом поле
            $value = [$this->key => $amount, $this->getCurrencyIsoDbFieldKey() => $currencyIso];
        }

        return $value;
    }

    /**
     * Сигнатура метода должна совпадать с методом предка, поэтому существует последний параметр $currencyIso
     * Однако, он не используется, т.к. валюта хранится непосредтсвенно в значении $value
     * @param $value
     * @param bool $short
     * @param null $currencyIso не используется
     * @param bool $useSymbol
     * @param int $decimals
     * @return string
     */
    public static function beautify($value, $short = false, $currencyIso = null, $useSymbol = false, int $decimals = 2)
    {
        [$currencyIso, $amount] = self::getAsTouple($value);
        return FieldMoney::beautify($amount, $short, $currencyIso, $useSymbol, $decimals);
    }

    public static function parseFromDb($dbValue)
    {
        return $dbValue;
    }

    public static function createFromMoney(FieldMoney $fieldMoney, string $currencyFieldKey = 'CurrencyISO')
    {
        $sourceParams = $fieldMoney->initialParams;
        $sqlSelect = $fieldMoney->getSqlSelect();
        if (!$sqlSelect) {
            $sqlSelect = Db::quote($fieldMoney->key);
        }
        // Создаём новое поле типа MoneyWithCurrency на основе старого поля с ключём $moneyFieldKey
        $params = [
            'sqlSelect' => "CONCAT(`$currencyFieldKey`, ' ', $sqlSelect)",
            'modes' => self::MODE_READONLY,
        ];

        return self::createMoneyWithCurrency($fieldMoney->key, $params + $sourceParams);
    }

    public static function empty($value): bool
    {
        if (!$value) {
            return true;
        }

        $amount = self::getAsTouple($value)[1];
        return FieldMoney::empty($amount);
    }


}
