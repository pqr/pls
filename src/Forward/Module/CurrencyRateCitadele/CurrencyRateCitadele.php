<?php
namespace Forward\Module\CurrencyRateCitadele;

use Light\Db;
use Light\Field\FieldDate;
use Light\Link;
use Light\ModuleBase\ModuleAbstract;

class CurrencyRateCitadele extends ModuleAbstract
{
    /**
     * Метод для переопределения в классах потомках - здесь определяются поля и настройки модуля
     */
    public function __construct()
    {
        parent::__construct();

        $this->_textObjectName = 'Currency Rate';
    }

    protected function initFilters()
    {
        $this->_filtersNoSettings = true;
        $this->addFiltersDatesFromUntil('ValidDate', ['name' => 'Valid']);
    }

    protected function validateUnique(array $dataFromPost)
    {
        $query = "SELECT ID
                  FROM currencyrate_citadele
                  WHERE Name = @s
                      AND ValidDate = @s
                      AND ID != @i";

        if ($existId = Db::getOne($query, $dataFromPost['Name'], $dataFromPost['ValidDate'], $this->_obj->getId())) {

            $msg = 'Курс ' . htmlspecialchars($dataFromPost['Name'])
                . ' на дату ' . FieldDate::beautify($dataFromPost['ValidDate'])
                . ' уже существует, '
                . Link::moduleViewBlank('посмотреть...', $existId);

            $this->riseSaveErorr($msg, 'ValidDate');

        }
    }


}