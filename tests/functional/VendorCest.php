<?php

namespace tests\functional;
use Light\Codeception\ModuleCestAbstract;

class VendorCest extends ModuleCestAbstract
{
    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeList($I)
    {
        $I->amOnPage('?a=' . $this->getModule());
        $this->canSeeModuleNameInPageHead($I);
        $body = (string)$I->grabResponse();
        file_put_contents('vendor_list.html', $body);
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }
}
