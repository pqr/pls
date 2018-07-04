<?php

namespace Light\Module\Reportmenu;

use Light\Config;
use Light\Css;
use Light\Db;
use Light\DbLog;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Img;
use Light\Js;
use Light\Link;
use Light\ModuleRegister;
use Light\Response;
use Light\Session;
use Light\Table;
use Light\Template;
use Light\User;

class Reportmenu
{
    /**
     * Возвращает список всех доступных отчётов, сканируя папку src/<PROJECT_NAMESPACE>/Reportmenu
     *
     * Делаем метод публичным, чтобы его можно было вызвать из тестов
     *
     * @return array
     */
    public function getAllReports(): array
    {
        $out = [];
        foreach (glob(LIGHT_DIR_PROJECT . '/Reportmenu/*.php') as $file) {

            if (substr($file, -8) == '.tpl.php') {
                continue;
            }

            $reportClass = pathinfo($file, PATHINFO_FILENAME);
            $out[] = $reportClass;
        }

        return $out;
    }

    /**
     * Возвращает массив из имён классов отчётов (без неймспейсов) к которым у указанного пользователя есть доступ
     * @param int $userId
     * @param int $groupId
     * @param array $allReports
     * @return array
     */
    private function getReportsForUser(int $userId, int $groupId, array $allReports): array
    {
        return array_filter($allReports, function ($reportClass) use ($userId, $groupId) {
            return $this->hasUserAccessToReport($userId, $groupId, $reportClass);
        });
    }

    private static function getReportFullClass(string $reportClass): string
    {
        return PROJECT_NAMESPACE . "\\Reportmenu\\$reportClass";
    }

    private function getReportLink(string $innerHtml, string $reportClass): Link
    {
        $module = 'Reportmenu';
        $params = ['class' => $reportClass];
        return Link::create($module, $innerHtml)->params($params);
    }

    /**
     * Делаем метод публичным, чтобы его можно было вызвать из тестов
     * @param string $reportClass
     * @return string
     */
    public function getReportTitle(string $reportClass): string
    {
        /** @var ReportAbstract $reportFullClass */
        $reportFullClass = self::getReportFullClass($reportClass);
        return (string)$reportFullClass::getTitle();
    }

    /**
     * Делаем метод публичным, чтобы его можно было вызвать из тестов
     * @param string $reportClass
     * @return string
     */
    public function getReportDescription(string $reportClass): string
    {
        /** @var ReportAbstract $reportFullClass */
        $reportFullClass = self::getReportFullClass($reportClass);
        return (string)$reportFullClass::getDescription();
    }

    private function getReportImageHtml(string $reportClass): string
    {
        return Img::png('Reportmenu/' . $reportClass);
    }

    /**
     * Возвращает ассоциативный массив: ключи массви - это имена классов отчётов (без неймспейсов)
     * Значения массивов - HTML код ссылок для вывода в шаблоке Reprotmenu.tpl.php
     * @return array
     */
    private function getReportmenuTplData()
    {
        $out = [];
        $hasWriteAcess = User::hasWriteAccess();
        $allReports = $this->getAllReports();
        $reportsForUser = $this->getReportsForUser(User::getId(), User::getGroupId(), $allReports);

        foreach ($reportsForUser as $reportClass) {

            $params = ['class' => $reportClass];
            $manageAccessLink = '';
            if ($hasWriteAcess) {
                $manageAccessText = Config::$lang === 'ru' ? 'Доступ' : 'Access';
                $innerHtml = Img::icon('fa-user-plus') . " $manageAccessText";
                $manageAccessLink = Link::create('Reportmenu', $innerHtml)
                    ->params($params)
                    ->ajax()
                    ->attrs(['class' => 'reportblock__accesslink'])
                    ->getHtml();
            }

            $title = $this->getReportTitle($reportClass);
            $descriptioon = $this->getReportDescription($reportClass);
            $imageHtml = $this->getReportImageHtml($reportClass);

            $out[$reportClass] = '<div class="reportblock">'
                . '<div>'
                . Link::create('Reportmenu', $imageHtml)->params($params)->getHtml()
                . '</div>'
                . '<div>'
                . Link::create('Reportmenu', $title)->params($params)->getHtml()
                . '<br>' . $descriptioon
                . '</div>'
                . $manageAccessLink
                . '</div>';

        }

        // Ещё раз пройдёмся по всем отчётам и те, которые не попали в список доступных пользовтелю, заполним пустыми строками
        foreach ($allReports as $reportClass) {
            if (!isset($out[$reportClass])) {
                $out[$reportClass] = '';
            }
        }

        return $out;

    }

