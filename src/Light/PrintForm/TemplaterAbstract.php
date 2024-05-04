<?php


namespace Light\PrintForm;


use Symfony\Component\Process\Process;

abstract class TemplaterAbstract
{
    /**
     * @var string
     */
    protected $templateFilepathAbs;

    public function __construct(string $templateFilepathAbs)
    {
        $this->templateFilepathAbs = $templateFilepathAbs;

        if (!is_file($this->templateFilepathAbs)) {
            throw new \Exception("Template file $this->templateFilepathAbs not found");
        }
    }

    abstract public function writeData(array $data);

    public function saveHtml(string $dest)
    {
        throw new \Exception('writeToHtml not implemented');
    }

    public function savePdf(string $dest, bool $landscape)
    {
        throw new \Exception('writeToPdf not implemented');
    }

    public function savePdfAsync(string $dest, bool $landscape):?Process
    {
        throw new \Exception('writeToPdf not implemented');
    }

    public function saveExcel(string $dest)
    {
        throw new \Exception('writeToExcel not implemented');
    }

    public function saveDoc(string $dest)
    {
        throw new \Exception('writeToDoc not implemented');
    }

    public function saveDocx(string $dest)
    {
        throw new \Exception('writeToDocx not implemented');
    }
}