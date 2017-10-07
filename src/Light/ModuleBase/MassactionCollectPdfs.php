<?php
/**
 * Created by PhpStorm.
 * User: petr
 * Date: 17.08.17
 * Time: 16:35
 */

namespace Light\ModuleBase;


use Light\Db;
use Light\File;
use Light\FilesFolder;
use Light\Html;
use Light\Request;
use Light\User;

class MassactionCollectPdfs
{
    public static function proceedCollectPdfs(array $ids, string $dbTable, callable $getFolderRelativePath)
    {
        $destFolder = Request::getModule() . '_pdfs/' . User::getId() . '_' . date('Y_m_d_H_i_s');

        FilesFolder::mkdir($destFolder);
        if (!FilesFolder::isDir($destFolder)) {
            MassactionAbstract::sendErrorDialog('Не удалось создать папку ' . FilesFolder::getAbsolutePath($destFolder));
            return;
        }

        $successCount = 0;
        $errorFiles = [];

        $records = Db::getAll("SELECT * FROM @n WHERE ID IN (@a)", $dbTable, $ids);

        foreach ($records as $record) {

            $srcFolder = $getFolderRelativePath($record);
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

        $outHtml = 'Копируем файлы в папку ' . FilesFolder::getLinkWithIcon($destFolder, $destFolder);
        $outHtml .= '<br>';

        if ($errorFiles) {
            $errorFilesText = implode("\n", $errorFiles);
            $errorFilesHtml = Html::getTruncatedList($errorFilesText, 'file');
            $outHtml .= '<br>Не удалось скопировать ' . count($errorFiles) . ' файлов:<br>' . $errorFilesHtml;
        }

        $outHtml .= '<br>Успешно скопировано файлов: ' . $successCount;

        MassactionAbstract::sendSuccessDialog($outHtml, false);
    }
}