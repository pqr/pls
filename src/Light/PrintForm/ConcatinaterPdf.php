<?php


namespace Light\PrintForm;


use Light\Pdf;

class ConcatinaterPdf implements ConcatinaterInterface
{
    #[\Override]
    public function concat(array $files, string $resultFile, string $title)
    {
        return Pdf::concatenatePdfs($files, $resultFile);
    }
}
