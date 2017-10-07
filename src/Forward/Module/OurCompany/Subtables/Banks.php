<?php

namespace Forward\Module\OurCompany\Subtables;

use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;
use Light\SubtableBase\Subtable;

class Banks extends Subtable
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('ourcompanybank');
        $this->addFields([

            F::createString('Name'),

            F::createText('BeneficiaryBank', ['name' => 'Beneficiary Bank']),

            F::createText('CorrespondentBank', ['name' => 'Correspondent Bank']),

        ]);
    }

    protected function getMasterFieldKey()
    {
        return 'CompanyID';
    }


}