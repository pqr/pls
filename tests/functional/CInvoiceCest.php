<?php
namespace tests\functional;
use Codeception\Example;
use Light\Codeception\ModuleCestAbstract;
use Light\Codeception\ModuleCestWithMassUpdateTrait;
use Light\Codeception\ModuleCestWithPrintFormsTrait;

class CInvoiceCest extends ModuleCestAbstract
{
    use ModuleCestWithMassUpdateTrait, ModuleCestWithPrintFormsTrait;

    protected function getTestObjId():int
    {
        return 8508;
    }

    protected function getAllPrintForms():array
    {
        return ['Word', 'WordStamp', 'PDF', 'PDFStamp'];
    }

    /**
     * @group pdf
     *
     * GTS
     * @example [8528, "PDF"]
     *
     * PLS
     * @example [9104, "PDF"]
     * @example [9104, "PDFStamp"]
     *
     */
    public function printPdf(\AcceptanceTester $I, Example $example)
    {
        $this->printPdfInternal($I, $example);
    }

    /**
     * GTS
     * @example [8528, "8528", "Word"]
     *
     * PLS
     * @example [9104, "017-0916", "Word"]
     * @example [9104, "017-0916", "WordStamp"]
     */
    public function printWord(\AcceptanceTester $I, Example $example)
    {
        list($cinvoiceId, $cinvocieName, $form) = $example;

        $I->wantTo("Print Word for invoice ID $cinvoiceId with form: $form");

        $I->sendPOST("?a=CInvoice&id=$cinvoiceId&func=Print&form=$form");

        $subfolder = floor($cinvoiceId / 1000);
        $filename = 'Invoice ' . $cinvocieName . '.doc';
        $filepathAbs = \Light\Config::$filesfolderLocal . "/CInvoice/$subfolder/$cinvoiceId/$filename";
        $I->expect('Word file exists: ' . $filename);
        $I->assertFileExists($filepathAbs);

        $wordContent = file_get_contents($filepathAbs);
        $I->assertNotContainsSqlError($wordContent);
        $I->assertNotContainsXdebugError($wordContent);
    }

    /**
     * GTS
     * @example [8528, "Word|PDF"]
     *
     * PLS
     * @example [9104, "Word|WordStamp|PDF|PDFStamp"]
     *
     * PLS OOO
     * @example [9093, ""]
     *
     */
    public function testPrintButtons(\AcceptanceTester $I, Example $example)
    {
        $this->testPrintButtonsInternal($I, $example);
    }

    /**
     * GTS
     * @example ["8528", "Word|PDF"]
     *
     * PLS
     * @example ["9104", "Word|WordStamp|PDF|PDFStamp"]
     *
     */
    public function testPrintButtonsMultiple(\AcceptanceTester $I, Example $example)
    {
        $this->testPrintButtonsMultipleInternal($I, $example);
    }
}
