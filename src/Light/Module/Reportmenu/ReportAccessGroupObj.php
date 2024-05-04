<?php


namespace Light\Module\Reportmenu;


use Light\ObjAbstract;
use Light\Field\FieldAbstract as F;

/**
 * Вспомагательный класс исключительно чтобы удобно записывать логи при изменении прав доступа к отчёту
 */
class ReportAccessGroupObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('reportaccess_group');

        $this->addFields(

            F::createString('ReportClass', [
                'name' => 'Report Class',
                'modes' => F::MODE_ALL & ~F::MODE_LOG
            ]),

            F::createSelect('GroupID', [
                'name' => 'Group',
                'options' => 'usergroup'
            ])

        );
    }

    #[\Override]
    public function getDescriptionHtml()
    {
        return $this->getFieldHtml('ReportClass') . ' - ' . $this->getFieldHtml('GroupID');
    }


}
