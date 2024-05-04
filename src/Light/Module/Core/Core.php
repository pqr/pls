<?php

namespace Light\Module\Core;

use Carbon\Carbon;
use League\Csv\Reader;
use Light\ApplicationAbstract;
use Light\Config;
use Light\Currency;
use Light\Db;
use Light\DbLog;
use Light\Field\FieldAbstract;
use Light\Field\FieldDate;
use Light\Fieldset;
use Light\File;
use Light\FilesFolder;
use Light\Ico;
use Light\Link;
use Light\LoggerCsv;
use Light\Mainmenu;
use Light\Metric;
use Light\Misc;
use Light\Module\UserSettings\UserSettings;
use Light\Module\UserVacation\UserVacation;
use Light\Module\UserVacation\UserVacationFields;
use Light\ModuleRegister;
use Light\Request;
use Light\Response;
use Light\Table;
use Light\Table\TableUserParamsRepository;
use Light\Template;
use Light\Traits\TableLayoutable;
use Light\User;

class Core
{
    public function __invoke(): void
    {
        //Если по какой-то причине был запрос на ?a=Core без параметров (и это не ajax), просто перенправляем пользователя
        //на default модуль
        if (!Request::isAjax()) {
            Response::redirect(ModuleRegister::getDefaultModule());
        }
    }

    public static function proceedUserparam(): void
    {
        if (!isset($_REQUEST['param'])) {
            Response::abort(400);
            return;
        }

        $param = (string)$_REQUEST['param'];

        if (Request::getMehod() === 'POST') {

            if (!isset($_POST['value'])) {
                Response::abort(400);
                return;
            }

            User::setParam($param, $_POST['value']);
        }

        // Распечатаем сохранённое в базе значение - возможно, оно ещё понадобится для js клиента
        print User::getParam($param);

        ApplicationAbstract::terminate();
    }

    public static function proceedTable(): void
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

    public static function acCore($value): void
    {
        $out = [];

        //Запрос к базе данных на поиск значения для autocomplete
        $listTables = explode(',', (string) $_GET['source']);
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
            if (str_starts_with($dbTable, 'list_') || User::hasReadAccess(ucfirst($dbTable))) {

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

    public static function proceedJslog(): void
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
            'domContentLoadedEventDuration',
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
            $_POST['domContentLoadedEventDuration'],
        ];

        LoggerCsv::write('jslog', $head, $data);

        ApplicationAbstract::terminate();
    }

    public static function proceedMetric(): void
    {
        if (isset($_POST['ActionType'])) {
            $payload = $_POST['Payload'] ?? '';
            Metric::record($_POST['ActionType'], $_POST['TimeMs'], $payload);
        }
    }

    /**
     * Вспомогательная функция для сохранения сортировки в потдаблице в режиме Edit/AddNew
     */
    public static function proceedSetTableUserParams(): void
    {
        if (isset($_POST['table'], $_POST['sortFieldKey'], $_POST['sortOrder'])) {
            $tableUserParams = new TableUserParamsRepository($_POST['table'], User::getId());
            $tableUserParams->saveSortFieldKey($_POST['sortFieldKey']);
            $tableUserParams->saveSortOrder($_POST['sortOrder']);
        }
    }

