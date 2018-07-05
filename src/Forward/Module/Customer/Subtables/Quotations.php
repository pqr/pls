<?php
namespace Forward\Module\Customer\Subtables;

use Forward\Application;
use Forward\Field\QuotationFields;
use Light\Field\CommonFields as CF;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;
use Light\User;

class Quotations extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
        $this->_printHeadBlock = false;
        $this->tableLinkModule = 'Quotation';
        $this->_tableMsgIfEmpty = '';
        $this->_orderBy = 'ID DESC';
        $this->setDbTable('quotation');

        $this->addFields(

            CF::id(),

            QuotationFields::status(),

            QuotationFields::name(),

            CF::validFrom(),

            CF::validUntil(),

            CF::grossWeight(),

            CF::chargeableWeight(),

            QuotationFields::cacheGp(),

            CF::currency()

        );
    }
}
