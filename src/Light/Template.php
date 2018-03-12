<?php

namespace Light;

use Light\Field\FieldAbstract as F;
use Light\SubtableBase\Subtable;

class Template
{
    private static $tmpfilepath;
    private static $tdata = [];

    /**
     * @param string $templateName имя шаблона, например, Shipment.tpl.php
     * @return string полный путь к фалу шаблону
     */
    public static function findfile(string $templateName): string
    {
        if ($templateName[0] === '/') {
            // Если имя шаблона начинается с /, значит это абсолютный путь к файлу
            return $templateName;
        }

        $module = Request::getModule();

        $folders = [
            LIGHT_DIR_PROJECT . "/Module/$module/",
            LIGHT_DIR_LIGHT . "/Module/$module/",
            LIGHT_DIR_PROJECT . '/templates/',
            LIGHT_DIR_LIGHT . '/templates/',
            LIGHT_DIR_ROOT . '/src/',
            ''
        ];

        foreach ($folders as $folder) {
            $filepathAbs = $folder . $templateName;
            if (file_exists($filepathAbs)) {
                return $filepathAbs;
            }
        }

        return '';
    }

    /**
     * @param string $filename имя файла включая расширение .tpl.php
     * @param array $tdata шаблонные переменные
     * Отправляет HTML код отрендренного шаблона в поток вывода
     * @param Fieldset|null $fieldset
     * @param array|null $fieldsData
     * @param string|null $fieldMode
     * @throws \Exception
     */
    public static function includeTpl(
        $filename,
        array $tdata = [],
        Fieldset $fieldset = null,
        array $fieldsData = null,
        $fieldMode = null
    ) {
        self::$tmpfilepath = self::findfile($filename);
        if (!self::$tmpfilepath) {
            throw new \Exception("Cant find template file $filename");
        }

        //Для вложенных шаблонов сохраним то значение self::$_tdata, которое было до текущего вызова
        $__oldtdata = self::$tdata;

        self::$tdata = $tdata;
        if ($fieldset !== null) {
            self::$tdata['_fieldset'] = $fieldset;
        }

        if ($fieldsData !== null) {
            self::$tdata['_data'] = $fieldsData;
        }

        if ($fieldMode !== null) {
            self::$tdata['_mode'] = $fieldMode;
        }

        /** @noinspection UselessUnsetInspection */
        unset($filename, $fieldset, $fieldsData, $fieldMode);

        extract($tdata, EXTR_OVERWRITE);
        include self::$tmpfilepath;

        //Для вложенных шаблонов вернём то значение self::$_tdata, которое было до текущего вызова
        self::$tdata = $__oldtdata;
    }

    /**
     * @param string $filename имя файла включая расширение .tpl.php
     * @param array $tdata шаблонные переменные
     * @param Fieldset|null $fieldset
     * @param array|null $fieldsData
     * @param string|null $fieldMode
     * @return string возвращает HTML код отрендренного шаблона
     */
    public static function parseTpl(
        $filename,
        array $tdata = [],
        Fieldset $fieldset = null,
        array $fieldsData = null,
        $fieldMode = null
    ) {
        ob_start();
        self::includeTpl($filename, $tdata, $fieldset, $fieldsData, $fieldMode);
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    public static function setTdata(array $tdata)
    {
        self::$tdata = $tdata;
    }

    public static function control($title, $label, $html, $cssClass = null, $filterTemplateKey = null)
    {
        $dataSort = $filterTemplateKey ? ' data-templatekey="' . htmlspecialchars($filterTemplateKey) . '"' : '';
        $class = $cssClass ? ' class="' . $cssClass . '"' : '';

        return "<tr{$title}{$dataSort}{$class}><th>{$label}</th><td>{$html}</td></tr>";
    }

    public static function h3($fieldKey)
    {
        return static::c($fieldKey, true);
    }

    public static function c($fieldKey, $h3 = false, $cssClass = null, $filterTemplateKey = null)
    {
        if (!isset(static::$tdata['_fieldset'][$fieldKey])) {
            return static::control('', htmlspecialchars($fieldKey),
                '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>');
        }

        $title = static::title($fieldKey);
        $label = static::label($fieldKey);
        $html = $h3 ? static::htmlh3($fieldKey) : static::html($fieldKey);
        $filterTemplateKey = $filterTemplateKey ? (is_string($filterTemplateKey) ? $filterTemplateKey : $fieldKey) : null;
        return static::control($title, $label, $html, $cssClass, $filterTemplateKey);
    }

    public static function amountWithCurrency($fieldKey, $currencyKey = null)
    {
        if (!isset(static::$tdata['_fieldset'][$fieldKey])) {
            return static::control('', htmlspecialchars($fieldKey),
                '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>');
        }

        $title = static::title($fieldKey);
        $label = static::label($fieldKey);
        $html = static::html($fieldKey);

        if (!$currencyKey) {
            if (substr($fieldKey, -6) === 'Amount') {
                $amountKey = substr($fieldKey, 0, -6);
            } else {
                $amountKey = $fieldKey;
            }
            $currencyKey = $amountKey . 'CurrencyISO';
        }
        $html .= ' ' . static::html($currencyKey);
        return static::control($title, $label, $html);
    }

    public static function value($fieldKey)
    {
        return static::$tdata['_data'][$fieldKey] ?? null;
    }

    public static function title($fieldKey)
    {
        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];

        if ($field && $field->title) {
            return ' title="' . htmlspecialchars($field->title) . '"';
        }

        return '';
    }

