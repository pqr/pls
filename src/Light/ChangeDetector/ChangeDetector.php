<?php


namespace Light\ChangeDetector;


use Light\Field\FieldAbstract;

class ChangeDetector
{
    /**
     * Возвращает true, если объект был только что создан (insertDb()) или в процессе updateDb() изменилось значение
     * этого поля
     * @param string $fieldKey
     * @param array|null $newData
     * @param array|null $oldData
     * @return bool
     */
    public static function isChanged(string $fieldKey, ?array $newData, ?array $oldData): bool
    {
        if (!$newData || !array_key_exists($fieldKey, $newData)) {
            // Если в текущем массиве данных нет этого поля, значит пользователь передал какой-то ошибочный fieldKey
            // просто вернём false, будто поле не менялось
            return false;
        }

        if (!$oldData || !array_key_exists($fieldKey, $oldData)) {
            // Если в старом массиве данных нет этого поля, скорее всего объект был только что создан,
            // считаем поле изменившимся
            return true;
        }

        $oldValue = $oldData[$fieldKey];
        $newValue = $newData[$fieldKey];

        return $oldValue !== $newValue;
    }

    public static function isChangedTo(string $fieldKey, ?array $newData, ?array $oldData, $expectedValue): bool
    {
        return self::isChanged($fieldKey, $newData, $oldData)
            && $newData[$fieldKey] === $expectedValue;
    }

    public static function isChangedFrom(string $fieldKey, ?array $newData, ?array $oldData, $expectedOldValue): bool
    {
        if (!self::isChanged($fieldKey, $newData, $oldData)) {
            return false;
        }

        $oldValue = $oldData[$fieldKey] ?? null;
        return $oldValue === $expectedOldValue;
    }

    /**
     * Возвращает true, если поле изменило своё значение с пустого на заполненное
     * @param string $fieldKey
     * @param array|null $newData
     * @param array|null $oldData
     * @param FieldAbstract $field
     * @return bool
     */
    public static function isFilled(string $fieldKey, ?array $newData, ?array $oldData, FieldAbstract $field): bool
    {
        if (!$newData || !array_key_exists($fieldKey, $newData)) {
            // Если в текущем массиве данных нет этого поля, значит пользователь передал какой-то ошибочный fieldKey
            // просто вернём false, будто поле не менялось
            return false;
        }

        $oldValue = $oldData[$fieldKey] ?? null;
        $newValue = $newData[$fieldKey];

        return $field::empty($oldValue) && !$field::empty($newValue);
    }


    /**
     * Возвращает true, если поле изменило своё значение с заполненного на пустое
     * @param string $fieldKey
     * @param array|null $newData
     * @param array|null $oldData
     * @param FieldAbstract $field
     * @return bool
     */
    public static function isCleared(string $fieldKey, ?array $newData, ?array $oldData, FieldAbstract $field): bool
    {
        if (!$newData || !array_key_exists($fieldKey, $newData)) {
            // Если в текущем массиве данных нет этого поля, значит пользователь передал какой-то ошибочный fieldKey
            // просто вернём false, будто поле не менялось
            return false;
        }

        $oldValue = $oldData[$fieldKey] ?? null;
        $newValue = $newData[$fieldKey];

        return !$field::empty($oldValue) && $field::empty($newValue);
    }
}
