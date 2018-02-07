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
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }
}
