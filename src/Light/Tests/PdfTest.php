<?php
namespace Light\Tests;

use Light\Msg;
use Light\Pdf;
use PHPUnit\Framework\TestCase;

class PdfTest extends TestCase
{
    public function testSave()
    {
        $msgBuff = new Msg();
        $html = file_get_contents(__DIR__ . '/PdfAssets/Html2PdfExample.html');

        $result = Pdf::save($html, __DIR__ . '/PdfAssets/PdfSaveTest.pdf', false, $msgBuff);

        self::assertEquals(true, $result);
    }
}
 