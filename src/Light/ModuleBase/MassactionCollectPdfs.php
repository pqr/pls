<?php

namespace Light\ModuleBase;


use Light\Config;
use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\Html;
use Light\Request;
use Light\Traits\TableWithFoldersTrait;
use Light\User;

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
            $this->addMassaction('CollectPdfs',
                Config::$lang === 'ru' ? 'Собрать PDF файлы в папку' : 'Collect PDF files into one folder'
            );
        }
    }

    protected function massactionCollectPdfs(array $ids)
    {
        $dbTable = $this->getDbTable();

        $destFolder = Request::getModule() . '_pdfs/' . User::getId() . '_' . date('Y_m_d_H_i_s');

        FilesFolder::mkdir($destFolder);
        if (!FilesFolder::isDir($destFolder)) {
            $msg = Config::$lang === 'ru' ? 'Не удалось создать директорию' : 'Can not create directory';
            MassactionUtils::sendErrorDialog($msg . ' ' . FilesFolder::getAbsolutePath($destFolder));
            return;
        }

        $successCount = 0;
        $errorFiles = [];

        $records = Db::getAll("SELECT * FROM @n WHERE ID IN (@a)", $dbTable, $ids);

        foreach ($records as $record) {

            /** @var TableWithFoldersTrait $this */
            $srcFolder = $this->getFolderRelativePath($record);
            if (!FilesFolder::isDir($srcFolder)) {
                continue;
            }

            foreach (FilesFolder::scandir($srcFolder) as $file) {

                //Проверим, что это pdf файл
                $ext = strtolower(File::pathinfo($file, PATHINFO_EXTENSION));
                if ($ext !== 'pdf') {
                    continue;
                }

                //Попробуем скопировать
                $srcRelativePath = $srcFolder . '/' . $file;

                $destRelativePath = $destFolder . '/' . $file;
                //Сделаем имя файла назначения уникальным
                $destRelativePath = FilesFolder::getUniqueFilename($destRelativePath);

                if (FilesFolder::copy($srcRelativePath, $destRelativePath)) {
                    $successCount++;
                } else {
                    $errorFiles[] = FilesFolder::getAbsolutePath($srcRelativePath);
                }
            }
        }

        $msg = Config::$lang === 'ru' ? 'Копируем файлы в папку' : 'Copying to directory';
        $outHtml = $msg .' ' . FilesFolder::getLinkWithIcon($destFolder, $destFolder);
        $outHtml .= '<br>';

        if ($errorFiles) {
            $errorFilesText = implode("\n", $errorFiles);
            $errorFilesHtml = Html::getTruncatedList($errorFilesText, 'file');
            $msg = Config::$lang === 'ru' ? 'Не удалось скопировать' : 'Unable to copy';
            $outHtml .= "<br>$msg <strong>" . count($errorFiles) . "</strong>:<br>$errorFilesHtml";
        }

        $msg = Config::$lang === 'ru' ? 'Успешно скопировано' : 'Success';
        $outHtml .= "<br>$msg: $successCount";

        MassactionUtils::sendSuccessDialog($outHtml, false);
    }
}
