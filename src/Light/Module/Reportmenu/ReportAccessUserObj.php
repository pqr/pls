<?php


namespace Light\Module\Reportmenu;


use Light\Config;
use Light\ObjAbstract;
use Light\Field\FieldAbstract as F;

/**
 * Вспомагательный класс исключительно чтобы удобно записывать логи при изменении прав доступа к отчёту
 */
class ReportAccessUserObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('reportaccess_user');

        $this->addFields(

            F::createString('ReportClass', [
                'name' => 'Report Class',
                'modes' => F::MODE_ALL & ~F::MODE_LOG
            ]),

            F::createSelect('UserID', [
                'name' => 'User',
                'options' => 'user',
                'getHtmlFunction' => function($value, array $data, $outHtml) {
                    if ($value == -1) {
                        $outHtml = Config::$lang === 'ru' ? 'Все' : 'All';
                    }
                    return $outHtml;
                }
            ])

        );
    }

    #[\Override]
    public function getDescriptionHtml()
    {
        return $this->getFieldHtml('ReportClass') . ' - ' . $this->getFieldHtml('UserID');
    }


}
