<?php

namespace tests\functional;

use Light\Codeception\ModuleCestAbstract;

class OurCompanyCest extends ModuleCestAbstract
{
    protected function getTestObjId()
    {
        return 2;
    }
}