<?php

namespace Light;

use Light\Field\FieldAbstract as F;
use Light\Field\FieldAbstract;
use Light\ModuleBase\ModuleAbstract;
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
        if (strpos($templateName, '/') === 0) {
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
            '',
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
    ): void {
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

    public static function setTdata(array $tdata): void
    {
        self::$tdata = $tdata;
    }

    public static function control(string $title, string $label, string $html): string
    {
        return "<tr{$title}><th>{$label}</th><td>{$html}</td></tr>";
    }

    public static function h3($fieldKey)
    {
        return static::c($fieldKey, true);
    }

    public static function c($fieldKey, $h3 = false): string
    {
        /** @var FieldAbstract $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            $title = '';
            $label = htmlspecialchars($fieldKey);
            $html = '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
            return static::control($title, $label, $html);
        }

        if (!$field->hasMode(F::MODE_VIEW | F::MODE_EDIT)) {
            // Если для поря не предусмотрен режим вывода в карточке AddNew/View/Edit,
            // то ничего не выводим (метод t::с() используется исключительно в этих режимах)
            return '';
        }

        // Сразу проверяем права доступа на просмотр, чтобы в случае отсутвия этих прав вообще не выводить контрол
        $data = static::$tdata['_data'] ?? [];
        if (!$field->checkAccessFunction($data)) {
            return '';
        }

        $title = static::title($fieldKey);
        $label = static::label($fieldKey);
        $html = $h3 ? static::htmlh3($fieldKey) : static::html($fieldKey);
        return static::control($title, $label, $html);
    }

    public static function amountWithCurrency($fieldKey, $currencyKey = null)
    {
        /** @var FieldAbstract $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            $title = '';
            $label = htmlspecialchars($fieldKey);
            $html = '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
            return static::control($title, $label, $html);
        }

        if (!$field->hasMode(F::MODE_VIEW | F::MODE_EDIT)) {
            // Если для поря не предусмотрен режим вывода в карточке AddNew/View/Edit,
            // то ничего не выводим (метод t::amountWithCurrency() используется исключительно в этих режимах)
            return '';
        }

        // Сразу проверяем права доступа на просмотр, чтобы в случае отсутвия этих прав вообще не выводить контрол
        $data = static::$tdata['_data'] ?? [];
        if (!$field->checkAccessFunction($data)) {
            return '';
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

    public static function empty($fieldKey): bool
    {
        /** @var F $field */
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            // Самого поля почему-то нет в fieldset - не можем проверить на пустоту, вернём false (т.е. empty)
            return true;
        }

        // Узнаем empty или нет благодаря методу определённому для данного типа поля FieldAbstract::empty
        $value = self::value($fieldKey);
        return $field::empty($value);
    }

    public static function notEmpty($fieldKey): bool
    {
        return !self::empty($fieldKey);
    }

    public static function showNotEmpty($fieldKey)
    {
        return self::isStateAddNewOrEdit() || self::notEmpty($fieldKey);
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

    public static function label($fieldKey, string $name = null)
    {
        $field = static::$tdata['_fieldset'][$fieldKey];
        if (!$field) {
            return '<span class=bad>Unknown field ' . htmlspecialchars($fieldKey) . '</span>';
        }

        if ($name === null) {
            $name = static::name($fieldKey);
        }

        /** @var F $field */
        return '<label for="' . $field->editId . '">' . $name . '</label>';
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
        if ($mode === F::MODE_EDIT) {
            // Преобразуем поле в элемент ввода
            return self::htmlInput($fieldKey);
        }

        return self::htmlView($fieldKey);
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

        $data = static::$tdata['_data'] ?? [];
        $value = $data[$fieldKey] ?? null;
        // Заранее проверяем наличие нужного MODE и editAccessFunction, чтобы в случае отказа не получить пустую строку
        // а перейти к выводу self::htmlView($fieldKey);
        if ($field->hasMode(F::MODE_EDIT) && $field->checkEditAccessFunction($data)) {
            return $field->getHtmlInput($value, $data) . ' ' . $field->uom;
        }

        // Если доступа на редактирование нет, выводим в режиме просмотра
        return self::htmlView($fieldKey);
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

    public static function isStateAddNew()
    {
        return self::_state() === ModuleAbstract::STATE_ADDNEW;
    }

    public static function isStateView()
    {
        return self::_state() === ModuleAbstract::STATE_VIEW;
    }

    public static function isStateEdit()
    {
        return self::_state() === ModuleAbstract::STATE_EDIT;
    }

    public static function isStateAddNewOrEdit()
    {
        return self::isStateAddNew() || self::isStateEdit();
    }

    public static function showHideWhen(string $fieldKey, $showWhenFieldValue = null): string
    {
        $out = ' data-showhide-when-fieldkey="' . $fieldKey . '"';
        if ($showWhenFieldValue !== null) {
            $out .= ' data-showhide-when-fieldvalue="' . htmlspecialchars(json_encode($showWhenFieldValue)) . '"';
        }

        if ($showWhenFieldValue === null) {
            $show = !empty(self::value($fieldKey));
        } else {
            $currentaValue = self::value($fieldKey);
            if (is_array($showWhenFieldValue)) {
                $show = in_array($currentaValue, $showWhenFieldValue);
            } else {
                $show = $currentaValue == $showWhenFieldValue;
            }
        }

        if (!$show) {
            $out .= ' style="display: none"';
        }

        return $out;
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
        $subtableName = Misc::getClass($subtableName);
        /** @var Subtable $subtableObj */
        $subtableObj = static::$tdata['_subtables'][$subtableName]['obj'];
        if (!$subtableObj) {
            return 'Unknown subtable ' . htmlspecialchars($subtableName);
        }

        $readonly = static::$tdata['_subtables'][$subtableName]['readonly'];
        $state = static::$tdata['_state'];

        if ($state === 'view' || $readonly) {
            // Отключаем автоподстройку ширины колонок в случае если таблица используется только для чтения
            $adjustFieldsWidth = !$readonly;
            $html = $subtableObj->getHtmlView($adjustFieldsWidth);
        } else {
            $html = $subtableObj->getHtmlEdit();
        }

        return $html;
    }

    public static function issetSubtable(string $subtableName)
    {
        $subtableName = Misc::getClass($subtableName);
        return isset(static::$tdata['_subtables'][$subtableName]);
    }

    public static function subtableIsEmpty(string $subtableName)
    {
        $subtableName = Misc::getClass($subtableName);
        /** @var Subtable $subtableObj */
        $subtableObj = static::$tdata['_subtables'][$subtableName]['obj'];
        return $subtableObj->getSubtableDataCount() === 0;
    }

    public static function subtableCount(string $subtableName)
    {
        $subtableName = Misc::getClass($subtableName);
        /** @var Subtable $subtableObj */
        $subtableObj = static::$tdata['_subtables'][$subtableName]['obj'];
        return '<span class="' . $subtableName . '-subtable-count-span">' . $subtableObj->getSubtableDataCount() . '</span>';
    }

    public static function subtable(string $subtableName): string
    {
        $subtableName = Misc::getClass($subtableName);
        /** @var Subtable $subtableObj */
        $subtableObj = static::$tdata['_subtables'][$subtableName]['obj'];

        // Сначала сформируем HTML всей подтаблицы, а лишь затем будем вызывать subtableCount()
        // чтобы не делать два раза loadData() внутри подтаблицы
        $subtableHtml = static::subtableHtml($subtableName);

        $html = '<div class="subtableblock">';

        $title = $subtableObj->getTextSubtableTitle();
        if ($title) {
            $html .= '<h2>' . $title . ' (' . static::subtableCount($subtableName) . ')</h2>';
        }

        $html .= $subtableHtml
            . '</div>';

        return $html;
    }

    public static function datesFromUntil($fieldKey, string $name = null)
    {
        $title = static::title($fieldKey . 'From');
        $label = static::label($fieldKey . 'From', $name);
        $html = '<nobr>' . static::html($fieldKey . 'From') . ' - ' . static::html($fieldKey . 'Until') . '</nobr>';

        return static::control($title, $label, $html);
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
            //$this->getFieldset()->filterByMode(F::MODE_MULTIPLEUPDATE); в ModuleAbstract::muFieldset()
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
        if ($field->getType() === F::TYPE_SELECT) {
            //В форме Multiple Update превращаем поля SelectChild в обычные Select, т.к. родительских Select элементов
            // может и не быть или они могут быть скрыты в форме Multiple Update
            $field->setParentFieldKeys('');
        }
        $html = $field->getHtmlInput($field->default, [], 'mu') . ' ' . $field->uom;
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
