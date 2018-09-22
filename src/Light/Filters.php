<?php

namespace Light;

use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldInputAbstract;
use Light\Traits\FieldsetAwareTrait;

/**
 * Класс с методами по работе с фильтрами
 */
class Filters
{
    use FieldsetAwareTrait;

    private $tableName;
    private $tableId;
    private $fieldset;
    private $data;

    public function __construct(string $tableName)
    {
        $this->fieldset = new Fieldset();
        $this->tableName = $tableName;
        $this->tableId = (int)DbLog::getTableId($tableName);
    }

    public function getFieldset(): Fieldset
    {
        return $this->fieldset;
    }

    private function getDefaultValue(string $fieldKey)
    {
        if (!$this->fieldset->hasField($fieldKey)) {
            return null;
        }

        return $this->fieldset->getField($fieldKey)->default;
    }

    public function getValue(string $fieldKey)
    {
        return $this->data[$fieldKey] ?? $this->getDefaultValue($fieldKey);
    }

    public function getValues(): array
    {
        $values = [];
        foreach ($this->fieldset->getKeys() as $fieldKey) {
            $values[$fieldKey] = $this->getValue($fieldKey);
        }
        return $values;
    }

    /**
     * Заполняет массив $this->data новыми значениями фильтров, но только такими, которые реально существуют в fieldset
     * @param array $newFilterValues
     */
    public function setValues(array $newFilterValues)
    {
        $this->data = [];
        foreach ($newFilterValues as $fieldKey => $newValue) {
            $this->setValue($fieldKey, $newValue);
        }
    }

    public function setValue(string $fieldKey, $newValue)
    {
        if ($this->hasField($fieldKey)) {
            $this->data[$fieldKey] = $newValue;
        }
    }

    /**
     * Дополняет массив сырых значений значениями по умолчанию для полей FieldInputAbstract отмеченных как editMandatory
     * @param array $values
     * @return array
     */
    private function fillDefaultForEditMandatory(array $values): array
    {
        $outValues = $values;

        // Дополним этот массив значениями по умолчанию для обязательных полей (editMandatory)
        // Будем делать это только для полей ввода типа input type="text" (т.е. наследников FieldInputAbstract)
        foreach ($this->fieldset as $field) {

            if ($field instanceof FieldInputAbstract
                && $field->editMandatory
                && (!isset($outValues[$field->key]) || !$outValues[$field->key])
            ) {
                $outValues[$field->key] = $field->default;
            }

        }

        return $outValues;
    }

    public function loadAndSave()
    {
        // Если есть POST или GET параметр с новыми значениями фильтров, применим их!
        if (isset($_REQUEST['filterValues'])) {

            $this->loadFromRequest();

            $notDefaultValues = $this->filtersGetNotDefaultValues();

            // Сохраним текущие значения в таблицу user_filter
            $this->saveAsCurrent($notDefaultValues);

            // Сохраним в историю последних 10 комбинаций в таблицу user_filter_history, чтобы показывать пользователю в интерфейсе
            $this->saveToHistory($notDefaultValues);

            // Дополнительно запишем в лог таблицу log_filtes для последующего анализа используемых фильтров
            $this->saveToLog($notDefaultValues);
            return;

        }

        // Если POST/GET параметра с новыми значениями фильтров не было, загрузим текущие фильтры из базы
        $this->loadCurrentFromDb();
    }

    private function loadFromRequest()
    {
        $newValues = json_decode($_REQUEST['filterValues'], true);
        if (!is_array($newValues)) {
            $newValues = [];
        } else {
            // Прогоним значения массива через функции getDataFromRequest для каждого поля
            // чтобы нормализировать значения, например даты первести в формат YYYY-MM-DD
            $newValues = $this->fieldset->getDataFromRequest($newValues);
        }

        $newValues = $this->fillDefaultForEditMandatory($newValues);
        $this->setValues($newValues);
    }

