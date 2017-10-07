<?php
namespace Light\Module\Autolaunch;

use Light\ObjAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\CommonFields as CF;

class AutolaunchLogObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('autolaunchlog');

        $this->addFields(
            CF::id(),
            F::createHtml('ShortMessage', ['name' => 'Message']),
            F::createHtml('Report'),
            F::createFloat('WorkTime', ['name' => 'Working Time', 'uom' => 'sec'])
        );

        $this->getFieldset()->addFieldsCreatedBy();
    }
}