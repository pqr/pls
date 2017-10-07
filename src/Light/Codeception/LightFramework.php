<?php
namespace Light\Codeception;

use Codeception\Lib\Framework;
use Codeception\TestInterface;

class LightFramework extends Framework
{
    /*
     * Create the client connector. Called before each test
     */
    public function _createClient()
    {
        $this->client = new LightConnector();
    }

    public function _before(TestInterface $test)
    {
        $this->_createClient();
    }

    public function _after(TestInterface $test)
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