    private function loadCurrentFromDb()
    {
        $currentValues = [];
        $currentFiltersJson = Db::getOne(
            "SELECT FiltersJSON
             FROM user_filter
             WHERE CreatedBy = " . User::getId() . "
                 AND TableName = " . Db::escString($this->tableName)
        );
        if ($currentFiltersJson) {
            $jsonValues = json_decode($currentFiltersJson, true);
            if (is_array($jsonValues)) {
                $currentValues = $jsonValues;
            }
        }
        $currentValues = $this->fillDefaultForEditMandatory($currentValues);
        $this->setValues($currentValues);
    }

    /**
     * Метод возвращает текущие значения фильтров, которые отличны от default значений
     * (пригождается перед сохранением в базу данных)
     */
    private function filtersGetNotDefaultValues(): array
    {
        $notDefaultValues = [];
        foreach ($this->fieldset->getKeys() as $fieldKey) {
            $currentValue = $this->getValue($fieldKey);
            $defaultValue = $this->getDefaultValue($fieldKey);
            if ($currentValue !== $defaultValue) {
                $notDefaultValues[$fieldKey] = $currentValue;
            }
        }
        return $notDefaultValues;
    }


    /**
     * Сохраняет текущие значения фильтров в таблицу user_filter
     * @param array $notDefaultValues
     */
    private function saveAsCurrent(array $notDefaultValues)
    {
        // Сначала сохраним фильтры в таблице user_filter (таблица со знаениями текущих фильтров)
        $query = "REPLACE INTO user_filter (CreatedBy,TableName,FiltersJSON) VALUES (@i, @s, @s)";
        Db::query($query, User::getId(), $this->tableName, json_encode($notDefaultValues));
    }

    private function saveToHistory(array $notDefaultValues)
    {
        if (!$notDefaultValues) {
            return;
        }

        // Сформируем текстовые описания для всех значений фильтров и потом в готовом HTML виде сохраним их в базу
        // Это удобно при последующем отображении списка истории фильтров
        $descriptions = [];
        foreach ($notDefaultValues as $fieldKey => $value) {

            if (!$this->hasField($fieldKey)) {
                continue;
            }

            // Сформируем текстовое описание для данного значения фильтра
            $field = $this->getField($fieldKey);
            $fieldName = $field->getName();
            $fieldType = $field->getType();
            if ($fieldType === 'Date' || $fieldType === 'DateTime') {
                if (substr($fieldKey, -4) === 'From') {
                    $fieldName .= ' from';
                } elseif (substr($fieldKey, -5) === 'Until') {
                    $fieldName .= ' until';
                }
            }

            $valueText = $this->getField($fieldKey)->getText($value);
            if (mb_strlen($valueText) > 40) {
                $valueText = mb_substr($valueText, 0, 4) . '...';
            }
            $descriptions[] = "$fieldName: <strong>" . htmlspecialchars($valueText) . "</strong>";
        }

        $userId = User::getId();
        $filtersJson = json_encode($notDefaultValues);
        $dataForHistory = [
            'CreatedBy' => $userId,
            'TableID' => $this->tableId,
            'FiltersJSON' => $filtersJson,
            'Description' => implode(', ', $descriptions),
            'FilterValuesMD5' => md5($filtersJson)
        ];

        // REPLACE будет происходить по уникальному полю FilterValuesMD5
        Db::replaceRow($dataForHistory, 'user_filter_history');

        // Удалим старые значения в истории, оставив только последние 10
        // Сначала получим ID последней 10-й записи
        $tenthId = Db::getOne("SELECT ID FROM user_filter_history WHERE CreatedBy = $userId AND TableID = {$this->tableId} ORDER BY ID DESC LIMIT 9,1");
        if ($tenthId) {
            // Есть такой ID, занчит у пользователя накопилось достаточно записей в истории. Удалим всё что более старое
            Db::query("DELETE FROM user_filter_history WHERE CreatedBy = $userId AND TableID = {$this->tableId} AND ID < $tenthId");
        }
    }

