<?php

namespace Light\Module\Core;

use Carbon\Carbon;
use Light\ApplicationAbstract;
use Light\CsvLog;
use Light\Db;
use Light\DbLog;
use Light\File;
use Light\FilesFolder;
use Light\Metric;
use Light\Misc;
use Light\ModuleRegister;
use Light\Request;
use Light\Response;
use Light\Table;
use Light\Template;
use Light\Traits\TableLayoutable;
use Light\User;

class Core
{
    public function __invoke()
    {
        //Если по какой-то причине был запрос на ?a=Core без параметров (и это не ajax), просто перенправляем пользователя
        //на default модуль
        if (!Request::isAjax()) {
            Response::redirect(ModuleRegister::getDefaultModule());
        }
    }

    public static function proceedUserparam()
    {
        if (Request::getMehod() === 'POST') {
            //Распечатаем сохранённое в базе значение - возможно, оно ещё понадобится для js клиента
            print User::setParam($_POST['param'], $_POST['value']);
        } else {
            print User::getParam($_GET['param']);
        }

        ApplicationAbstract::terminate();
    }

    public static function proceedTable()
    {
        if (Request::getMehod() === 'POST') {

            if (isset($_POST['delid'])) {

                $query = "DELETE
                          FROM user_tablelayout
                          WHERE TableName='" . Db::esc($_GET['table']) . "'
                              AND CreatedBy=" . User::getId() . "
                              AND ID=" . (int)$_POST['delid'];
                Db::query($query);


            } else {

                //Сохранение новой пользовательской настройки в базу или обновление уже существующей, если имя совпадает
                $layoutName = $_POST['layoutName'];
                $fieldsShow = $_POST['fieldsShow'];

                $query = "INSERT INTO user_tablelayout
                                (CreatedBy
                                , LayoutName
                                , TableName
                                , FieldsShow)
                              VALUES
                                (" . User::getId() . "
                                , '" . Db::esc($layoutName) . "'
                                , '" . Db::esc($_GET['table']) . "'
                                , '" . Db::esc($fieldsShow) . "'
                                )
                              ON DUPLICATE KEY UPDATE
                                LayoutName=VALUES(LayoutName),
                                FieldsShow=VALUES(FieldsShow)";

                Db::query($query);

            }

        }

        // Вне зависимости от того, был это POST запрос на сохранение или удаление какой-то настройки
        // или это был get запрос на получение списка всех настроек в при открытии диалога
        // в обоих случаях возвращаем готовый html со всеми ссылками пользовательских настроек
        print TableLayoutable::getHtmlTableUserLayouts($_GET['table']);

        ApplicationAbstract::terminate();
    }

    public static function acCore($value)
    {
        $out = [];

        //Запрос к базе данных на поиск значения для autocomplete
        $listTables = explode(',', $_GET['source']);
        foreach ($listTables as $table) {

            //В строке $table может содержаться имя таблицы и указание какой тип LIKE использовать через |
            $table = explode('|', $table);
            if (isset($table[1])) {
                $likeType = $table[1];
            } else {
                $likeType = 'startwith';
            }

            $table = $table[0];

            //В качестве параметра могла быть передана таблица и поле, разделённые через чточку
            $tableParams = explode('.', $table);
            $dbTable = $tableParams[0];
            if (!empty($tableParams[1])) {
                $dbField = $tableParams[1];
            } else {
                $dbField = 'Name';
            }

            //Проверим таблицу на допустимые значения для таблиц (иначе можно сформировать запрос на выборку `user`.Password)
            if (strpos($dbTable, 'list_') === 0 || User::hasReadAccess(ucfirst($dbTable))) {

                $likeValue = Db::esc($value);
                if ($likeType === 'startwith') {
                    $likeValue .= '%';
                } elseif ($likeType === 'full') {
                    $likeValue = '%' . $likeValue . '%';
                }

                //Поиск значений в режиме редактирования
                $query = "SELECT " . Db::quote($dbField) . " as value
                     FROM " . Db::quote($dbTable) . "
                     WHERE " . Db::quote($dbField) . " LIKE '" . $likeValue . "'";

                //Если при поиске по предыдущим таблцам были найдены какие-то значения - их нужно исключить
                //для уникальности общего результата
                if ($out) {
                    $prevValues = array_column($out, 'value');
                    $query .= Db::parse(" AND " . Db::quote($dbField) . " NOT IN (@a)", $prevValues);
                }

                $query .= " ORDER BY"
                    . " LENGTH(" . Db::quote($dbField) . "),"
                    . " " . Db::quote($dbField)
                    . " LIMIT 10";

                $data = Db::getAll($query);
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $out = array_merge($out, $data);
            }
        }

        Response::json($out);
    }

