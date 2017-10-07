<?php
namespace Light\Tests;

use Light\Msg;
use Light\Pdf;

class PdfTest extends \PHPUnit_Framework_TestCase
{
    public function testSave()
    {
        $msgBuff = new Msg();
        $html = file_get_contents(__DIR__ . '/PdfAssets/Html2PdfExample.html');

        $result = Pdf::save($html, __DIR__ . '/PdfAssets/PdfSaveTest.pdf', $msgBuff);

        self::assertEquals(true, $result);
    }
}
 