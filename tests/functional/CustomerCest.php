<?php

namespace tests\functional;


use Light\Codeception\ModuleCestAbstract;

class CustomerCest extends ModuleCestAbstract
{
    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function standartRateDialog($I)
    {
        $I->amGoingTo('Click "New Standart Rate" button on "Standart Rates" tab');
        $I->canSeeAjaxDialog('?a=Customer&id=' . $this->getTestObjId() . '&func=StandartRateDialog&action=new');
    }
}
