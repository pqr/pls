<?php

namespace tests\functional;

use Light\Codeception\ModuleCestAbstract;

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

        $reader = new \PHPExcel_Reader_Excel2007();
        $phpExcel = $reader->load($filepath);

        $a9val = $phpExcel->getSheet(0)->getCellByColumnAndRow(0, 9)->getValue();
        $title = $lang === 'ru' ? 'Котировка' : 'Quotation';
        $I->assertContains($title . ' #' . $this->getTestObjId(), $a9val);
    }
}
