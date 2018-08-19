<?php

namespace Light\Traits;

use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldMonth;
use Light\Field\FieldSelect;
use Light\Field\FieldSelect2Multiple;
use Light\Field\FieldText;
use Light\Fieldset;
use Light\Filters;

trait TableFilterable
{
    /** @var Filters */
    protected $filters;

    /** @var array некоторым фильтрам можно запретить сбрасываение по кнопке Reset */
    protected $filtersDoNotReset = [];

    /** @var bool выключает возможность пользовательской настройки */
    protected $allowFiltersLayoutChange = true;

    /**
     * @var array Шаблон фильтров как массив из трёх колонок
     * Массив из трёх массивов - колонок
     * Каждая колонка - массив элементов описывающих фильтры в этой колонке
     * Элементом может быть:
     * - просто строка "FilterName" или "ContainerName" - тогда в данной строке рендрится ровно один фильтр с таким FieldKey
     * - кортеж из двух элементов:
     *  – уникальный ключ характеризующий эту строку, например "ColorAndMonth"
     *  – html код выводящий этот фильтр
     */
    protected $filtersLayout = [];

    /**
     * Контракт на метод getTableName, который описан в классе Table к которому должен подключаться этот Trait
     * @return string
     */
    abstract public function getTableName();

    /**
     * Метод для переопределяни классов потомков
     * Добавлять фильтры можно вызовом $this->addFilters(...) или $this->addFilter(...)
     */
    protected function initFilters()
    {
    }

    /**
     * Удобный метод на замену старому setFiltersFieldset
     * В этот метод нет необходимости передавать целый объект Fieldset внутри которого описаны поля с фильтрами
     * Сюда можно передать переменное число параметров - сами объекты Field
     * или обычный массив из объектов Field
     * @param F[] $fields
     */
    public function addFilters(...$fields)
    {
        if (count($fields) !== 0) {
            if (is_array($fields[0]) && count($fields) === 1) {
                $filtersArr = $fields[0];
            } else {
                $filtersArr = $fields;
            }

            foreach ($filtersArr as $filterField) {
                $this->addFilter($filterField);
            }
        }
    }

    protected function getFiltersFieldset(): Fieldset
    {
        return $this->filters->getFieldset();
    }

    protected function hasFilters(): bool
    {
        return !$this->getFiltersFieldset()->isEmpty();
    }

    protected function addFilter(F $field)
    {
        //В зависимости от типа поля, навесим доп параметры
        if ($field instanceof FieldSelect2Multiple) {

            if (!isset($field->initialParams['any'])) {
                $field->any = false;
            }
            if (!isset($field->initialParams['empty'])) {
                $field->empty = false;
            }
            if (!isset($field->initialParams['default'])) {
                $field->default = '%';
            }

            // Принудильено отключаем возможность добавления новых элементов в выпадающий список для фильтров
            $field->addNew = false;

        } elseif ($field instanceof FieldSelect) {

            if (!isset($field->initialParams['any'])) {
                $field->any = true;
            }
            if (!isset($field->initialParams['empty'])) {
                $field->empty = false;
            }
            if (!isset($field->initialParams['default'])) {
                $field->default = '%';
            }

            // Принудильено отключаем возможность добавления новых элементов в выпадающий список для фильтров
            $field->addNew = false;

        } elseif ($field instanceof FieldMonth) {

            if (!isset($field->initialParams['allYear'])) {
                $field->allYear = true;
            }

        } elseif ($field instanceof FieldText) {

            if (!isset($field->initialParams['title'])) {
                $field->title = 'Multiple values separated by comma or new line';
            }
            if (!isset($field->initialParams['rows'])) {
                $field->rows = 2;
            }

        } elseif ($field->isAutocomplete()) {

            //Если это поле с виджетом Autocomplete, то нужно сделать его readonly, чтобы после сабмита фильтров
            //новое значение не запоминалось в базу
            $field->acReadonly = true;

        } /*elseif ($field instanceof FieldCheckbox) {

            //Чекбоксы нужно превратить в выпадающие списки yes/no
            $params = $field->getUserDefinedParams();
            $params['any'] = true;
            $params['default'] = '%';
            $field = new FieldYesNo($field->key, $params);

        }*/

        // В случае когда фильтры инициализируются из другого Fieldset, обязательно проставим режим редактирования
        $field->modes |= F::MODE_EDIT;

        $this->filters->addField($field);
    }

    /**
     * Добавляет в фильтры один из наиболее частных и стандартных полей для фильтров: FilterName
     * @param string $type
     * @param array $params
     * @param bool $synonyms
     * @return $this
     */
    public function addFilterName(string $type = F::TYPE_STRING, array $params = [], bool $synonyms = false)
    {
        if (!isset($params['name'])) {
            $params['name'] = Config::$lang === 'ru' ? 'Поиск по тексту' : 'Search Text';
        }

        if (!isset($params['sqlWhere'])) {
            $params['sqlWhere'] = "Name LIKE '%{value}%'";
            if ($synonyms) {
                $params['sqlWhere'] .= " OR Synonyms LIKE '%{value}%'";
            }
        }

        $this->addFilter(F::create('FilterName', $type, $params));

        return $this;
    }

    public function addFilterNameComaSeparated(array $params = [], $sqlVarName = 'Name', $normalStrLen = 0, $fullLike = false, $isIntField = false)
    {
        if (!isset($params['name'])) {
            $params['name'] = Config::$lang === 'ru' ? 'Номера' : 'Text';
        }

        if (!isset($params['sqlWhere'])) {
            $params['sqlWhere'] = function($filterValue) use ($sqlVarName, $normalStrLen, $fullLike, $isIntField) {
                return Db::getSqlWhereSeparatedByComa($filterValue, $sqlVarName, $normalStrLen, $fullLike, $isIntField);
            };
        }

        $fieldKey = 'Filter' . $sqlVarName;
        $this->addFilter(F::createText($fieldKey, $params));

        return $this;
    }

