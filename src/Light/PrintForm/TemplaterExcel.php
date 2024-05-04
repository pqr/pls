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
     * @var string
     */
    private $titleExcelSheet;

    public function __construct(string $templateFilepathAbs, string $titleExcelSheet, private readonly Fieldset $fieldset, private readonly int $tableOpts)
    {
        parent::__construct($templateFilepathAbs);

        $this->xlsWriter = new XlsWriter($templateFilepathAbs);

        $titleExcelSheet = mb_substr($titleExcelSheet, 0, 30);
        $this->titleExcelSheet = str_replace('/', '_', $titleExcelSheet);
    }

    #[\Override]
    public function writeData(array $data): void
    {
        $this->xlsWriter->replaceData($data, $this->fieldset, $this->tableOpts);
        $this->xlsWriter->clearPlaceholders();
        $this->xlsWriter->getSpreadsheet()->getActiveSheet()->setTitle($this->titleExcelSheet);
    }

    #[\Override]
    public function saveExcel(string $dest): void
    {
        $this->xlsWriter->save($dest);
    }

    #[\Override]
    public function savePdf(string $dest, bool $landscape): void
    {
        $this->xlsWriter->savePdf($dest);
    }

    #[\Override]
    public function savePdfAsync(string $dest, bool $landscape): ?Process
    {
        // Для Excel шаблона асинхронная генерация PDF не предусмотрена, поэтому просто запускаем синхронный метод
        $this->savePdf($dest, $landscape);
        // Возвращаем null, чтобы сигнализировать вызывающему методу, что тут никакого асинхронного процесса нет
        // и дожидаться завершения нет смысла (всё уже синхронно завершено)
        return null;
    }


}
