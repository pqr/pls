<?php
namespace Light\Module\UserModule;

use Light\ApplicationAbstract;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class UserModuleUserObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('user');

        $this->addFields(
            F::createString('Name'),
            F::createString('Login'),
            F::createString('Password', ['modes' => F::MODE_EDITSUBMIT]),
            F::createString('Email', 'E-mail'),
            F::createString('InnerPhone', 'Phone'),
            F::createString('Mobile', 'Mobile'),
            F::createInt('BirthDay', 'Birth Day'),
            F::createInt('BirthMonth', 'Birth Month'),
            F::createSelect('Sex', [
                'name' => 'Sex',
                'empty' => true,
                'options' => [
                    'M' => 'M',
                    'F' => 'F',
                ],
            ]),
            F::createInt('Started', 'Started (Year)'),
            F::createString('Position', 'Position'),
            F::createSelect('BranchOfficeID', ['name' => 'Branch Office', 'options' => 'SELECT ID, Name FROM userbranchoffice ORDER BY ID']),
            F::createSelect('GroupID', [
                'name' => 'Group',
                'tableWidthExcel' => 25,
                'linkModule' => 'UserGroup',
                'options' => "SELECT ID, Name FROM usergroup ORDER BY OrderID",
                'empty' => false
            ]),
            CF::active(),
            F::createCheckboxSet('ResponsibleRoles', [
                'name' => 'Roles',
                'options' => ApplicationAbstract::$app->getUserRolesList()
            ]),
            F::createText('ModuleAccessJSON'),
            F::createText('SpecialAccessJSON')
        );
    }

    #[\Override]
    public function getMasterFieldKey(): string
    {
        return 'ID';
    }

    #[\Override]
    public function getMasterModuleForDbLog(): string
    {
        return 'UserModule';
    }
}
