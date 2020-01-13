<?php
namespace Light\Module\UserModule;

use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

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

    public function getMasterFieldKey(): string
    {
        return 'ID';
    }

    public function getMasterModuleForDbLog(): string
    {
        return 'UserModule';
    }

}
