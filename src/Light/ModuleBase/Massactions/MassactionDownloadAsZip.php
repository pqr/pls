<?php


namespace Light\ModuleBase\Massactions;


use Light\ApplicationAbstract;
use Light\Config;
use Light\File;
use Light\FilesFolder;
use Light\ModuleRegister;
use Light\ObjAbstract;
use function Light\reportException;
use Light\Response;
use Light\Session;
use Symfony\Component\Process\Process;

trait MassactionDownloadAsZip
{
    protected function addMassactionDownloadAsZip()
    {
        if ($this->folders) {
            $text = Config::$lang === 'ru' ? 'Скачать файлы в ZIP архиве' : 'Download files as ZIP';
            $this->addMassaction('DownloadAsZip', $text, '', '', null, true);
        }
    }

    protected function massactionDownloadAsZip(array $ids)
    {
        $tmpDir = File::mkdirTmp('massactionDownloadAsZip');
        if (!$tmpDir) {
            $msg = Config::$lang === 'ru' ? 'Не удалось создать архив' : 'Can\'t create archive';
            Session::flashError($msg);
            $this->redirectToList();
            return;
        }

        $success = 0;

        $objBase = $this->getObj();
        foreach ($ids as $id) {
            /** @var ObjAbstract $obj */
            $obj = clone $objBase;
            $obj->setId($id);
            if (!$obj->loadData()) {
                continue;
            }

            // $objName = strip_tags($obj->getDescriptionHtml());

            $relative = $this->getFolderRelative($obj->getData());
            foreach (FilesFolder::scandir($relative) as $file) {
                $origin = FilesFolder::getAbsolute("$relative/$file");
                $target = File::getUniqueFilename("$tmpDir/$file");
                $copyResult = File::copy($origin, $target);
                if (!$copyResult) {
                    $msg = Config::$lang === 'ru'
                        ? 'Не удалось скопировать файл '
                        : 'Can\'t copy file ';
                    Session::flashError($msg . htmlspecialchars("$relative/$file"));
                } else {
                    $success++;
                }
            }
        }

        if (!$success) {
            $msg = Config::$lang === 'ru' ? 'Нет файлов' : 'No files';
            Session::flashError($msg);
            $this->redirectToList();
            return;
        }

        $module = $this->getModule();
        $moduleName = ModuleRegister::getModuleName($module);
        $zipFile = "$moduleName ($success).zip";
        $zipFileAbs = "$tmpDir/$zipFile";
        // Зазипуем с помощью 7z
        $args = [
            '7z',
            'a', // команда 'a' - добавление файлов в архив
            $zipFileAbs, // файл назначения
            "$tmpDir/*" // источник
        ];

        try {

            $process = new Process($args);
            $process->mustRun();

            Response::streamFileFromFilesystem($zipFileAbs);
            ApplicationAbstract::terminate();

        } catch (\Exception $e) {

            reportException($e);
            Session::flashError($e->getMessage());
            $this->redirectToList();

        }
    }
}
