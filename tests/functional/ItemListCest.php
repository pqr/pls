<?php

namespace tests\functional;

use Light\Codeception\ModuleCestAbstract;

class ItemListCest extends ModuleCestAbstract
{
    protected function getDbTable()
    {
        return 'list_chargedescription';
    }

}
