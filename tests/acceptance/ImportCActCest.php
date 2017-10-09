<?php

namespace tests\acceptance;

use tests\acceptance\Base\ImportFromOneCCestAbstract;

class ImportCActCest extends ImportFromOneCCestAbstract
{
    protected function getType(): string
    {
        return 'cact';
    }
}
