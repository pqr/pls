<?php

namespace Light\Module\CurrencyRate;

use Light\Autolaunch\CurrencyRateCBRF;
use Light\Field\CommonFields as CF;
use Light\Link;
use Light\Module\Autolaunch\Autolaunch;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class CurrencyRate extends ModuleAbstract
{
    public $tableLinkFieldKeys = [];
    public $tableFieldsShow = ['ValidDate', 'CurrencyISO', 'RateToRUB', 'Provider'];

    protected $textSaveBtn = 'Currency Rate';
    public $tableDefaultOrderBy = 'ValidDate DESC';

    #[\Override]
    protected function initFilters()
    {
        $this->addFilters(
            CF::currency()
        );

        $this->addFiltersDatesFromUntil('ValidDate', ['name' => 'Valid']);
    }

    #[\Override]
    protected function initListLinks()
    {
        if (User::hasWriteAccess() && User::hasReadAccess('Autolaunch')) {
            $this->addListLink(
                Link::create(Autolaunch::class, 'Обновить с сайта ЦБРФ')
                    ->params(['class' => CurrencyRateCBRF::class])
            );
        }
    }
}