    /**
     * Проверяет, есть ли у указанного пользователя доступ к указанному отчёту?
     * @param int $userId
     * @param int $groupId
     * @param string $reportClass
     * @return bool
     */
    private function hasUserAccessToReport(int $userId, int $groupId, string $reportClass)
    {
        if (User::hasWriteAccess('Reportmenu', $userId)) {
            // Если у пользователя есть доступ на запись в модуль отчётов, значит у него есть доступ ко всем отчётам
            return true;
        }

        // Загрузим из базы настройки доступа по всем отсчётам сразу (может пригодиться потом)
        static $allReportsAccess;
        if ($allReportsAccess === null) {
            $allReportsAccess = Db::getAssoc("SELECT ReportClass, UserIDs, GroupIDs FROM reportaccess");
        }

        if (!isset($allReportsAccess[$reportClass])) {
            // Для данного запрашиваемого отчёта настроек вообще не найдено, значит и доступа нет
            return false;
        }

        $thisReportUsers = $allReportsAccess[$reportClass]['UserIDs'];
        if (strpos($thisReportUsers, '|%|') !== false) {
            // У данного отчёта есть настройка "Доступ всем!"
            return true;
        }

        if (strpos($thisReportUsers, "|$userId|") !== false) {
            // У данного отчёта есть есть привязка к запрашиваемому пользователю
            return true;
        }

        $thisReportGroups = $allReportsAccess[$reportClass]['GroupIDs'];
        if (strpos($thisReportGroups, "|$groupId|") !== false) {
            // У данного отчёта есть есть привязка к группе пользователя
            return true;
        }

        // Никаких признаков доступа не нашлось
        return false;
    }

    public function __invoke()
    {
        if (isset($_GET['class'])) {

            // Нам нужно определить, правильное ли имя класса было передано в ссылке?
            $reportFullClass = PROJECT_NAMESPACE . "\\Reportmenu\\" . $_GET["class"];

            //Создадим объект отчёта и запустим его!
            /** @var $reportObj ReportAbstract */
            $reportObj = new $reportFullClass;
            $reportObj();

        } else {

            if (User::hasWriteAccess()) {
                // Если у пользователя есть Write права на модуль Reportmenu, значит
                // он может вызывать диалог с настройками прав доступа к отчётам, а в этом
                // диалоге исользуется select2
                Js::$select2 = true;
                Css::$select2 = true;
            }

            // Если в GET запросе не передан конкретный класс подмодуля, выведем список всех
            $module = 'Reportmenu';
            $moduleName = ModuleRegister::getModuleName($module);
            Response::setPageTitle($moduleName);
            Response::setPageHeadHtml($moduleName);

            $filterPlaceholder = Config::$lang === 'ru'
                ? 'Фильтр по названию или описанию'
                : 'Search report by name or description';

            print '<h3>'
                . '<input type="text" placeholder="' . $filterPlaceholder . '" id="reportmenu-filter-input" style="padding: 5px">';
            if (User::hasWriteAccess()) {
                $auditText = $this->getAuditText();
                $innerHtml = Img::icon('fa-user-circle') . " $auditText";
                print Link::create($module, $innerHtml)
                    ->params(['p' => 'AccessAudit'])
                    ->btnFlat()
                    ->attrs(['style' => "margin-left: 50px;"])
                    ->getHtml();
            }
            print '</h3>';

            //Шаблон Reportmenu.tpl.php должен быть отпределён в папке src/<PROJECT_NAMESPACE>/templates
            Template::includeTpl('Reportmenu.tpl.php', $this->getReportmenuTplData());

            Js::addLight('Reportmenu.js');

        }
    }

    private function getUsersWithFullAccessHtml(array $allUsers)
    {
        $text = Config::$lang === 'ru' ? 'Пользователи с полным доступом ко всем отчётам' : 'Users with full access to all reports';
        $outHtml = "<div>$text:</div>";
        $outHtml .= '<ul>';
        foreach ($allUsers as $user) {
            if (User::hasWriteAccess('Reportmenu', $user['ID'])) {
                $outHtml .= '<li>' . htmlspecialchars($user['Name']) . '</li>';
            }
        }
        $outHtml .= '</ul>';

        return $outHtml;
    }

    private function getAllActiveUsers()
    {
        return Db::getAll("SELECT ID, Name, GroupID FROM user WHERE Active = 1 ORDER BY Name");
    }

