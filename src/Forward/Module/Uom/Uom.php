<?php
namespace Forward\Module\Uom;

use Forward\Application;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class Uom extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('uom');

        $this->textSaveBtn = 'UOM';
        $this->tableDefaultOrderBy = 'OrderID';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);

        $this->addFields(

            F::createString('Name'),

            F::createText('Descriptions', ['name' => 'Services', 'title' => 'Сервисы, для которых эта идиница измерения будет автоматически подставлена при заполнении Vendor Invoice']),

            CF::orderId()

        );
    }
}
