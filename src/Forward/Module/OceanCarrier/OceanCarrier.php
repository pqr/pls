<?php
namespace Forward\Module\OceanCarrier;

use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class OceanCarrier extends ModuleAbstract
{
    /**
     * Метод для переопределения в классах потомках - здесь определяются поля и настройки модуля
     */
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('oceancarrier');
        $this->_textObjectName = 'Ocean Carrier';
        $this->tableDefaultOrderBy = 'Name ASC';

        $this->allowTableLayouts = false;

        $this->addFields(
            F::createString('Name', ['upper' => true])
        );
    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;
        $this->addFilterName();
    }
}
