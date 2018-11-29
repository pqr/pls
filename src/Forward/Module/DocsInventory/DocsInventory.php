<?php


namespace Forward\Module\DocsInventory;


use Forward\Field\GeneralFields;
use Forward\Module\DocsInventory\Subtables\Lines;
use Light\Db;
use Light\Field\CommonFields;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;

class DocsInventory extends ModuleAbstract
{
    protected $textSaveBtn = 'Опись док-ов';

    public function __construct()
    {
        parent::__construct();

        $this->setNameFieldKey('ID');

        $this->addFields(

            CommonFields::id(),
            F::createDate('SentDate', [
                'name' => 'Дата отправки в бухгалтерию'
            ]),
            CommonFields::comments()->setAjax(false)
        );

    }

    protected function registerSubtables()
    {
        $this->registerSubtable(Lines::class);
    }

    protected function initFilters()
    {
        $this->addFilters(
            F::createText('FileNumbers', [
                'name' => 'File Numbers',
                'sqlWhere' => function ($value) {
                    $query = "EXISTS (SELECT 1
                                  FROM docsinventoryline dil
                                  WHERE dil.DocsInventoryID = docsinventory.ID
                                      AND " . Db::getSqlWhereSeparatedByComa($value, 'dil.FileNumber', -1, false,
                            true) .
                        ")";

                    return $query;
                }
            ]),
            F::createText('MAWBNumbers', [
                'name' => 'MAWB Numbers',
                'sqlWhere' => function ($value) {
                    $value = str_replace([' ', '-'], '', $value);
                    $query = "EXISTS(SELECT 1
                             FROM docsinventoryline dil
                             WHERE dil.DocsInventoryID = docsinventory.ID
                                 AND " . Db::getSqlWhereSeparatedByComa(
                            $value,
                            "REPLACE(REPLACE(dil.MAWBNumber, ' ', ''), '-', '')",
                            11,
                            true
                        ) . ")";

                    return $query;
                }
            ])

        );
    }


}