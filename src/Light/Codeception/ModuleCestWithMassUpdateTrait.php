<?php

namespace Light\Codeception;

/**
 * Trait для подключения к классам тестов унаследованных от ModuleCestAbstract
 * Добавляет тест на проверку работоспособности диалогвого окна Multiple Update
 */
trait ModuleCestWithMassUpdateTrait
{
    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function massUpdate($I): void
    {
        $I->expect('See Mass Update ajax Dialog');
        $module = $this->getModule();
        $I->sendAjaxPostRequest("?a=$module&p=massaction&action={$module}MassUpdate",
            ['CheckedIds' => $this->getTestObjId()]);
        $I->canSeeHttpHeader('x-light-showdialog');
        $I->seeResponseMatchesJsonType([
            'html' => 'string',
            'dialogOptions' => [
                'buttonOkText' => 'string'
            ]
        ]);
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function massUpdateSubmit($I): void
    {
        $I->expect('See Multiple Update success Dialog');
        $module = $this->getModule();
        $I->sendAjaxPostRequest("?a=$module&p=massaction&action={$module}MassUpdate&submit=1",
            ['CheckedIds' => $this->getTestObjId(), 'multipleupdate' => ['testfield' => 'hidden']]);
        $I->canSeeHttpHeader('x-light-showdialog');
        $I->seeResponseMatchesJsonType([
            'html' => 'string',
        ]);

        $I->expect('See x-light-jseval header to refresh table');
        $I->canSeeHttpHeader('x-light-jseval');
    }
}