    public static function proceedFiltersGetHistoryHtml(): void
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
            return;
        }

        $html = '<ul>';
        foreach ($history as $createdDateTime => ['Description' => $description, 'FiltersJSON' => $filtersJson]) {

            $createdDateTimeText = (new Carbon($createdDateTime))->diffForHumans();

            $html .= '<li>'
                . '<a href="#" data-filtersjson="' . htmlspecialchars((string) $filtersJson) . '">'
                . '<div class="note nobr">' . $createdDateTimeText . ':</div>'
                . '<div>' . $description . '</div>'
                . '</a>'
                . '</li>';

        }
        $html .= '</ul>';

        print $html;
    }

    public static function proceedFiltersSaveLikeReset(): void
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
                'Defaults' => $defaultsJson,
            ], 'user_filter_settings');
        }

        $msg = Config::$lang === 'ru'
            ? 'Значения фильтров сохранены как СБРОС'
            : 'Saved as default';

        Response::sendToast('success', $msg);
    }

    public static function proceedFiltersGetSavedHtml(): void
    {
        if (!isset($_GET['TableName'])) {
            Response::abort(400, 'No GET TableName');
            return;
        }

        $textSetReset = Config::$lang === 'ru' ? "Установить как СБРОС" : "Set current filter values as default";
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
                    . '<a href="#" data-filtersjson="' . htmlspecialchars((string) $filtersJson) . '">'
                    . htmlspecialchars($name)
                    . '</a>'
                    . '<a href="#" class="del-link">' . Ico::delete() . '</a>'
                    . '</li>';
            }
            $html .= '</ul>';
        }

        print $html;
    }

    public static function proceedFiltersSave(): void
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
            'FiltersJSON' => json_encode($_POST['Filters']),
        ], 'user_filter_saved');

        $msg = Config::$lang === 'ru'
            ? 'Фильтр <strong>' . htmlspecialchars((string) $_POST['Name']) . '</strong> успешно сохранён'
            : 'Filter values <strong>' . htmlspecialchars((string) $_POST['Name']) . '</strong> saved';
        Response::sendToast('success', $msg);

        // В ответ отдадим свежий список сохранённых фильтров
        self::proceedFiltersGetSavedHtml();
    }

    public static function proceedFiltersDelete(): void
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
            ? 'Фильтр ' . htmlspecialchars((string) $_POST['Name']) . ' удалён'
            : 'Filter values ' . htmlspecialchars((string) $_POST['Name']) . ' deleted';
        Response::sendToast('success', $msg);

        // В ответ отдадим свежий список сохранённых фильтров
        self::proceedFiltersGetSavedHtml();
    }

    public static function proceedFiltersSaveLayout(): void
    {
        if (!isset($_GET['TableName'])) {
            Response::abort(400, 'No GET TableName');
            return;
        }

        if (!isset($_POST['Layout'])) {
            Response::abort(400, 'No POST Layout');
            return;
        }

        $tableId = (int)DbLog::getTableId($_GET['TableName']);
        $data = [
            'Sort' => json_encode($_POST['Layout']),
            'Invisible' => json_encode($_POST['Invisible'] ?? []),
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
                    'TableID' => $tableId,
                ], 'user_filter_settings');
        }

        $msg = Config::$lang === 'ru'
            ? "Настройки фильтров сохранены"
            : "Filters layout saved";
        Response::sendToast('success', $msg);
    }

    public static function proceedUploadUserAvatar(): void
    {
        if (!isset($_FILES['avatar']['tmp_name'])) {
            Response::json(['error' => true, 'text' => 'Произошла внутренняя ошибка'], 500);
            return;
        }

        $tmpFileName = $_FILES['avatar']['tmp_name'];

        $imageSize = getimagesize($tmpFileName);

        $image = match ($imageSize[2]) {
            1 => \imagecreatefromgif($tmpFileName),
            2 => \imagecreatefromjpeg($tmpFileName),
            3 => \imagecreatefrompng($tmpFileName),
            default => false,
        };

        if (!$image) {
            Response::json(['error' => true, 'text' => 'Не опознано расширение'], 500);
            return;
        }

        $dir = FilesFolder::getAbsoluteModule('User', User::getId());
        try {
            File::fs()->mkdir($dir);
        } catch (\Exception $e) {
            Response::json(['error' => true, 'text' => $e->getMessage()], 500);
            \Light\ErrorHandler::reportException($e);
            return;
        }

        $photoFileAbs = "$dir/Photo.jpg";
        $avatarFileAbs = "$dir/Avatar.jpg";

        move_uploaded_file($tmpFileName, $photoFileAbs);

        // обрежем фотографию до размера 500x500 px
        $img = Misc::resizeImage($photoFileAbs, $image, 500, 500, true);

        // сделаем также уменьшенную фотографию для аватарки
        $avatar = Misc::resizeImage($photoFileAbs, $image, 100, 100, true);

        // сохраним фотографии
        imagejpeg($img, $photoFileAbs, 90);
        imagejpeg($avatar, $avatarFileAbs, 90);

        Response::json(['success' => true]);
    }

    public static function proceedGetUserAvatar(): void
    {
        $userId = $_GET['UserID'] ?? User::getId();

        if (User::hasAvatar($userId)) {
            $avatarFileAbs = User::avatarFilepath($userId);
            // Кэшируем изображение навечно, т.к. в url есть признак времени изменения файла (см getUserAvatarImgTag)
            // https://www.mnot.net/cache_docs/
            Response::header('Content-Type: image/jpg');
            Response::header('Cache-Control: public, max-age=31556926');
            Response::header('Expires: Sun, 17-Jan-2038 19:14:07 GMT');
            Response::header('Pragma: ');
            readfile($avatarFileAbs);
            ApplicationAbstract::terminate();
            return;
        }

        Response::redirectUrl('lightpublic/i/user.png');
    }

    public static function proceedGetUserPhoto(): void
    {
        $userId = $_GET['UserID'] ?? User::getId();
        $relative = FilesFolder::getRelativeModule('User', $userId);
        $avatarFileAbs = FilesFolder::getAbsolute("$relative/Photo.jpg");
        if (file_exists($avatarFileAbs)) {

            Response::header('Content-Type: image/jpg');
            print file_get_contents($avatarFileAbs);
            ApplicationAbstract::terminate();

        }

        $defaultImages = [];
        foreach (scandir(LIGHT_DIR_LIGHTPUBLIC . "/i/user") as $file) {
            if ($file[0] !== '.') {
                $defaultImages[] = $file;
            }
        }

        $defaultCount = count($defaultImages);
        // Выбираем номер картинки как остаток от деления ID пользователя на число доступных картинок
        $randomDefaultImageIndex = $userId % $defaultCount;

        $randomFile = $defaultImages[$randomDefaultImageIndex];

        Response::header('Content-Type: image/jpg');
        print file_get_contents(LIGHT_DIR_LIGHTPUBLIC . "/i/user/$randomFile");
        ApplicationAbstract::terminate();
    }

    public static function proceedGetCurrRate(): void
    {
        if (!isset($_GET['from']) || !isset($_GET['to']) || !isset($_GET['date'])) {
            Response::abort(400, 'Not enought GET parameters');
            return;
        }

        print Currency::getRate((string)$_GET['from'], (string)$_GET['to'], FieldDate::parse($_GET['date']));
    }

    public static function acSearchCountry($value)
    {
        if (Config::$lang === 'ru') {
            $sqlSelectName = "IF(Name!='', Name, NameEn)";
        } else {
            $sqlSelectName = "IF(NameEn!='', NameEn, Name)";
        }

        $query = "SELECT `Code` AS value, $sqlSelectName AS text
                  FROM list_country
                  WHERE Code LIKE '" . Db::esc($value) . "%'
                    OR NameEn LIKE '" . Db::esc($value) . "%'
                    OR Name LIKE '" . Db::esc($value) . "%'
                  ORDER BY `Code`, $sqlSelectName
                  LIMIT 10";
        return Db::getAll($query);
    }

    public static function proceedMainmenuSettings(): void
    {
        Mainmenu::proceedMainmenuSettings();
    }

    public static function proceedParseCsv(): void
    {
        $input = file_get_contents('php://input');
        $csv = Reader::createFromString($input);
        $csv->setDelimiter("\t");
        Response::json($csv);
    }

    public static function proceedShowUserDialog(): void
    {
        if (!Request::isAjax()) {
            Response::abort(400);
            return;
        }
        $userId = (int)($_GET['UserID'] ?? 0);
        if (!$userId) {
            Response::abort(400, 'Empty GET UserID');
            return;
        }
        $fieldset = static::getUserDialogFieldset();
        $user = Db::getRow("SELECT {$fieldset->getSqlSelect()} FROM user WHERE ID = @i", $userId);
        if (!$user) {
            Response::abort(404, "User ID = $userId not found");
            return;
        }
        $tdata = $fieldset->getTplData($user, FieldAbstract::MODE_VIEW);

        $lastRefresh = Db::getOne("SELECT LastRefreshDateTime FROM user_visit WHERE UserID = @i", $userId);
        if ($lastRefresh) {
            $tdata['Idle'] = (new Carbon($lastRefresh))->locale('ru')->diffForHumans();
        }

        $tdata['UserVacationsHtml'] = static::getUserDialogVacationsHtml($userId);
        $tdata['UserDialogAdditionalHtml'] = static::getUserDialogAdditionalHtml($userId);
        if (User::getId() == $userId && User::hasWriteAccess(UserSettings::class)) {
            $tdata['UserSettingsLink'] = Link::create(UserSettings::class,
                'Редактировать информацию о себе ' . Ico::externalLink())
                ->cssClass('note')
                ->blank()
                ->getHtml();
        }

        $html = Template::parseTpl('UserDialog.tpl.php', $tdata);
        Response::showDialog($html);
    }

    protected static function getUserDialogFieldset(): Fieldset
    {
        return (new UserSettings())->getFieldset();
    }

    protected static function getUserDialogVacationsHtml(int $userId): string
    {
        $table = new Table();
        $table->tableSticky = false;
        $table->addFields(
            UserVacationFields::vacationType(),
            UserVacationFields::validFrom(),
            UserVacationFields::validUntil(),
            UserVacationFields::days(),
            UserVacationFields::availableRemote(),
            UserVacationFields::availableMobile(),
            UserVacationFields::comments()
        );
        $table->getFieldset()->setParam('ajax', false);

        $data = Db::getAll(
            "SELECT {$table->getFieldset()->getSqlSelect()}
             FROM uservacation
             WHERE ValidUntil >= CURDATE()
                AND UserID = $userId
             ORDER BY ValidFrom"
        );

        if (!$data) {
            return '';
        }

        $table->setTableData($data);

        $outHtml = '<p style="margin-top: 20px"><strong>График работы</strong>';
        if (User::hasWriteAccess(UserVacation::class)) {
            $outHtml .= ' <small style="margin-left: 10px">' . Link::create(UserVacation::class,
                    'Редактировать график работы ' . Ico::externalLink())
                    ->cssClass('note')
                    ->blank()
                . '</small>';
        }
        $outHtml .= '</p>'
            . $table->getHtmlTable();

        return $outHtml;
    }

    /**
     * Для переопределения в классах потомках, если нужно добавить ещё какой-то html код в карточку пользователя
     * @return string
     */
    protected static function getUserDialogAdditionalHtml(int $userId): string
    {
        return '';
    }
}
