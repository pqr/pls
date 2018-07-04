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
    private static $_queryCache;

    protected $_cacheEvaluatedOptions;

    public $options;
    public $linkModule;

    /**
     * @param string|array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        // При установке новых опций сбрасываем вычеселенный ранее кеширующий массив (если он, конечно, вычислялся)
        $this->_cacheEvaluatedOptions = null;

        return $this;
    }

    public function getEvaluatedOptions()
    {
        if ($this->_cacheEvaluatedOptions === null) {
            $this->_cacheEvaluatedOptions = [];
            $options = $this->options;
            if ($options !== null) {
                if (is_array($options)) {
                    $this->_cacheEvaluatedOptions = $options;
                } elseif (is_callable($options)) {
                    $this->_cacheEvaluatedOptions = $options($this);
                } elseif (is_string($options)) {

                    if (isset(self::$_queryCache[$options])) {

                        $this->_cacheEvaluatedOptions = self::$_queryCache[$options];

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
                            $this->_cacheEvaluatedOptions = Db::getAssoc($query, $tableName, $orderBy);

                        } else {

                            //В переменной options лежит строка с sql запросом
                            $this->_cacheEvaluatedOptions = Db::getAssoc($options);
                        }

                        //Запомним в статическом кеше запросов, чтобы не повторять однаковые запросы для разных полей
                        self::$_queryCache[$options] = $this->_cacheEvaluatedOptions;
                    }
                }
            }
        }
        return $this->_cacheEvaluatedOptions;
    }

    protected function getOptionHtml($option, $originalValue, $forTable)
    {
        $text = $this->getOptionText($option, $originalValue);
        $outHtml = htmlspecialchars($text);

        if (!$forTable && $this->linkModule) {
            $outHtml = Link::create($this->linkModule, $outHtml)
                ->view((int)$originalValue)
                ->funcDialog()
                ->getHtml();
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
