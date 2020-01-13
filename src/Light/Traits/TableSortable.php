<?php

namespace Light\Traits;

use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\SubtableBase\Subtable;
use Light\Table\TableUserParamsRepository;

trait TableSortable
{
    private $tableSortFieldKey = ''; // Имя поля по которому сортировать
    private $tableSortOrder = 'ASC'; // Направление сортировки ASC | DESC

    /**
     * @var bool
     */
    public $allowSort = true;

    /**
     * SQL фрагмент, который будет добавлен в конец запроса ORDER BY ...
     * @var string
     */
    public $tableDefaultOrderBy;

    protected function initSortable()
    {
        // Сначала сохраним в базу новые значения сортировки, если они пришли в REQUEST
        if (!$this->allowSort) {
            return;
        }

        // Проинициализируем поле $this->tableSortFieldKey
        $this->initSortFieldKey();
        // Проинициализируем поле $this->tableSortOrder
        $this->initSortOrder();
    }

    private function initSortFieldKey()
    {
        /** @var TableUserParamsRepository $tableUserParams */
        // Отдельный объект, которых заведует сохранением настроек в базу для данного пользователя
        $tableUserParams = $this->tableUserParams;

        $requestKey = $this->getTableName() . '_sort';
        if (isset($_REQUEST[$requestKey])) {
            // От браузера пришли какие-то новые настройки для таблицы - используем их!
            $newSortFromRequest = $_REQUEST[$requestKey];
            // Применим пришедшие настроки к текущей таблице
            $this->tableSortFieldKey = $newSortFromRequest;
            // Также сохраним эти настройки в настройки пользователя
            $tableUserParams->saveSortFieldKey($newSortFromRequest);

            return;
        }

        // Если из браузера не поступало никаких сигналов об изменении настройки sort,
        // то загрузим из настроек пользователя из базы
        $this->tableSortFieldKey = $tableUserParams->loadSortFieldKey();
    }

    private function initSortOrder()
    {
        /** @var TableUserParamsRepository $tableUserParams */
        // Отдельный объект, которых заведует сохранением настроек в базу для данного пользователя
        $tableUserParams = $this->tableUserParams;

        $requestKey = $this->getTableName() . '_order';
        if (isset($_REQUEST[$requestKey])) {
            // От браузера пришли какие-то новые настройки для таблицы - используем их!
            $newOrderFromRequest = $_REQUEST[$requestKey];
            // Применим пришедшие настроки к текущей таблице
            $this->tableSortOrder = $newOrderFromRequest;
            // Также сохраним эти настройки в настройки пользователя
            $tableUserParams->saveSortOrder($newOrderFromRequest);

            return;
        }

        // Если из браузера не поступало никаких сигналов об изменении настройки sort,
        // то загрузим из настроек пользователя из базы
        $this->tableSortOrder = $tableUserParams->loadSortOrder();
    }

    /**
     * Возвращает информацию о текущем способе сортировки и ссылку disable sort
     * @return string
     */
    protected function getHtmlTableHeadBlockSortable()
    {
        $outHtml = '';

        $sortField = $this->getSortField();
        if ($sortField) {
            //Сортировка включена (искомый столбец не спрятан и у него существует параметр сортировки
            $sortFieldName = $sortField->getNameTableSettings();
            $sortedByText = Config::$lang === 'ru' ? 'Отсортировано по' : 'Sorted by';
            $outHtml .= "<span class=table-sortinfo>$sortedByText $sortFieldName";

            //Добавим ссылку на текущее состояние сортировки, которая будет её переворачивать
            $opositeOrder = $this->getTableSortOrder() === 'ASC' ? 'DESC' : 'ASC';
            $outHtml .= ' ' . $this->getHtmlSortLink($this->getTableSortOrder(), $sortField->key, $opositeOrder);
            $outHtml .= ' | ';

            //Добавим ссылку на отключение сортировки
            $disableText = Config::$lang === 'ru' ? 'отменить' : 'disable';
            $outHtml .= ' ' . $this->getHtmlSortLink($disableText, '', '');

            $outHtml .= '</span>';
        }

        return $outHtml;
    }

    /**
     * Возвращает поле (FieldAbstract) из модели по которому в данный момент включена сортировка
     * @return \Light\Field\FieldAbstract
     */
    protected function getSortField()
    {
        if (!$this->allowSort || !$this->tableSortFieldKey || !$this->tableSortOrder) {
            return null;
        }

        //По умаолчанию ищем поле в _fieldset
        $field = $this->getField($this->tableSortFieldKey);

        //Попробуем найти поле в _fieldsetFirst
        if (!$field) {
            $field = $this->getFieldsetFirst()->getField($this->tableSortFieldKey);
        }

        //Попробуем найти поле в _fieldsetLast
        if (!$field) {
            $field = $this->getFieldsetLast()->getField($this->tableSortFieldKey);
        }

        if (!$field) {
            return null;
        }

        //Если поле успешно нашли, проверим, что по нему можно делсть сортировку: оно отображается и оно сортеруемое
        if (!$this->isFieldSortable($field)) {
            return null;
        }

        return $field;

    }

