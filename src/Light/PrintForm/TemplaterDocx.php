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

    #[\Override]
    public function writeData(array $data): void
    {
        $keys = array_keys($data);
        $values = array_values($data);
        $this->templateProcessor->setValue($keys, $values);
    }

    #[\Override]
    public function saveDocx(string $dest): void
    {
        $this->templateProcessor->saveAs($dest);
    }

}