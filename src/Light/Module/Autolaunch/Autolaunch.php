<?php

namespace Light\Module\Autolaunch;

use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Js;
use Light\Link;
use Light\Request;
use Light\Response;
use Light\Session;
use Light\Template;
use Light\Traits\ModuleSessionTrait;

class Autolaunch
{
    use ModuleSessionTrait;

    /**
     * Полностью переопределяем метод __invoke у класса предка, чтобы вывести ссылки на подмодули в виде таблицы,
     * а не виде больших кнопок, как это сделано в SubmoduleMenuAbstract по умолчанию
     *
     * Кроме того, нужно уметь обрабатывать запуск autolaunch ($_GET['run']) и просмотр логов (AutolaunchHistoryWithForm)
     */
    public function __invoke()
    {
        Js::addLight('Autolaunch.js');

        if (isset($_GET['class'])) {

            $className = $_GET['class'];
            // Базовая проверка: класс должен начинаться либо с неймспейса проекта, либо с Light\\
            // и не должен содержать точек внутри
            if (strpos($className, 'Light\\') === false
                && strpos($className, PROJECT_NAMESPACE)
                || strpos($className, '.') !== false
            ) {
                throw new \InvalidArgumentException("Invalid autolaunch class $className");
            }

            if (isset($_GET['run']) && Request::isPost()) {

                // Поступил запрос на запуск скприта
                $this->run($className);

            } elseif (isset($_GET['id'])) {

                // Пользователь хочет отобразить подробный отчёт о конкретном запуске
                $this->renderOneLog($className, (int)$_GET['id']);

            } else {

                $this->renderFormAndHistory($className);

            }

            return;

        }

        $this->renderMenu();

    }

    /**
     * Отображате таблицу со списком всех Autolaunch скпритов
     */
    private function renderMenu()
    {
// Если не было параметра $_GET['class'], тогда выводим общий список всех скпритов
        Response::setPageTitle('Autolaunch');
        Response::setPageHeadHtml('<strong>Autolaunch</strong>');

        print '<h3><input type="text" placeholder="Фильтр по названию или описанию" id="autolaunch-filter-input" style="padding: 5px"></h3>';

        //Подгтовим таблицу для вывода списка Autolaunch скриптов
        $autolaunchMenuTable = new AutolaunchMenuTable();


        //Теперь выведем все найденные скрипты в форме таблицы
        print $autolaunchMenuTable->getHtmlTable(false);
    }

    /**
     * Отображает форму запуска отдельного Autolaunch скрипта и таблицу с историей его запусков
     * @param string $className
     * @throws \Exception
     */
    private function renderFormAndHistory(string $className)
    {
        // Мы находимся где-то на форме запуска с историей
        // Сразу подготовим объект с таблицей истории запусков
        $autolaunchHistoryTable = new AutolaunchLog($className);

        if (Request::isAjax()) {

            // Ajax обновление таблицы с итсорией запусков при переключении страниц
            print $autolaunchHistoryTable->getHtmlTableForAjax();
            return;

        }

        // Отображение формы с историей запусков
        /** @var AutolaunchAbstract $className */
        $title = $className::getTitle();

        Response::setPageTitle("Autolaunch $title");

        $link = Link::create('Autolaunch', 'Autolaunch');
        $imgSrc = 'lightpublic/i/autolaunch.png';
        $img = "<img src=$imgSrc width=24>";
        Response::setPageHeadHtml("$link $img <strong>$title</strong>");

        // Подготовим к отображению форму запуска
        $tdata = [
            'className' => $className,
            'historyTableHtml' => $autolaunchHistoryTable->getHtmlTable(),
            'description' => $className::getDescription()
        ];

        /** @var AutolaunchAbstract $autolauchObj */
        $autolauchObj = new $className();
        $formFieldset = $autolauchObj->getFieldset();
        if ($formFieldset) {
            // В параметрах GET могли быть переданы значения полей по умолчанию, ицицилилизуем их
            $params = $formFieldset->getDataFromRequest($_GET);
            $tdata += $formFieldset->getTplData($params, F::MODE_EDIT);
        }

        Template::includeTpl('AutolaunchFormWithHistory.tpl.php', $tdata);
    }

    /**
     * Запускает Autolaunch, когда пользователь нажал на форме Run
     * @param string $className
     */
    private function run(string $className)
    {
        // Запуск с формы
        // Создадим объект подмодуля и запустим его!
        /** @var \Light\Module\Autolaunch\AutolaunchAbstract $autolaunchObj */
        $autolaunchObj = new $className;
        // Ручной запуск - используем __invoke()
        $logId = $autolaunchObj();
        // После окончания работы перенарпавляем браузер на страницу логов по данному запуску
        Response::redirect('Autolaunch', ['class' => $className, 'id' => $logId]);
    }

    /**
     * Отображает детальные логи отдельно взятого запуска
     * @param string $className
     * @param int $logId
     * @throws \Exception
     */
    private function renderOneLog(string $className, int $logId)
    {
        // Попробуем загрузить данные текущего лога
        $logObj = new AutolaunchLogObj();
        $logObj->setId($logId);
        if (!$logObj->loadData()) {
            //Лог с таким id не найден в базе, возвращаем пользователя к экрану с формой и таблицей History
            Session::flashError("Autolaunch History record with ID: $logId not found");
            Response::redirect('Autolaunch', ['class' => $className]);
        }

        /** @var AutolaunchAbstract $className */
        $title = $className::getTitle();

        Response::setPageTitle("Autolaunch $title Log $logId");

        $link = Link::create('Autolaunch', 'Autolaunch');
        $imgSrc = 'lightpublic/i/autolaunch.png';
        $img = "<img src=$imgSrc width=24>";

        // Сформируем параметры ссылки обратно на форму и таблицу с логами
        $backUrlParams = [
            'class' => $className,
            'AutolaunchLog_findid' => $logId
        ];

        if (isset($_GET['msid'])) {
            $backUrlParams['msid'] = $_GET['msid'];
        }
        $titleWithLink = Link::create('Autolaunch', $title)->params($backUrlParams);
        Response::setPageHeadHtml("$link $img <strong>$titleWithLink Log $logId</strong>");

        $this->addModuleSessionNextPrevLinks($logObj, 'id', ['class' => $className]);

        $tdata = $logObj->getTplData(F::MODE_VIEW);
        $tdata['Report'] = Db::getOne("SELECT Report FROM log_autolaunch_text WHERE ID = @i", $logId);
        $tdata['description'] = $className::getDescription();

        //Добавим css файл в котором описан div.subtableblock для обрамления текста лога
        Template::includeTpl(
            'tabsmenu.tpl.php',
            [
                'items' => [
                    [
                        'text' => 'Back',
                        'url' => $titleWithLink->getHref()
                    ]
                ]
            ]
        );
        Template::includeTpl('AutolaunchLog.tpl.php', $tdata);
    }


}