    protected function getDataQueryWithSortFieldInSelect(string $querySelect): string
    {
        $sortField = $this->getSortField();
        if ($sortField && $sortField->getSqlSelect()) {
            $querySelect .= ', ' . $this->getFieldset()->getSqlSelect([$sortField->key]);
        }

        return $querySelect;
    }

    protected function getDataQueryWithSortFieldOrderBy(string $query): string
    {
        $sqlOrderBy = $this->getSqlOrderBy();
        if ($sqlOrderBy) {
            $query .= " ORDER BY $sqlOrderBy";
        }

        return $query;
    }

    private function isFieldSortable(F $field)
    {
        //Если поле скрыто и не является одним из полей first или last - то сортировку по нему делать не надо
        if (!$this->isFieldShowed($field->key)
            && !$this->getFieldsetFirst()->hasField($field->key)
            && !$this->getFieldsetLast()->hasField($field->key)
        ) {
            return false;
        }

        if (!$field->tableOrderBy) {
            return false;
        }

        //Проверим, возможно пользователь задал это поле как вычисляемое?
        $manualSqlSelect = $field->getSqlSelect();
        //1. если sqlSelect === null, значит поле не вычисляемое и по нему можно делать сортировку
        //2. если sqlSelect отлично от null, значит оно было определено пользователем и тут важно проверить
        //что пользователь задал какой-то подзапрос, а не сбросил в пустую строку или false
        return $manualSqlSelect === null || $manualSqlSelect;
    }

    protected function getTableSortOrder()
    {
        return $this->tableSortOrder === 'DESC' ? 'DESC' : 'ASC';
    }

    /**
     * Вспомагательный метод
     * используется, например, внтури ModuleAbstract::tableLoadData() чтобы сформировать строку, которая должна идти
     * после ORDER BY
     * @return string
     */
    protected function getSqlOrderBy()
    {
        $sqlOrderBy = [];
        $sortField = $this->getSortField();
        if ($sortField) {
            if ($sortField->tableOrderByLikeFilter) {
                // Для этого поля поставлено условие "сортировать как в фильтре"
                // Проверим, заполнен ли этот фильтр? (если такого фильтра вообще нет, то вернётся null)
                $filterValue = $this->getFilterValue($sortField->tableOrderByLikeFilter);
                if ($filterValue) {
                    // Проверим, сколько значений разделённых через запятую или перевод строки в этом фильтре?
                    $filterValuesSeparated = Db::explodeByComa($filterValue);
                    if (count($filterValuesSeparated) > 1) {
                        // Пользователь ввёл много значений, используем их для установки порядка сортировки!
                        // Обёдиним значения в строку через запятую, попутно проэкранировав
                        $filterValuesSql = Db::createIn($filterValuesSeparated);
                        $sqlOrderBy[] = "FIELD({$sortField->tableOrderBy}, $filterValuesSql)";
                    }
                }
            }

            $orderFieldKey = $sortField->tableOrderBy;

            // В случае если сортировка по неким ID - нужно сделать подзапрос на внешнюю таблицу,
            // чтобы отсортировать по реальным текстовым описаниям
            $foreignTable = $sortField->tableOrderByForeignTable;
            // Если внешняя таблица не задана явно, возможно есть указание таблицы для FieldSelect поля?
            // таблицы из которой берутся значения для отображения?
            if (!$foreignTable && property_exists($sortField, 'optionsView')) {
                // Сначала попробуем узнать внешнюю таблицу по свойству optionsView
                // это самое надёжное свойство, оно хранит ссылку на внешнюю таблицу для отображения справочника
                $optionsView = $sortField->optionsView;
                if ($optionsView && !is_callable($optionsView) && is_string($optionsView) && strpos($optionsView, ' ') === false) {
                    $foreignTable = explode('|', $optionsView)[0];
                }
            }
            if (!$foreignTable && property_exists($sortField, 'options')) {
                // Если по optionsView не удалось определить внешнюю таблицу, то попробуем по options
                // options должен быть строковый, также нужно отделить первую часть до вертикальной черты
                $options = $sortField->options;
                if ($options && !is_callable($options) && is_string($options) && strpos($options, ' ') === false) {
                    $foreignTable = explode('|', $options)[0];
                }
            }

            if ($foreignTable) {
                $orderFieldKey = "(SELECT Name FROM " . Db::quote($foreignTable) . " WHERE ID = $orderFieldKey LIMIT 1)";
            }

            $sqlOrderBy[] = $orderFieldKey . ' ' . $this->getTableSortOrder();
        }

        if ($this->tableDefaultOrderBy) {
            $sqlOrderBy[] = $this->tableDefaultOrderBy;
        } else {
            // Если сортировка по умолчанию не задана, то проверим наличие поля OrderID, а затем применим сортировку по ID
            if ($this->hasField('OrderID')) {
                $sqlOrderBy[] = 'OrderID';
            }
            $sqlOrderBy[] = 'ID DESC';
        }

        if ($sqlOrderBy) {
            return implode(', ', $sqlOrderBy);
        }

        // Возвращаем текст NULL, чтобы результат вызова этого метода можно было без проблем подставить после ORDER BY
        return 'NULL';
    }

