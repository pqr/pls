<?php

namespace Light\Traits;

use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldSelect;
use Light\Fieldset;
use Light\Filters;
use Light\FiltersBuilderInterface;
use Light\Misc;
use Light\Response;

trait TableFilterable
{
    /** @var Filters */
    protected $filters;

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
     * Метод для переопределяни классов потомков
     * Добавлять фильтры можно вызовом $this->addFilters(...) или $this->addFilter(...)
     */
    protected function initFilters()
    {
        // Реализация по умолчанию будет искать файл с таким же именем, что текущий класс,
        // с добавлением слова Filter в конце
        // Если объект не задан явно, попробуем найти соответсвующий класс рядом с текущим файлом модуля
        $tableClass = $this::class;
        if (!str_starts_with($tableClass, 'class@anonymous')) {
            $filtersClass = $tableClass . 'Filters';
            if (Misc::classExists($filtersClass)) {
                $interfaces = class_implements($filtersClass);
                if (in_array(FiltersBuilderInterface::class, $interfaces)) {
                    call_user_func($filtersClass . '::init', $this->filters);
                    $this->filtersLayout = call_user_func($filtersClass . '::getLayout');
                }
            }
        }
    }

    public function addFilters(...$fields): void
    {
        $this->filters->addFilters($fields);
    }

    public function addFilter(F $field): void
    {
        $this->filters->add($field);
    }

    protected function getFiltersFieldset(): Fieldset
    {
        return $this->filters->getFieldset();
    }

    protected function hasFilters(): bool
    {
        return $this->filters && !$this->getFiltersFieldset()->isEmpty();
    }

    /**
     * @param string|array $fieldKeys
     * @return $this
     */
    public function addFilterText($fieldKeys, array $params = [])
    {
        $this->filters->addManyText($fieldKeys, $params);
        return $this;
    }

    /**
     * Добавляет сразу два фильтра: <$fieldKey>From и <$fieldKey>Until
     * @param string|F $field
     * @return $this
     */
    public function addFiltersDatesFromUntil($field, array $params = [])
    {
        $this->filters->addDatesFromUntil($field, $params);
        return $this;
    }

    /**
     * Добавляет фильтр по году
     * Интервал годов инициализируется двумя способами:
     * 1) либо заданием таблицы и имени пол, по которым необходимо найти минимальный и максимальный год
     * 2) либо значением минимального и максимального года в явном виде
     * Явный вид имеет приоритет над таблица+поле (если вдруг все 4 параметра будут переданы в функцию)
     * @return FieldSelect
     * @deprecated use addFilterYear2()
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
                $yearFrom = (int)date('Y');
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
                $yearTill = (int)date('Y');
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
            'any' => false,
        ]);

        if ($dateField) {
            $filterYear->setSqlWhere("YEAR(`$dateField`) = {value}");
        }

        $this->addFilter($filterYear);

        return $filterYear;
    }

    public function addFilterYear2(string $fieldKey, string $filterFieldKey, int $yearFrom, int $yearUntill = null, array $params = [])
    {
        $this->filters->addYear($fieldKey, $filterFieldKey, $yearFrom, $yearUntill, $params);
        return $this;
    }

    /**
     * Возвращает значение определённого фильтра. Если у пользователя данное значение не задано явно, то будет
     * возвращено значение по умолчанию
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

    public function setFiltersValues(array $newFilterValues): void
    {
        $this->filters->setValues($newFilterValues);
    }

    public function setFilterValue(string $fieldKey, $newValue): void
    {
        $this->filters->setValue($fieldKey, $newValue);
    }

    /**
     * Загружает значения фильтров из POST переменных
     */
    public function proceedFilters(): void
    {
        // В методе initFilters() в классе потомке должны были быть определены все фильтры
        $this->filters = new Filters($this->getTableName());
        $this->initFilters();

        if (isset($_GET['select2ajax'])) {
            $this->proceedFiltersSelect2ajax();
            ApplicationAbstract::terminate();
            return;
        }

        if (!$this->hasFilters()) {
            // В классе потомке фильтры так и не были определены, ничего не делаем
            return;
        }

        // Если есть POST или GET параметр с новыми значениями фильтров, применим их!
        if (isset($_REQUEST['filterValues'])) {
            $this->filters->proceedFromRequestJson($_REQUEST['filterValues']);
            return;
        }

        // Если в запросе не было новых значений фильтров, то загрузим текущие из базы данных
        $this->filters->loadCurrentFromDb();
    }

    protected function proceedFiltersSelect2ajax()
    {
        // Иначе ищем поле среди полей объекта или подтаблиц
        $fieldKey = $_GET['select2ajax'];

        // Это может быть multiple поле, в этом случае fieldKey на конце содержит '[]'
        $fieldKey = str_replace('[]', '', (string) $fieldKey);

        $res = $this->getFiltersFieldset()->proceedSelect2ajax($fieldKey);
        if ($res === null) {
            Response::abort(400, "Unknown field $fieldKey of field is not Select");
        }
        Response::json($res);
    }

    public function getFiltersHtml()
    {
        return $this->filters->getHtml($this->allowFiltersLayoutChange, $this->filtersLayout);
    }

    public function getFiltersSqlWhere()
    {
        return $this->filters->getSqlWhere();
    }

    public function getFilterSqlWhere(string $fieldKey)
    {
        return $this->filters->getFieldSqlWhere($fieldKey);
    }

    public function getDataQueryWithFiltersWhere(string $querySelect): string
    {
        return $querySelect . " WHERE " . $this->getFiltersSqlWhere();
    }

    protected function getFiltersHiddenInput()
    {
        return '<input type="hidden" name="filterValues" value="'
            . htmlspecialchars(json_encode($this->getFiltersValues()))
            . '">';
    }

    public function getHtmlTableForAjax()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $outHtml = parent::getHtmlTableForAjax();
        if ($this->hasFilters()) {
            $outHtml .= $this->getFiltersHiddenInput();
        }

        return $outHtml;
    }
}