    public static function name($fieldKey)
    {
        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            return '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
        }

        return nl2br($field->getName());
    }

    public static function label($fieldKey)
    {
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            return '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
        }

        /** @var F $field */
        return '<label for="' . $field->editId . '">' . static::name($fieldKey) . '</label>';
    }

    public static function text($fieldKey)
    {
        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            return 'Unknown field ' . htmlspecialchars($fieldKey);
        }

        $data = static::$tdata['_data'];
        $value = static::$tdata['_data'][$fieldKey];

        return $field->getText($value, $data);
    }

    public static function html($fieldKey)
    {
        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            return '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
        }

        $mode = static::$tdata['_mode'];
        $data = static::$tdata['_data'] ?? [];
        $value = $data[$fieldKey] ?? null;
        if ($mode === F::MODE_EDIT && $field->hasMode(F::MODE_EDIT)) {
            // Преобразуем поле в элемент ввода
            $html = $field->getHtmlInput($value, $data) . ' ' . $field->uom;
        } else {
            // Преобразуем поле в текстовый элемент для просмотра
            $html = $field->getHtml($value, $data);
            if ($html === '') {
                $html = '&nbsp;';
            }
        }

        return $html;
    }

    public static function htmlView($fieldKey)
    {
        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            return '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
        }

        $data = static::$tdata['_data'] ?? [];
        $value = $data[$fieldKey] ?? null;

        //Преобразуем поле в текстовый элемент для просмотра
        $html = $field->getHtml($value, $data);
        if ($html === '') {
            $html = '&nbsp;';
        }

        return $html;
    }

    public static function htmlInput($fieldKey)
    {
        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            return '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
        }

        $data = static::$tdata['_data'];
        $value = static::$tdata['_data'][$fieldKey];
        return $field->getHtmlInput($value, $data) . ' ' . $field->uom;
    }

    public static function uom($fieldKey)
    {
        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            return '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
        }

        return $field->uom;
    }

    public static function htmlh3($fieldKey)
    {
        $html = static::html($fieldKey);
        return '<h3>' . $html . '</h3>';
    }

    public static function _state()
    {
        return static::$tdata['_state'] ?? null;
    }

    public static function _id()
    {
        return static::$tdata['_id'];
    }

    public static function variable($var)
    {
        return static::$tdata[$var];
    }

    public static function issetvar($var)
    {
        return isset(static::$tdata[$var]);
    }

    public static function subtableHtml($subtableName)
    {
        /** @var Subtable $subtableObj */
        $subtableObj = static::$tdata['_subtables'][$subtableName]['obj'];
        if (!$subtableObj) {
            return 'Unknown subtable ' . htmlspecialchars($subtableName);
        }

        $readonly = static::$tdata['_subtables'][$subtableName]['readonly'];
        $state = static::$tdata['_state'];

        if ($state === 'view' || $readonly) {
            $html = $subtableObj->getHtmlView();
        } else {
            $html = $subtableObj->getHtmlEdit();
        }

        return $html;
    }

    public static function issetSubtable($subtableName)
    {
        return isset(static::$tdata['_subtables'][$subtableName]);
    }

    public static function subtableIsEmpty(string $subtableName)
    {
        /** @var Subtable $subtableObj */
        $subtableObj = static::$tdata['_subtables'][$subtableName]['obj'];
        return $subtableObj->getDataCount() === 0;
    }

    public static function subtableCount($subtableName)
    {
        /** @var Subtable $subtableObj */
        $subtableObj = static::$tdata['_subtables'][$subtableName]['obj'];
        return '<span class="' . $subtableName . '-subtable-count-span">' . $subtableObj->getDataCount() . '</span>';
    }

    public static function subtable($subtableName)
    {
        /** @var Subtable $subtableObj */
        $subtableObj = static::$tdata['_subtables'][$subtableName]['obj'];
        $title = $subtableObj->getTitle();
        $html = '<div class="subtableblock">';
        if ($title) {
            $html .= '<h2>' . $title . ' (' . static::subtableCount($subtableName) . ')</h2>';
        }
        $html .= static::subtableHtml($subtableName)
            . '</div>';

        return $html;
    }

    public static function filtersButtons($allowLayoutChange = true)
    {
        $isLangRu = Config::$lang === 'ru';
        $textHistory = $isLangRu ? 'История фильтров' : 'Filters history';
        $textSaved = $isLangRu ? 'Сохраненные фильтры' : 'Saved filters';
        $textSettings = $isLangRu ? 'Настройка фильтров' : 'Filters layout';
        $textReset = $isLangRu ? 'СБРОС' : 'RESET';
        return '<div class="filters-buttons">
                <button class="btn waves-effect waves-light btn-filter" type="submit">' . Img::iconSearch() . '</button>
                <button class="btn-flat waves-effect filters-reset" type="button">' . $textReset . '</button>
                <nobr><div><div class="filters-buttons-icons">
                    <span class="btn-flat waves-effect toggle-tooltip filters-history" title="' . $textHistory . '"><i class="fa fa-history"></i></span>
                    <span class="btn-flat waves-effect toggle-tooltip filters-save" title="' . $textSaved . '"><i class="fa fa-save"></i></span>
                    ' . ($allowLayoutChange ? '<span class="btn-flat waves-effect toggle-tooltip filters-layout" title="' . $textSettings . '"><i class="fa fa-cog"></i></span>' : '') . '
                </div></div></nobr>
                </div>';
    }

    public static function filtersButtonsRow($allowLayoutChange = true)
    {
        return '<tr><th></th><td style="padding-top:1em;">' . static::filtersButtons($allowLayoutChange) . '</td></tr>';
    }

    public static function filterImport()
    {
        $html = static::html('ImportID') . ' ' . static::html('ImportChangeType');
        return static::control('', 'Import ID', $html);
    }

    public static function datesFromUntil($fieldKey, $cssClass = null, $filterTemplateKey = null)
    {
        $title = static::title($fieldKey . 'From');
        $label = static::label($fieldKey . 'From');
        $html = '<nobr>' . static::html($fieldKey . 'From') . ' - ' . static::html($fieldKey . 'Until') . '</nobr>';
        if (!$filterTemplateKey) {
            // Поскольку функция datesFromUntil() используется в основном только в фильтрах
            // добавим
            $filterTemplateKey = $fieldKey;
        }
        return static::control($title, $label, $html, $cssClass, $filterTemplateKey);
    }

    /**
     * Возвращает html код блока для Multiple Update диалога
     * @param $fieldKey
     * @return string
     */
    public static function mu($fieldKey)
    {
        if (!isset(static::$tdata['_fieldset'][$fieldKey])) {
            //Если этого поля нет в Fieldset, то, скорее всего, оно не прошло по фильтр
            //$this->getFieldset()->filterByMode(F::MODE_MULTIPLEUPDATE); в ModuleAbstract::getMultipleUpdateFieldset()
            //Возможно, для данного пользователя это поле запрещено редактировать, однако на общей вёрстке Multiple Update
            //оно присутсвует (чтобы не горить if ... в шаблоне)
            //В этом случае ничего не рендрим.
            return '';
        }

        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];

        $title = static::title($fieldKey);

        //Обернём label поля в ссылку с классом showhide
        $label = static::label($fieldKey);
        $label = '<a href="#" class="mushowhide">' . $label . '</a>';
        $label .= '<input type="hidden" name="multipleupdate[' . $fieldKey . ']" value="hidden">';
        //Обернём html поля в div, который изначально скрыт
        //Преобразуем поле в элемент ввода
        if ($field->getType() === F::TYPE_SELECT_CHILD) {
            //В форме Multiple Update превращаем поля SelectChild в обычные Select, т.к. родительских Select элементов
            // может и не быть или они могут быть скрыты в форме Multiple Update
            $field = F::createSelect($field->key, $field->initialParams);
        }
        $html = $field->getHtmlInput($field->default) . ' ' . $field->uom;
        $html = '<div class="mufield display-none">' . $html . '</div>';

        return static::control($title, $label, $html);
    }

    public static function csrfHidden()
    {
        if (isset($_SESSION['csrf_token'])) {
            return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
        }

        return '';
    }
}