    public function proceedAccessAudit()
    {
        $module = 'Reportmenu';

        if (!User::hasWriteAccess()) {
            Session::flashError('No access');
            Response::redirect('Reportmenu');
            return;
        }
        // Если в GET запросе не передан конкретный класс подмодуля, выведем список всех
        $moduleName = ModuleRegister::getModuleName($module);
        $auditText = $this->getAuditText();
        $link = Link::create($module, $moduleName);
        Response::setPageTitle("$moduleName - $auditText");
        Response::setPageHeadHtml("$link - <strong>$auditText</strong>");

        $backText = Config::$lang === 'ru' ? 'Вернуться к отчётам' : 'Back to reports';
        print Link::create($module, "◀ $backText")->btnFlat();

        $allUsers = $this->getAllActiveUsers();
        print $this->getUsersWithFullAccessHtml($allUsers);


        $text = Config::$lang === 'ru' ? 'Пользователи с доступом к определённым отчётам' : 'Users with limited access';
        print "<div style='margin-top: 20px'>$text:</div>";
        $table = new Table('ReporotmenuAccessAudit');
        $table->addFields(
            F::createString('UserName', [
                'name' => Config::$lang === 'ru' ? 'Пользовтатель' : 'User',
            ]),
            F::createHtml('Reports', [
                'name' => Config::$lang === 'ru' ? 'Отчёты' : 'Reports',
            ])
        );

        $tableData = [];

        $allReports = $this->getAllReports();
        foreach ($allUsers as $user) {
            $reportsLinksForThisUser = [];
            if (User::hasWriteAccess('Reportmenu', $user['ID'])) {
                // Если у пользователя есть доступ на запись в модуль Reportmenu, значит у него есть доступ ко всем отчётам
                $text = Config::$lang === 'ru' ? 'Полный доступ ко всем отчётам' : 'Full access to all reports';
                $reportsLinksForThisUser[] = "<span class=good>$text</span>";
            } elseif (!User::hasReadAccess('Reportmenu', $user['ID'])) {
                $text = Config::$lang === 'ru' ? 'Доступ к модулю отчётов закрыт' : 'Access denied';
                $reportsLinksForThisUser[] = "<span class=bad>$text</span>";
            } else {
                // Получим список отчётов к которым у текущего пользователя есть доступ
                $reportsForThisUser = $this->getReportsForUser($user['ID'], $user['GroupID'], $allReports);
                if (!$reportsForThisUser) {
                    $text = Config::$lang === 'ru' ? 'Пользователю не подключён ни один отчёт' : 'No reports available';
                    $reportsLinksForThisUser[] = "<span class=bad>$text</span>";
                } else {
                    foreach ($reportsForThisUser as $reportClass) {
                        $title = $this->getReportTitle($reportClass);
                        $reportsLinksForThisUser[] = $this->getReportLink($title, $reportClass)->blank();
                    }
                }
            }

            $tableData[] = [
                'UserName' => $user['Name'],
                'Reports' => implode('<br>', $reportsLinksForThisUser)
            ];
        }

        $table->setTableData($tableData);
        print $table->getHtmlTable(false);

    }

    private function getAuditText()
    {
        return Config::$lang === 'ru' ? 'Аудит доступа' : 'Access Audit';
    }

    private function getAccessDialogFieldset()
    {
        $usersOptions = ['%' => Config::$lang === 'ru' ? 'Все' : 'All'];
        $usersOptions += Db::getAssoc("SELECT ID, Name FROM user WHERE Active = 1 ORDER BY Name");

        return new Fieldset(
            F::createSelect2Multiple('UserIDs', [
                'name' => Config::$lang === 'ru' ? 'Пользователи' : 'Users',
                'editWidth' => 'default',
                'empty' => false,
                'options' => $usersOptions
            ]),

            F::createSelect2Multiple('GroupIDs', [
                'name' => Config::$lang === 'ru' ? 'Группы' : 'Groups',
                'editWidth' => 'default',
                'empty' => false,
                'options' => "usergroup|OrderID"
            ])
        );
    }

    private function getReportAccess(string $class): array
    {
        return Db::getRow("SELECT UserIDs, GroupIDs FROM reportaccess WHERE ReportClass = @s", $class);
    }

    private function writeDbLogText($oldValue, $newValue, $oldValueFormated, $newValueFormated, $text, $class)
    {
        $textData = [
            'Text' => $text,
            'OldValue' => $oldValue,
            'NewValue' => $newValue,
            'OldValueFormated' => $oldValueFormated,
            'NewValueFormated' => $newValueFormated,
            'ObjectDescription' => $class
        ];
        DbLog::write('Rerportmenu', 0, 'reportaccess', 0, 'UserIDs', 'Пользователи', $textData);
    }

