<?php

namespace tests\functional;

class EmailNoticeSettingsCest
{
    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function open($I)
    {
        $I->wantTo('View Email Notice Settings');
        $I->amOnPage('?a=EmailNoticeSettings');
        $I->canSeePageHead('E-Mail Notice Settings');
        $I->cantSeeXdebugError();
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    /*
    public function submit($I)
    {
        $I->wantTo('Submit Email Notice Settings');
        $I->amOnPage('?a=EmailNoticeSettings');

        $oldSubject = $I->grabValueFrom('#EmailNoticeSubject');
        $newSubject = 'test subject';

        $oldBody = $I->grabValueFrom('#EmailNoticeBody');
        $newBody = 'test body';

        $I->submitForm('form', ['EmailNoticeSubject' => $newSubject, 'EmailNoticeBody' => $newBody]);
        $I->canSee('Email notice settings updated', '.alert-success');
        $I->cantSeeXdebugError();
        $I->seeInField('EmailNoticeSubject', $newSubject);
        $I->seeInField('EmailNoticeBody', $newBody);

        $I->submitForm('form', ['EmailNoticeSubject' => $oldSubject, 'EmailNoticeBody' => $oldBody]);
        $I->canSee('Email notice settings updated', '.alert-success');
        $I->cantSeeXdebugError();
        $I->seeInField('EmailNoticeSubject', $oldSubject);
        $I->seeInField('EmailNoticeBody', $oldBody);
    }
    */
}
