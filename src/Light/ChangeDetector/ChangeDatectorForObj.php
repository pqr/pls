<?php


namespace Light\ChangeDetector;


use Light\ObjAbstract;

class ChangeDatectorForObj
{
    /**
     * Определяет, измелось ли хотя бы одно поле в объекте?
     * Опционально можно передать списко полей, которые проверять. Если не передан, то проверяются все поля из $obj
     * @param callable|null $compareFunc
     * @return bool
     */
    public function isChanged(ObjAbstract $obj, array $fieldKeys = null, callable $compareFunc = null): bool
    {
        if (!$fieldKeys) {
            $fieldKeys = $obj->getFieldset()->getKeys();
        }

        foreach ($fieldKeys as $fieldKey) {
            if (\is_callable($compareFunc)) {
                if ($compareFunc($obj, $fieldKey)) {
                    return true;
                }
            } elseif ($obj->isChanged($fieldKey)) {
                return true;
            }
        }

        return false;
    }
}
