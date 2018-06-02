<?php
namespace Forward\Module\Quotation\Subtables;

use Light\Field\CommonFields as CF;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

class Cargo extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('quotationcargo');
        $this->_title = 'Cargo';
        $this->textAddLink = 'Add Cargo';
        $this->_printHeadBlock = false;

        $this->addFields(

            CF::length()->setParam('uom', $this->_masterObj['CargoUOM']),

            CF::width()->setParam('uom', $this->_masterObj['CargoUOM']),

            CF::height()->setParam('uom', $this->_masterObj['CargoUOM']),

            CF::pieces()->setParam('default', 1),

            CF::volume()

        );

        $this->_obj->setDescriptionFunction(function(ObjAbstract $obj, $originalDescriptionHtml) {

            return $obj->getFieldHtml('Length')
            . ' x '
            . $obj->getFieldHtml('Width')
            . ' x '
            . $obj->getFieldHtml('Height')
            . ' x '
            . $obj->getFieldHtml('Pieces');

        });
    }
}
