<?php
namespace Forward\Module\Customer\StandartRateDialog;

use Forward\Field\RateFields;
use Light\Field\CommonFields as CF;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

abstract class SurchargesAbstract extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->_printHeadBlock = false;

        $this->addFields(

            RateFields::name()->setEditWidth('surcharge-name')->setTableWidthHtml(156),
            RateFields::baseAmount(),
            RateFields::minAmount(),
            RateFields::maxAmount(),
            RateFields::perKgAmount(),
            CF::currency()

        );
    }

    protected function getMasterFieldKey()
    {
        return 'CustomerStandartRateID';
    }
}
