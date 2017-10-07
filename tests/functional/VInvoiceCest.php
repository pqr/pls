<?php

namespace tests\functional;

use Light\Codeception\ModuleCestAbstract;

class VInvoiceCest extends ModuleCestAbstract
{
    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function download($I)
    {
        $I->wantTo('Multuple download files from Vendor Invoices');
        $I->sendPOST('?a=VInvoice&p=massaction&action=Download', ['CheckedIds' => $this->getTestObjId()]);
        $I->seeResponseCodeIs(200);
        $I->canSeeHttpHeader('Content-Type', 'application/zip');
        $I->canSeeHttpHeader('Content-Disposition',
            'attachment;filename="VendorInvocies ' . date('Y-m-d') . ' (1).zip"');
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function fromCostSheet($I)
    {
        $I->wantTo('Open Add New form of Vendor Invoice from Cost-sheet');
        $I->amOnPage('?a=VInvoice&addnew=1&fromCostSheet=7773&csLineIds=23323');

        $I->cantSeeSqlError();
        $I->cantSeeXdebugError();

        $I->expect('See one invoice line with amonts');
        $I->canSeeInFormFields('#edit-form', [
            'LinesSubtable[0][MAWBNumber]' => '580-09255411',
            'LinesSubtable[0][FileNumber]' => '105967',
            'LinesSubtable[0][Name]' => 'Airfreight',
            'LinesSubtable[0][UOMID]' => '1',
            'LinesSubtable[0][Quantity]' => '397',
            'LinesSubtable[0][Price]' => '1.65',
            'LinesSubtable[0][Amount]' => '655.05',
            'LinesSubtable[0][VATAmount]' => '',
            'LinesSubtable[0][FinalAmount]' => '655.05',
        ]);
    }
}