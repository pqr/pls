<?php

namespace Light\Codeception;

use Codeception\Module;
use Codeception\Module\PhpBrowser;

class AdminSessionHelper extends Module
{
    protected $requiredFields = ['username', 'password'];

    private $phpsessid;
    private $csrf;

    // HOOK: before each suite
    public function _initialize(): void
    {
        /** @var PhpBrowser $phpBrowser */
        $phpBrowser = $this->getModule('PhpBrowser');
        $phpBrowser->sendAjaxPostRequest('?a=Login', [
            'username' => $this->config['username'],
            'password' => $this->config['password']
        ]);
        $this->phpsessid = $phpBrowser->grabCookie('PHPSESSID');

        $homeHtml = $phpBrowser->_request('GET', 'index.php');
        preg_match('/window._lightCsrfToken = "(\w+)"/', (string) $homeHtml, $m);
        if (!isset($m[1])) {
            throw new \Exception('window._lightCsrfToken not found on Home');
        }
        $this->csrf = $m[1];
    }

    // HOOK: before test
    public function _before(\Codeception\TestInterface $test): void
    {
        /** @var PhpBrowser $phpBrowser */
        $phpBrowser = $this->getModule('PhpBrowser');
        $phpBrowser->setCookie('PHPSESSID', $this->phpsessid);
        $phpBrowser->setHeader('X_CSRF_TOKEN', $this->csrf);
        //$phpBrowser->setCookie('XDEBUG_SESSION', 'PHPSTORM');
    }

    public function hasHeaderXLightRedirect($url): void
    {
        /** @var PhpBrowser $phpBrowser */
        $phpBrowser = $this->getModule('PhpBrowser');
        $xlightredirect = $phpBrowser->client->getInternalResponse()->getHeader('x-light-redirect');
        $this->assertNotEmpty($xlightredirect);
        $this->assertContains($url, $xlightredirect);
    }
}