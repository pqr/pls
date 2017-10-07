<?php

namespace tests\unit;

class ShipmentCriteriaTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testMe()
    {
        \Forward\Classes\ShipmentCriteria\ShipmentCriteria::updateAll(100);
        \Forward\Classes\ShipmentCriteria\ShipmentCriteria::flush();
    }
}