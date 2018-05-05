<?php

namespace Light\Module\ProductionCalendar;


use Light\ModuleBase\ModuleAbstract;

class ProductionCalendar extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->textSaveBtn = 'Дату';
    }

    protected function initFilters()
    {
        $this->addFilterYear('production_calendar', 'Date')
            ->setDefault(date('Y'));
    }


}
