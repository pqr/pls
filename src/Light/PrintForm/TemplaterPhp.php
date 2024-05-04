<?php


namespace Light\PrintForm;


use Light\Misc;
use Light\Pdf;
use Light\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class TemplaterPhp extends TemplaterAbstract
{
    /**
     * @var string
     */
    public $bodyHtml = '';

    public function __construct(string $templateFilepathAbs, private readonly string $titleHtml)
    {
        parent::__construct($templateFilepathAbs);
    }

    public function writeData(array $data): void
    {
        $this->bodyHtml = Template::parseTpl($this->templateFilepathAbs, $data);
    }

    public function saveHtml(string $dest): void
    {
        $fullHtml = $this->getFullHtml();
        $fs = new Filesystem();
        $fs->dumpFile($dest, $fullHtml);
    }

    public function savePdf(string $dest, bool $landscape): void
    {
        $fullHtml = $this->getFullHtml();
        $fullHtml = Misc::makeLinksAbsolute($fullHtml);
        $cmd = Pdf::getCmdPdfFromHtmlStdin($dest, $landscape);
        $proc = Process::fromShellCommandline($cmd, null, null, $fullHtml);
        // Запускаем синхронно. Если процесс вернёт код отличный от 0, то бростися исключение
        // https://symfony.com/doc/current/components/process
        $proc->mustRun();
    }

    public function savePdfAsync(string $dest, bool $landscape): ?Process
    {
        $fullHtml = $this->getFullHtml();
        $fullHtml = Misc::makeLinksAbsolute($fullHtml);
        $cmd = Pdf::getCmdPdfFromHtmlStdin($dest, $landscape);
        $proc = Process::fromShellCommandline($cmd, null, null, $fullHtml);
        // Запускаем асинхронно
        // https://symfony.com/doc/current/components/process
        $proc->start();
        // Возвращаем объект процесса, чтобы была возможность дождаться его завершения
        return $proc;
    }

    public function saveDoc(string $dest): void
    {
        $fullHtml = $this->getFullHtml();
        $fullHtml = Misc::makeLinksAbsolute($fullHtml);
        $fs = new Filesystem();
        $fs->dumpFile($dest, $fullHtml);
    }

    private function getFullHtml(): string
    {
        $masterTpl = LIGHT_DIR_LIGHT . '/templates/module/PrintFormsMaster.tpl.php';
        return Template::parseTpl($masterTpl, ['title' => $this->titleHtml, 'bodyHtml' => $this->bodyHtml]);
    }
}
