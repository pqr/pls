<?php


namespace Light\PrintForm;


use Light\Fieldset;
use Light\XlsWriter;
use Symfony\Component\Process\Process;

class TemplaterExcel extends TemplaterAbstract
{
    /**
     * @var XlsWriter
     */
    public $xlsWriter;

    /**
     * @var Fieldset
     */
    private $fieldset;
    /**
     * @var string
     */
    private $titleExcelSheet;

    public function __construct(string $templateFilepathAbs, string $titleExcelSheet, Fieldset $fieldset)
    {
        parent::__construct($templateFilepathAbs);

        $this->xlsWriter = new XlsWriter($templateFilepathAbs);
        $this->fieldset = $fieldset;

        $titleExcelSheet = mb_substr($titleExcelSheet, 0, 30);
        $this->titleExcelSheet = str_replace('/', '_', $titleExcelSheet);
    }

    public function writeData(array $data)
    {
        $this->xlsWriter->replaceData($data, $this->fieldset);
        $this->xlsWriter->getSpreadsheet()->getActiveSheet()->setTitle($this->titleExcelSheet);
    }

    public function saveExcel(string $dest)
    {
        $this->xlsWriter->save($dest);
    }

    public function savePdf(string $dest, bool $landscape)
    {
        $this->xlsWriter->savePdf($dest);
    }

    public function savePdfAsync(string $dest, bool $landscape): ?Process
    {
        // Для Excel шаблона асинхронная генерация PDF не предусмотрена, поэтому просто запускаем синхронный метод
        $this->savePdf($dest, $landscape);
        // Возвращаем null, чтобы сигнализировать вызывающему методу, что тут никакого асинхронного процесса нет
        // и дожидаться завершения нет смысла (всё уже синхронно завершено)
        return null;
    }


}