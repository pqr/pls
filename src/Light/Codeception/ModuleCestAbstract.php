<?php

namespace Light\Codeception;

use Light\Db;
use Light\Field\FieldDate;
use Light\ModuleRegister;

abstract class ModuleCestAbstract
{
    protected $minIdFromDb;

    protected function getDbTable()
    {
        $module = $this->getModule();
        return strtolower($module);
    }

    /**
     * Можно переопределить в классе потомке
     * @return int
     */
    protected function getTestObjId()
    {
        if ($this->minIdFromDb === null) {
            $dbtable = $this->getDbTable();
            $this->minIdFromDb = (int)Db::getOne("SELECT min(ID) FROM $dbtable");
            if (!$this->minIdFromDb) {
                // Если таблица ещё пуста, создадим пустую фиктивную запись в целях тестирования
                Db::query("INSERT INTO $dbtable (ID) VALUES (NULL)");
                $this->minIdFromDb = Db::getInsertId();
            }
        }
        return $this->minIdFromDb;
    }

    protected function getModule(): string
    {
        static $module;
        if ($module === null) {
            $parts = explode('\\', get_class($this));
            $class = end($parts);
            $module = substr($class, 0, -4); //remove 'Cest' suffix
        }
        return $module;
    }

    protected function getModuleName()
    {
        return ModuleRegister::getModuleName($this->getModule());
    }

    /**
     * Отедльный вспомагательный метод, который проверяет наличия текста с названием модуля в шапке страницы
     * Этот метод можно переопределить в конкретныз Cest классах тестов, если проверяеть наличие названия модуля не нужно
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    protected function canSeeModuleNameInPageHead($I)
    {
        $I->canSeePageHead($this->getModuleName());
    }

    /**
     * Отедльный вспомагательный метод, который проверяет наличия текста с ID текущей записи в шапке страницы
     * Этот метод можно переопределить в конкретныз Cest классах тестов, если проверяеть наличие ID в шапке не нужно
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    protected function canSeeIdInPageHead($I)
    {
        $I->canSeePageHead('(ID: ' . $this->getTestObjId() . ')');
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeAddNew($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&addnew=1');
        $this->canSeeModuleNameInPageHead($I);
        $I->canSeeElement('.btn-save');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

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

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeListAjaxRefresh($I)
    {
        $I->sendAjaxPostRequest('?a=' . $this->getModule());
        $html = $I->grabResponse();

        $I->assertNotContainsXdebugError($html);
        $I->assertNotContainsSqlError($html);
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeView($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&id=' . $this->getTestObjId() /*. '&XDEBUG_SESSION_START=PHPSTORM'*/);
        $this->canSeeModuleNameInPageHead($I);
        $this->canSeeIdInPageHead($I);
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeEdit($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&editid=' . $this->getTestObjId());
        $this->canSeeModuleNameInPageHead($I);
        $this->canSeeIdInPageHead($I);
        $I->canSeeElement('.btn-save');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function submit($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&editid=' . $this->getTestObjId());
        $I->submitForm('#edit-form', ['_continmethod' => 'view']);

        if ($I instanceof \AcceptanceTester) {

            $I->canSeeInCurrentUrl('?a=' . $this->getModule() . '&id=' . $this->getTestObjId());
            $I->canSeeElement('.alert-success');

        } elseif ($I instanceof \FunctionalTester) {

            $html = $I->grabResponse();
            $I->assertNotContainsXdebugError($html);
            $I->assertNotContainsSqlError($html);

        }
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function modeLog($I)
    {
        $I->amOnPage('?a=' . $this->getModule() . '&logid=' . $this->getTestObjId());
        $this->canSeeModuleNameInPageHead($I);
        $this->canSeeIdInPageHead($I);
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function deleteDialog($I)
    {
        $I->canSeeAjaxDialog('?a=' . $this->getModule() . '&delid=' . $this->getTestObjId());
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param string $fieldKey
     * @param string $date
     */
    protected function testChangeFieldDate($I, string $fieldKey, string $date)
    {
        $I->wantTo('Change field ' . $fieldKey . ' via ajax');
        $I->sendAjaxPostRequest('?a=' . $this->getModule() . '&id=' . $this->getTestObjId() . '&func=ChangeField&fieldKey=' . $fieldKey,
            ['newValue' => $date]);
        $I->canSeeResponseCodeIs(200);
        $response = $I->grabResponse();
        $I->assertContains(FieldDate::beautify($date), $response);
    }
}
