<?php
namespace Forward\Module\ContainerType;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class ContainerType extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('containertype');

        $this->textSaveBtn = 'Container Type';
        $this->tableDefaultOrderBy = 'OrderID';
        $this->tableLayouts['Default'] = 'OrderID|Name|TEU';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            CF::id(),

            F::create('Name', F::TYPE_STRING),

            F::create('TEU', F::TYPE_INT, ['editAlign' => 'left']),

            CF::orderId()

        );
    }
}
