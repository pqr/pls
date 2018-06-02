<?php
namespace Forward\Module\Quotation\Subtables;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

abstract class IncomeExpensesAbstract extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->_printHeadBlock = false;

        $this->addFields(

            F::createString('Name', [
                F::PARAM_AC_SOURCE => 'list_chargedescription',
                F::PARAM_AC_READONLY => true,
                'editWidth' => 'medium'
            ]),

            F::createMoney('Price', ['editWidth' => 'mini']),

            F::createSelect('Per', [
                'options' => ['GW' => 'GW', 'CW' => 'CW', 'MAWB' => 'MAWB', 'Piece' => 'Piece'],
                'empty' => false,
                'editCssClass' => 'input-per',
                'tableAlign' => 'center'
            ]),

            F::createMoney('MinAmount', ['name' => 'Min', 'editWidth' => 'mini']),

            F::createMoney('Amount')

        );

        $this->_copyOnAddFieldKeys = ['Per'];
    }
}