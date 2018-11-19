<?php


namespace Light\PrintForm;


interface ConcatinaterInterface
{
    public function concat(array $files, string $resultFile, string $title);
}