<?php
namespace Light\Field;

class FieldYesNo extends FieldSelect
{
    protected function initDefaultParams()
    {
        parent::initDefaultParams();

        $this->empty = false;
        $this->tableAlign = 'center';
        $this->options = [
            0 => ['text' => 'No', 'cssClass' => 'no'],
            1 => ['text' => 'Yes', 'cssClass' => 'yes']
        ];
        $this->editWidth = 'small';
    }

    public function getNameTableSettings()
    {
        return parent::getNameTableSettings() . ' (Y/N)';
    }
}