    private function saveToLog(array $notDefaultValues)
    {
        if ($this->tableName === 'FiltersLog') {
            // Это особая таблица по которой лог не ведём
            return;
        }

        if (!$notDefaultValues) {
            return;
        }

        $userId = User::getId();

        $insertQueryArr = [];
        foreach ($notDefaultValues as $fieldKey => $value) {
            $fieldId = DbLog::getFieldId($this->tableId, $fieldKey);
            $insertQueryArr[] = "($userId,$this->tableId,$fieldId," . Db::escString($value) . ")";
        }

        $insertQuery = "INSERT INTO log_filter (CreatedBy, TableID, FieldID, Value) VALUES "
            . implode(',', $insertQueryArr);

        Db::query($insertQuery);
    }

    public function getSqlWhere(): string
    {
        if ($this->fieldset->isEmpty()) {
            return '1';
        }

        $values = $this->getValues();
        return $this->fieldset->getSqlWhere($values);
    }

    public function getFieldSqlWhere(string $fieldKey)
    {
        if (!$this->hasField($fieldKey)) {
            return '';
        }

        $field = $this->getField($fieldKey);
        $value = $this->getValue($fieldKey);
        $allValues = $this->getValues();

        return $field->getEvaluatedSqlWhere($value, $allValues);
    }

    /**
     * Возвращает массив с описанием layout состоящий из одной колонки
     * (все поля в одной колонки)
     * Используется если пользователь не задал вручную какой-либо $this->filtersLayout
     * @return array
     */
    private function getDefaultLayoutOneColumn(): array
    {
        // если шаблон не задан - автоматически сгенерируем кортеж из 1 колонки
        $firstColumn = [];
        foreach ($this->fieldset as $field) {

            $templateKey = $field->key;

            /** @var FieldDate $field */
            if (property_exists($field, 'filterFromUntil')
                && $field->filterFromUntil) {

                if (substr($field->key, -4) === 'From') {
                    // Нашли поле, которое является либо датой From либо датой Until
                    // В колонку будем долавлять только поле From, причём нужно отрезать суффикс From
                    // Да, имеем дело с полем типа From, отрезаем суффикс From
                    $templateKey = substr($field->key, 0, -4);
                } elseif (substr($field->key, -5) === 'Until') {
                    // Поле Until вообще не добавляем в эту автосгенерированную колонку
                    continue;
                }
            }

            $firstColumn[$templateKey] = $templateKey;
        }

        return [$firstColumn];
    }

