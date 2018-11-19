<?php

namespace Light\Module\ProductionCalendar;


use Light\ModuleBase\ModuleAbstract;

class ProductionCalendar extends ModuleAbstract
{
    protected $textSaveBtn = 'Дату';

    protected function initFilters()
    {
        $this->addFilterYear('production_calendar', 'Date')
            ->setDefault(date('Y'));
    }


}
