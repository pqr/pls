<?php

namespace Light\Traits;

use Light\Db;
use Light\DbLog;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldInputAbstract;
use Light\Field\FieldMonth;
use Light\Field\FieldSelect;
use Light\Field\FieldText;
use Light\Fieldset;
use Light\ObjAbstract;
use Light\SimpleObj;
use Light\Template;
use Light\User;
use Symfony\Component\DomCrawler\Crawler;

trait TableFilterable
{
    /** @var ObjAbstract */
    private $_filtersObj;

    /** @var string имя файла шаблона для вывода на экран */
    protected $_filtersTemplate;

    /** @var array некоторым фильтрам можно запретить сбрасываение по кнопке Reset */
    protected $_filtersDoNotReset;

    /** @var bool выключает возможность пользовательской настройки */
    protected $_filtersNoSettings = false;

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
    protected $_filtersDefaultTemplateArray = [];

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

    /**
     * @return Fieldset
     */
    protected function getFiltersFieldset()
    {
        if (!$this->_filtersObj) {
            return null;
        }

        return $this->_filtersObj->getFieldset();
    }

    protected function hasFilters()
    {
        if (!$this->_filtersObj) {
            return false;
        }

        return !$this->_filtersObj->getFieldset()->isEmpty();
    }

    protected function addFilter(F $field)
    {
        //В зависимости от типа поля, навесим доп параметры
        if ($field instanceof FieldSelect) {

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

        //Если _filtersObj ещё не проинициализирован, проинициализируем его!
        if (!$this->_filtersObj) {
            $this->_filtersObj = new SimpleObj();
        }

        $this->_filtersObj->addField($field);
    }

    /**
     * Добавляет в фильтры один из наиболее частных и стандартных полей для фильтров: FilterName
     * @param string $type
     * @param array $params
     * @param bool $synonyms
     * @return $this
     */
    public function addFilterName($type = F::TYPE_STRING, array $params = [], $synonyms = false)
    {
        if (!isset($params['name'])) {
            $params['name'] = 'Search Text';
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

    /**
     * Добавляет сразу два фильтра: <$fieldKey>From и <$fieldKey>Until
     * @param string|F $field
     * @param array $params
     * @return $this
     */
    public function addFiltersDatesFromUntil($field, array $params = [])
    {
        //Первым параметром может быть передано готовое поле (FieldAbstract) со всеми настройами
        //а может быть передан только ключ (string)
        if ($field instanceof F) {
            $fieldKey = $field->key;
            $baseField = $field;
        } else {
            //первый параметр это не целое поле, а всего лишь ключ (string)
            $fieldKey = $field;
            $baseField = new FieldDate('tmp');
        }

        $params['filterFromUntil'] = true;

        //Добавим фильтр From
        $this->addFilter($baseField->getClone($fieldKey . 'From', $params));

        //Добавим фильтр Until
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
     * @return $this
     */
    public function addFilterYear($tableName = null, $dateField = null, $yearFrom = null, $yearTill = null)
    {
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

        $fieldKey = 'Year';
        $fieldParams = ['default' => date('Y'), 'options' => $options, 'empty' => false, 'any' => false];

        $this->addFilter(F::createSelect($fieldKey, $fieldParams));

        return $this;
    }

    private function getDefaultValue($fieldKey)
    {
        if (!$this->_filtersObj) {
            return null;
        }

        if (!$this->_filtersObj->hasField($fieldKey)) {
            return null;
        }

        return $this->_filtersObj->getField($fieldKey)->default;
    }

    public function getFilterValue($fieldKey)
    {
        if (!$this->_filtersObj) {
            return null;
        }

        return $this->_filtersObj[$fieldKey] ?? $this->getDefaultValue($fieldKey);
    }

    /**
     * Возвращает занчения всех полей, в том числе знаяния по умолчанию - используется для обновления фильтров на экране
     * @return array $values
     */
    public function getFiltersValues()
    {
        if (!$this->_filtersObj) {
            return [];
        }

        $values = [];
        foreach ($this->_filtersObj->getFieldset()->getKeys() as $fieldKey) {
            $values[$fieldKey] = $this->getFilterValue($fieldKey);
        }

        return $values;
    }

    public function getFilterField($fieldKey)
    {
        if (!$this->_filtersObj) {
            return null;
        }

        return $this->_filtersObj->getField($fieldKey);
    }

    public function setFiltersValues(array $newFilterValues)
    {
        if (!$this->_filtersObj) {
            return;
        }

        $this->_filtersObj->setData([]);

        foreach ($newFilterValues as $fieldKey => $value) {
            if ($this->_filtersObj->hasField($fieldKey)) {
                $this->_filtersObj[$fieldKey] = $value;
            }
        }
    }

    public function setFilterValue($fieldKey, $newValue)
    {
        if (!$this->_filtersObj) {
            return;
        }

        if (!$this->_filtersObj->hasField($fieldKey)) {
            return;
        }

        $this->_filtersObj[$fieldKey] = $newValue;
    }

    /**
     * Загружает значения фильтров из POST переменных
     */
    public function proceedFilters()
    {
        $this->initFilters();
        if ($this->_filtersObj) {
            //Если есть параметр с новыми значениями фильтров, применим их!
            if (isset($_REQUEST['filterValues'])) {

                $newFilterValues = $this->filtersJsonDecode($_REQUEST['filterValues']);
                //Прогоним значения массива через функции getDataFromRequest для каждого поля
                //чтобы нормализировать значения, например даты первести в формат YYYY-MM-DD
                $newFilterValues = $this->_filtersObj->getDataFromRequest($newFilterValues);

            } else {

                //Если параметра с новыми значениями фильтров не было, загрузим текущие фильтры из базы
                $newFiltersJson = $this->filtersLoadCurrent();
                $newFilterValues = $newFiltersJson ? $this->filtersJsonDecode($newFiltersJson) : [];

            }

            //Если какие-то фильтры заданы как editMandatory - то пустые значения нужно превратить в default
            //Будем делать это только для полей ввода типа input type="text" (т.е. наследников FieldInputAbstract)
            foreach ($this->_filtersObj->getFieldset() as $field) {

                if ($field instanceof FieldInputAbstract
                    && $field->editMandatory
                    && (!isset($newFilterValues[$field->key]) || !$newFilterValues[$field->key])
                ) {
                    $newFilterValues[$field->key] = $field->default;
                }

            }

            //Применим получившиеся значения $newFiltersValues к текущему объекту $this->_filtersObj
            $this->setFiltersValues($newFilterValues);

            //Сохраним текущие значения фильтров ($this->_values) в таблицу user_filter
            //только в том случае, если был активный запрос от пользователя на применение новых фильтров
            if (isset($_REQUEST['filterValues'])) {
                $this->filtersSave();
            }
        }
    }

    /**
     * Раскодирует JSON строку, проверяя существование описанных полей в $this->_fieldset
     * @param string $filtersJson
     * @return array
     */
    private function filtersJsonDecode($filtersJson)
    {
        $outValues = [];

        $values = json_decode($filtersJson, true);
        if (is_array($values)) {
            foreach ($values as $fieldKey => $value) {
                if ($this->_filtersObj->hasField($fieldKey)) {
                    $outValues[$fieldKey] = $value;
                }
            }
        }

        return $outValues;
    }

    private function filtersLoadCurrent()
    {
        return Db::getOne(
            "SELECT FiltersJSON
             FROM user_filter
             WHERE CreatedBy = @i
                 AND TableName = @s"
            , User::getId(), $this->getTableName());
    }

    private function filtersSave()
    {
        $notDefaultValues = [];
        $filterValues = [];
        foreach ($this->_filtersObj->getData() as $fieldKey => $value) {

            //Если такое поле есть в fieldset и его значение отличается от значения по умолчанию - запишем в JSON
            if ($this->_filtersObj->hasField($fieldKey) &&
                $this->_filtersObj->getField($fieldKey)->default !== $value
            ) {
                $notDefaultValues[$fieldKey] = $value;
                $filterValues[] = [
                    'FieldKey' => $fieldKey,
                    'Value' => $value,
                    'Text' => $this->_filtersObj->getField($fieldKey)->getText($value)
                ];
            }
        }

        //Сначала сохраним фильтры в таблице user_filter
        $query = "REPLACE INTO user_filter (CreatedBy,TableName,FiltersJSON) VALUES (@i, @s, @s)";
        Db::query($query, User::getId(), $this->getTableName(), json_encode($notDefaultValues));

        if ($notDefaultValues) {

            $userId = User::getId();
            $tableId = DbLog::getTableId($this->getTableName());

            // Запишем в таблицу с историей "последних пяти фильтров" user_filter_history
            // Не забудем добавить в JSON текстовые описания значений (удобно для выпадающих списков или дат и т.п.)
            $filterValuesJson = json_encode($filterValues);
            Db::replaceRow([
                'CreatedBy' => $userId,
                'TableID' => $tableId,
                'FilterValues' => $filterValuesJson,
                'FilterValuesMD5' => md5($filterValuesJson)
            ], 'user_filter_history');

            // Удалим старые значения в истории, оставив только последние 10
            // Сначала получим ID последней 10-й записи
            $tenthId = Db::getOne("SELECT ID FROM user_filter_history WHERE CreatedBy = $userId AND TableID = $tableId ORDER BY ID DESC LIMIT 9,1");
            if ($tenthId) {
                // Есть такой ID, занчит у пользователя накопилось достаточно записей в истории. Удалим всё что более старое
                Db::query("DELETE FROM user_filter_history WHERE CreatedBy = $userId AND TableID = $tableId AND ID < $tenthId");
            }

            if ($this->getTableName() !== 'FiltersLog') {

                //Дополнительно запишем в лог таблицу для последующего анализа только те фильтры, которые реально имеют не default значение
                $insertQueryArr = [];
                foreach ($notDefaultValues as $fieldKey => $value) {
                    $fieldId = DbLog::getFieldId($tableId, $fieldKey);
                    $insertQueryArr[] = "($userId,$tableId,$fieldId," . Db::escString($value) . ")";
                }

                $insertQuery = "INSERT INTO user_filter_log (CreatedBy, TableID, FieldID, Value) VALUES "
                    . implode(',', $insertQueryArr);

                Db::query($insertQuery);
            }
        }
    }

    protected function filtersGetTemplate()
    {
        if ($this->_filtersTemplate) {
            return $this->_filtersTemplate;
        }

        //Проверим, есть ли шаблон для отображения в нескольких папках
        $parts = explode('\\', get_class($this));
        $className = array_pop($parts);;
        $namespaces = $parts;

        //Имя файла шаблона по умолчанию выглядит как имя класса таблицы с добавлением слова Filters
        $templateFile = "{$className}Filters.tpl.php";

        //Попробуем найти этот файл шаблона в папке, в которой лежит текущий файл таблицы

        $filepathFromSrc = implode('/', $namespaces) . "/$templateFile";
        //Существование файла проверяем по абсолютному пути
        if (file_exists(LIGHT_DIR_ROOT . "/src/$filepathFromSrc")) {
            //Есть такой файл - возвращаем путь относительно src
            return $filepathFromSrc;
        }

        // Пробуем найти файл в стандартных папках
        if (Template::findfile($templateFile)) {
            // Если найден в стандартных папках, то возвращаем его имя как есть (без какого-либо указания пути)
            return $templateFile;
        }

        return null;
    }

    protected function filtersGetTplData()
    {
        //Заполним массив $values актуальными значениями фильтров, чтобы передать в шаблон
        $values = $this->getFiltersValues();
        return $this->_filtersObj->getFieldset()->getTplData($values, F::MODE_EDIT);
    }

    public function getFiltersHtml()
    {
        if (!$this->_filtersObj) {
            return '';
        }

        $tdata = $this->filtersGetTplData();
        $tdata['noSettings'] = $this->_filtersNoSettings;

        $tableId = DbLog::getTableId($this->getTableName());
        $settings = Db::getRow("SELECT ID, Sort, Sort_deprecated, Invisible, Invisible_deprecated, Defaults FROM user_filter_settings WHERE TableID = @i AND CreatedBy = @i",
            $tableId,
            User::getId());

        $defaults = $settings ? json_decode($settings['Defaults'], true) : [];

        //Обернём полученную вёрстку фильтров в форму
        //Соберём все значения по умолчанию в ассоциативный массив фильтров для JS
        $fieldsForJs = [];
        /** @var $field \Light\Field\FieldAbstract */
        foreach ($this->_filtersObj->getFieldset() as $field) {

            if (is_array($this->_filtersDoNotReset) && in_array($field->key, $this->_filtersDoNotReset)) {
                $donotreset = true;
            } else {
                $donotreset = false;
            }

            $fieldsForJs[$field->key] = [
                'name' => $field->getName(),
                'def' => $defaults[$field->key] ?? $field->default,
                'donotreset' => $donotreset,
                'type' => $field->getType(),
                'noCheckInvisible' => $field->noCheckInvisible
            ];
        }

        $tableId = DbLog::getTableId($this->getTableName());
        $savedFilters = Db::getAll("
                SELECT Name, FilterValues
                FROM user_filter_saved
                WHERE TableID = @i AND CreatedBy = @i
                ORDER BY ID DESC
                LIMIT 10
            ", $tableId, User::getId());

        foreach ($savedFilters as &$filter) {
            // Декодируем JSON сохранённый в базе
            $filter['FilterValues'] = json_decode($filter['FilterValues'], true);
        }

        $template = $this->filtersGetTemplate();
        $content = null;
        $settingsSort = json_decode($settings['Sort'] ?? "", true);
        $settingsInvisible = json_decode($settings['Invisible'] ?? "", true);
        $newSystemFlag = true;

        if (!$this->_filtersDefaultTemplateArray && !$template) {

            // если шаблон не задан - автоматически сгенерируем кортеж из 1 колонки
            $filtersFirstColumn = [];
            foreach ($this->getFiltersFieldset() as $field) {
                if (property_exists($field, 'filterFromUntil') && $field->filterFromUntil) {
                    if (substr($field->key, -4) == 'From') {
                        $filtersFirstColumn[] = preg_replace('/(From|Until)$/', '', $field->key);
                    }
                    continue;
                }
                $filtersFirstColumn[] = $field->key;
            }

            $this->_filtersDefaultTemplateArray = [$filtersFirstColumn];

            if ($settings && $settings['Sort_deprecated'] && $settings['Invisible_deprecated'] && !$settingsSort && !$settingsInvisible) {

                // если настройки пользователей не переведены под новый формат, переведем их налету
                $oldSort = json_decode($settings['Sort_deprecated'], true);
                $oldSort = reset($oldSort);
                $oldInvisible = json_decode($settings['Invisible_deprecated'], true);
                $newSort = [];
                $newInvisible = [];
                foreach ($oldSort as $defaultOrder) {
                    if (is_numeric($defaultOrder)) {
                        $defaultOrderKey = $defaultOrder - 1;
                        if (isset($filtersFirstColumn[$defaultOrderKey])) {
                            $newSort[] = $filtersFirstColumn[$defaultOrderKey];
                            $newInvisible[$filtersFirstColumn[$defaultOrderKey]] = $oldInvisible[$defaultOrder];
                        }
                    }
                }
                $settingsSort = [$newSort];
                $settingsInvisible = $newInvisible;

                // сохраним настройки в базу
                Db::updateRow([
                    'Sort' => json_encode($settingsSort),
                    'Invisible' => json_encode($settingsInvisible)
                ], 'user_filter_settings', $settings['ID']);
            }
        }

        // Todo: если указан флаг $this->_filtersNoSettings, не пытаться пересоритровать поля вообще

        // в случае, если указан кортеж, предварительно должны быть переведены настройки пользователей под новый формат
        if ($this->_filtersDefaultTemplateArray && (
                !$settings
                || !$template
                || (!$settings['Sort_deprecated'] && !$settings['Invisible_deprecated'])
                || ($settingsSort && $settingsInvisible)
            )
        ) {

            // заранее подготовим все фильтры в один массив, для простоты обращения к ним
            $allFieldsTemplates = [];
            $defaultOrder = [];

            foreach ($this->_filtersDefaultTemplateArray as $colIndex => $fieldKeysInCol) {

                foreach ($fieldKeysInCol as $fieldIndex => $fieldTemplate) {

                    if (is_string($fieldTemplate)) {

                        // если указана просто строка - значит это стандартный фильтр
                        // в данном случае в переменной $fieldIndex оказался числовой индекс в массиве,
                        // а в переменной $fieldTemplate находится интересущий нас ключ поля-фильтра
                        $fieldKey = $fieldTemplate;


                    } else {

                        // В противном случае, если поле описано не простой строкой,
                        // значит это либо массив из label и func
                        // либо это просто анонимная функция
                        // В этом случае ключём поля будет является $fieldIndex (который внезапно оказывается не числом)
                        $fieldKey = $fieldIndex;

                    }

                    $allFieldsTemplates[$fieldKey] = $fieldTemplate;
                    $defaultOrder[$colIndex][] = $fieldKey;
                }

                if ($colIndex === 0) {
                    // В конце первой колонки добавим специальный элемент - кнопки!
                    $defaultOrder[$colIndex][] = 'FilterButtons';
                }
            }

            // Проверим, не появились ли новые фильтры
            $newFieldKeys = [];
            if ($settingsSort) {
                // Берём полный список ключей-полей
                $newFieldKeys = array_keys($allFieldsTemplates);
                // В цикле последовательно вычитаем из него те ключи, которые явно указаны в настройках пользователя
                // Таким образом в конце останутся только новые ключи, которых раньше в настройках пользователя не было
                foreach ($settingsSort as $settingsColFieldKeys) {
                    $newFieldKeys = array_diff($newFieldKeys, $settingsColFieldKeys);
                }
            }

            Template::setTdata($tdata);

            $columnsOrder = $settingsSort ?? $defaultOrder;

            $outHtml = '<table><tr>';
            foreach ($columnsOrder as $colIndex => $fieldKeysInCol) {

                $outHtml .= '<td><table class="filters">';

                foreach ($fieldKeysInCol as $fieldKey) {
                    // проверяем, есть ли такой фильтр в шаблоне, так как фильтр может быть удален
                    if (isset($allFieldsTemplates[$fieldKey])) {
                        $invisible = isset($settingsInvisible[$fieldKey]) && $settingsInvisible[$fieldKey];
                        $outHtml .= $this->getFilterHtml($allFieldsTemplates[$fieldKey], $fieldKey, $tdata, $invisible);
                    }
                }

                // если есть новые фильтры, добавим их в конец последней колонки
                if ($colIndex == (count($columnsOrder) - 1) && $newFieldKeys) {
                    foreach ($newFieldKeys as $fieldKey) {
                        $outHtml .= $this->getFilterHtml($allFieldsTemplates[$fieldKey], $fieldKey, $tdata);
                    }
                }

                if ($colIndex === 0) {
                    $outHtml .= Template::filtersButtonsRow();
                }

                $outHtml .= '</table></td>';
            }
            $outHtml .= '</tr></table>';

            $content = $outHtml;
            $defaultSort = $defaultOrder;
        }

        // старая система
        /** @deprecated */
        if (!$content) {

            $newSystemFlag = false;

            if (!$template) {
                $content = $this->getFiltersAutoHtml($tdata);
            } else {
                $content = Template::parseTpl($template, $tdata);
            }

            if (!$this->_filtersNoSettings) {
                // Отсортируем фильтры в соответствии с настройками
                $crawler = new Crawler();
                $crawler->addHTMLContent($content, 'UTF-8');

                // пронумеруем все tr чтобы установить значения сортировки по-умолчанию
                $sort = 1;
                $defaultSort = [];
                $crawler->filter('table.filters')->each(function (Crawler $tableNode, $tableIndex) use (
                    &$sort,
                    &
                    $defaultSort
                ) {
                    $tableNode->filter('table.filters > tr')->each(function (Crawler $trNode, $i) use (
                        &$sort,
                        &$defaultSort,
                        $tableIndex
                    ) {
                        $trNode->getNode(0)->setAttribute('data-sort', $sort);
                        $defaultSort[$tableIndex][] = $sort;
                        $sort++;
                        return $trNode;
                    });
                    return $tableNode;
                });

                // отсортируем в соответствии с пользовательскими настройками
                if ($settings) {
                    if ($settings['Sort_deprecated']) {
                        $sort = json_decode($settings['Sort_deprecated'], true);
                        $crawler->filter('table.filters')->each(function (Crawler $tableNode, $i) use ($sort, $crawler) {
                            if (isset($sort[$i])) {
                                foreach ($sort[$i] as $tableSort) {
                                    try {
                                        $trNode = $crawler->filter("table.filters tr[data-sort='" . $tableSort . "']")->getNode(0);
                                        if ($trNode) {
                                            $tableNode->getNode(0)->appendChild($trNode);
                                        }
                                    } catch (\Exception $e) {
                                    }
                                }
                            }
                            return $tableNode;
                        });
                    }

                    if ($settings['Invisible_deprecated']) {
                        $invisible = json_decode($settings['Invisible_deprecated'], true);
                        foreach ($invisible as $sortValue => $invisibleValue) {
                            if ($invisibleValue) {
                                $domelem = $crawler->filter("table.filters tr[data-sort='" . $sortValue . "']")->getNode(0);
                                $class = $domelem->getAttribute('class');
                                $domelem->setAttribute('class',
                                    ($class ? $class . ' filter-invisible' : 'filter-invisible'));
                            }
                        }
                    }
                }

                $content = $crawler->html();
            }
        }

        $formTdata = [
            'tableName' => $this->getTableName(),
            'fields' => $fieldsForJs,
            'content' => $content,
            'savedFilters' => $savedFilters,
            'defaultSort' => $defaultSort ?? [],
            'noSettings' => $this->_filtersNoSettings,
            'newSystemFlag' => $newSystemFlag
        ];

        return Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/table/filters/form.tpl.php', $formTdata);

    }

    private function getFilterHtml($fieldTemplate, $fieldKey, array $tdata, $invisible = false): string
    {
        $cssClass = $invisible ? 'filter-invisible' : null;

        if (is_string($fieldTemplate)) {

            $fieldTemplate = $this->getFilterField($fieldKey);
            if (!$fieldTemplate) {
                $fieldFrom = $this->getFilterField($fieldKey . 'From');
                $fieldUntil = $this->getFilterField($fieldKey . 'Until');

                /** @var FieldDate $fieldFrom */
                /** @var FieldDate $fieldUntil */
                if ($fieldFrom && $fieldUntil
                    && $fieldFrom->getType() === 'Date' && $fieldFrom->filterFromUntil
                    && $fieldUntil->getType() === 'Date' && $fieldUntil->filterFromUntil) {
                    return Template::datesFromUntil($fieldKey, $cssClass, $fieldKey);
                }
            }
            return Template::c($fieldKey, false, $cssClass, $fieldKey);

        }

        if (is_array($fieldTemplate)) {

            // В массиве с настройками колонок фильтров данный фильтр был описан массивом
            $title = Template::title($fieldKey);

            if (isset($fieldTemplate['label'])) {
                // Если в массиве явно задан label, то используем его
                $label = $fieldTemplate['label'];

            } else {

                // Если же label не задан, то попробуем определить label по $fieldKey
                if ($this->getFilterField($fieldKey)) {
                    $label = Template::label($fieldKey);
                } else {
                    // Если же поля-фильтра с таким $fieldKey нет, просто выведем этот $fieldKey
                    $label = $fieldKey;
                }
            }
            $html = is_callable($fieldTemplate['func']) ? call_user_func($fieldTemplate['func'], $tdata) : '<span class="bad">Html function is not defined!</span>';
            return Template::control($title, $label, $html, $cssClass, $fieldKey);

        }

        if (is_callable($fieldTemplate)) {

            // В массиве с настройками колонок фильтров данный фильтр был описан просто функцией
            $title = Template::title($fieldKey);
            $label = Template::label($fieldKey);
            $html = call_user_func($fieldTemplate, $tdata);
            return Template::control($title, $label, $html, $cssClass, $fieldKey);

        }

        return '<span class=bad>No template for field ' . htmlspecialchars($fieldKey) . '</span>';
    }

    private function getFiltersAutoHtml($tdata)
    {
        Template::setTdata($tdata);
        $outHtml = '<table class="filters">';
        foreach ($this->getFiltersFieldset() as $field) {
            $outHtml .= Template::c($field->key);
        }
        $outHtml .= Template::filtersButtonsRow();
        $outHtml .= '</table>';
        return $outHtml;
    }

    public function getFiltersSqlWhere()
    {
        if (!$this->_filtersObj) {
            return '';
        }

        return $this->_filtersObj->getFieldset()->getSqlWhere($this->getFiltersValues());
    }

    public function getFilterSqlWhere($fieldKey)
    {
        if (!$this->_filtersObj) {
            return '';
        }

        $filterValue = $this->getFilterValue($fieldKey);
        $allFilterValues = $this->getFiltersValues();
        $field = $this->_filtersObj->getField($fieldKey);

        return $field->getEvaluatedSqlWhere($filterValue, $allFilterValues);
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