    private function mergeWithUserLayout(array $sourceLayout, string $userLayoutJson): array
    {
        if (!$userLayoutJson) {
            // У пользователя не заданы какие-то специальные настройки сортировки
            return $sourceLayout;
        }
        $userLayout = json_decode($userLayoutJson, true);
        if (!is_array($userLayout) || !$userLayout) {
            // Не удалось распарсить json, либо у пользователя пустой массив в баз - значит нет настроек
            return $sourceLayout;
        }

        // Если у пользователя всё-таки заданы какие-то собственные настройки сортировки,
        // то полностью переформируем $templateArray
        // Перед этим подготовим плоский массив из исходных значений $templateArray
        $flatArray = [];
        foreach ($sourceLayout as $col) {
            foreach ($col as $templateKey => $templateVal) {
                $flatArray[$templateKey] = $templateVal;
            }
        }

        // Как минимум одна колонка точно будет, заготовим пустой массив
        $resultLayout = [0 => []];
        // Также подготовим массив в котором сохраним все ключи ($templateKey) упоминавшиеся в пользовательских настройках
        $usedTemplatesHash = [];
        $colIndex = 0;
        foreach ($userLayout as $userCol) {
            if (!is_array($userCol)) {
                // Какой-то странный элемент сохранён в базе в json - не массив, пропускаем
                continue;
            }

            foreach ($userCol as $templateKey) {
                // Проверим, то что сохранено в пользовательских настройках в базе ещё актуально?
                // Существует ли такой $templateKey в плоском массиве $flatArray?
                if (isset($flatArray[$templateKey])) {
                    $templateVal = $flatArray[$templateKey];
                    $resultLayout[$colIndex][$templateKey] = $templateVal;

                    // Сохраним использованный валидный ключ в отдельный массив, чтобы в конце быстро найти все новые поля
                    $usedTemplatesHash[$templateKey] = true;
                }
            }

            // Переходим к наполнению следующей колонки
            $colIndex++;
        }

        // Теперь в последнюю колонку добавим все поля, которых нет в пользовательских настройках,
        // но которые упоминались в исходном $templateArray
        // Оба массива $flatArray и $usedTemplatesHash являются плоскими
        // и в качестве ключей у них записаны ключи $templateKey,
        // а в качестве значений в первом массиве $flatArray находятся реальные значения шаблонов по умолчанию.
        // Вычитая один из другого получим массив $templateKey => $templateVal, которые не были упомянуты в
        // пользовательских настройках, т.е. это новые поля добавленные в код программы
        $unusedKeyVal = array_diff_key($flatArray, $usedTemplatesHash);
        if ($unusedKeyVal) {
            // Нашлись какие-то новые поля, не упомянутые в пользовательских настройках, добавляем в последнюю колонку
            $lasColIndex = count($resultLayout) - 1;
            $resultLayout[$lasColIndex] += $unusedKeyVal;
        }

        return $resultLayout;
    }

    private function getFieldsForJs(string $userResetDefaultsJson, array $doNotReset): array
    {
        $userResetDefaults = json_decode($userResetDefaultsJson, true);
        if (!is_array($userResetDefaults)) {
            $userResetDefaults = [];
        }
        $doNotResetHash = array_flip($doNotReset);
        $fieldsForJs = [];
        foreach ($this->fieldset as $field) {

            $fieldKey = $field->key;

            $fieldsForJs[$fieldKey] = [
                'name' => $field->getName(),
                'def' => $userResetDefaults[$fieldKey] ?? $field->default,
                'donotreset' => isset($doNotResetHash[$fieldKey]),
                'type' => $field->getType()
            ];
        }

        return $fieldsForJs;
    }

    private function getDefaultLayoutForJs(array $layout): array
    {
        $defaultLayoutForJs = [];
        foreach ($layout as $colIndex => $col) {
            foreach ($col as $templateKey => $templateVal) {
                $defaultLayoutForJs[$colIndex][] = $templateKey;
            }
        }
        return $defaultLayoutForJs;
    }

