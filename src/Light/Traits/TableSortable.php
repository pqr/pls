<?php
namespace Light\Traits;

use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Img;
use Light\SubtableBase\Subtable;

trait TableSortable
{
    private $_sortFieldKey = false; //Имя поля по которому сортировать
    private $_sortOrder; //Направление сортировки ASC | DESC

    protected $allowSort = true;

    protected $tableDefaultOrderBy = 'ID DESC'; //SQL фрагмент, который будет добавлен в конец запроса ORDER BY ...

    /**
     * @param string $fieldKey
     * @return F
     */
    abstract public function getField($fieldKey);

    /** @return Fieldset|F[] */
    abstract public function getFieldsetFirst();

    /** @return Fieldset|F[] */
    abstract public function getFieldsetLast();

    abstract protected function setTableUserParam($name, $value);

    abstract protected function getTableUserParam($name);

    abstract public function isFieldShowed($fieldKey);

    /** @return array */
    abstract public function getTableData();

    protected function initSortable()
    {
        //Сначала сохраним в базу новые значения сортировки, если они пришли в REQUEST
        if ($this->allowSort) {

            if (isset($_POST['sort'])) {
                $this->setTableUserParam('SortFieldKey', $_POST['sort']);
            }

            if (isset($_POST['order'])) {
                $this->setTableUserParam('SortOrder', $_POST['order']);
            }

            //Применим значения из $userParams к текущему объекту
            $this->_sortFieldKey = $this->getTableUserParam('SortFieldKey');
            $this->_sortOrder = $this->getTableUserParam('SortOrder');
        }
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
            $opositeOrder = $this->getSortOrder() === 'ASC' ? 'DESC' : 'ASC';
            $outHtml .= ' ' . $this->getHtmlSortLink($this->getSortOrder(), $sortField->key, $opositeOrder);
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
        if (!$this->allowSort || !$this->_sortFieldKey || !$this->_sortOrder) {
            return null;
        }

        //По умаолчанию ищем поле в _fieldset
        $field = $this->getField($this->_sortFieldKey);

        //Попробуем найти поле в _fieldsetFirst
        if (!$field) {
            $field = $this->getFieldsetFirst()->getField($this->_sortFieldKey);
        }

        //Попробуем найти поле в _fieldsetLast
        if (!$field) {
            $field = $this->getFieldsetLast()->getField($this->_sortFieldKey);
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

    protected function getSortOrder()
    {
        return $this->_sortOrder === 'DESC' ? 'DESC' : 'ASC';
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

            $sqlOrderBy[] = $sortField->tableOrderBy . ' ' . $this->getSortOrder();
        }

        if ($this->tableDefaultOrderBy) {
            $sqlOrderBy[] = $this->tableDefaultOrderBy;
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
                    if (!isset($this->_tableDataFormated[$rownum])) {
                        $this->_tableDataFormated[$rownum] = NULL;
                    }
                    if (!isset($this->_tableDataParams[$rownum])) {
                        $this->_tableDataParams[$rownum] = NULL;
                    }
                }

                ksort($this->_tableDataFormated);
                ksort($this->_tableDataParams);

                //Отсортируем все массивы
                array_multisort($tmpvalues, $sortorder, $this->_tableData, $this->_tableDataFormated,
                    $this->_tableDataParams);
            }
        }

        //Генерируем возвращаемый массив из ID
        $returnIds = array();
        foreach ($this->_tableData as $rownum => $row) {
            if (isset($this->_tableDataParams[$rownum]['id'])) {
                $returnIds[] = $this->_tableDataParams[$rownum]['id'];
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

            // Добавим иконку сортировки, если сортировка по текущему полю
            $sortField = $this->getSortField();
            if ($sortField === $field) {
                $icon = $this->getSortOrder() === 'DESC' ? '▼' : '▲';
                $html .= ' <span class="tr-sort-icon">' . $icon . '</span>';
            }

            // Превратим загловок поля в ссылку
            if ($this->isFieldSortable($field)) {
                if ($sortField === $field) {
                    $order = $this->getSortOrder() === 'DESC' ? null : 'DESC';
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
