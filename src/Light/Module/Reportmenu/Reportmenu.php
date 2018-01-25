<?php
namespace Light\Module\Reportmenu;

use Codeception\Module;
use Light\ApplicationAbstract;
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
use Light\Template;
use Light\User;

class Reportmenu
{
    /**
     * Возвращает ассоциативный массив: ключи массви - это имена классов отчётов (без неймспейсов)
     * Значения массивов - HTML код ссылок для вывода в шаблоке Reprotmenu.tpl.php
     * @return array
     */
    private function getReportsLinks()
    {
        $reportsLinks = [];
        $writeAcess = User::hasWriteAccess();
        $reportsAccess = Db::getAssoc("SELECT ReportClass, UserIDs FROM reportaccess");

        foreach (glob(LIGHT_DIR_PROJECT . '/Reportmenu/*.php') as $file) {

            if (substr($file, -8) == '.tpl.php') {
                continue;
            }

            $reportClass = pathinfo($file, PATHINFO_FILENAME);

            $hasAccess = $writeAcess
                || (isset($reportsAccess[$reportClass])
                    && (strpos($reportsAccess[$reportClass], '|%|') !== false
                        || strpos($reportsAccess[$reportClass], '|' . User::getId() . '|') !== false));

            if (!$hasAccess) {

                $reportsLinks[$reportClass] = '';

            } else {

                /** @var ReportAbstract $reportFullClass */
                $reportFullClass = PROJECT_NAMESPACE . "\\Reportmenu\\$reportClass";

                $reportsLinks[$reportClass] = '<div class="reportblock">'
                    . '<div>'
                    . Link::module(Img::png('Reportmenu/' . $reportClass), ['class' => $reportClass])
                    . '</div>'
                    . '<div>'
                    . Link::module($reportFullClass::getTitle(), ['class' => $reportClass])
                    . '<br>' . $reportFullClass::getDescription()
                    . '</div>'
                    . ($writeAcess ? '<a href="?a=Reportmenu&ps=AccessDialog&class=' . $reportClass . '#ajax" class="reportblock__accesslink"><i class="fa fa-user-plus"></i> Доступ</a>' : '')
                    . '</div>';
            }

        }

        return $reportsLinks;

    }

    /** Выводит список под-модулей, находящихся в этом меню */
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

            //Если в GET запросе не передан конкретный класс подмодуля, выведем список всех
            $moduleName = ModuleRegister::getModuleName('Reportmenu');
            Response::setPageTitle($moduleName);
            Response::setPageHeadHtml($moduleName);

            print '<h3><input type="text" placeholder="Фильтр по названию или описанию" id="reportmenu-filter-input" style="padding: 5px"></h3>';

            //Шаблон Reportmenu.tpl.php должен быть отпределён в папке www/templates
            Template::includeTpl('Reportmenu.tpl.php', $this->getReportsLinks());

            Js::addLight('Reportmenu.js');

        }
    }

    private static function getAccessDialogFieldset()
    {
        $options = ['%' => 'Все'];
        $options += Db::getAssoc("SELECT ID, Name FROM user WHERE Active = 1 ORDER BY Name");

        return new Fieldset(
            F::createSelect2Multiple('UserIDs', [
                'name' => 'Пользователи',
                'editWidth' => 'default',
                'options' => $options
            ])
        );
    }

    private static function getReportAccess(string $class)
    {
        return (string)Db::getOne("SELECT UserIDs FROM reportaccess WHERE ReportClass = @s", $class);
    }

    private static function writeDbLogText($oldValue, $newValue, $oldValueFormated, $newValueFormated, $text, $class)
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

    public static function proceedAccessDialog()
    {
        if (!User::hasWriteAccess()) {
            Response::abort(403);
        }

        $currentAccess = self::getReportAccess($_GET['class']);

        $fieldset = self::getAccessDialogFieldset();
        $userIdsField = $fieldset['UserIDs'];
        $bodyHtml = '<form action="?a=Reportmenu&ps=AccessDialogSubmit&class=' . $_GET['class'] . '#ajax" method="post">'
            . 'Разрешить доступ для: '
            . $userIdsField->getHtmlInput($currentAccess)
            . '</form>';

        Response::showDialog($bodyHtml, 'Сохранить');
    }

    public static function proceedAccessDialogSubmit()
    {
        if (!User::hasWriteAccess()) {
            Response::abort(403);
        }

        $currentAccess = self::getReportAccess($_GET['class']);

        $fieldset = self::getAccessDialogFieldset();
        $newAccess = $fieldset->getDataFromRequest($_POST)['UserIDs'];

        Db::insertRow(['ReportClass' => $_GET['class'], 'UserIDs' => $newAccess], 'reportaccess', true);

        // Запишем логи
        $userIdsField = $fieldset['UserIDs'];
        $currentUserIds = array_filter(explode('|', $currentAccess));
        $newUserIds = array_filter(explode('|', $newAccess));
        foreach (array_diff($currentUserIds, $newUserIds) as $removedUserId) {
            self::writeDbLogText($removedUserId, '', $userIdsField->getHtml($removedUserId), '',
                '<span class="bad">Доступ закрыт</span>', $_GET['class']);
        }

        foreach (array_diff($newUserIds, $currentUserIds) as $addedUserId) {
            self::writeDbLogText('', $addedUserId, '', $userIdsField->getHtml($addedUserId),
                '<span class="good">Доступ открыт</span>', $_GET['class']);
        }

        Session::flashSuccess('Сохранено');
        Session::$showAsToast = true;
    }


}
