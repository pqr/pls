<?php

namespace Forward\Classes\ImportFromOneC\Importers;

use Forward\Classes\Constant;
use Light\Link;
use Light\Msg;

abstract class ImporterAbstract
{

    const RESULT_INCORRECT = 'incorrect';
    const RESULT_NOTFOUND = 'notfound';
    const RESULT_INSERTED = 'inserted';
    const RESULT_UPDATED = 'updated';
    const RESULT_DELETED = 'deleted';
    const RESULT_NOTCHANGED = 'notchanged';

    protected $importId;
    protected $msgBuff;

    public function __construct(int $importId, Msg $msgBuff)
    {
        $this->importId = $importId;
        $this->msgBuff = $msgBuff;
    }

    protected function validateData(array $data)
    {
        $result = true;
        if (!isset($data['type'])) {
            $this->msgBuff->addError('Отсуствует обязательное поле type');
            $result = false;
        } else {
            $validTypes = ['cact', 'cinvoice', 'cpayment'];
            if (!in_array($data['type'], $validTypes)) {
                $this->msgBuff->addError('Поле type имеет недопустимое значение "' . $data['type'] . '", возможные значения: ' . implode(', ',
                        $validTypes));
                $result = false;
            }
        }

        if (!isset($data['CompanyID'])) {
            $this->msgBuff->addError('Отсуствует обязательное поле CompanyID');
            $result = false;
        } else {
            if (!Constant::isOneCCompany($data['CompanyID'])) {
                $this->msgBuff->addError('Поле CompanyID имеет недопустимое значение "' . $data['CompanyID'] . '", возможные значения: ' . implode(', ',
                        array_keys(Constant::$onecCompany)));
                $result = false;
            }
        }

        if (!isset($data['Amount'])) {
            $this->msgBuff->addError('Отсуствует обязательное поле Amount');
            $result = false;
        } else {
            if (!is_numeric($data['Amount'])) {
                $this->msgBuff->addError('Поле Amount должно быть числом, получено "' . htmlspecialchars($data['Amount']) . '"');
                $result = false;
            }
        }

        return $result;
    }

    abstract public function import(array $data): int;

    protected function getImportLink()
    {
        return Link::moduleViewBlank($this->importId, $this->importId, null, 'ImportFromOneCLog');
    }


}
