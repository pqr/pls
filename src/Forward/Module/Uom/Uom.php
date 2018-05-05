<?php
namespace Forward\Module\Uom;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class Uom extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('uom');

        $this->textSaveBtn = 'UOM';
        $this->tableDefaultOrderBy = 'OrderID';

        $this->addFields(

            F::create('Name', F::TYPE_STRING),

            F::create('Descriptions', F::TYPE_TEXT, ['name' => 'Services', 'title' => 'Сервисы, для которых эта идиница измерения будет автоматически подставлена при заполнении Vendor Invoice']),

            CF::orderId()

        );
    }
}
