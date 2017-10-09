<?php

namespace tests\acceptance;

use tests\acceptance\Base\ImportFromOneCCestAbstract;

class ImportCInvoiceCest extends ImportFromOneCCestAbstract
{
    protected function getType(): string
    {
        return 'cinvoice';
    }
}
