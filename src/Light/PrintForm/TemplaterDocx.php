<?php


namespace Light\PrintForm;


use PhpOffice\PhpWord\TemplateProcessor;

class TemplaterDocx extends TemplaterAbstract
{
    /**
     * @var TemplateProcessor
     */
    public $templateProcessor;

    public function __construct(string $templateFilepathAbs)
    {
        parent::__construct($templateFilepathAbs);

        $this->templateProcessor = new TemplateProcessor($templateFilepathAbs);
    }

    public function writeData(array $data)
    {
        $keys = array_keys($data);
        $values = array_values($data);
        $this->templateProcessor->setValue($keys, $values);
    }

    public function saveDocx(string $dest)
    {
        $this->templateProcessor->saveAs($dest);
    }

}