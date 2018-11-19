<?php

namespace Light\ModuleBase;


use Light\Config;
use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\Html;
use Light\ObjAbstract;
use Light\Request;
use Light\Traits\TableWithFoldersTrait;
use Light\User;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Один из стандартных Massaction реализован в виде Trait к ModuleAbstract
 * Trait MassactionCollectPdfs
 * @package Light\ModuleBase
 */
trait MassactionCollectPdfs
{
    protected function addMassactionCollectPdfs()
    {
        if ($this->folders) {
            $text = Config::$lang === 'ru' ? 'Собрать PDF файлы в папку' : 'Collect PDF files into one folder';
            $this->addMassaction('CollectPdfs', $text);
        }
    }

    protected function massactionCollectPdfs(array $ids)
    {
        try {

            [$destFolderRelative, $successCount, $errorFiles] = $this->collectPdfFilesToTmpDir($ids);

            $msg = Config::$lang === 'ru' ? 'Копируем файлы в папку' : 'Copying to directory';
            $outHtml = $msg . ' ' . FilesFolder::getLinkWithIcon($destFolderRelative, $destFolderRelative);
            $outHtml .= '<br>';

            if ($errorFiles) {
                $errorFilesHtml = Html::getTruncatedList($errorFiles, 'file');
                $msg = Config::$lang === 'ru' ? 'Не удалось скопировать' : 'Unable to copy';
                $outHtml .= "<br>$msg <strong>" . count($errorFiles) . "</strong>:<br>$errorFilesHtml";
            }

            $msg = Config::$lang === 'ru' ? 'Успешно скопировано' : 'Success';
            $outHtml .= "<br>$msg: $successCount";

            MassactionUtils::sendSuccessDialog($outHtml, false);

        } catch (\Throwable $e) {

            $msg = Config::$lang === 'ru' ? 'Не удалось скопировать файлы': 'Can\'t copy files';
            $msg .= ': ' . $e->getMessage();
            MassactionUtils::sendErrorDialog($msg);
        }


    }

    protected function collectPdfFilesToTmpDir(array $ids, string $filenameIncludes = ''): array
    {
        $destFolderRelative = Request::getModule() . '_pdfs/' . User::getId() . '_' . date('Y_m_d_H_i_s');
        $destFolderAbs = FilesFolder::getAbsolutePath($destFolderRelative);

        $fs = new Filesystem();
        $fs->mkdir($destFolderAbs);

        $successCount = 0;
        $errorFiles = [];

        foreach ($ids as $id) {

            /** @var ObjAbstract $obj */
            $obj = clone $this->_obj;
            $obj->setId($id);
            if (!$obj->loadData()) {
                continue;
            }
            if (!$this->hasAccessRightToObj($obj)) {
                continue;
            }

            /** @var TableWithFoldersTrait $this */
            $srcRelative = $this->getFolderRelativePath($obj->getData());
            if (!FilesFolder::isDir($srcRelative)) {
                continue;
            }

            foreach (FilesFolder::scandir($srcRelative) as $file) {

                // Проверим, что это pdf файл
                $ext = strtolower(File::pathinfo($file, PATHINFO_EXTENSION));
                if ($ext !== 'pdf') {
                    continue;
                }

                $filename = File::pathinfo($file, PATHINFO_FILENAME);

                if ($filenameIncludes !== '' && mb_stripos($filename, $filenameIncludes) === false) {
                    // От этого метода требовалось, чтобы файл содержал опеределённые слова (например, "без_печати")
                    continue;
                }

                //Попробуем скопировать
                $srcRelativeFilepath = $srcRelative . '/' . $file;

                $destRelativeFilepath = $destFolderRelative . '/' . $file;
                //Сделаем имя файла назначения уникальным
                $destRelativeFilepath = FilesFolder::getUniqueFilename($destRelativeFilepath);

                if (FilesFolder::copy($srcRelativeFilepath, $destRelativeFilepath)) {
                    $successCount++;
                } else {
                    $errorFiles[] = FilesFolder::getAbsolutePath($srcRelativeFilepath);
                }
            }
        }
        return [$destFolderRelative, $successCount, $errorFiles];
    }
}