    /**
     * @desc сортирует средсвами array_multisort и возвращает новый порядок $ids
     * @param F $field
     * @return array
     * @internal param string $defaultSortField
     * @internal param string $defaultSortOrder
     */
    /*
    public function sortWithPhp($defaultSortField, $defaultSortOrder)
    {
        $sortField = $this->getSortField();

        if (!$sortField) {
            $sortby = $defaultSortField;
            $sortorder = $defaultSortOrder;
        } else {
            $sortby = $sortField->tableOrderBy;
            $sortorder = $this->getSortOrder();
        }

        if ($sortby) {
            //Соберём значения поля по которому сортировать в отдельный массив
            $multisort = true;
            $tmpvalues = [];
            foreach ($this->getTableData() as $row) {
                if (!isset($row[$sortby])) {
                    $multisort = false;
                    break; //Нашли в строку в которой нет поля для сортировки!
                }
                $tmpvalues[] = $row[$sortby]; //Запомним значение поля для сортировки
            }

            if ($multisort) //Если подготовка $tmpvalues прошла успешно, то сортируем
            {
                if ($sortorder == 'ASC') {
                    $sortorder = SORT_ASC;
                } else {
                    $sortorder = SORT_DESC;
                }

                //Необходимо приести все массивы к одной размерности, чтобы сделать array_multisort
                //Для этого пройдёмся по основному массиву $this->Data и создадим NULL элементы в других массивах
                //NULL удобен тем, что isset(NULL) == false и empty(NULL) == false, значит все механизмы вывода на экран и в excel не заметят подмены
                foreach ($this->getTableData() as $rownum => $row) {
                    if (!isset($this->tableDataFormated[$rownum])) {
                        $this->tableDataFormated[$rownum] = NULL;
                    }
                    if (!isset($this->tableDataParams[$rownum])) {
                        $this->tableDataParams[$rownum] = NULL;
                    }
                }

                ksort($this->tableDataFormated);
                ksort($this->tableDataParams);

                //Отсортируем все массивы
                array_multisort($tmpvalues, $sortorder, $this->tableData, $this->tableDataFormated,
                    $this->tableDataParams);
            }
        }

        //Генерируем возвращаемый массив из ID
        $returnIds = array();
        foreach ($this->tableData as $rownum => $row) {
            if (isset($this->tableDataParams[$rownum]['id'])) {
                $returnIds[] = $this->tableDataParams[$rownum]['id'];
            } elseif (isset($row['ID'])) {
                $returnIds[] = $row['ID'];
            }
        }

        return $returnIds;
    }
    */

    /**
     * @param F $field
     * @return string
     */
    protected function getHtmlTableTheadThContent(F $field)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $html = parent::getHtmlTableTheadThContent($field);

        if ($this->allowSort) {

            $html = $this->getFieldSortLink($field, $html);
        }

        return $html;
    }

    protected function getFieldSortLink(F $field, string $innerHtml): string
    {
        $html = $innerHtml;
        // Добавим иконку сортировки, если сортировка по текущему полю
        $sortField = $this->getSortField();
        if ($sortField === $field) {
            $icon = $this->getTableSortOrder() === 'DESC' ? '▼' : '▲';
            $html .= ' <span class="tr-sort-icon">' . $icon . '</span>';
        }

        // Превратим загловок поля в ссылку
        if ($this->isFieldSortable($field)) {
            if ($sortField === $field) {
                $order = $this->getTableSortOrder() === 'DESC' ? null : 'DESC';
                if ($order === null && isset($this->_state) && $this->_state === Subtable::STATE_EDIT) {
                    $order = 'ASC';
                }
            } else {
                $order = 'ASC';
            }

            if ($order === null) {
                $html = $this->getHtmlSortLink($html, $field->key, '');
            } else {
                $html = $this->getHtmlSortLink($html, $field->key, $order);
            }
        }

        return $html;
    }

    private function getHtmlSortLink($innerHtml, $fieldKey, $order)
    {
        return '<a href="#' . rawurlencode(
                $fieldKey
            ) . '|' . $order . '" class="table-sort-link">' . $innerHtml . '</a>';
    }
}
