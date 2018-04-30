<?php


namespace Light\ModuleBase;


use Light\ApplicationAbstract;
use Light\Config;
use Light\File;
use Light\FilesFolder;
use Light\ModuleRegister;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;
use Light\Session;
use Light\User;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
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
        $fileSystem = new Filesystem();

        $tmpDir = LIGHT_DIR_TEMP . '/massactionDownloadAsZip' . time() . '_' . User::getId();
        try {
            $fileSystem->mkdir($tmpDir);
        } catch (IOExceptionInterface $exception) {
            Session::flashError($exception->getMessage());
            $this->redirectToList();
            return;
        }

        $msgBuff = new Msg();

        $success = 0;
        $errors = 0;

        $objBase = $this->getObj();
        foreach ($ids as $id) {
            /** @var ObjAbstract $obj */
            $obj = clone $objBase;
            $obj->setId($id);
            if (!$obj->loadData()) {
                continue;
            }

            // $objName = strip_tags($obj->getDescriptionHtml());

            $relativePath = $this->getFolderRelativePath($obj->getData());
            foreach (FilesFolder::scandir($relativePath) as $file) {
                $originFile = FilesFolder::getAbsolutePath("$relativePath/$file");
                if (is_dir(File::encode($originFile))) {
                    // Директории пропускаем
                    continue;
                }
                // $targetFile = File::getUniqueFilename("$tmpDir/$objName $file");
                $targetFile = File::getUniqueFilename("$tmpDir/$file");
                try {
                    $fileSystem->copy($originFile, $targetFile);
                    $success++;

                } catch (\Exception $exception) {
                    $msgBuff->addError($exception->getMessage());
                    $errors++;

                }
            }
        }

        if ($errors) {
            $msgBuff->putIntoSessionFlash();
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

            $fileSystem->remove($tmpDir);
            ApplicationAbstract::terminate();

        } catch (ProcessFailedException $exception) {

            Session::flashError($exception->getMessage());
            $this->redirectToList();

        }
    }
}
