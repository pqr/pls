<?php
namespace Light\Module\CurrencyRate;

use Light\Field\CommonFields as CF;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class CurrencyRate extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->textSaveBtn = 'Currency Rate';
        $this->tableDefaultOrderBy = 'ValidDate DESC';
        $this->allowTableLayouts = false;
        $this->tableLinkFieldKeys = [];
        $this->tableFieldsShow = ['ValidDate', 'CurrencyISO', 'RateToRUB', 'Provider'];
    }

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

