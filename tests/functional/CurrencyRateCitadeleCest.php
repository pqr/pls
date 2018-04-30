<?php

namespace tests\functional;
use Light\Codeception\ModuleCestAbstract;

class CurrencyRateCitadeleCest extends ModuleCestAbstract
{
    protected function getDbTable()
    {
        return 'currencyrate_citadele';
    }
}
