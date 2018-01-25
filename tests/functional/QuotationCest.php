<?php

namespace tests\functional;

use Light\Codeception\ModuleCestAbstract;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuotationCest extends ModuleCestAbstract
{
    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function downloadExcelEn( $I)
    {
        $this->downloadExcelTest($I, 'en');
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester$I
     */
    public function downloadExcelRu( $I)
    {
        $this->downloadExcelTest($I, 'ru');
    }

    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     * @param string $lang
     */
    private function downloadExcelTest( $I, string $lang)
    {
        $I->wantTo('Download Quotation Excel ' . $lang);

        $I->sendGET('?a=Quotation&lang=' . $lang . '&id=' . $this->getTestObjId() . '&func=Excel');
        $I->canSeeHttpHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $filepath = __DIR__ . '/../_output/quotation_test_' . $lang . '.xlsx';
        file_put_contents($filepath, $I->grabResponse());

        $spreadsheet = IOFactory::load($filepath);
        $a9val = $spreadsheet->getSheet(0)->getCell("A2")->getValue();
        $title = $lang === 'ru' ? 'Котировка' : 'Quotation';
        $I->assertContains($title . ' #' . $this->getTestObjId(), $a9val);
    }
}
