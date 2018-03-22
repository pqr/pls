<?php

namespace Light\Module\Reportmenu;

use Codeception\Module;
use Light\ApplicationAbstract;
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
     * @param array $allReports
     * @return array
     */
    private function getReportsForUser(int $userId, array $allReports): array
    {
        return array_filter($allReports, function ($reportClass) use ($userId) {
            return $this->hasUserAccessToReport($userId, $reportClass);
        });
    }

    private static function getReportFullClass(string $reportClass): string
    {
        return PROJECT_NAMESPACE . "\\Reportmenu\\$reportClass";;
    }

    private function getReportLink(string $innerHtml, string $reportClass, bool $blank = false): string
    {
        return Link::module($innerHtml, ['class' => $reportClass], 'Reportmenu', null, null, $blank);
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
        $reportsForUser = $this->getReportsForUser(User::getId(), $allReports);

        foreach ($reportsForUser as $reportClass) {

            $manageAccessLink = '';
            if ($hasWriteAcess) {
                $manageAccessText = Config::$lang === 'ru' ? 'Доступ' : 'Access';
                $manageAccessLink = '<a href="?a=Reportmenu&p=AccessDialog&class=' . $reportClass . '#ajax" class="reportblock__accesslink"><i class="fa fa-user-plus"></i> ' . $manageAccessText . '</a>';
            }

            $title = $this->getReportTitle($reportClass);
            $descriptioon = $this->getReportDescription($reportClass);
            $imageHtml = $this->getReportImageHtml($reportClass);

            $out[$reportClass] = '<div class="reportblock">'
                . '<div>'
                . $this->getReportLink($imageHtml, $reportClass)
                . '</div>'
                . '<div>'
                . $this->getReportLink($title, $reportClass)
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
     * @param string $reportClass
     * @return bool
     */
    private function hasUserAccessToReport(int $userId, string $reportClass)
    {
        if (User::hasWriteAccess('Reportmenu', $userId)) {
            // Если у пользователя есть доступ на запись в модуль отчётов, значит у него есть доступ ко всем отчётам
            return true;
        }

        // Загрузим из базы настройки доступа по всем отсчётам сразу (может пригодиться потом)
        static $allReportsAccess;
        if ($allReportsAccess === null) {
            $allReportsAccess = Db::getAssoc("SELECT ReportClass, UserIDs FROM reportaccess");
        }

        if (!isset($allReportsAccess[$reportClass])) {
            // Для данного запрашиваемого отчёта настроек вообще не найдено, значит и доступа нет
            return false;
        }

        $thisReportUsers = $allReportsAccess[$reportClass];
        if (strpos($thisReportUsers, '|%|') !== false) {
            // У данного отчёта есть настройка "Доступ всем!"
            return true;
        }

        if (strpos($thisReportUsers, "|$userId|") !== false) {
            // У данного отчёта есть есть привязка к запрашиваемому пользователю
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
            $moduleName = ModuleRegister::getModuleName('Reportmenu');
            Response::setPageTitle($moduleName);
            Response::setPageHeadHtml($moduleName);

            $filterPlaceholder = Config::$lang === 'ru' ? 'Фильтр по названию или описанию' : 'Search report by name or description';
            print '<h3>'
                . '<input type="text" placeholder="' . $filterPlaceholder . '" id="reportmenu-filter-input" style="padding: 5px">';
            if (User::hasWriteAccess()) {
                $auditText = $this->getAuditText();
                print '<a href="?a=Reportmenu&p=AccessAudit" class="btn-flat waves-effect" style="margin-left: 50px;">' . Img::icon('fa-user-circle') . ' ' . $auditText . '</a>';
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
        foreach ($allUsers as $userId => $name) {
            if (User::hasWriteAccess('Reportmenu', $userId)) {
                $outHtml .= '<li>' . htmlspecialchars($name) . '</li>';
            }
        }
        $outHtml .= '</ul>';

        return $outHtml;
    }

    private function getAllActiveUsers()
    {
        return Db::getAssoc("SELECT ID, Name FROM user WHERE Active = 1 ORDER BY Name");
    }

    public function proceedAccessAudit()
    {
        if (!User::hasWriteAccess()) {
            Session::flashError('No access');
            Response::redirect('Reportmenu');
            return;
        }
        // Если в GET запросе не передан конкретный класс подмодуля, выведем список всех
        $moduleName = ModuleRegister::getModuleName('Reportmenu');
        $auditText = $this->getAuditText();
        Response::setPageTitle("$moduleName - $auditText");
        Response::setPageHeadHtml(Link::module($moduleName) . " - <strong>$auditText</strong>");

        $backText = Config::$lang === 'ru' ? 'Вернуться к отчётам' : 'Back to reports';
        print '<a href="?a=Reportmenu" class="btn-flat waves-effect">◀ ' . $backText . '</a>';

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
        foreach ($allUsers as $userId => $name) {
            $reportsLinksForThisUser = [];
            if (User::hasWriteAccess('Reportmenu', $userId)) {
                // Если у пользователя есть доступ на запись в модуль Reportmenu, значит у него есть доступ ко всем отчётам
                $text = Config::$lang === 'ru' ? 'Полный доступ ко всем отчётам' : 'Full access to all reports';
                $reportsLinksForThisUser[] = "<span class=good>$text</span>";
            } elseif (!User::hasReadAccess('Reportmenu', $userId)) {
                $text = Config::$lang === 'ru' ? 'Доступ к модулю отчётов закрыт' : 'Access denied';
                $reportsLinksForThisUser[] = "<span class=bad>$text</span>";
            } else {
                // Получим список отчётов к которым у текущего пользователя есть доступ
                $reportsForThisUser = $this->getReportsForUser($userId, $allReports);
                if (!$reportsForThisUser) {
                    $text = Config::$lang === 'ru' ? 'Пользователю не подключён ни один отчёт' : 'No reports available';
                    $reportsLinksForThisUser[] = "<span class=bad>$text</span>";
                } else {
                    foreach ($reportsForThisUser as $reportClass) {
                        $title = $this->getReportTitle($reportClass);
                        $reportsLinksForThisUser[] = $this->getReportLink($title, $reportClass, true);
                    }
                }
            }

            $tableData[] = [
                'UserName' => $name,
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
        $options = ['%' => Config::$lang === 'ru' ? 'Все' : 'All'];
        $options += $this->getAllActiveUsers();

        return new Fieldset(
            F::createSelect2Multiple('UserIDs', [
                'name' => Config::$lang === 'ru' ? 'Пользователи' : 'Users',
                'editWidth' => 'default',
                'options' => $options
            ])
        );
    }

    private function getReportAccess(string $class)
    {
        return (string)Db::getOne("SELECT UserIDs FROM reportaccess WHERE ReportClass = @s", $class);
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

        $reportClass = $_GET['class'];
        $currentAccess = $this->getReportAccess($reportClass);

        $fieldset = $this->getAccessDialogFieldset();
        $userIdsField = $fieldset['UserIDs'];
        $text = Config::$lang === 'ru' ? 'Разрешить доступ к данному отчёту' : 'Allow access for this report';
        $bodyHtml = '<form action="?a=Reportmenu&p=AccessDialogSubmit&class=' . $reportClass . '#ajax" method="post" style="margin-bottom: 20px">'
            . "<div>$text:</div>"
            . $userIdsField->getHtmlInput($currentAccess)
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
        $currentAccess = $this->getReportAccess($reportClass);

        $fieldset = $this->getAccessDialogFieldset();
        $newAccess = $fieldset->getDataFromRequest($_POST)['UserIDs'];

        Db::insertRow(['ReportClass' => $reportClass, 'UserIDs' => $newAccess], 'reportaccess', true);

        // Запишем логи
        $userIdsField = $fieldset['UserIDs'];
        $currentUserIds = array_filter(explode('|', $currentAccess));
        $newUserIds = array_filter(explode('|', $newAccess));
        foreach (array_diff($currentUserIds, $newUserIds) as $removedUserId) {
            $this->writeDbLogText($removedUserId, '', $userIdsField->getHtml($removedUserId), '',
                '<span class="bad">Доступ закрыт</span>', $reportClass);
        }

        foreach (array_diff($newUserIds, $currentUserIds) as $addedUserId) {
            $this->writeDbLogText('', $addedUserId, '', $userIdsField->getHtml($addedUserId),
                '<span class="good">Доступ открыт</span>', $reportClass);
        }

        Session::flashSuccess('Сохранено');
        Session::$showAsToast = true;
    }


}
