<?php

namespace tests\functional;

use Light\Codeception\ModuleCestAbstract;
use Light\Codeception\ModuleCestWithMassUpdateTrait;

class ShipmentCest extends ModuleCestAbstract
{
    use ModuleCestWithMassUpdateTrait;

    /**
     * Переопределяем метод, чтобы не проверять в шапке наличие (ID: ...) - в модуле Shipment этого текста нет!
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    protected function canSeeIdInPageHead($I)
    {
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function copy($I)
    {
        $I->amOnPage('?a=Shipment&addnew=1&copyfrom=' . $this->getTestObjId() . '&copymode=Copy');
        $I->canSeePageHead($this->getModuleName());
        $I->canSeeElement('.btn-save');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function costSheet($I)
    {
        $I->amOnPage('?a=Shipment&id=' . $this->getTestObjId() . '&func=cs');
        $I->canSeePageHead($this->getModuleName());
        $I->canSeePageHead('Cost-sheet');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function costSheetEdit($I)
    {
        $I->expect('Get Cost-sheet edit html in JSON');
        $I->sendGET('?a=Shipment&id=' . $this->getTestObjId() . '&func=cs&editcs=1');
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonType([
            'IncomeHtml' => 'string',
            'ExpensesHtml' => 'string'
        ]);
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function costSheetCopy($I)
    {
        $I->expect('Get Cost-sheet data for copy in JSON');
        $I->sendGET('?a=Shipment&ps=CopyFromCs&FileNumber=106525');
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonType([
            'income' => 'array',
            'expenses' => 'array',
        ]);
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function costSheetSubmit($I)
    {
        $I->expect('Get new Cost-sheet view html in JSON');
        $I->sendPOST('?a=Shipment&id=' . $this->getTestObjId() . '&func=cs&submitcs=1', [
            'IncomeEditSubtable' => ['ids' => '', 'delrows' => ''],
            'ExpensesEditSubtable' => ['ids' => '', 'delrows' => ''],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseMatchesJsonType([
            'IncomeHtml' => 'string',
            'ExpensesHtml' => 'string',
            'EGP' => 'float|integer',
            'Deviation' => 'float|integer',
            'HistoryHtml' => 'string',
            'QuotationIDHtml' => 'string'
        ]);
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function costSheetSearchAirfreightRateDialog($I)
    {
        $I->canSeeAjaxDialog('?a=SearchAirfreightRateDialog&action=render');
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function deleteDialog($I)
    {
        // Переопределяем стандартный тест на появление диалогового окна удаления
        // т.к. в этом диалоговом окне не будет кнопки "Ок" - к перевозке ID=8458 привязаны какие-то счета, которые не
        // позволяют её удалить
        $I->canSeeAjaxDialog('?a=' . $this->getModule() . '&delid=' . $this->getTestObjId(), false);
    }
}
