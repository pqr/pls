<?php

namespace Light\Codeception;

use Codeception\Example;
use Light\Module\Reportmenu\Reportmenu;

abstract class ReportmenuCestAbstract
{
    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function reportmenu($I): void
    {
        $I->amOnPage('?a=Reportmenu');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     */
    public function accessAudit($I): void
    {
        $I->amOnPage('?a=Reportmenu&p=AccessAudit');
        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param Example $example
     * @dataprovider reportsProvider
     */
    public function report($I, \Codeception\Example $example): void
    {
        $reportClass = $example['reportClass'];
        $I->amOnPage("?a=Reportmenu&class=$reportClass");

        $I->cantSeeXdebugError();
        $I->cantSeeSqlError();

        $I->expectTo("See report title: $reportClass");
        $I->canSee(strip_tags((string) $example['title']));
        if ($example['description']) {
            $I->expectTo('See report description: ' . $reportClass);
            $I->canSee(strip_tags((string) $example['description']));
        }
    }

    /**
     * @return array
     */
    protected function reportsProvider()
    {
        $out = [];

        $reportmenu = new Reportmenu();
        $allReports = $reportmenu->getAllReports();
        foreach ($allReports as $reportClass) {
            $out[] = [
                'reportClass' => $reportClass,
                'title' => Reportmenu::getReportTitle($reportClass),
                'description' => Reportmenu::getReportDescription($reportClass)
            ];
        }

        return $out;
    }
}
