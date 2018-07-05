<?php
namespace Forward\Module\Customer\Subtables;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;
use Light\User;

class Agents extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('customeragent');
        $this->_title = 'Origins & Agents';
        $this->textAddLink = 'Add Origin Agent';
        $this->_printHeadBlock = false;

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            F::create('Origin', F::TYPE_STRING, ['editWidth' => 'origin', 'tableWidthHtml' => 206]),

            F::create(
                'Name',
                F::TYPE_STRING,
                [
                    'editCssClass' => 'contact-name',
                    'tableWidthHtml' => 126
                ]
            ),

            CF::email(),

            CF::phone(),

            CF::comments(
                [
                    'rows' => 2,
                    'editCssClass' => 'contact-comments',
                    'clickable' => true,
                    'tableWidthHtml' => 152
                ]
            )

        );
    }
}
