<?php


namespace Light\PrintForm;


use Light\Template;
use Symfony\Component\Filesystem\Filesystem;

class ConcatinaterHtml implements ConcatinaterInterface
{
    public const PLACEHOLDER_BEGIN = '<!-- PRINT FORM BODY BEGIN -->';
    public const PLACEHOLDER_END = '<!-- PRINT FORM BODY END -->';
    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
    }

    public function concat(array $files, string $resultFile, string $title): void
    {
        $bodyHtmls = [];
        foreach ($files as $file) {
            $bodyHtmls[] = $this->getBodyHtml($file);
        }
        $pageBreak = '<br clear=all style="mso-special-character:line-break;page-break-before:always">';
        $newBodyHtml = implode($pageBreak, $bodyHtmls);

        $masterTpl = LIGHT_DIR_LIGHT . '/templates/module/PrintFormsMaster.tpl.php';
        $resultHtml = Template::parseTpl($masterTpl, ['title' => $title, 'bodyHtml' => $newBodyHtml]);

        $this->fs->dumpFile($resultFile, $resultHtml);
    }

    private function getBodyHtml(string $file): string
    {
        $html = file_get_contents($file);
        $pattern = '/' . preg_quote(self::PLACEHOLDER_BEGIN) . '(.*)' . preg_quote(self::PLACEHOLDER_END) . '/s';
        preg_match($pattern, $html, $matches);
        return $matches[1] ?? $html;
    }
}