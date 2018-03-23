<?php

namespace tests\functional;

use Codeception\Example;
use Light\Codeception\ModuleCestAbstract;
use PhpOffice\PhpSpreadsheet\IOFactory;

class QuotationCest extends ModuleCestAbstract
{
    /**
     * @group download
     *
     * @param \FunctionalTester|\AcceptanceTester $I
     *
     * @param Example $example
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     *
     * @example ["en"]
     * @example ["ru"]
     */
    public function downloadExcel($I, Example $example)
    {
        $lang = $example[0];

        $I->wantTo('Download Quotation Excel ' . $lang);
        $url = '?a=Quotation&lang=' . $lang . '&id=' . $this->getTestObjId() . '&func=Excel';

        $spreadsheet = $I->assertDownloadingExcel($url);

        $a2val = $spreadsheet->getSheet(0)->getCell("A2")->getValue();
        $title = $lang === 'ru' ? 'Котировка' : 'Quotation';
        $I->assertContains($title . ' #' . $this->getTestObjId(), $a2val);
    }

}
