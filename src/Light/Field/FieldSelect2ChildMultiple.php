<?php

namespace Light\Field;

use Light\Css;
use Light\Db;
use Light\Js;

/**
 * @param bool $oneLine
 */
class FieldSelect2ChildMultiple extends FieldSelect
{
    use PipeSeparatedIdsTrait;

    protected $logicSqlWhere = "OR";

    /**
     * @var string|array здесь может быть указано одно поле или несколько в массиве
     */
    public $parentFieldKeys;

    /**
     * Строка запроса или метод, который должен возвращать простой массив содержащий ассоциативные массивы
     * Например: Db::getAll("SELECT CompanyID, CustomerAccountID, ID AS CustomerAccountContractID FROM customeraccountcontract")
     * В этом примере мы хотим сделать поле CustomerAccountContractID зависимым от CompanyID и CustomerAccountID
     * Db::getAll() вернёт нам все комбинации. Благодаря совпадению имён полей в parentFieldKeys и в самом запросе
     * на стороне JavaScript движок сможет подобрать нужные значения.
     * Если в качестве $relations используется метод, то он принимает два параметра: $options и $this ($field)
     * @var string|callable
     */
    public $relations;

    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->default = '';
        $this->oneLine = false;
        $this->editWidth = 'full';
        $this->any = false;
    }

    protected function getEditCssClass($value, array $data)
    {
        return parent::getEditCssClass($value, $data) . ' select-select2multiple select-child';
    }

    protected function getEditTagParams($value, array $data)
    {
        $tagParams = parent::getEditTagParams($value, $data) . ' multiple size=1';
        //Принудительно приводим к массиву, на случай если была указана строка
        $parentFieldKeys = (array)$this->parentFieldKeys;
        $tagParams .= ' data-parentfieldkeys="' . htmlspecialchars(json_encode($parentFieldKeys)) . '"';
        $tagParams .= ' data-ownfieldkey="' . htmlspecialchars($this->key) . '"';

        $options = $this->getEvaluatedOptions();
        $relations = $this->relations;
        if (is_callable($relations)) {
            //Пользователь определил метод, который возвращает массив связей - вызовем этот метод
            $relations = $relations($options, $this);
        } elseif (is_string($relations)) {
            //Этот Relations в форме запроса - выполним запрос!
            $relations = Db::getAll($relations);
        }
        $tagParams .= ' data-relations="' . htmlspecialchars(json_encode($relations)) . '"';
        return $tagParams;
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

    protected function getSqlWhereInner($value): string
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