    public static function proceedJslog()
    {
        $head = [
            'DateTime',
            'UserID',
            'User Name',
            'Module',
            'Href',
            'User Agent',
            'Width',
            'Height',
            'Server Working Time',
            'domContentLoadedEventStart',
            'domContentLoadedEventDuration'
        ];
        $data = [
            date('Y-m-d H:i:s'),
            User::getId(),
            User::getName(),
            $_POST['module'],
            $_POST['href'],
            $_POST['useragent'],
            $_POST['width'],
            $_POST['height'],
            $_POST['serverWorkingTime'],
            $_POST['domContentLoadedEventStart'],
            $_POST['domContentLoadedEventDuration']
        ];

        CsvLog::write('jslog', $head, $data);

        ApplicationAbstract::terminate();
    }

    public static function proceedMetric()
    {
        $payload = $_POST['Payload'] ?? '';
        Metric::record($_POST['ActionType'], $_POST['TimeMs'], $payload);
    }

    /**
     * Вспомогательная функция для сохранения сортировки в потдаблице в режиме Edit/AddNew
     */
    public static function proceedSetTableUserParams()
    {
        if (isset($_POST['table'], $_POST['sortFieldKey'], $_POST['sortOrder'])) {
            Table::writeDbUserTableParam($_POST['table'], 'SortFieldKey', $_POST['sortFieldKey']);
            Table::writeDbUserTableParam($_POST['table'], 'SortOrder', $_POST['sortOrder']);
        }
    }

