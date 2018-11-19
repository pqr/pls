<?php
namespace Forward\Module\Customer\Subtables;

use Forward\Application;
use Forward\Field\GeneralFields;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;
use Light\Field\FieldAbstract as F;
use Light\User;

class Consignee extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('customerconsignee');
        $this->_title = 'Consignee';
        $this->textAddLink = 'Add Consignee';
        $this->_printHeadBlock = false;

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            F::createString('Name', ['name' => 'Получатель', 'editWidth' => 'medium', 'upper' => true]),

            GeneralFields::shipmentRequestZip()->setEditWidth('small')->setNameTable(null),

            GeneralFields::shipmentRequestStreet()->setEditWidth('medium')->setNameTable(null),

            GeneralFields::shipmentRequestCity()->setEditWidth('medium')->setNameTable(null),

            GeneralFields::shipmentRequestCountry()->setEditWidth('medium')->setNameTable(null),

            GeneralFields::shipmentRequestPhone()->setEditWidth('medium')->setNameTable(null),

            F::createString('INN', ['name' => 'ИНН', 'editWidth' => 'medium']),
            F::createString('KPP', ['name' => 'КПП', 'editWidth' => 'medium']),
            F::createString('OGRN', ['name' => 'ОГРН', 'editWidth' => 'medium'])

        );
    }
}
