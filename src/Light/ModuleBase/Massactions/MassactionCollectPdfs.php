<?php

namespace Light\ModuleBase\Massactions;


use Light\Config;
use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\Html;
use Light\ModuleBase\MassactionUtils;
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

    }

    /**
     * @param string $filenameIncludes текст, который должен содержаться в имени файла.
     * Если начать с восклицательного знака, то будем искать файлы НЕ содержащие этот текст
     * @return array
     * @throws \Exception
     */
    protected function collectPdfFilesToTmpDir(array $ids, string $filenameIncludes = ''): array
    {
        $destFolderRelative = Request::getModule() . '_pdfs/' . User::getId() . '_' . date('Y_m_d_H_i_s');
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

            $filenameNotIncludes = '';
            if ($filenameIncludes !== '' && mb_strpos($filenameIncludes, '!') === 0) {
                // Задано специальное правило "искать файлы НЕ включаюище следующий текст
                $filenameNotIncludes = mb_substr($filenameIncludes, 1);
                $filenameIncludes = '';
            }

            /** @var TableWithFoldersTrait $this */
            $srcRelative = $this->getFolderRelative($obj->getData());
            foreach (FilesFolder::scandir($srcRelative, 'pdf') as $file) {

                $filename = pathinfo($file, PATHINFO_FILENAME);
                if ($filenameIncludes !== '' && mb_stripos($filename, $filenameIncludes) === false) {
                    // От этого метода требовалось, чтобы файл содержал опеределённые слова (например, "без_печати")
                    continue;
                }
                if ($filenameNotIncludes !== '' && mb_stripos($filename, $filenameNotIncludes) !== false) {
                    // От этого метода требовалось, чтобы файл НЕ содержал опеределённые слова (например, "без_печати")
                    continue;
                }

                // Попробуем скопировать
                $srcRelativeFilepath = "$srcRelative/$file";
                $destRelativeFilepath = "$destFolderRelative/$file";
                // Сделаем имя файла назначения уникальным
                $destRelativeFilepath = FilesFolder::getUniqueFilename($destRelativeFilepath);

                if (FilesFolder::copy($srcRelativeFilepath, $destRelativeFilepath)) {
                    $successCount++;
                } else {
                    $errorFiles[] = FilesFolder::getAbsolute($srcRelativeFilepath);
                }
            }
        }
        return [$destFolderRelative, $successCount, $errorFiles];
    }
}
