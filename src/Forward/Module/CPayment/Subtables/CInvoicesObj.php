<?php
namespace Forward\Module\CPayment\Subtables;

use Forward\ModuleBase\Payments\Subtables\InvoicesObjAbstract;

/**
 * Ничего переопределять не нужною. Метод init полностью определён в классе InvoicesObjAbstract и там заполняется
 * fieldset и другие параметры на основе класса текущего объекта (get_class())
 */
class CInvoicesObj extends InvoicesObjAbstract
{
}
