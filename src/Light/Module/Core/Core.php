<?php

namespace Light\Module\Core;

use Carbon\Carbon;
use Light\ApplicationAbstract;
use Light\Config;
use Light\CsvLog;
use Light\Currency;
use Light\Db;
use Light\DbLog;
use Light\Field\FieldDate;
use Light\File;
use Light\Metric;
use Light\Misc;
use Light\ModuleRegister;
use Light\Request;
use Light\Response;
use Light\Table;
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

    public static function proceedFiltersGetHistoryHtml()
    {
        if (!isset($_GET['TableName'])) {
            Response::abort(400, 'No GET TableName');
            return;
        }

        $tableId = (int)DbLog::getTableId($_GET['TableName']);
        $history = Db::getAssoc("
            SELECT CreatedDateTime, Description, FiltersJSON
            FROM user_filter_history
            WHERE TableID = $tableId AND CreatedBy = " . User::getId() . "
            ORDER BY ID DESC
            LIMIT 10"
        );

        if (!$history) {
            $msg = Config::$lang === 'ru' ? 'История фильтров пуста' : 'History is empty';
            print $msg;
            ApplicationAbstract::terminate();
            return;
        }

        $html = '<ul>';
        foreach ($history as $createdDateTime => ['Description' => $description, 'FiltersJSON' => $filtersJson]) {

            $createdDateTimeText = (new Carbon($createdDateTime))->diffForHumans();

            $html .= '<li>'
                . '<a href="#" data-filtersjson="' . htmlspecialchars($filtersJson) . '">'
                . '<div class="note nobr">' . $createdDateTimeText . ':</div>'
                . '<div>' . $description . '</div>'
                . '</a>'
                . '</li>';

        }
        $html .= '</ul>';

        print $html;
        ApplicationAbstract::terminate();
    }

    public static function proceedFiltersSaveLikeReset()
    {
        if (!isset($_GET['TableName'])) {
            Response::abort(400, 'No GET TableName');
            return;
        }

        if (!isset($_POST['Defaults'])) {
            Response::abort(400, 'No POST Defaults');
            return;
        }

        $defaultsJson = json_encode($_POST['Defaults']);

        $tableId = (int)DbLog::getTableId($_GET['TableName']);
        $existSettingsId = $id = Db::getOne(
            "SELECT ID 
             FROM user_filter_settings 
             WHERE CreatedBy = " . User::getId() . " 
                AND TableID = $tableId"
        );
        if ($existSettingsId) {
            Db::updateRow(['Defaults' => $defaultsJson], 'user_filter_settings', $existSettingsId);
        } else {
            Db::insertRow([
                'CreatedBy' => User::getId(),
                'TableID' => $tableId,
                'Defaults' => $defaultsJson
            ], 'user_filter_settings');
        }

        $msg = Config::$lang === 'ru'
            ? 'Значения фильтров сохранены как REST'
            : 'Saved as RESET';

        Response::sendToast('success', $msg);

        ApplicationAbstract::terminate();
    }

    public static function proceedFiltersGetSavedHtml()
    {
        if (!isset($_GET['TableName'])) {
            Response::abort(400, 'No GET TableName');
            return;
        }

        $textSetReset = Config::$lang === 'ru' ? "Установить как RESET" : "Set current filter values as default";
        $textSave = Config::$lang === 'ru' ? "Сохранить фильтры" : "Save filter values as";
        $textSavePlaceholder = Config::$lang === 'ru' ? "Название" : "...give it a name";
        $textSaveBtn = Config::$lang === 'ru' ? "Сохранить" : "Save";

        $html = '<div class="like-reset">
                    <span>' . $textSetReset . '</span>
                </div>
                <div class="save-filter">
                    <div class="save-filter-text">' . $textSave . ': </div>
                    <div class="save-filter-form">
                        <input type="text" class="input-default" placeholder="' . $textSavePlaceholder . '">
                        <button>' . $textSaveBtn . '</button>
                    </div>
                 </div>';

        $tableId = (int)DbLog::getTableId($_GET['TableName']);

        $saved = Db::getAssoc("
                SELECT Name, FiltersJSON
                FROM user_filter_saved
                WHERE TableID = $tableId
                   AND CreatedBy = " . User::getId() . "
                ORDER BY ID DESC"
        );

        if ($saved) {
            $html .= '<ul>';
            foreach ($saved as $name => $filtersJson) {

                $html .= '<li>'
                    . '<a href="#" data-filtersjson="' . htmlspecialchars($filtersJson) . '">'
                    . htmlspecialchars($name)
                    . '</a>'
                    . '<a href="#" class="del-link"><i class="fa fa-trash-o"></i></a>'
                    . '</li>';
            }
            $html .= '</ul>';
        }

        print $html;
        ApplicationAbstract::terminate();
    }

    public static function proceedFiltersSave()
    {
        if (!isset($_GET['TableName'])) {
            Response::abort(400, 'No GET TableName');
            return;
        }

        if (!isset($_POST['Name'], $_POST['Filters'])) {
            Response::abort(400, 'No POST Name or Filters');
            return;
        }

        $tableId = (int)DbLog::getTableId($_GET['TableName']);
        Db::replaceRow([
            'CreatedBy' => User::getId(),
            'Name' => $_POST['Name'],
            'TableID' => $tableId,
            'FiltersJSON' => json_encode($_POST['Filters'])
        ], 'user_filter_saved');

        $msg = Config::$lang === 'ru'
            ? 'Фильтр <strong>' . htmlspecialchars($_POST['Name']) . '</strong> успешно сохранён'
            : 'Filter values <strong>' . htmlspecialchars($_POST['Name']) . '</strong> saved';
        Response::sendToast('success', $msg);

        // В ответ отдадим свежий список сохранённых фильтров
        self::proceedFiltersGetSavedHtml();
    }

    public static function proceedFiltersDelete()
    {
        if (!isset($_GET['TableName'])) {
            Response::abort(400, 'No GET TableName');
            return;
        }

        if (!isset($_POST['Name'])) {
            Response::abort(400, 'No POST Name or FilterValues');
            return;
        }

        $tableId = (int)DbLog::getTableId($_GET['TableName']);
        Db::query(
            "DELETE FROM user_filter_saved 
          WHERE CreatedBy = " . User::getId() . " 
              AND TableID = $tableId 
              AND Name = @s"
            , $_POST['Name']
        );

        $msg = Config::$lang === 'ru'
            ? 'Фильтр ' . htmlspecialchars($_POST['Name']) . ' удалён'
            : 'Filter values ' . htmlspecialchars($_POST['Name']) . ' deleted';
        Response::sendToast('success', $msg);

        // В ответ отдадим свежий список сохранённых фильтров
        self::proceedFiltersGetSavedHtml();
    }

    public static function proceedFiltersSaveLayout()
    {
        if (!isset($_GET['TableName'])) {
            Response::abort(400, 'No GET TableName');
            return;
        }

        if (!isset($_POST['Layout'], $_POST['Invisible'])) {
            Response::abort(400, 'No POST Layout or Invisible');
            return;
        }

        $tableId = (int)DbLog::getTableId($_GET['TableName']);
        $data = [
            'Sort' => json_encode($_POST['Layout']),
            'Invisible' => json_encode($_POST['Invisible'])
        ];

        $existSettingsId = $id = Db::getOne(
            "SELECT ID 
             FROM user_filter_settings 
             WHERE CreatedBy = " . User::getId() . " 
                AND TableID = $tableId"
        );
        if ($existSettingsId) {
            Db::updateRow($data, 'user_filter_settings', $existSettingsId);
        } else {
            Db::insertRow($data + [
                    'CreatedBy' => User::getId(),
                    'TableID' => $tableId
                ], 'user_filter_settings');
        }

        $msg = Config::$lang === 'ru'
            ? "Настройки фильтров успешно сохранены"
            : "Filters layout saved";
        Response::sendToast('success', $msg);

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

    public static function proceedGetCurrRate()
    {
        if (!isset($_GET['from']) || !isset($_GET['to']) || !isset($_GET['date'])) {
            Response::abort(400, 'Not enought GET parameters');
            ApplicationAbstract::terminate();
        }

        print Currency::getRate($_GET['from'], $_GET['to'], FieldDate::parse($_GET['date']));
        ApplicationAbstract::terminate();
    }
}
