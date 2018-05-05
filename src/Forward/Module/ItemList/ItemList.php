<?php
namespace Forward\Module\ItemList;

use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class ItemList extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->setDbTable('list_chargedescription');

        $this->textSaveBtn = 'Item';
        $this->tableDefaultOrderBy = 'Name';

        $this->addFields(

            F::create('Name', F::TYPE_STRING, ['name' => 'Name (En)']),

            F::create('NameRu', F::TYPE_STRING, ['name' => 'Name (Ru)'])

        );
    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;
        $this->addFilterName(F::TYPE_STRING, ['sqlWhere' => "Name LIKE '%{value}%' OR NameRu LIKE '%{value}%'"]);
    }
}
