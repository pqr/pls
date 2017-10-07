<?php
namespace Light;

/**
 * Класс, когда нужно создать какой-то объект одновременно содержащий данные и fieldset
 * тогда делаем $simpleObj = new SimpleObj()
 * $simpleObj->addFields(...)
 * $simpleObj->setData(...)
 *
 * Этот класс также исползуется внутри движка, например, если мы создаём какой-то модуль на основе ModuleAbstract, но
 * не создаём отдельный класс объекта.
 *
 * Второй случай: SimpleObj создаётся для того, чтобы хранить значения фильтров в TableFiltarable
 *
 */
final class SimpleObj extends ObjAbstract
{
}