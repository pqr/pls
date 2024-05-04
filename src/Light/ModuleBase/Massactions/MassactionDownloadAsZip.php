<?php


namespace Light\ModuleBase\Massactions;


use Light\Autolaunch\BackupHelpres\P7zip;
use Light\Config;
use Light\ErrorHandler;
use Light\File;
use Light\FilesFolder;
use Light\ModuleBase\MassactionAbstract;
use Light\ModuleRegister;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\Session;
use Symfony\Component\Process\Process;

class MassactionDownloadAsZip extends MassactionAbstract
{
    /**
     * @var array
     */
    private $options = [
        'ext' => '',
        'startWith' => '',
        'zipFilename' => '',
    ];

    public $blank = true;
    /**
     * @var ObjAbstract
     */
    private $objBase;

    public function __construct($text = '', array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        if ($text === '') {
            $text = Config::$lang === 'ru' ? 'Скачать файлы в ZIP архиве' : 'Download files as ZIP';
        }
        $this->text = $text;
    }

    public function getKey(): string
    {
        return 'DownloadAsZip' . implode('', $this->options);
    }

    public function __invoke(array $ids): void
    {
        $tmpDir = File::mkdirTmp('massactionDownloadAsZip');
        if (!$tmpDir) {
            $msg = Config::$lang === 'ru' ? 'Не удалось создать архив' : 'Can\'t create archive';
            Session::flashError($msg);
            $this->redirectToList();
            return;
        }

        $copied = $this->copyFilesToTmpDir($ids, $tmpDir);

        if (!$copied) {
            $msg = Config::$lang === 'ru' ? 'Нет файлов' : 'No files';
            Session::flashError($msg);
            $this->redirectToList();
            return;
        }

        $zipFileAbs = $this->zipFiles($tmpDir);
        if (!$zipFileAbs) {
            $this->redirectToList();
            return;
        }

        Response::streamFileFromFilesystem($zipFileAbs);
    }

    private function copyFilesToTmpDir(array $ids, string $tmpDir)
    {
        $copied = 0;

        foreach ($ids as $id) {

            try {

                $relativeDir = FilesFolder::getRelativeModule(Request::getModule(), $id);
                foreach (FilesFolder::scandir($relativeDir) as $file) {

                    if (!$this->filterFile($file)) {
                        // Файл не подходит по расширению или имени файла
                        continue;
                    }

                    $copied += $this->copyFileToTmpDir($relativeDir, $file, $tmpDir);

                }

            } catch (\Throwable $e) {

                Session::flashError($e->getMessage());
                ErrorHandler::reportException($e);

            }
        }

        return $copied;
    }

    private function filterFile(string $file): bool
    {
        $ext = $this->options['ext'];
        if ($ext) {
            $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($fileExt !== $ext) {
                // Данный файл не подходит по условию расширения
                return false;
            }
        }

        $startWith = $this->options['startWith'];
        if ($startWith && mb_stripos($file, (string) $startWith) !== 0) {
            // Данный файл не подходит по условию что имя файла должно начинаться с $startWith
            return false;
        }

        return true;
    }

    private function copyFileToTmpDir(string $relativeDir, string $file, string $tmpDir): int
    {
        $origin = FilesFolder::getAbsolute("$relativeDir/$file");
        $target = File::getUniqueFilename("$tmpDir/$file");
        $copyResult = File::copy($origin, $target);
        if (!$copyResult) {
            $msg = Config::$lang === 'ru'
                ? 'Не удалось скопировать файл '
                : 'Can\'t copy file ';
            Session::flashError($msg . htmlspecialchars("$relativeDir/$file"));
            return 0;
        }

        return 1;
    }

    private function redirectToList(): void
    {
        Response::redirect(Request::getModule());
    }

    private function zipFiles(string $tmpDir): ?string
    {
        $filesCount = count(scandir($tmpDir)) - 2;

        $zipFilename = $this->options['zipFilename'] ?: ModuleRegister::getModuleName(Request::getModule());
        $zipFile = "$zipFilename ($filesCount).zip";
        $zipFileAbs = "$tmpDir/$zipFile";

        $cmd = P7zip::create($zipFileAbs)
            ->inputDir($tmpDir)
            ->getCmd();

        try {

            $process = Process::fromShellCommandline($cmd);
            $process->mustRun();

            return $zipFileAbs;

        } catch (\Exception $e) {

            \Light\ErrorHandler::reportException($e);
            Session::flashError($e->getMessage());
            return null;

        }
    }
}
