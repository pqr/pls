<?php

namespace Light\Field;

use Light\Db;
use Light\Link;

/**
 * @method $this setLinkModule(string $module)
 *
 * @method string|array|callable getOptions() //возвращает значение параметра options, не путать с методом getEvaluatedOptions()
 * @method string getLinkModule()
 */
abstract class FieldOptionsAbstract extends FieldAbstract
{
    //Если опции заданы sql запросом или названием таблицы к которой делать запрос и несколько полей использут один
    //и тот же запрос, то нет смысла делать одинаковый запрос к базе несколько раз
    private static $queryCache;

    protected $evaluatedOptions;
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

    /**
     * @param string|array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        // При установке новых опций сбрасываем вычеселенный ранее кеширующий массив (если он, конечно, вычислялся)
        $this->evaluatedOptions = null;

        return $this;
    }

    /**
     * Метод вызывается в ModuleAbstract в режимах View и List, чтобы предзагрузить только нужные записи из базы
     * @param array $ids
     */
    public function prefetchViewOptions(array $ids)
    {
        if (is_array($this->options)) {
            // Если массив опций задан статически, то нет смысла обращаться к базе и делать предзагрузку каких-то значений
            return;
        }

        if (count($ids) === 1 && reset($ids) == 0) {
            $this->prefetchedOptions[0] = '';
            return;
        }

        if (is_string($this->optionsView) && strpos($this->optionsView, ' ') !== false) {
            // Это некий SQL запрос без WHERE части
            $this->prefetchedOptions = Db::getAssoc($this->optionsView . " WHERE ID IN (@a)", $ids);
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

        $this->prefetchedOptions = Db::getAssoc("SELECT ID, Name FROM " . Db::quote($dbtable) . " WHERE ID IN (@a)",
            $ids);

    }

    /**
     * Возвращает текстовое значение для полей типа Select на основе индекса $value
     * Ищет значение в массиве $this->options
     * @param string $value
     * @return string
     */
    public function getOption($value)
    {
        if (isset($this->prefetchedOptions[$value])) {
            return $this->prefetchedOptions[$value];
        }

        $evaluatedOptions = $this->getEvaluatedOptions();
        if (isset($evaluatedOptions[$value])) {
            return $evaluatedOptions[$value];
        }

        // Если такой опции нет ни в prefetchedOptions, ни в evaluatedOptions(), то возвращаем исходное value
        // (в случае если value пусто, в том числе 0, возвращаем пустую строку
        return $value ?: '';
    }

    public function getEvaluatedOptions(): array
    {
        if ($this->evaluatedOptions === null) {
            $this->evaluatedOptions = [];
            $options = $this->options;
            if ($options !== null) {
                if (is_array($options)) {
                    $this->evaluatedOptions = $options;
                } elseif (is_callable($options)) {
                    $this->evaluatedOptions = $options($this);
                } elseif (is_string($options)) {

                    if (isset(self::$queryCache[$options])) {

                        $this->evaluatedOptions = self::$queryCache[$options];

                    } else {

                        //Это может быть имя таблицы или полноценный sql запрос SELECT ID, Name FROM ...
                        //Можно определить по наличию пробела
                        if (strpos($options, ' ') === false) {
                            //Пробела нет, значит это имя таблицы
                            //Здесь также может быть вертикальная черта, после которой указывается поле для сортировки
                            //Например: usergroup|OrderID
                            $parts = explode('|', $options);
                            $query = "SELECT ID, Name FROM @n ORDER BY @n";
                            $tableName = $parts[0];
                            $orderBy = $parts[1] ?? 'Name';
                            $this->evaluatedOptions = Db::getAssoc($query, $tableName, $orderBy);

                        } else {

                            //В переменной options лежит строка с sql запросом
                            $this->evaluatedOptions = Db::getAssoc($options);
                        }

                        //Запомним в статическом кеше запросов, чтобы не повторять однаковые запросы для разных полей
                        self::$queryCache[$options] = $this->evaluatedOptions;
                    }
                }
            }
        }
        return $this->evaluatedOptions;
    }

    protected function getOptionHtml($option, $originalValue, $forTable)
    {
        $text = $this->getOptionText($option, $originalValue);
        $outHtml = htmlspecialchars($text);

        if (!$forTable && $this->linkModule) {
            $link = Link::create($this->linkModule, $outHtml)
                ->view((int)$originalValue);

            if ($this->linkModuleDialog) {
                $link->funcDialog();
            }
            $outHtml = $link->getHtml();
        }

        if (is_array($option) && isset($option['cssClass'])) {
            $outHtml = '<span class="' . $option['cssClass'] . '">' . $outHtml . '</span>';
        }

        return $outHtml;
    }

    protected function getOptionText($option, $originalValue)
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
            //Иногда значение - это и есть процент, например при выборе единици измерения
            //$text = ($option == '%') ? '' : $option;
            $text = $option;
        }

        return $text;
    }
}