    public function proceedAccessDialog()
    {
        if (!User::hasWriteAccess()) {
            Response::abort(403);
        }

        if (!isset($_GET['class'])) {
            Response::abort(400, 'No class provided');
        }

        $class = $_GET['class'];
        $currentAccess = $this->getReportAccess($class);

        $fieldset = $this->getAccessDialogFieldset();
        $userIdsField = $fieldset['UserIDs'];
        $groupIdsField = $fieldset['GroupIDs'];
        $text = Config::$lang === 'ru' ? 'Разрешить доступ к данному отчёту' : 'Allow access for this report';
        $usersText = Config::$lang === 'ru' ? 'Пользователи' : 'Users';
        $groupsText = Config::$lang === 'ru' ? 'Группы' : 'Groups';
        $bodyHtml = '<form action="?a=Reportmenu&p=AccessDialogSubmit&class=' . $class . '#ajax" method="post" style="margin-bottom: 20px">'
            . "<p>$text:</p>"
            . "<p><label>$usersText:<br>"
            . $userIdsField->getHtmlInput($currentAccess['UserIDs'] ?? '')
            . "</label></p>"
            . "<p><label>$groupsText:<br>"
            . $groupIdsField->getHtmlInput($currentAccess['GroupIDs'] ?? '')
            . "</label></p>"

            . '</form>';


        $allUsers = $this->getAllActiveUsers();
        $bodyHtml .= $this->getUsersWithFullAccessHtml($allUsers);

        $saveBtnText = Config::$lang === 'ru' ? 'Сохранить' : 'Save';
        Response::showDialog($bodyHtml, $saveBtnText);
    }

    public function proceedAccessDialogSubmit()
    {
        if (!User::hasWriteAccess()) {
            Response::abort(403);
        }

        if (!isset($_GET['class'])) {
            Response::abort(400, 'No class provided');
        }

        $reportClass = $_GET['class'];
        $oldAccess = $this->getReportAccess($reportClass);

        $fieldset = $this->getAccessDialogFieldset();
        $newAccess = $fieldset->getDataFromRequest($_POST);

        Db::insertRow([
            'ReportClass' => $reportClass,
            'UserIDs' => $newAccess['UserIDs'],
            'GroupIDs' => $newAccess['GroupIDs']
        ], 'reportaccess', true);

        // Запишем логи
        $reportTitle = $this->getReportTitle($reportClass);
        $this->writeDbLogTextAccessChange($reportTitle, $oldAccess, $newAccess, $fieldset, 'UserIDs');
        $this->writeDbLogTextAccessChange($reportTitle, $oldAccess, $newAccess, $fieldset, 'GroupIDs');

        $flashMsg = Config::$lang === 'ru' ? 'Сохранено' : 'Saved';
        Session::flashSuccess($flashMsg);
        Session::$showAsToast = true;
    }

    private function writeDbLogTextAccessChange(
        string $reportTitle,
        array $oldAccess,
        array $newAccess,
        Fieldset $fieldset,
        string $fieldKey
    ) {
        $field = $fieldset[$fieldKey];

        $oldIds = array_filter(explode('|', $oldAccess[$fieldKey]));
        $newIds = array_filter(explode('|', $newAccess[$fieldKey]));

        if (Config::$lang === 'ru') {
            $description = $fieldKey === 'UserIDs' ? 'пользователю' : 'группе';
        } else {
            $description = $fieldKey === 'UserIDs' ? 'user' : 'group';
        }

        foreach (array_diff($oldIds, $newIds) as $removedId) {
            $userOrGroupName = $field->getHtml($removedId);
            if (Config::$lang === 'ru') {
                $dbLogText = "<span class=bad>Доступ $description <strong>$userOrGroupName</strong> к отчёту <strong>$reportTitle</strong> закрыт</span>";
            } else {
                $dbLogText = "<span class=bad>Access for $description <strong>$userOrGroupName</strong> report <strong>$reportTitle</strong> denied</span>";
            }
            $this->writeDbLogText($removedId, '', $userOrGroupName, '', $dbLogText, $reportTitle);
        }


        foreach (array_diff($newIds, $oldIds) as $addedId) {
            $userOrGroupName = $field->getHtml($addedId);
            if (Config::$lang === 'ru') {
                $dbLogText = "<span class=good>Доступ $description <strong>$userOrGroupName</strong> к отчёту <strong>$reportTitle</strong> открыт</span>";
            } else {
                $dbLogText = "<span class=good>Access for $description <strong>$userOrGroupName</strong> for report <strong>$reportTitle</strong> granted</span>";
            }
            $userOrGroupName = $field->getHtml($addedId);
            $this->writeDbLogText('', $addedId, '', $userOrGroupName, $dbLogText, $reportTitle);
        }
    }


}
