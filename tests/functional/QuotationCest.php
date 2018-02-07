<?php

namespace tests\functional;

use Codeception\Example;
use Light\Codeception\ModuleCestAbstract;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuotationCest extends ModuleCestAbstract
{
    /**
     * @param \FunctionalTester|\AcceptanceTester $I
     *
     * @example ["en"]
     * @example ["ru"]
     */
    public function downloadExcel($I, Example $example)
    {
        $lang = $example[0];

        $I->wantTo('Download Quotation Excel ' . $lang);

        $I->sendGET('?a=Quotation&lang=' . $lang . '&id=' . $this->getTestObjId() . '&func=Excel');
        $I->canSeeHttpHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $filepath = __DIR__ . '/../_output/quotation_test_' . $lang . '.xlsx';
        file_put_contents($filepath, $I->grabResponse());

        $spreadsheet = IOFactory::load($filepath);
        $a2val = $spreadsheet->getSheet(0)->getCell("A2")->getValue();
        $title = $lang === 'ru' ? 'Котировка' : 'Quotation';
        $I->assertContains($title . ' #' . $this->getTestObjId(), $a2val);
    }

}
