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

        $this->_textObjectName = 'Currency Rate';
        $this->_tableDefaultOrderBy = 'ValidDate DESC';
        $this->_tableLayoutsEnabled = false;
        $this->_tableLinkFieldKeys = [];
        $this->_tableFieldsShow = ['ValidDate', 'CurrencyISO', 'RateToRUB', 'Provider'];
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

