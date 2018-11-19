<?php

namespace Light\Module\CurrencyRate;

use Light\Field\CommonFields as CF;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class CurrencyRate extends ModuleAbstract
{
    protected $allowTableLayouts = false;
    protected $tableLinkFieldKeys = [];
    protected $tableFieldsShow = ['ValidDate', 'CurrencyISO', 'RateToRUB', 'Provider'];

    protected $textSaveBtn = 'Currency Rate';
    protected $tableDefaultOrderBy = 'ValidDate DESC';

    protected function initFilters()
    {
        $this->addFilters(
            CF::currency()
        );

        $this->addFiltersDatesFromUntil('ValidDate', ['name' => 'Valid']);
    }

    protected function getListButtons()
    {
        $out = parent::getListButtons();
        if (User::hasWriteAccess() && User::hasReadAccess('Autolaunch')) {
            $out[] = [
                'text' => 'Обновить с сайта ЦБРФ',
                'url' => '?a=Autolaunch&class=CurrencyRateCBRF'
            ];
        }

        return $out;
    }
}

