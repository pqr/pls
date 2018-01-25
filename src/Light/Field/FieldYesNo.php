<?php

namespace Light\Field;

use Light\Config;

class FieldYesNo extends FieldSelect
{
    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $textYes = Config::$lang === 'ru' ? 'Да' : 'Yes';
        $textNo = Config::$lang === 'ru' ? 'Нет' : 'No';

        $this->empty = false;
        $this->tableAlign = 'center';
        $this->options = [
            0 => ['text' => $textNo, 'cssClass' => 'no'],
            1 => ['text' => $textYes, 'cssClass' => 'yes']
        ];
        $this->editWidth = 'small';
    }

    public function getNameTableSettings()
    {
        return parent::getNameTableSettings() . ' (Y/N)';
    }
}
