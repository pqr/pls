<?php

namespace Light\Field;

use Light\Currency;
use Light\Db;

/**
 * @method bool getShort()
 * @method string getCurrencyIso()
 * @method string getUseSymbol()
 *
 * @method $this setShort(bool $short)
 * @method $this setCurrencyIso(string $currencyIso)
 * @method $this setUseSymbol(bool $useSymbol)
 *
 */
class FieldMoney extends FieldFloat
{
    public $short = false;
    public $useSymbol = false;
    public $currencyIso;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->total = true;
        $this->tableWidthExcel = 14;
        $this->inputPattern = '-?[\d\s]+([.,]\d{1,2})?';
        $this->editAlign = 'right';
        $this->tableAlign = 'right';
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        $value = (double)$value;
        if ($this->zeroEmpty && $value == 0) {
            return '';
        }

        $currencyIso = $this->currencyIso ?: $this->uom;
        $outHtml = self::beautify($value, $this->short, $currencyIso, $this->useSymbol);
        if (!$forTable && $outHtml) {
            $outHtml = '<span class="MoneyView">' . $outHtml . '</span>';
        }

        return $outHtml;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        $outHtml = parent::getHtmlInputInner($value, $data, $idPrefix);
        //Если задана константая валюта, её надо прицепить хвостом (это особенно удобно в subtable)
        $currencyIso = $this->currencyIso;
        if ($currencyIso) {
            if ($this->useSymbol) {
                $currencyIso = Currency::getSymbol($currencyIso);
            }
            $outHtml .= ' <span>' . $currencyIso . '</span>';
            $outHtml = '<span class="nobr">' . $outHtml . '</span>';
        }

        return $outHtml;
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        $value = (double)$value;
        if ($this->zeroEmptyExcel && $value == 0) {
            return '';
        }

        return number_format($value, 2, '.', '');
    }

    public static function beautify($value, $short = false, $currencyIso = null, $useSymbol = false)
    {
        $value = (double)$value;
        $outHtml = number_format($value, 2, '.', ' ');

        //Если задан формат short, это значит нужно уобрать .00 копеек в конце вывода, если сумма целая
        $cents = substr($outHtml, -2, 2);
        if ($short && $cents === '00') {
            //Отрезаем нули вместе с точкой на конце
            $outHtml = substr($outHtml, 0, -3);
        } else {
            //Иначе нужно обернуть центы в специальный класс, чтобы они отображались шрифтом поменьше
            $outHtml = substr($outHtml, 0, -3) . '<span class="cnt">.' . $cents . '</span>';
        }

        //Если задана валюта - добавляем как суффикс
        if ($currencyIso) {
            if ($useSymbol) {
                $currencyIso = Currency::getSymbol($currencyIso);
            }
            $outHtml .= ' ' . $currencyIso;
        }

        //Если внутри получились пробелы - оберём всё в класс nobr, чтобы не было переводов строк
        if (strpos($outHtml, ' ') !== false) {
            $outHtml = '<span class="nobr">' . $outHtml . '</span>';
        }

        return $outHtml;
    }

    public static function parseFromDb($dbValue)
    {
        if ($dbValue === null) {
            return null;
        }
        return round((float)$dbValue, 2);
    }

    public static function isEmpty($value): bool
    {
        $value = (double)$value;
        return !$value;
    }
}
