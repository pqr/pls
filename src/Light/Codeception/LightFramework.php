<?php
namespace Light\Codeception;

use Codeception\Lib\Framework;
use Codeception\TestInterface;

class LightFramework extends Framework
{
    /*
     * Create the client connector. Called before each test
     */
    public function _createClient(): void
    {
        $this->client = new LightConnector();
    }

    public function _before(TestInterface $test): void
    {
        $this->_createClient();
    }

    public function _after(TestInterface $test): void
    {
        $_SESSION = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];

        parent::_after($test);
    }
}
