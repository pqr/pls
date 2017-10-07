<?php
namespace Light\SubtableBase;

use Light\Css;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

/**
 * Базовый класс для подтаблицы контактов
 *
 * Class ContactsAbstract
 * @package Light\SubtableBase
 */
abstract class ContactsAbstract extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->_title = 'Contacts';
        $this->_tableMsgIfEmpty = 'No contacts';
        $this->_printHeadBlock = false;
        $this->_tableExcelEnabled = false;
        $this->_tableLayoutsEnabled = false;
        $this->_textAddLink = 'Add Contact';

        $this->addFields(

            F::createString('Name', [
                'editCssClass' => 'contact-name',
                'tableWidthHtml' => 126
            ]),

            F::createString('Position', [
                F::PARAM_AC_SOURCE => 'list_concatposition',
                'editCssClass' => 'contact-position',
                'tableWidthHtml' => 106
            ]),

            CF::email(),

            CF::phone('WorkPhone', ['name' => 'Work Phone']),

            CF::phone('MobilePhone', ['name' => 'Mobile Phone']),

            CF::comments([
                'rows' => 2,
                'editCssClass' => 'contact-comments',
                'clickable' => true,
                'tableWidthHtml' => 152
            ])

        );
    }

    protected function initCssJs()
    {
        parent::initCssJs();
        if ($this->isStateEdit()) {
            Css::addLight('Contacts.css');
        }
    }
}
