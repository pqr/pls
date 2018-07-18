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
        $ra = new ReportAccess();
        return array_filter($allReports, function ($reportClass) use ($ra, $userId, $groupId) {
            return $ra->checkAccess($userId, $groupId, $reportClass);
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
    public static function getReportTitle(string $reportClass): string
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
    public static function getReportDescription(string $reportClass): string
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
                    ->params($params + ['p' => 'AccessDialog'])
                    ->ajax()
                    ->attrs(['class' => 'reportblock__accesslink'])
                    ->getHtml();
            }

            $title = Reportmenu::getReportTitle($reportClass);
            $descriptioon = Reportmenu::getReportDescription($reportClass);
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
                        $title = Reportmenu::getReportTitle($reportClass);
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
        $usersOptions = ['-1' => Config::$lang === 'ru' ? 'Все' : 'All'];
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

    public function proceedAccessDialog()
    {
        if (!User::hasWriteAccess()) {
            Response::abort(403);
        }

        if (!isset($_GET['class'])) {
            Response::abort(400, 'No class provided');
        }

        $class = $_GET['class'];
        $ra = new ReportAccess();
        $userIds = $ra->getReportUsers($class);
        $groupIds = $ra->getReportGroups($class);

        $fieldset = $this->getAccessDialogFieldset();
        $userIdsField = $fieldset['UserIDs'];
        $groupIdsField = $fieldset['GroupIDs'];
        $text = Config::$lang === 'ru' ? 'Разрешить доступ к данному отчёту' : 'Allow access for this report';
        $bodyHtml = '<form action="?a=Reportmenu&p=AccessDialogSubmit&class=' . $class . '#ajax" method="post" style="margin-bottom: 20px">'
            . "<p>$text:</p>"
            . "<p><label>" . $userIdsField->getName() . ":<br>"
            . $userIdsField->getHtmlInput(implode('|', $userIds))
            . "</label></p>"
            . "<p><label>" . $groupIdsField->getName() . ":<br>"
            . $groupIdsField->getHtmlInput(implode('|', $groupIds))
            . "</label></p>"
            . '</form>';


        $allUsers = $this->getAllActiveUsers();
        $bodyHtml .= $this->getUsersWithFullAccessHtml($allUsers);

        $saveBtnText = Config::$lang === 'ru' ? 'Сохранить' : 'Save';
        Response::showDialog($bodyHtml, $saveBtnText);
    }

    /**
     * @throws \Exception
     */
    public function proceedAccessDialogSubmit()
    {
        if (!User::hasWriteAccess()) {
            Response::abort(403);
        }

        if (!isset($_GET['class'])) {
            Response::abort(400, 'No class provided');
        }

        $reportClass = $_GET['class'];

        $fieldset = $this->getAccessDialogFieldset();
        ['UserIDs' => $userIdsString, 'GroupIDs' => $groupIdsString] = $fieldset->getDataFromRequest($_POST);
        $newUserIds = array_filter(array_map('intval', explode('|', $userIdsString)));
        $newGroupIds = array_filter(array_map('intval', explode('|', $groupIdsString)));

        $ra = new ReportAccess();
        $ra->setNewUsers($newUserIds, $reportClass);
        $ra->setNewGroups($newGroupIds, $reportClass);

        $flashMsg = Config::$lang === 'ru' ? 'Сохранено' : 'Saved';
        Session::flashSuccess($flashMsg);
        Session::$showAsToast = true;
    }
}