    /**
     * @param bool $allowLayoutChange
     * @param array $filtersLayout - это массив настроек заданный внутри метода $this->initFilters(), он имеет не совсем верную стуктуру с числовыми ключами
     * @param array $doNotReset
     * @return string
     * @throws \Exception
     */
    public function getHtml(bool $allowLayoutChange, array $filtersLayout, array $doNotReset): string
    {
        if ($this->fieldset->isEmpty()) {
            return '';
        }

        if (!$filtersLayout) {

            // В исходном классе не задан массив с описанием расположения полей фильтров
            // сформируем одноколоночный вариант
            $layout = $this->getDefaultLayoutOneColumn();

        } else {

            // Если же схема расположения фильтров была задана пользователем внутри $this->initFilters()
            // то её нужно её чуть-чуть улучшить
            // Для кракости в исходном массиве зачастую задаём поля без явного указания ключа в массиве $this->filtersLayout
            // и получаются числовые ключи в $templateArray - преобразуем их в нормальные строковые ключи
            $layout = [];
            foreach ($filtersLayout as $colIndex => $col) {
                foreach ($col as $key => $val) {
                    if (is_numeric($key)) {
                        if ($val === null) {
                            // Элемент null - пропускаем (иногда удобно задать вместо некоторых фильтров null,
                            // когда фильтры формируются динамически в зависимости от текущего модуля
                            continue;
                        }
                        if (!is_string($val)) {
                            throw new \Exception("В массиве filtersLayout в колонке $colIndex задан элемент с индексом $key значение которого не является строкой - такого не должно быть!");
                        }
                        $layout[$colIndex][$val] = $val;
                    } else {
                        $layout[$colIndex][$key] = $val;
                    }
                }
            }
        }

        $defaultLayoutForJs = null;
        $invisibleArray = [];

        if ($allowLayoutChange) {

            // Текущий массив $layout сохраним в отдельную структуру для вывода в js окружение
            $defaultLayoutForJs = $this->getDefaultLayoutForJs($layout);

            // Далее попробуем загрузить настроки пользователя и применить их поверх $layout
            $settings = Db::getRow(
                "SELECT ID, Sort, Invisible, Defaults 
                FROM user_filter_settings 
                WHERE TableID = {$this->tableId} 
                   AND CreatedBy = " . (int)User::getId()
            );

            if ($settings) {

                $layout = $this->mergeWithUserLayout($layout, $settings['Sort']);
                $invisibleArray = json_decode($settings['Invisible'], true);
                if (!is_array($invisibleArray)) {
                    $invisibleArray = [];
                }

            }
        }

        // Описания всех полей поместим в контекст шаблонизатора
        $tdata = $this->fieldset->getTplData($this->getValues(), F::MODE_EDIT);
        Template::setTdata($tdata);

        $content = '<table><tr>';
        foreach ($layout as $colIndex => $col) {

            $content .= '<td><ul class="filters__list">';

            foreach ($col as $templateKey => $templateVal) {
                $isInvisible = in_array($templateKey, $invisibleArray);
                $content .= $this->getFilterHtml($templateKey, $templateVal, $tdata, $isInvisible);
            }

            if ($colIndex === 0) {
                // В конце первой колонки добавляем строку с кнопками Filter и Reset
                $content .= $this->getFilterButtonsHtml($allowLayoutChange);
            }

            $content .= '</ul></td>';
        }
        $content .= '</tr></table>';

        $userResetDefaultsJson = $settings['Defaults'] ?? '';
        $fieldsForJs = $this->getFieldsForJs($userResetDefaultsJson, $doNotReset);

        $fieldsAttr = ' data-fields="' . htmlspecialchars(json_encode($fieldsForJs)) . '"';
        $defaultLayoutAttr = $defaultLayoutForJs ? ' data-defaultlayout="' . htmlspecialchars(json_encode($defaultLayoutForJs)) . '"' : '';
        $allowLayoutChangeAttr = ' data-allowlayoutchange="' . ($allowLayoutChange ? '1' : '0') . '"';

        return '
        <div class="filters">
            <form id="' . $this->tableName . '-filters-form" name="' . $this->tableName . '" class="filters-form"
                  onsubmit="return false;"
                  ' . $fieldsAttr . '
                  ' . $defaultLayoutAttr . '
                  ' . $allowLayoutChangeAttr . '
            >
                ' . $content . '
            </form>
        </div>';

    }

