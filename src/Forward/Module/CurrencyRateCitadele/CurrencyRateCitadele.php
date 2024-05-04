<?php

namespace Forward\Module\CurrencyRateCitadele;

use Forward\Application;
use Light\Db;
use Light\Field\FieldDate;
use Light\Link;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class CurrencyRateCitadele extends ModuleAbstract
{
    /**
     * Метод для переопределения в классах потомках - здесь определяются поля и настройки модуля
     */
    public function __construct()
    {
        parent::__construct();

        $this->textSaveBtn = 'Currency Rate';
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
    }

    #[\Override]
    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;
        $this->addFiltersDatesFromUntil('ValidDate', ['name' => 'Valid']);
    }

    #[\Override]
    protected function validateUnique(array $dataFromPost)
    {
        $query = "SELECT ID
                  FROM currencyrate_citadele
                  WHERE Name = @s
                      AND ValidDate = @s
                      AND ID != @i";

        if ($existId = Db::getOne($query, $dataFromPost['Name'], $dataFromPost['ValidDate'], $this->_obj->getId())) {

            $msg = 'Курс ' . htmlspecialchars((string) $dataFromPost['Name'])
                . ' на дату ' . FieldDate::beautify($dataFromPost['ValidDate'])
                . ' уже существует, '
                . Link::create(CurrencyRateCitadele::class, 'посмотреть...')
                    ->view($existId)
                    ->blank()
                    ->getHtml();

            $this->riseSaveErorr($msg, 'ValidDate');

        }
    }


}
