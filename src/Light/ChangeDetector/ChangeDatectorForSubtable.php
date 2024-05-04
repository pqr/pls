<?php


namespace Light\ChangeDetector;


use Light\SubtableBase\Subtable;

class ChangeDatectorForSubtable
{
    /**
     * Определяет, измелось ли хотя бы одно поле в объекте?
     * Опционально можно передать списко полей, которые проверять. Если не передан, то проверяются все поля из $obj
     * @param callable|null $compareFunc
     * @return bool
     */
    public function isChanged(Subtable $subtable, array $fieldKeys = null, callable $compareFunc = null): bool
    {
        if ($subtable->hasInsertedRows() || $subtable->hasDeletedRows()) {
            // Если есть какие-то вставленные или удалённые строки - принимает это за существенное изменение
            return true;
        }

        if (!$fieldKeys) {
            $fieldKeys = $subtable->getFieldset()->getKeys();
        }

        foreach ($subtable->getSubtableData() as $id => $_) {
            foreach ($fieldKeys as $fieldKey) {
                if (\is_callable($compareFunc)) {
                    if ($compareFunc($subtable, $id, $fieldKey)) {
                        return true;
                    }
                } elseif ($subtable->isChanged($id, $fieldKey)) {
                    // Как только нашли изменённое поле, возвращаем true
                    return true;
                }
            }
        }

        return false;
    }
}
