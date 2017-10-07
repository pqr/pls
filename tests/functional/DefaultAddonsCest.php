<?php
namespace tests\functional;
use Light\ModuleRegister;

class DefaultAddonsCest
{

    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function view($I)
    {
        $I->wantTo('View Default Addons');
        $I->amOnPage('?a=DefaultAddons');
        $I->canSeePageHead(ModuleRegister::getModuleName('DefaultAddons'));
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function submit($I)
    {
        $I->wantTo('Submit Default Addons');
        $I->amOnPage('?a=DefaultAddons');

        $oldValue = (float) $I->grabValueFrom('#AddonUSDAmountN');
        $newValue = $oldValue + 1;

        $I->submitForm('form', ['AddonUSDAmountN' => $newValue]);
        $I->amOnPage('?a=DefaultAddons&XDEBUG_SESSION_START=PHPSTORM');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
        $I->seeInField('AddonUSDAmountN', $newValue);

        $I->submitForm('form', ['AddonUSDAmountN' => $oldValue]);
        $I->amOnPage('?a=DefaultAddons');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
        $I->seeInField('AddonUSDAmountN', $oldValue);
    }
}
