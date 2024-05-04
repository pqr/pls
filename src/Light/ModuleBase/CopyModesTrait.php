<?php


namespace Light\ModuleBase;

use Light\Config;
use Light\Ico;
use Light\Link;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

/**
 * Этот trait безусловно подключается к ModuleAbstract.
 * Код вынесен в этот trait исключительно чтобы облегчить файл ModuleAbstract.php
 */
trait CopyModesTrait
{
    /**
     * Наборы полей, которые нужно сохранять при копировании
     * Можно определить несколько режимов копирования, задвая различные наборы полей
     * ['default' => ['fieldKeys' => ['fieldKey1', 'fieldKey2'], 'subtables' => ['subtableName1', 'subtableName2']], ...]
     * @var array
     */
    protected $copyModes = [];

    /**
     * Метод для переопределения в классах потомках
     * Здесь нужно последовательно вызывать $this->addCopyMode(), добавляя разные режимы копирования
     */
    protected function initCopyModes()
    {
    }

    /**
     * @param string $name
     * @param string|array $fieldsKeys
     * @param string|array $subtables
     */
    final protected function addCopyMode($name = '', $fieldsKeys = 'all', $subtables = 'all')
    {
        if (!$name) {
            $name = Config::$lang === 'ru' ? 'Копировать' : 'Copy';
        }
        $this->copyModes[$name] = ['fieldKeys' => $fieldsKeys, 'subtables' => $subtables];
    }

    protected function copyModesSubmenuLinks(): array
    {
        if (!$this->copyModes) {
            return [];
        }

        $links = [];

        $linkBase = Link::create($this->getModule())
            ->addNew()
            ->params(['copyfrom' => $this->_obj->getId()]);
        foreach ($this->copyModes as $name => $_) {
            $link = clone $linkBase;
            $links[] = $link
                ->innerHtml(Ico::copy() . ' ' . $name)
                ->params(['copymode' => $name]);
        }

        return $links;
    }

    /**
     * @desc инициализирует $this->_obj переменные данными из файла id=$copyfrom
     * @return void
     * @throws \Exception
     */
    protected function initDataForCopy(
        string $copymode,
        int $copyfrom
    ) {
        //Проверим, есть ли набор полей определённых для текущего $copymode
        if (isset($this->copyModes[$copymode])) {
            // Возьмём данные из объекта источника
            /** @var ObjAbstract $sourceObj */
            $sourceObj = clone $this->_obj;
            if ($sourceObj->setId($copyfrom)->loadData()) {
                //Перенесём нужные поля в $this->_obj
                $copyFieldKeys = $this->copyModes[$copymode]['fieldKeys'];
                if ($copyFieldKeys !== null) {
                    if (is_array($copyFieldKeys)) {
                        $fieldKeys = $copyFieldKeys;
                    } else {
                        $fieldKeys = $this->_obj->getFieldset()->getKeys();
                    }
                    foreach ($fieldKeys as $fieldKey) {
                        if (isset($sourceObj[$fieldKey])) {
                            //Поле существует в источнике
                            //Инициализируем данные значением из источника
                            $this->_obj[$fieldKey] = $sourceObj[$fieldKey];
                        }
                    }
                }

                $copySubtables = $this->copyModes[$copymode]['subtables'];
                if ($copySubtables !== null) {
                    if (!is_array($copySubtables)) {
                        $copySubtables = array_keys($this->subtables);
                    }

                    foreach ($copySubtables as $subtableClass) {
                        if ($this->subtables[$subtableClass]['readonly']) {
                            continue;
                        }
                        // Получим объект, который нужно заполнить данными из копируемого источника
                        /** @var Subtable $subtable */
                        $subtable = $this->getSubtable($subtableClass);
                        // Источник - это на самом деле подтаблица того же класса, но с другим masterId
                        /** @var Subtable $sourceSubtable */
                        $sourceSubtable = new $subtableClass($sourceObj);
                        $sourceData = $this->getDataForCopySubtable($sourceSubtable);
                        $subtable->setSubtableDataAdditional($sourceData);
                    }
                }
            }
        }
    }

    protected function getDataForCopySubtable(Subtable $sourceSubtable): array
    {
        // Копируем данные в подтаблицу текущего объекта
        // Метод $sourceSubtable->getData() возвращает массив где ключи - это ID в базе
        // чтобы добавить данные в $subtable нам не нужны ключи в виде ID, нам нужны ключи от 0 до N
        // для этого применим array_values
        return array_values($sourceSubtable->getSubtableData());
    }
}
