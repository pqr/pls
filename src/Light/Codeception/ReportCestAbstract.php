<?php
namespace Light\Codeception;

abstract class ReportCestAbstract
{

    protected function getReport():string
    {
        static $report;
        if ($report === null) {
            $parts = explode('\\', get_class($this));
            $class = end($parts);
            $report = substr($class, 0, -4); //remove 'Cest' suffix
        }
        return $report;
    }

    public function openReport(\AcceptanceTester $I)
    {
        $I->amOnPage('?a=Reportmenu&class=' . $this->getReport());
        $I->canSeePageHead('Reports');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

}