<?php
namespace Light\Field;

use Light\Db;

/**
 * @method $this setParentFieldKeys(string|array|null $parentFieldKeys)
 */
class FieldSelectChild extends FieldSelect
{
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

    #[\Override]
    protected function getEditCssClass($value, array $data)
    {
        return parent::getEditCssClass($value, $data) . ' select-child';
    }

    #[\Override]
    protected function getEditTagParams($value, array $data)
    {
        $tagParams = parent::getEditTagParams($value, $data);
        //Принудительно приводим к массиву, на случай если была указана строка
        $parentFieldKeys = (array)$this->parentFieldKeys;
        $tagParams .= ' data-parentfieldkeys="' . htmlspecialchars(json_encode($parentFieldKeys)) . '"';
        $tagParams .= ' data-ownfieldkey="' . htmlspecialchars((string) $this->key) . '"';

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
}