    public static function proceedGetHistoryFilters()
    {
        if (!isset($_POST['TableName'])) {
            return;
        }

        $response = [];

        $tableId = DbLog::getTableId($_POST['TableName']);
        $data = Db::getAssoc("
            SELECT CreatedDateTime, FilterValues
            FROM user_filter_history
            WHERE TableID = @i AND CreatedBy = @i
            ORDER BY ID DESC
            LIMIT 10
        ", $tableId, User::getId());

        if ($data) {
            foreach ($data as $date => $values) {
                $values = json_decode($values, true);
                foreach ($values as &$valueData) {
                    if (strlen($valueData['Text']) > 50) {
                        $valueData['Text'] = mb_substr($valueData['Text'], 0, 50) . '...';
                    }
                    $valueData['Text'] = htmlspecialchars($valueData['Text']);
                }
                unset($valueData);
                $response[] = [
                    'DateText' => (new Carbon($date))->diffForHumans(),
                    'Values' => $values
                ];
            }
        }

        Response::json($response);
    }

    public static function proceedSaveFiltersLikeReset()
    {
        if (!isset($_POST['TableName'], $_POST['FilterValues'])) {
            return;
        }

        $tableId = DbLog::getTableId($_POST['TableName']);

        $filterValues = [];
        foreach ($_POST['FilterValues'] as $value) {
            $filterValues[$value['FieldKey']] = $value['Value'];
        }

        if ($id = Db::getOne("SELECT ID FROM user_filter_settings WHERE CreatedBy = @i AND TableID = @i", User::getId(), $tableId)) {
            Db::updateRow(['Defaults' => json_encode($filterValues)], 'user_filter_settings', 'ID', $id);
        } else {
            Db::insertRow([
                'CreatedBy' => User::getId(),
                'TableID' => $tableId,
                'Defaults' => json_encode($filterValues)
            ], 'user_filter_settings');
        }

        ApplicationAbstract::terminate();
    }

    public static function proceedSaveFilters()
    {
        if (!isset($_POST['TableName'], $_POST['Name'], $_POST['FilterValues'])) {
            return;
        }

        $tableId = DbLog::getTableId($_POST['TableName']);
        Db::replaceRow([
            'CreatedBy' => User::getId(),
            'Name' => $_POST['Name'],
            'TableID' => $tableId,
            'FilterValues' => json_encode($_POST['FilterValues'])
        ], 'user_filter_saved');

        ApplicationAbstract::terminate();
    }

    public static function proceedDeleteFilters()
    {
        if (!isset($_POST['TableName'], $_POST['Name'])) {
            return;
        }

        $tableId = DbLog::getTableId($_POST['TableName']);
        Db::query("DELETE FROM user_filter_saved WHERE CreatedBy = @i AND TableID = @i AND Name = @s", User::getId(),
            $tableId, $_POST['Name']);

        ApplicationAbstract::terminate();
    }

    public static function proceedSaveSettingsFilters()
    {
        if (!isset($_POST['TableName'], $_POST['Sort'], $_POST['Invisible'], $_POST['NewSystemFlag'])) {
            return;
        }

        $tableId = DbLog::getTableId($_POST['TableName']);

        if (filter_var($_POST['NewSystemFlag'], FILTER_VALIDATE_BOOLEAN)) {
            $data = [
                'Sort' => json_encode($_POST['Sort']),
                'Invisible' => json_encode($_POST['Invisible'])
            ];
        } else {
            $data = [
                'Sort_deprecated' => json_encode($_POST['Sort']),
                'Invisible_deprecated' => json_encode($_POST['Invisible'])
            ];
        }

        if ($id = Db::getOne("SELECT ID FROM user_filter_settings WHERE CreatedBy = @i AND TableID = @i", User::getId(), $tableId)) {
            Db::updateRow($data, 'user_filter_settings', 'ID', $id);
        } else {
            Db::insertRow(array_merge($data, [
                'CreatedBy' => User::getId(),
                'TableID' => $tableId
            ]), 'user_filter_settings');
        }

        ApplicationAbstract::terminate();
    }

    public static function proceedUploadUserAvatar()
    {
        if (!isset($_FILES['avatar']['tmp_name'])) {
            print json_encode(['error' => true, 'text' => 'Произошла внутренняя ошибка']);
            ApplicationAbstract::terminate();
        }

        $tmpFileName = $_FILES['avatar']['tmp_name'];

        $imageSize = getimagesize($tmpFileName);

        switch ($imageSize[2]) {
            case 1:
                $image = \imagecreatefromgif($tmpFileName);
                break;
            case 2:
                $image = \imagecreatefromjpeg($tmpFileName);
                break;
            case 3:
                $image = \imagecreatefrompng($tmpFileName);
                break;
            default:
                $image = false;
        }

        if (!$image) {
            print json_encode(['error' => true, 'text' => 'Не опознано расширение (jpg)']);
            ApplicationAbstract::terminate();
        }

        File::mkdir(LIGHT_DIR_PUBLIC . "/i/employees/users/avatar");

        $userId = User::getId();
        $destImg = LIGHT_DIR_PUBLIC . "/i/employees/users/$userId.jpg";
        $destAvatar = LIGHT_DIR_PUBLIC . "/i/employees/users/avatar/$userId.jpg";

        move_uploaded_file($tmpFileName, $destImg);

        // обрежем фотографию до размера 500x500 px
        $img = Misc::resizeImage($destImg, $image, 500, 500, true);

        // сделаем также уменьшенную фотографию для аватарки
        $avatar = Misc::resizeImage($destImg, $image, 100, 100, true);

        // сохраним фотографии
        imagejpeg($img, $destImg, 90);
        imagejpeg($avatar, $destAvatar, 90);

        echo json_encode(['imgUrl' => "i/employees/users/$userId.jpg" . "?" . filemtime($destImg)]);
    }
}