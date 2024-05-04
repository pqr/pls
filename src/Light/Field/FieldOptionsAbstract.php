<?php

namespace Light\Field;

use Light\Db;
use Light\Ico;
use Light\Link;
use Light\User;

/**
 * @method $this setLinkModule(string $module)
 * @method $this setLinkModuleDialog(bool $value)
 * @method $this setLinkModuleForTable(bool $value)
 * @method $this setHtmlspecialchars(bool $value)
 *
 * @method string|array|callable getOptions() //возвращает значение параметра options, не путать с методом getEvaluatedOptions()
 * @method string getLinkModule()
 * @method bool getLinkModuleDialog()
 * @method bool getLinkModuleForTable()
 * @method bool getHtmlspecialchars()
 */
abstract class FieldOptionsAbstract extends FieldAbstract
{
    //Если опции заданы sql запросом или названием таблицы к которой делать запрос и несколько полей использут один
    //и тот же запрос, то нет смысла делать одинаковый запрос к базе несколько раз
    private static $queryCache;

    protected $evaluatedOptions = [
        self::MODE_VIEW => null,
        self::MODE_EDIT => null,
    ];
    protected $prefetchedOptions;

    /**
     * @var string|array|callable
     */
    public $options;

    /**
     * @var string параметр optionsView для режима View - имя таблицы из которой выбирать ID и Name
     * либо SQL запрос без WHERE части (WHERE часть будет сгенерирована автоматически)
     * Второй враиант подходит для полей у которых текстовые значения выглядят как СONCAT из нескольких значений
     *
     * Если задано или если стандартный параметр 'options' содержит имя таблицы, то движок сделает оптимизацию
     * в режиме View - загрузит только одно значение из таблицы, добавив WHERE ID = ...
     */
    public $optionsView;

    // В режиме view сделать в виде ссылки на модуль указанный в linkModule (на просмотр записи справочника)
    public $linkModule;

    // Ссылка linkModule должна открывать диалоговое окно (по умочланию false - открывается новая страница)
    public $linkModuleDialog = false;

    // Ссылка linkModule должна должна работать даже в режиме таблицы
    public $linkModuleForTable = false;

    // Экранировать ли текст опций
    public $htmlspecialchars = true;

    /**
     * @param string|array|callable $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        // При установке новых опций сбрасываем вычеселенный ранее кеширующий массив (если он, конечно, вычислялся)
        $this->evaluatedOptions = [
            self::MODE_VIEW => null,
            self::MODE_EDIT => null,
        ];

        $this->prefetchedOptions = null;

        return $this;
    }

    /**
     * @param string $optionsView
     * @return $this
     */
    public function setOptionsView($optionsView)
    {
        $this->optionsView = $optionsView;

        // При установке новых опций сбрасываем вычеселенный ранее кеширующий массив (если он, конечно, вычислялся)
        $this->evaluatedOptions = [
            self::MODE_VIEW => null,
            self::MODE_EDIT => null,
        ];

        $this->prefetchedOptions = null;

        return $this;
    }

    /**
     * Метод вызывается в ModuleAbstract в режимах View и List, чтобы предзагрузить только нужные записи из базы
     * @param array $ids
     */
    public function prefetchOptions(array $ids): void
    {
        if (is_array($this->options) || is_callable($this->options)) {
            // Если массив опций задан статически, то нет смысла обращаться к базе и делать предзагрузку каких-то значений
            return;
        }

        $ids = array_filter($ids);
        if (!$ids) {
            // Обязательно заполним пустым массивом, проверка на массив используется в FieldSelect::prefetchViewOptions()
            $this->prefetchedOptions = [];
            return;
        }

        // Некоторые элементы из массив $ids могут содержать несколько значений разделённых \n (groupconcat поля)
        // Получим полный список раздельных значений:
        $ids = explode(',', str_replace("\n", ',', implode(',', $ids)));

        if (is_string($this->optionsView) && strpos($this->optionsView, ' ') !== false) {
            // Это некий SQL запрос без WHERE части
            $optionsViewQuery = $this->optionsView . ' WHERE ID IN (@a)';
            $this->prefetchedOptions = Db::getAssoc($optionsViewQuery, $ids);
            return;
        }

        $dbtable = null;
        if (is_string($this->optionsView)) {
            // Задана таблица из которой выбирать ID
            $dbtable = $this->optionsView;
        }

        // Если специальный параметр 'optionsView' не задан, то попробуем использовать стандартный options
        // только если это имя таблицы (а не callable или какой-то кастомный sql запрос)
        if (!$dbtable && is_string($this->options) && strpos($this->options, ' ') === false) {

            $dbtable = $this->options;
        }

        if (!$dbtable) {
            // Не удалось определить таблицу из которой предзагружать данные для режимов View и List
            return;
        }

        // Здесь также может быть вертикальная черта, после которой указывается поле для сортировки
        // Например: usergroup|OrderID

        $pipePos = strpos($dbtable, '|');
        if ($pipePos !== false) {
            $dbtable = substr($dbtable, 0, $pipePos);
        }

        $this->prefetchedOptions = Db::getAssoc(
            "SELECT ID, Name FROM " . Db::quote($dbtable) . " WHERE ID IN (@a)"
            , $ids
        );

    }

