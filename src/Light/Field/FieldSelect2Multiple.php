<?php

namespace Light\Field;

use Light\Css;
use Light\Db;
use Light\Js;

/**
 * @param bool $oneLine
 */
class FieldSelect2Multiple extends FieldSelect
{
    use PipeSeparatedIdsTrait;

    protected $logicSqlWhere = "OR";

    protected function initDefaultParams()
    {
        parent::initDefaultParams();
        $this->default = '';
        $this->oneLine = false;
        $this->editWidth = 'full';
    }

    protected function getEditCssClass($value, array $data)
    {
        return parent::getEditCssClass($value, $data) . ' select-select2multiple';
    }

    protected function getEditTagParams($value, array $data)
    {
        return parent::getEditTagParams($value, $data) . ' multiple size=1';
    }

    protected function getHtmlInputInner($value, array $data, $idPrefix)
    {
        // Это поле требует подключения библиотки select2 на стороне клиента
        Js::$select2 = true;
        Css::$select2 = true;
        return parent::getHtmlInputInner($value, $data, $idPrefix);
    }


    protected function getHtmlInputTplData($value, array $data, $idPrefix)
    {
        $tdata = parent::getHtmlInputTplData($value, $data, $idPrefix);
        //Сделаем добавим к имени поля квадратные скобки, чтобы оно отправильнос на сервер при POST запросе как массив
        $tdata['editName'] .= '[]';
        return $tdata;
    }

    protected function getDataFromRequestInner(array $request)
    {
        if (array_key_exists($this->key, $request)) {

            if (is_array($request[$this->key])) {
                $valueArr = $request[$this->key];
                $value = '|' . implode('|', $valueArr) . '|';
            } else {
                $value = $request[$this->key];
            }

            return $value;
        }

        //Хитрость в select2 - если ничего не пришло из javascript, значит ничего не было выбрано (пустой вариант)
        return '';
    }

    protected function getSqlWhereInner($value)
    {
        $sql = [];
        $values = explode('|', $value);
        foreach ($values as $val) {
            if ($val !== '') {
                $sql[] = "{fieldKey} = " . Db::escString($val);
            }
        }
        return implode(" " . $this->logicSqlWhere . " ", $sql);
    }

}