<?php

namespace tests\acceptance;

use tests\acceptance\Base\ImportFromOneCCestAbstract;

class ImportCPaymentCest extends ImportFromOneCCestAbstract
{
    protected function getType(): string
    {
        return 'cpayment';
    }
}
