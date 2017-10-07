<?php
namespace Light\Codeception;

trait TesterTrait
{
    public function canSeePageHead(string $text)
    {
        $this->expect('Page head contains: ' . $text);
        $this->canSee($text, '#page-head');
    }

    public function cantSeeXdebugError()
    {
        $this->expect('No Xdebug errors');
        $this->cantSeeElement('.xdebug-error');
    }

    public function assertNotContainsXdebugError($html)
    {
        $this->expect('No Xdebug errors');
        $this->assertNotContains('xdebug-error', $html);
    }

    public function cantSeeSqlError()
    {
        $this->expect('No SQL errors');
        $this->cantSee('SQL error:', '.alert-danger');
    }

    public function assertNotContainsSqlError($html)
    {
        $this->expect('No SQL errors');
        $this->assertNotContains('SQL error:', $html);
    }

    public function canSeeAjaxDialog($url, $hasButtonOk = true)
    {
        $this->expect('See Ajax Dialog');
        $this->sendAjaxGetRequest($url);
        $this->canSeeHttpHeader('x-light-showdialog');

        $jsonSchema = ['html' => 'string'];
        if ($hasButtonOk) {
            $jsonSchema['dialogOptions'] = [
                'buttonOkText' => 'string'
            ];
        }

        $this->seeResponseMatchesJsonType($jsonSchema);
    }
}