<?php
namespace tests\acceptance\Base;


use Forward\ForwardConfig;

abstract class ImportFromOneCCestAbstract
{
    abstract protected function getType(): string;

    public function testImport(\AcceptanceTester $I)
    {
        $type = $this->getType();

        $I->haveHttpHeader('X-Auth', ForwardConfig::$onecAuthToken);

        $dir = __DIR__ . '/../../_data/onec/' . $type;
        foreach (scandir($dir) as $file) {
            if ($file[0] === '.') {
                continue;
            }
            $I->wantToTest("$type/$file");
            $postData = file_get_contents("$dir/$file");
            $I->sendPOST('/onec.php', $postData); //?XDEBUG_SESSION_START=1
            $I->seeResponseCodeIs(200);
            $I->seeResponseEquals('');
        }

    }
}
