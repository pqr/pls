<?php
namespace Forward\Module\Quotation\Subtables;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;
use Light\User;

class Cargo extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('quotationcargo');
        $this->textSubtableTitle = 'Cargo';
        $this->textAddLink = 'Add Cargo';
        $this->allowTablePrintHeadBlock = false;
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            CF::length()->setParam('uom', $this->_masterObj['CargoUOM']),

            CF::width()->setParam('uom', $this->_masterObj['CargoUOM']),

            CF::height()->setParam('uom', $this->_masterObj['CargoUOM']),

            CF::pieces()->setParam('default', 1),

            CF::volume()

        );

        $this->_obj->setDescriptionFunction(fn(ObjAbstract $obj, $originalDescriptionHtml) => $obj->getFieldHtml('Length')
        . ' x '
        . $obj->getFieldHtml('Width')
        . ' x '
        . $obj->getFieldHtml('Height')
        . ' x '
        . $obj->getFieldHtml('Pieces'));
    }
}
