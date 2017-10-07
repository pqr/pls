<?php
namespace Light\Module\UserModule;

use Light\ObjAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\CommonFields as CF;

class UserModuleGroupObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('usergroup');

        $this->addFields(
            F::createString('Name'),
            CF::orderId(),
            F::createText('ModuleAccessJSON'),
            F::createText('SpecialAccessJSON')
        );

    }

}