    /**
     * Возвращает текстовое значение для полей типа Select на основе индекса $value
     * Ищет значение в массиве $this->options
     * @param string|int|null $value
     * @param int $mode
     * @return string
     */
    public function getOption($value, int $mode = self::MODE_EDIT)
    {
        if (is_int($value) || is_string($value)) {
            if (is_array($this->prefetchedOptions) && array_key_exists($value, $this->prefetchedOptions)) {
                return $this->prefetchedOptions[$value];
            }

            $evaluatedOptions = $this->getEvaluatedOptions($mode);
            if (is_array($evaluatedOptions) && array_key_exists($value, $evaluatedOptions)) {
                return $evaluatedOptions[$value];
            }
        }

        // Если такой опции нет ни в prefetchedOptions, ни в evaluatedOptions(), то возвращаем исходное value
        // (в случае если value пусто, в том числе 0, возвращаем пустую строку
        return $value ?: '';
    }

    public function getEvaluatedOptions(int $mode = self::MODE_EDIT): array
    {
        if ($this->evaluatedOptions[$mode] !== null) {
            return $this->evaluatedOptions[$mode];
        }

        $options = $this->options;
        if ($mode !== self::MODE_EDIT && $this->optionsView) {
            // Если отображение в режиме view и задан параметр optionsView - то используем его
            $options = $this->optionsView;
        }

        $this->evaluatedOptions[$mode] = [];

        if (is_array($options)) {
            $this->evaluatedOptions[$mode] = $options;
        } elseif (is_callable($options)) {
            $this->evaluatedOptions[$mode] = $options($this);
        } elseif (is_string($options)) {
            $this->evaluatedOptions[$mode] = $this->getOptionsFromDb($options);
        }

        return $this->evaluatedOptions[$mode];
    }

    private function getOptionsFromDb(string $optionsAsString): array
    {
        if (isset(self::$queryCache[$optionsAsString])) {
            return self::$queryCache[$optionsAsString];
        }

        // Это может быть имя таблицы или полноценный sql запрос SELECT ID, Name FROM ...
        // Можно определить по наличию пробела
        if (strpos($optionsAsString, ' ') === false) {
            //Пробела нет, значит это имя таблицы
            //Здесь также может быть вертикальная черта, после которой указывается поле для сортировки
            //Например: usergroup|OrderID
            $parts = explode('|', $optionsAsString);
            $query = "SELECT ID, Name FROM @n ORDER BY @n";
            $tableName = $parts[0];
            $orderBy = $parts[1] ?? 'Name';
            $result = Db::getAssoc($query, $tableName, $orderBy);
            self::$queryCache[$optionsAsString] = $result;
            return $result;

        }

        // В переменной options лежит строка с sql запросом
        $result = Db::getAssoc($optionsAsString);
        self::$queryCache[$optionsAsString] = $result;
        return $result;
    }

    protected function getOptionHtml($option, $originalValue, $forTable)
    {
        $outHtml = $this->getOptionText($option, $originalValue);
        if ($this->htmlspecialchars) {
            $outHtml = htmlspecialchars($outHtml);
        }
        if (!empty($option['icon'])) {
            $outHtml = Ico::i($option['icon']) . ' ' . $outHtml;
        }
        if (!empty($option['img'])) {
            $outHtml = '<img src="' . $option['img'] . '" class="select2-option-avatar"> ' . $outHtml;
        }

        if ((!$forTable || $this->linkModuleForTable) && $this->linkModule && (int)$originalValue && User::hasReadAccess($this->linkModule)) {
            $link = Link::create($this->linkModule, $outHtml)
                ->view((int)$originalValue);

            if ($this->linkModuleDialog) {
                $link->viewDialog();
            }
            $outHtml = $link->getHtml();
        }

        $cssClass = $option['cssClass'] ?? '';
        if (!empty($option['img'])) {
            $cssClass .= ' select2-option-with-avatar';
        }

        if ($cssClass) {
            $outHtml = '<span class="' . $cssClass . '">' . $outHtml . '</span>';
        }

        return $outHtml;
    }

    public function getOptionText($option, $originalValue)
    {
        if (is_array($option)) {

            if (isset($option['Name'])) {
                $text = $option['Name'];
            } elseif (isset($option['text'])) {
                $text = $option['text'];
            } else {
                $text = $originalValue;
            }

        } else {
            // Иногда значение - это и есть процент, например при выборе единици измерения
            // $text = ($option == '%') ? '' : $option;
            $text = $option;
        }

        if (!$this->htmlspecialchars) {
            $text = strip_tags($text);
        }

        return $text;
    }
}
