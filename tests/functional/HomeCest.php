<?php

namespace tests\functional;

class HomeCest
{
    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function home($I)
    {
        $I->amOnPage('?a=Home');
        $I->see('Welcome, Admin (Administrators)');
    }
}