    /**
     * Добавляет сразу два фильтра: <$fieldKey>From и <$fieldKey>Until
     * @param string|F $field
     * @param array $params
     * @return $this
     */
    public function addFiltersDatesFromUntil($field, array $params = [])
    {
        // Первым параметром может быть передано готовое поле (FieldAbstract) со всеми настройами
        // а может быть передан только ключ (string)
        if ($field instanceof F) {
            $fieldKey = $field->key;
            $baseField = $field;
        } else {
            // первый параметр это не целое поле, а всего лишь ключ (string)
            $fieldKey = $field;
            $baseField = new FieldDate('tmp');
        }

        $params['filterFromUntil'] = true;

        // Добавим фильтр From
        $this->addFilter($baseField->getClone($fieldKey . 'From', $params));

        // Добавим фильтр Until
        $this->addFilter($baseField->getClone($fieldKey . 'Until', $params));

        return $this;
    }

    /**
     * Добавляет фильтр по году
     * Интервал годов инициализируется двумя способами:
     * 1) либо заданием таблицы и имени пол, по которым необходимо найти минимальный и максимальный год
     * 2) либо значением минимального и максимального года в явном виде
     * Явный вид имеет приоритет над таблица+поле (если вдруг все 4 параметра будут переданы в функцию)
     * @param string $tableName
     * @param string $dateField
     * @param int $yearFrom
     * @param int $yearTill
     * @return FieldSelect
     */
    public function addFilterYear(
        string $tableName = null,
        string $dateField = null,
        int $yearFrom = null,
        int $yearTill = null
    ) {
        //Определим год начала
        if (!$yearFrom) {
            //Если переданы таблица и поле по котором искать год начала, сделаем запрос к базе
            if ($tableName && $dateField) {

                $query = "SELECT DATE_FORMAT(min(" . $dateField . "), '%Y')
                          FROM " . Db::quote($tableName) . " WHERE " . $dateField . " != '0000-00-00'";
                $yearFrom = Db::getOne($query);
            } else {
                $yearFrom = date('Y');
            }
        }

        //Определим год конца
        if (!$yearTill) {
            //Если переданы таблица и поле по котором искать год начала, сделаем запрос к базе
            if ($tableName && $dateField) {
                $query = "SELECT DATE_FORMAT(max(" . $dateField . "), '%Y')
                          FROM " . Db::quote($tableName);
                $yearTill = Db::getOne($query);
            } else {
                $yearTill = date('Y');
            }
        }

        $years = range($yearFrom, $yearTill);
        $years = array_reverse($years);
        $options = array_combine($years, $years);

        $filterYear = F::createSelect('Year', [
            'name' => Config::$lang === 'ru' ? 'Год' : 'Year',
            'default' => date('Y'),
            'options' => $options,
            'empty' => false,
            'any' => false
        ]);

        if ($dateField) {
            $filterYear->setSqlWhere("YEAR(`$dateField`) = {value}");
        }

        $this->addFilter($filterYear);

        return $filterYear;
    }

    /**
     * Возвращает значение определённого фильтра. Если у пользователя данное значение не задано явно, то будет
     * возвращено значение по умолчанию
     * @param string $fieldKey
     * @return null|number|string
     */
    public function getFilterValue(string $fieldKey)
    {
        if (!$this->filters) {
            return null;
        }
        return $this->filters->getValue($fieldKey);
    }

    /**
     * Возвращает занчения всех полей, в том числе знаяния по умолчанию
     * @return array $values
     */
    public function getFiltersValues(): array
    {
        if (!$this->filters) {
            return [];
        }
        return $this->filters->getValues();
    }

    public function getFilterField($fieldKey)
    {
        return $this->filters->getField($fieldKey);
    }

    public function setFiltersValues(array $newFilterValues)
    {
        $this->filters->setValues($newFilterValues);
    }

    public function setFilterValue(string $fieldKey, $newValue)
    {
        $this->filters->setValue($fieldKey, $newValue);
    }

    /**
     * Загружает значения фильтров из POST переменных
     */
    public function proceedFilters()
    {
        // В методе initFilters() в классе потомке должны были быть определены все фильтры
        $this->filters = new Filters($this->getTableName());
        $this->initFilters();
        if (!$this->hasFilters()) {
            // В классе потомке фильтры так и не были определены, ничего не делаем
            return;
        }

        $this->filters->loadAndSave();
    }

    public function getFiltersHtml()
    {
        return $this->filters->getHtml($this->allowFiltersLayoutChange, $this->filtersLayout, $this->filtersDoNotReset);
    }

    public function getFiltersSqlWhere()
    {
        return $this->filters->getSqlWhere();
    }

    public function getFilterSqlWhere(string $fieldKey)
    {
        return $this->filters->getFieldSqlWhere($fieldKey);
    }

    protected function getFiltersHiddenInput()
    {
        return '<input type="hidden" name="filterValues" value="'
            . htmlspecialchars(json_encode($this->getFiltersValues()))
            . '">';
    }

    public function getHtmlTableForAjax($printHeadBlock = true, $printIfEmpty = false)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $outHtml = parent::getHtmlTableForAjax($printHeadBlock, $printIfEmpty);
        if ($this->hasFilters()) {
            $outHtml .= $this->getFiltersHiddenInput();
        }

        return $outHtml;
    }
}
