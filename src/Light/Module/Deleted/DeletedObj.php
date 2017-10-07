<?php
namespace Light\Module\Deleted;

use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class DeletedObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('deleted');

        $this->addFields(
            F::createDateTime('CreatedDateTime', ['name' => 'Deleted Date,Time']),
            F::createSelect('CreatedBy', ['name' => 'Deleted By', 'options' => 'user', 'tableWidthExcel' => 15]),
            F::createInt('RecordID', ['name' => 'Record ID']),
            F::createString('TableName', ['name' => 'Table Name', 'tableWidthExcel' => 25]),
            F::createString('Query', ['name' => 'SQL Query', 'tableWidthExcel' => 50]),
            F::createText('JSON', ['name' => 'JSON Data', 'getHtmlFunction' => function($value, array $data, $outHtml) {
                if ($value) {
                    $jsonData = json_decode($value, true);
                    if ($jsonData) {
                        $outHtml = '<pre>' . json_encode($jsonData, JSON_PRETTY_PRINT) . '</pre>';
                    }
                }

                return $outHtml;
            }]),
            F::createYesNo('Restored', ['sqlSelectMandatory' => true])
        );
    }

    public function getDescriptionHtml()
    {
        return $this['TableName'] . ' ID: ' . $this['RecordID'];
    }
}