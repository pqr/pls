<?php

namespace tests\functional;

use Light\Codeception\ModuleCestAbstract;
use Light\Codeception\ModuleCestWithMultipleUpdateTrait;

class ShipmentAccountingCest
{
    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeList($I)
    {
        $I->amOnPage('?a=ShipmentAccounting');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeListAjaxRefresh($I)
    {
        $I->sendAjaxPostRequest('?a=ShipmentAccounting');
        $html = $I->grabResponse();

        $I->assertNotContainsXdebugError($html);
        $I->assertNotContainsSqlError($html);
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeView($I)
    {
        $I->amOnPage('?a=ShipmentAccounting&id=8458');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }
}
