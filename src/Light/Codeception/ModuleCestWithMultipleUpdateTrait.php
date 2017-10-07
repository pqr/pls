<?php

namespace Light\Codeception;

/**
 * Trait для подключения к классам тестов унаследованных от ModuleCestAbstract
 * Добавляет тест на проверку работоспособности диалогвого окна Multiple Update
 */
trait ModuleCestWithMultipleUpdateTrait
{
    abstract protected function getModule(): string;

    abstract protected function getTestObjId();

    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     */
    public function multipleUpdate($I)
    {
        $I->expect('See Multiple Update ajax Dialog');
        $I->sendAjaxPostRequest('?a=' . $this->getModule() . '&p=massaction&action=MultipleUpdate',
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
    public function multipleUpdateSubmit($I)
    {
        $I->expect('See Multiple Update success Dialog');
        $I->sendAjaxPostRequest('?a=' . $this->getModule() . '&p=MultipleUpdateSubmit',
            ['CheckedIds' => $this->getTestObjId(), 'multipleupdate' => ['testfield' => 'hidden']]);
        $I->canSeeHttpHeader('x-light-showdialog');
        $I->seeResponseMatchesJsonType([
            'html' => 'string',
        ]);

        $I->expect('See x-light-jseval header to refresh table');
        $I->canSeeHttpHeader('x-light-jseval');
    }
}