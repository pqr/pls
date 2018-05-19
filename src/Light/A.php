<?php
namespace Light;

class A
{
    /**
     * Returns true if some of the elements in the collection pass the callback truthy test. Short-circuits and stops
     * traversing the collection if a truthy element is found. Callback arguments will be value, index, collection
     *
     * @param \Traversable|array $collection
     * @param callable $callback
     * @return bool
     */
    public static function some($collection, $callback)
    {
        foreach ($collection as $index => $element) {

            if ($callback($element, $index, $collection)) {
                return true;
            }

        }

        return false;
    }

    /**
     * Returns true if every value in the collection passes the callback truthy test. Opposite of Functional\none().
     * Callback arguments will be element, index, collection
     *
     * @param \Traversable|array $collection
     * @param callable $callback
     * @return bool
     */
    public static function every($collection, $callback)
    {
        foreach ($collection as $index => $element) {

            if (!$callback($element, $index, $collection)) {
                return false;
            }

        }

        return true;
    }

    /**
     * Looks through each element in the collection, returning the first one that passes a truthy test (callback). The
     * function returns as soon as it finds an acceptable element, and doesn't traverse the entire collection. Callback
     * arguments will be element, index, collection
     *
     * @param \Traversable|array $collection
     * @param callable $callback
     * @return mixed
     */
    public static function find($collection, $callback)
    {
        foreach ($collection as $index => $element) {

            if ($callback($element, $index, $collection)) {
                return $element;
            }

        }

        return null;
    }

    public static function groupBy($collection, $key)
    {
        $groupped = [];
        foreach($collection as $element) {
            $groupped[$element[$key]][] = $element;
        }

        return $groupped;
    }

    /**
     * Проверяет указанную колонку в двумерном массиве - все ли значения в этой колонке одинаковые?
     * Если есть хотя бы два различных значения, то возвращает true
     * @param \Traversable|array $collection
     * @param int|string $key
     * @return bool
     */
    public static function hasDifferentInColumn($collection, $key)
    {
        $firstVal = reset($collection)[$key];
        foreach($collection as $element) {
            if ($element[$key] !== $firstVal) {
                return true;
            }
        }

        return false;
    }
} 