    private function getFilterHtml(string $templateKey, $templateVal, array $tdata, bool $isInvisible): string
    {
        $field = $this->getField($templateKey);

        if (is_string($templateVal)) {

            // Чаще всего ключ в шаблоне совпадает с ключём поля описанном в Fieldset
            if ($field) {

                $title = Template::title($field->key);
                $label = Template::label($field->key);
                $html = Template::html($field->key);

                return $this->getFilterLiHtml($templateKey, $isInvisible, $title, $label, $html);
            }

            // В данном случае не совпало, проверим, существуют ли похожие поля From/Until?
            $fieldFrom = $this->getField("{$templateKey}From");
            $fieldUntil = $this->getField("{$templateKey}Until");

            /** @var FieldDate $fieldFrom */
            /** @var FieldDate $fieldUntil */
            if ($fieldFrom && $fieldUntil
                && ($fieldFrom->getType() === 'Date' || $fieldFrom->getType() === 'DateTime')
                && $fieldFrom->filterFromUntil
                && ($fieldUntil->getType() === 'Date' || $fieldUntil->getType() === 'DateTime')
                && $fieldUntil->filterFromUntil) {


                $title = Template::title($fieldFrom->key);
                $label = Template::label($fieldFrom->key);
                $html = '<nobr>' . Template::html($fieldFrom->key) . ' - ' . Template::html($fieldUntil->key) . '</nobr>';

                return $this->getFilterLiHtml($templateKey, $isInvisible, $title, $label, $html);
            }
        }

        // Заранее подготовим пару переменных, пригодятся позже
        if ($field) {
            // Если значение $templateKey совпало с именем реального поля, то возьмём title и label из этого поля
            $title = Template::title($field->key);
            $label = Template::label($field->key);
        } else {
            // Если же имеем дело с виртуальной ячейкой, для начала по умолчанию примим label как $templateKey
            $title = '';
            $label = $templateKey;
        }

        if (is_array($templateVal)) {

            // В массиве с настройками колонок фильтров данный фильтр был описан массивом
            if (isset($templateVal['label'])) {
                // Если в массиве явно задан label, то используем его
                $label = $templateVal['label'];
            }

            $html = is_callable($templateVal['func'])
                ? call_user_func($templateVal['func'], $tdata)
                : '<span class="bad">Html function is not defined!</span>';

            return $this->getFilterLiHtml($templateKey, $isInvisible, $title, $label, $html);
        }

        if (is_callable($templateVal)) {

            // В массиве с настройками колонок фильтров данный фильтр был описан просто функцией
            $html = call_user_func($templateVal, $tdata);
            return $this->getFilterLiHtml($templateKey, $isInvisible, $title, $label, $html);

        }

        return '<span class=bad>No template for ' . htmlspecialchars($templateKey) . '</span>';
    }

    private function getFilterLiHtml($templateKey, $isInvisible, $title, $label, $html)
    {
        $class = $isInvisible ? ' class="filter-invisible"' : '';
        $dataKey = ' data-templatekey="' . htmlspecialchars($templateKey) . '"';

        $left = "<div class=filter__label>$label</div>";
        $right = "<div>$html</div>";

        return "<li {$title}{$dataKey}{$class}><div>{$left}{$right}</div></li>";
    }

    private function getFilterButtonsHtml(bool $allowLayoutChange): string
    {
        $isLangRu = Config::$lang === 'ru';
        $textHistory = $isLangRu ? 'История фильтров' : 'Filters history';
        $textSaved = $isLangRu ? 'Сохраненные фильтры' : 'Saved filters';
        $textSettings = $isLangRu ? 'Настройка фильтров' : 'Filters layout';
        $textReset = $isLangRu ? 'СБРОС' : 'RESET';
        return '<li class="filters__buttons">
                <button class="btn waves-effect btn-filter" type="submit">' . Img::iconSearch() . '</button>
                <button class="btn-flat waves-effect filters-reset" type="button">' . $textReset . '</button>
                <nobr><div><div class="filters__buttonsicons">
                    <span class="btn-flat waves-effect toggle-tooltip filters-history" title="' . $textHistory . '"><i class="fa fa-history"></i></span>
                    <span class="btn-flat waves-effect toggle-tooltip filters-save" title="' . $textSaved . '"><i class="fa fa-save"></i></span>
                    ' . ($allowLayoutChange ? '<span class="btn-flat waves-effect toggle-tooltip filters-layout" title="' . $textSettings . '"><i class="fa fa-cog"></i></span>' : '') . '
                </div></div></nobr>
                </li>';
    }

}
