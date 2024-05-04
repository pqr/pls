<?php

namespace Light\Module\Deleted;

use Light\Config;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class DeletedObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('deleted');

        $this->addFieldsCreatedBy();

        $this->addFields(
            F::createSelect('ModuleID', [
                'name' => 'Module',
                'options' => 'SELECT ID, ModuleName FROM dblog_module',
            ]),
            F::createSelect('TableID', [
                'name' => 'Table',
                'options' => 'SELECT ID, TableName FROM dblog_table',
                'sqlSelectMandatory' => true,
            ]),
            F::createInt('MasterID', ['name' => 'Master ID']),
            F::createInt('RecordID', ['name' => 'Record ID', 'sqlSelectMandatory' => true]),
            F::createInt('TraceID', ['name' => 'Trace ID']),

            F::createYesNo('Restored', [
                'name' => Config::$lang === 'ru' ? 'Восстановлено' : 'Restored',
                'sqlSelectMandatory' => true
            ]),

            F::createText('JSON', [
                'name' => 'JSON',
                'modes' => F::MODE_VIEW,
                'sqlSelect' => "SELECT `JSON` FROM deleted_text WHERE deletedID = deleted.ID",
                'getHtmlFunction' => function ($value, array $data, $outHtml) {
                    if ($value) {
                        $jsonData = json_decode($value, true);
                        if ($jsonData) {
                            $outHtml = '<pre>' . json_encode($jsonData, JSON_PRETTY_PRINT) . '</pre>';
                        }
                    }

                    return $outHtml;
                }
            ])
        );


    }

    #[\Override]
    public function getDescriptionHtml()
    {
        return $this->getFieldHtml('TableID') . ' ID ' . $this->_data['RecordID'];
    }
}
