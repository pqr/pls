<?php

namespace Light\Field;

use Light\Config;

class FieldYesNo extends FieldSelect
{
    public $textYes = 'Yes';
    public $textNo = 'No';

    public $cssClassYes = 'yes';
    public $cssClassNo = 'no';

    #[\Override]
    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        if (Config::$lang === 'ru') {
            $this->textYes = 'Да';
            $this->textNo = 'Нет';
        }

        $this->empty = false;
        $this->tableAlign = 'center';

        // используем функцию, чтобы подставить текст который пользователь возможно позже заполнил
        // в $this->textNo и $this->textYes
        $this->options = fn() => [
            0 => ['text' => $this->textNo, 'cssClass' => $this->cssClassNo],
            1 => ['text' => $this->textYes, 'cssClass' => $this->cssClassYes],
        ];

        $this->editWidth = 'small';
    }

    #[\Override]
    public function getNameTableSettings()
    {
        return parent::getNameTableSettings() . ' (Y/N)';
    }
}
