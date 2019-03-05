<?php

namespace Light\Field;

use Light\Db;

class FieldWeek extends FieldInputAbstract
{
    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->tableAlign = 'center';
        $this->tableWidthExcel = 14;
    }

    private function getAsTouple($value)
    {
        $week = '';
        $year = '';

        if ($value !== '-' && strpos($value, '-') !== false) {

            [$year, $week] = explode('-', $value);

            // intval уберёт лидирующий ноль у номера недели
            $week = (int)$week;
            $year = (int)$year;

            if ($week === 0) {
                $week = '';
            }
            if ($year === 0) {
                $year = '';
            }

        } elseif (strlen($value) === 4) {

            $year = $value;

        }

        return [$week, $year];
    }

    protected function getHtmlInner($value, array $data, $forTable)
    {
        $outHtml = '';
        [$week, $year] = $this->getAsTouple($value);
        if ($week || $year) {
            $outHtml = $week . '<span class="week-year">\'' . $year . '</span>';

            if ($week && $year) {
                //Неделя и год опередлены однозначно
                //TODO: добавить внешний span в котором будет указан интервал дат для этой недели
                $dateFrom = '';
                $dateUntil = '';
                $outHtml = '<span title="' . $dateFrom . ' - ' . $dateUntil . '">' . $outHtml . '</span>';
            }
        }

        return $outHtml;
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        $out = '';
        list($week, $year) = $this->getAsTouple($value);
        if ($week || $year) {
            $out = $week . "'" . $year;
        }

        return $out;
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        [$week, $year] = $this->getAsTouple($value);

        $params = $this->initialParams;

        $weekParams = $params;
        $weekParams['editCssClass'] = $this->getEditCssClass($value, $data) . ' input-week';
        $weekParams['editName'] = $this->editName . '[Week]';
        $weekParams['editId'] = $this->editId . '-Week';

        $yearParams = $params;
        $yearParams['editCssClass'] = $this->getEditCssClass($value, $data) . ' input-week-year';
        $yearParams['editName'] = $this->editName . '[Year]';
        $yearParams['editId'] = $this->editId . '-Year';

        $outHtml = (new FieldInt('', $weekParams))->getHtmlInput($week, $data, $idPrefix);
        $outHtml .= "'";
        $outHtml .= (new FieldInt('', $yearParams))->getHtmlInput($year, $data, $idPrefix);

        return $outHtml;
    }

    protected function getDataFromRequestInner(array $request)
    {
        $value = null;

        if (isset($request[$this->key])) {

            //Какие-то данные есть в $request - продолжаем их парсить

            $week = null;
            $year = null;

            if (is_array($request[$this->key]) && isset($request[$this->key]['Week'], $request[$this->key]['Year'])) {

                //Данные пришли в формате массива - скорее всего, это submit формы редактирования
                $week = $request[$this->key]['Week'];
                $year = $request[$this->key]['Year'];

            } else {

                //Данные пришли как одна строка - это происходит при переходе с фильтров
                $value = $request[$this->key];
                if ($value === '-') {
                    $value = null;
                } elseif (strpos($value, '-') !== false) {
                    [$year, $week] = explode('-', $value);
                }
            }

            if ($week !== null && $year !== null) {
                //Похоже, что поле Week разделено как массив в $request запросе - нужно обхъединить в одну строку с дефисом
                $week = (int)$week;
                $week = abs($week);
                $week = (string)$week;
                if (strlen($week) < 2) {
                    $week = '0' . $week;
                } elseif (strlen($week) > 2) {
                    $week = substr($week, 0, 2);
                }

                $year = (int)$year;
                $year = abs($year);
                $year = (string)$year;
                if (strlen($year) === 2) {
                    $year = '20' . $year;
                } elseif (strlen($year) !== 4) {
                    if ($week !== '' && $week !== '00') {
                        //Если неделя указана, а год не указан - используем текущий год!
                        $year = date('Y');
                    } else {
                        $year = '';
                    }
                }

                $value = $year . '-' . $week;
            }
        }

        return $value;
    }

    public function allowSqlWhereValue($value)
    {
        $value = trim($value);
        if (strpos($value, '-') === false) {
            return false;
        }

        [$year, $week] = explode('-', $value);
        return ($year !== '' && $year !== '0000') || ($week !== '' && $week !== '00');
    }

    /**
     * Возвращает SQL-условие для обработки фильтра
     * @param string $value
     * @return string
     */
    public function getSqlWhereInner($value)
    {
        [$year, $week] = explode('-', $value);
        if ($week !== '' && $week !== '00') {
            // Неделя задана точно, используем полное совпадение
            $sqlWhere = "{fieldKey} = '{value}'";
        } else {
            // Неделя задана не точно, используем только год
            $sqlWhere = "{fieldKey} LIKE '" . Db::esc($year) . "-%'";
        }

        return $sqlWhere;
    }

    public static function isEmpty($value): bool
    {
        return $value === '0000-00' || $value === null;
    }
}
