<?php

namespace Light\Module\ProductionCalendar;


use Light\ObjAbstract;
use Light\Field\FieldAbstract as F;

class ProductionCalendarObj extends ObjAbstract
{
    protected $nameFieldKey = 'Date';

    public function __construct()
    {
        $this->setDbTable('production_calendar');

        $this->addFields(
            F::createDate('Date', [
                'name' => 'Дата',
                'editMandatory' => true,
            ]),
            F::createSelect('WorkingDay', [
                'name' => 'Выходной/рабочий',
                'tableAlign' => 'center',
                'empty' => false,
                'options' => [
                    0 => 'Выходной',
                    1 => 'Рабочий'
                ],
            ])
        );
    }

}
