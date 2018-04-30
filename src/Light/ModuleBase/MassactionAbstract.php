<?php
namespace Light\ModuleBase;

/**
 * Действия massaction можно задавать в виде функций в классе модуля (extends ModuleAbstract)
 * либо в виде отдельных классов.
 * Во втором случае нужно использовать данный MassactionAbstract в качестве базового класса
 * Class MassactionAbstract
 * @package Light\ModuleBase
 */
abstract class MassactionAbstract
{
    /**
     * @param array $ids
     * @return mixed
     */
    abstract public function __invoke(array $ids);
}
