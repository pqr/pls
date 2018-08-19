<?php

namespace Forward\Classes;


class ConstantTest extends \PHPUnit\Framework\TestCase
{

    public function testGetWeightInterval()
    {
        $this->assertEquals(2000, Constant::getWeightInterval(2000));
        $this->assertEquals(1000, Constant::getWeightInterval(1000));
        $this->assertEquals(750, Constant::getWeightInterval(750));
        $this->assertEquals(500, Constant::getWeightInterval(500));
        $this->assertEquals(300, Constant::getWeightInterval(300));
        $this->assertEquals(200, Constant::getWeightInterval(200));
        $this->assertEquals(100, Constant::getWeightInterval(100));
        $this->assertEquals(45, Constant::getWeightInterval(45));
        $this->assertEquals(null, Constant::getWeightInterval(30));
    }

    public function testGetCompanyNameBeforePartnthesis()
    {
        $this->assertEquals('Company', Constant::getCompanyNameBeforePartnthesis('Company (USD)'));
        $this->assertEquals('Company', Constant::getCompanyNameBeforePartnthesis('Company (USD) (EUR)'));
    }

    public function testGenerageQuotationName()
    {
        $this->assertEquals('010218/HELDME/Q300/SU', Constant::generageQuotationName('2018-02-01', 'HEL', 'DME', 400, 'SU'));
        $this->assertEquals('311218/HELSVO/MIN/SU', Constant::generageQuotationName('2018-12-31', 'HEL', 'SVO', 40, 'SU'));
    }
}
