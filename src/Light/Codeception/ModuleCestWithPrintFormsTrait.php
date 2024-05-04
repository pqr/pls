<?php
namespace Light\Codeception;

use Codeception\Example;
use PhpOffice\PhpSpreadsheet\IOFactory;

trait ModuleCestWithPrintFormsTrait
{
    abstract protected function getModule():string;

    abstract protected function getAllPrintForms():array;

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param \Codeception\Example $example
     */
    private function printHtmlInternal($I, Example $example): void
    {
        [$id, $form] = $example;

        $I->wantTo('Print HTML for ID ' . $id . ' with form: ' . $form);

        $I->sendPOST('?a='.$this->getModule().'&id=' . $id . '&func=Print&form=' . $form);

        $responseBody = $I->grabResponse();
        $I->assertNotContainsSqlError($responseBody);
        $I->assertNotContainsXdebugError($responseBody);
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param \Codeception\Example|array $example
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    private function printExcelInternal($I, $example): void
    {
        [$id, $form] = $example;

        $I->wantTo('Print Excel for ID ' . $id . ' with form: ' . $form);

        $I->sendPOST('?a='.$this->getModule().'&id=' . $id . '&func=Print&form=' . $form);

        $I->expect('See Content-Type application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $I->canSeeHttpHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $responseBody = $I->grabResponse();
        $I->assertNotContainsSqlError($responseBody);
        $I->assertNotContainsXdebugError($responseBody);

        $filepath = LIGHT_DIR_ROOT . '/tests/_output/' . $form . '.xlsx';
        file_put_contents($filepath, $responseBody);

        // Попробуем прочитать весь Excle файл, чтобы убедиться, что нет Exception
        $spreadsheet = IOFactory::load($filepath);
        // Попробуем прочитать первый лист, чтобы убедиться, что нет Exception
        $spreadsheet->getSheet(0);
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param \Codeception\Example $example
     */
    private function printPdfInternal($I, Example $example): void
    {
        [$id, $form] = $example;

        $I->wantTo('Print PDF for ID ' . $id . ' with form: ' . $form);

        $I->sendPOST('?a='.$this->getModule().'&id=' . $id . '&func=Print&form=' . $form);

        $I->expect('See Content-Type application/pdf');
        $I->canSeeHttpHeader('Content-Type', 'application/pdf');

        $responseBody = $I->grabResponse();
        $I->assertNotContainsSqlError($responseBody);
        $I->assertNotContainsXdebugError($responseBody);
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param \Codeception\Example $example
     */
    private function testPrintButtonsInternal($I, Example $example): void
    {
        /** @var array $forms */
        [$id, $forms] = $example;
        if ($forms) {
            $forms = explode('|', $forms);
        } else {
            // Передана пустая строка - означает что в данном примере вообще не должно быть никаких печатных форм
            $forms = [];
        }
        $I->wantTo('Check buttons visibility for ID ' . $id . ': ' . implode(', ', $forms));

        $allForms = array_flip($this->getAllPrintForms());

        $I->amOnPage('?a='.$this->getModule().'&id=' . $id);

        $I->expect('See print buttons: ' . implode(', ', $forms));
        foreach ($forms as $form) {

            $I->canSeeElement('a[href$="func=Print&form=' . $form . '#post"]');
            unset($allForms[$form]);
        }

        // Если после удаления из массива $allForms остались какие-то элементы, нужно проверить, что их нет на странице
        if ($allForms) {
            $I->expect('Dont see print buttons: ' . implode(', ', $allForms));
            foreach ($allForms as $form => $text) {
                $I->cantSeeElement('a[href$="func=Print&form=' . $form . '#post"]');
            }
        }
    }

    /**
     * @param \AcceptanceTester | \FunctionalTester $I
     * @param \Codeception\Example $example
     */
    private function testPrintButtonsMultipleInternal($I, Example $example): void
    {
        /** @var array $forms */
        [$idsString, $forms] = $example;
        if ($forms) {
            $forms = explode('|', $forms);
        } else {
            // Передана пустая строка - означает что в данном примере вообще не должно быть никаких печатных форм
            $forms = [];
        }
        $I->wantTo('Check buttons visibility on multiple print dialog: ' . implode(', ', $forms));

        $I->sendAjaxPostRequest('?a=' . $this->getModule() . '&p=massaction&action=Print', ['CheckedIds' => $idsString]);
        $I->canSeeHttpHeader('x-light-showdialog');
        $I->seeResponseMatchesJsonType(['html' => 'string']);
        $responseJson = json_decode($I->grabResponse(), true);
        $html = $responseJson['html'];
        $I->assertNotContainsSqlError($html);
        $I->assertNotContainsXdebugError($html);

        $allForms = array_flip($this->getAllPrintForms());

        $I->expect('See print buttons: ' . implode(', ', $forms));
        foreach ($forms as $form) {
            $I->assertContains('p=MassactionPrintSubmit&form=' . $form, $html);
            $I->assertContains($idsString, $html);
            unset($allForms[$form]);
        }

        // Если после удаления из массива $allForms остались какие-то элементы, нужно проверить, что их нет на странице
        if ($allForms) {
            $I->expect('Dont see print buttons: ' . implode(', ', $allForms));
            foreach ($allForms as $form => $text) {
                $I->assertNotContains('p=MassactionPrintSubmit&form=' . $form, $html);
            }
        }
    }
}
