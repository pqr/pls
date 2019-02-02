<?php

namespace Light\SubtableBase;

use Light\Config;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

/**
 * Базовый класс для подтаблицы контактов
 */
abstract class ContactsAbstract extends Subtable
{
    public $allowTablePrintHeadBlock = false;

    public $allowExportToExcel = false;
    public $allowTableLayouts = false;
    public $allowPaste = false;

    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->textSubtableTitle = Config::$lang === 'ru' ? 'Контакты' : 'Contacts';
        $this->textTableEmpty = Config::$lang === 'ru' ? 'Нет контактов' : 'No Contacts';
        $this->textAddLink = Config::$lang === 'ru' ? 'Добавить контакт' : 'Add Contact';

        $this->addFields(

            F::createText('Name', [
                'name' => Config::$lang === 'ru' ? 'Имя' : 'Name',
                'editWidth' => 'medium',
                'tableWidthHtml' => 126,
                'rows' => 2,
            ]),

            F::createText('Position', [
                'name' => Config::$lang === 'ru' ? 'Должность' : 'Position',
                F::PARAM_AC_SOURCE => 'list_concatposition',
                'editWidth' => 'medium',
                'tableWidthHtml' => 106,
                'rows' => 2,
            ]),

            CF::email()->setEditWidth('medium'),

            CF::phone('WorkPhone', [
                'name' => Config::$lang === 'ru' ? 'Стационарный телефон' : 'Work Phone',
                'editWidth' => 'medium',
            ]),

            CF::phone('MobilePhone', [
                'name' => Config::$lang === 'ru' ? 'Мобильный телефон' : 'Mobile Phone',
                'editWidth' => 'medium',
            ]),

            CF::comments([
                'rows' => 2,
                'editCssClass' => 'contact-comments',
                'clickable' => true,
                'editWidth' => 'medium',
            ])

        );
    }
}
