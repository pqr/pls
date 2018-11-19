<?php


namespace Light\PrintForm;


use Light\Pdf;
use Symfony\Component\Process\Process;

class ConcatinaterPdf implements ConcatinaterInterface
{
    public function concat(array $files, string $resultFile, string $title)
    {
        $cmd = Pdf::getCmdConcatenatePdf($files, $resultFile);

        $process = (new Process($cmd))->setTimeout(600);
        $process->mustRun();
        return $process->wait() == 0;
    }
}