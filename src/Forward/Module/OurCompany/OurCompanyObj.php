<?php
namespace Forward\Module\OurCompany;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class OurCompanyObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('ourcompany');

        $this->addFields(

            F::createString('Name'),

            F::createText('Address'),

            CF::orderId()

        );
    }
}
