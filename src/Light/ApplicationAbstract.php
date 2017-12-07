<?php

namespace Light;

// Текущий файл находится в <rootdir>/src/Light/ApplicationAbstract.php - нужно сделать два раза dirname, чтобы получить <rootdir>
define('LIGHT_DIR_ROOT', dirname(dirname(__DIR__)));
define('LIGHT_DIR_PROJECT', LIGHT_DIR_ROOT . '/src/' . PROJECT_NAMESPACE);
define('LIGHT_DIR_LIGHT', LIGHT_DIR_ROOT . '/src/Light');
define('LIGHT_DIR_TEMP', LIGHT_DIR_ROOT . '/temp');
define('LIGHT_DIR_LOGS', LIGHT_DIR_ROOT . '/logs');
define('LIGHT_DIR_PUBLIC', LIGHT_DIR_ROOT . '/public');
define('LIGHT_DIR_LIGHTPUBLIC', LIGHT_DIR_ROOT . '/src/Light/public');

use Light\Codeception\TestResponseException;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;

abstract class ApplicationAbstract
{
    /** @var ApplicationAbstract */
    public static $app;

    /** @var Timer переменная таймер для измерения времени генерации всей страницы */
    private static $timer;

    // Набор флагов, которые включают запуск различных кешиющих функций в конце работы (в ApplicationAbstract::terminate())
    // Благодаря этим флагам можно запустить обновление кэшей в нужном порядке

    // Флаг, который нужно установить в true, если в конце работы следует запустить FilesFolder::flushCacheNotEmpty()
    public static $flushFilesFolder = false;

    public function __construct()
    {
        self::$app = $this;
        self::$timer = new Timer();

        // Зарегистрируем обработчик ошибок или  rollbar
        if (!defined("CODECEPTION_FUNCTIONAL_TEST")) {
            include __DIR__ . '/ErrorHandler.php';
        }

        $this->initDb();
    }

    public static function getApp()
    {
        return self::$app;
    }

    private function checkCsrf()
    {
        if (defined("CODECEPTION_FUNCTIONAL_TEST")) {
            return;
        }

        if (Request::getModule() !== 'Login' && User::getId()) {
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = md5(time() . rand() . User::getId());
            } else {
                if ($_POST) {
                    $testToken = '';
                    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                        $testToken = $_SERVER['HTTP_X_CSRF_TOKEN'];
                    } elseif (isset($_POST['csrf_token'])) {
                        $testToken = $_POST['csrf_token'];
                    } else {
                        if (Config::$rollbar) {
                            //Создаём Exception и отправяем его в логи rollbar - таким образом в логах будет backtrace!
                            Rollbar::log(Level::ERROR, new \Exception('no csrf token'));
                        } else {
                            die('no csrf token');
                        }
                    }

                    if ($testToken !== $_SESSION['csrf_token']) {
                        if (Config::$rollbar) {
                            //Создаём Exception и отправяем его в логи rollbar - таким образом в логах будет backtrace!
                            Rollbar
                                ::log(Level::ERROR, new \Exception('wrong csrf token'));
                        } else {
                            die('wrong csrf token');
                        }
                    }
                }
            }
        }
    }

    /**
     * Проверяет, существует ли запрашиваемый модуль вообще?
     */
    private function checkModuleExists()
    {
        $module = Request::getModule();

        if (!$module || (!ModuleRegister::isModuleCore($module) && !ModuleRegister::isModuleExists($module))) {
            if ($module) {
                Session::flashError('Module ' . $module . ' does not exists');
            }
            //Такого модуля вообще нет! Нужно срочно сделать редирект на правильный модуль.
            //А это зависит от того, залогинен ли пользователь
            if (User::isAuth()) {
                Response::redirect(ModuleRegister::getDefaultModule());
            } else {
                Response::redirect('Login');
            }
        }
    }

    private function checkModuleAccess()
    {
        $module = Request::getModule();

        if ($module !== 'Login' && !User::isAuth()) {
            if ($_SERVER['QUERY_STRING']) {
                $redirectUrlParams = ['next' => $_SERVER['QUERY_STRING']];
            } else {
                $redirectUrlParams = [];
            }
            Response::redirect('Login', $redirectUrlParams);
        }

        if (!User::hasReadAccess($module) && !ModuleRegister::isModuleCore($module)) {

            $moduleName = ModuleRegister::getModuleName($module);
            $msg = Config::$lang === 'ru' ? "Нет доступа к модулю $moduleName" : "No access to module $moduleName";

            if (Request::isAjax()) {

                Response::sendToast('error', $msg);
                Response::abort(403);
                return;

            }

            Session::flashError($msg);
            $redirectToModule = ModuleRegister::getDefaultModule();
            //Если к defaultModule нет доступа, то переберём все модули подряд и редиректим на первый,
            //к которому есть доступ
            if (!User::hasReadAccess($redirectToModule)) {
                foreach (ModuleRegister::getModulesKeys() as $tryModule) {
                    if (User::hasReadAccess($tryModule)) {
                        $redirectToModule = $tryModule;
                        break;
                    }
                }
            }

            Response::redirect($redirectToModule);
        }
    }

    public static function setParam($name, $value)
    {
        Db::query(
            "REPLACE INTO system_param (CreatedBy, Name, Value) VALUES (" . User::getId() . ", '" . Db::esc($name) .
            "','" . Db::esc($value) . "')"
        );
    }

    public static function getParam($name)
    {
        return Db::getOne("SELECT Value FROM system_param WHERE Name='" . Db::esc($name) . "'");
    }

    /**
     * Этот метод должен быть переопределён в классе потомке - в нём могут быть переопределены настройки title,
     * добавление общих глобальных css файлов и т.п.
     */
    abstract protected function init();

    /**
     * Этот метод должен быть переопределён в классе потомке - в нём должны быть зарегистрированы все модули
     * с помощью вызовов ModuleRegister::addModule
     */
    abstract protected function registerModules();

    /**
     * Метод для переопределения в классах потоках, возвращает массив с информацией общей информацией о напоминаниях на Home
     * Каждый элемент результирующего массива это массив, где:
     * нулевой элемент - имя класса Home блока
     * первый элемент - цвет для шапки (цвета удобно определять в этом массиве, т.к. видно все блоки на одном экране)
     *
     * Пример возвращаемого значения:
     * return [
     *      [SomeHome::class, HomeBlockAbstract::CSSCLASS_GREEN],
     *      [AnotherNotification::class, HomeBlockAbstract::CSSCLASS_ORANGE2]
     * ]
     *
     * @return array
     */
    abstract public function getHomeBlocks();

    /**
     * Возвращает массив с описанием ролей пользователей. Обычно, роли используются для определения в какой выпадающий
     * список должен попадать тот или иной пользователь (например ResponsibleCS, ReponsibleSales)
     * .
     * Этот массив нужен искоючительно для рендринга страницы редактирования прав пользователей, где нужно вывести описания
     * ролей рядом с чекбоксами
     *
     * Пример возвращаемого значения:
     * (ключи массива - это строки, но удобнее определить их в виде констант в Application)
     * return [
     *      'ShipmentResponsibleSales' => 'Shipments: ответственный Sales'
     *      Application::ROLE_CUSTOMER_CS => 'Customers: ответственный CS'
     * ]
     *
     * @return array ассоциативнй массив, ключ - это условный код роли, значнеие - текстовое описание
     */
    abstract public function getUserRolesList();


    /**
     * Возвращает массив специальных прав доступа с их опписаниями. Метод нужен для рендринга страниц редактирования
     * прав доступа пользователя и группы в UserModule
     * Каждый элемент результирующего массива это массив, где:
     * нулевой элемент - ModuleKey - имя модуля к которому концептуально относится этот флажок, нужно для отображения
     * в правильном месте на экране редактирования прав доступа (напротив соответствующего модуля)
     * первый элемент - AccessKey (уникальная строка описывающая этот элемент)
     * второй элемент - текстовое описание
     *
     * Пример возвращаемого значения:
     * return [
     *      ['Home', 'Home1', 'Показывать на экране Home такое-то напоминание'],
     *      ['Customer', 'CustomerAccessAll', 'Доступ ко всем клиентам (не только своим)']
     * ]
     *
     * @return array
     */
    abstract public function getSpecialAccessList();

    private function initDb()
    {
        //Инициализируем singleton объект базы
        Db::connect(
            Config::$mysqlHost,
            Config::$mysqlUser,
            Config::$mysqlPassword,
            Config::$mysqlDb,
            Config::$mysqlPort
        );
    }

    public function runAutolaunchCli()
    {
        //Попробуем запустить этот Autolaunch скрипт
        $autolaunchName = $GLOBALS['argv'][1] ?? null;
        if (!$autolaunchName) {
            print "Autolaunch name not provided";
            exit(1);
        }

        if (strpos($autolaunchName, 'Light/') === 0) {
            // Если имя Autolaunch скприта начинается с Light/, значит будем искать соответсвующий класс в папке движке
            $className = 'Light\\Autolaunch\\' . str_replace('Light/', '', $autolaunchName);
        } else {
            // Это какой-то Autolaunch из проекта
            $className = PROJECT_NAMESPACE . "\\Autolaunch\\$autolaunchName";
        }

        /** @var AutolaunchAbstract $autolaunchObj */
        $autolaunchObj = new $className;
        $autolaunchObj->proceedAutomaticRun();
        self::terminate();
    }

    /**
     * @desc главная функция приложения - обрабатывает основные переменные и вызывает необходимый модуль
     */
    public function __invoke()
    {
        // Выставляем значения по умолчанияю во все статические переменные
        // внутри объекта Response (важно для функциональных тестов)
        Response::reset();

        //В качестве обработчика сессий используем самописный класс реализующий интерфейс SessionHandlerInterface
        //http://php.net/manual/en/class.sessionhandlerinterface.php
        //В этом самописном классе будем сохранять сессии в базу данных в таблицу phpsession
        if (!defined("CODECEPTION_FUNCTIONAL_TEST")) {
            session_set_save_handler(new Session);
            session_start();
        }

        //Пытаемся инициализировать пользователя на основе данных сессии
        User::loadFromSession();

        if (!defined("CODECEPTION_FUNCTIONAL_TEST") && Config::$sentry) {
            $client = new \Raven_Client(Config::$sentry['dns'], Config::$sentry['options']);
            $client->install();
            $client->setRelease(ApplicationAbstract::getVersion());
            $client->user_context(array(
                'id' => User::getId(),
                'username' => User::getLogin()
            ));
        }

        //Вызовем метод, который должен быть определён в классе потомке - в нём настраивается title, добавляются глобкальные css
        $this->init();

        //Вызовем метод, который должен быть определён в классе потомке - в нём регистрируются все модули
        $this->registerModules();

        $this->checkCsrf();

        // Проверим, есть ли текущий модуль вообще? Если нет, пользователь будет перенаправлен внутри метода
        $this->checkModuleExists();

        // Проверим, есть ли у текущего пользователя права доступа к текущему модулю?
        $this->checkModuleAccess();

        // Запускаем модуль (происходит автоматический поиск в папке Module в проекте, потом в Light/Module)
        $output = $this->proceedModule();

        if (Request::isAjax() || Request::isXls() || !Response::haveMasterTemplate()) {

            //В случае ajax запроса или принудительно отключённого шаблона вывода - просто распечатаем $output
            //print $output;
            //Вывод $output будет ниже - передаётся как параметр в self::terminate($output);

        } else {

            Intro\IntroManager::show();

            $output = Template::parseTpl(
                Response::getMasterTemplate(),
                ['output' => $output]
            );
        }

        // Запись логов, завершение приложения
        self::terminate($output);
    }

    /**
     * Запускает модуль (происходит автоматический поиск в папке Module в проекте, потом в Light/Module)
     * @return string
     */
    private function proceedModule()
    {
        ob_start();

        $module = Request::getModule();
        $class = ModuleRegister::getModuleClass($module);

        //Вызов функции class_exists породит вызов автолодера классов, который сделает include нужного файла
        //Даже если в этом файле нет класса (т.е. модуль plain/php), он всё равно будет выполнен!
        if (class_exists($class)) {

            if (isset($_GET['ps'])) {
                //В параметре ps можно передать имя статического модуля,
                // в этом случае объект класса модуля не создаётся,
                // а у казанный в ps метод вызывается статически
                if (strpos(strtolower($_GET['ps']), 'post') === 0
                    && !Request::isPost()
                ) {
                    // функции, которые начинаются со слова post разрешено вызывать только
                    // методом POST. Это функции которые меняют что-то в базе,
                    // и они обязательно должны вызываться как POST!
                    Response::abort(403, 'Only POST is allowed for method proceed' . $_GET['ps']);
                }

                call_user_func($class . '::proceed' . ucfirst($_GET['ps']));

            } elseif (isset($_GET['ac'])) {

                //Специальный метод для Autocomplete - вызывается статически и возвращает массив для автокомплита
                //Массив двухмерный: проиндексированный массив со строками и внутри ассоциативный массив с полями
                //важное поле во вложенном массиве - 'value' - оно будет подставлено в input средствами JS
                $value = trim($_GET['term']);
                $out = call_user_func($class . '::ac' . ucfirst($_GET['ac']), $value);
                Response::json($out);

            } else {

                $module = new $class();

                if (isset($_GET['p'])) {
                    // Если в url был параметр p, значит нужно запустить особый метод proceed внутри этого класса
                    if (strpos(strtolower($_GET['p']), 'post') === 0
                        && !Request::isPost()
                    ) {
                        // функции, которые начинаются со слова post разрешено вызывать только
                        // методом POST. Это функции которые меняют что-то в базе,
                        // и они обязательно должны вызываться как POST!
                        Response::abort(403, 'Only POST is allowed for method proceed' . $_GET['p']);
                    }
                    $module->{'proceed' . ucfirst($_GET['p'])}();
                } else {
                    //Иначе запускаем стандартный метод __invoke
                    $module();
                }
            }
        }

        return ob_get_clean();
    }

    public static function getWorkingTime()
    {
        return self::$timer->get();
    }

    /**
     * Запись логов и принудительное завершениe приложения
     * Этот метод вызывается, например, из Response::redirect()
     *
     * Параметр $finalOutput нужен, чтобы отправить тело ответа ПОСЛЕ заголовков, коими могут быть x-light-flashmsg
     * в случае ajax запроса
     * @param string $finalOutput
     * @throws TestResponseException
     */
    public static function terminate($finalOutput = '')
    {
        self::getApp()->flushCaches();

        while (ob_get_level()) {
            $additionalOutput = ob_get_clean();
            $finalOutput .= $additionalOutput;
        }

        if (!isset($GLOBALS['argv']) && Request::isAjax() && !Response::isRedirected()) {
            // Запрос пришел через ajax - отправим все накопившиеся flash сообщения обратно через ajax
            $flashMessagess = Session::flashGetAll();
            if ($flashMessagess) {
                if (Session::$showAsToast) {
                    Response::header('x-light-toastmsg: ' . json_encode($flashMessagess));
                } else {
                    Response::header('x-light-flashmsg: ' . json_encode($flashMessagess));
                }
            }
        }

        // Далее пойдёт вывод всего в браузер.
        // Если же мы находимся в режиме функциональных тестов Codeception, то нужно прерать поток выполнения
        // Выбросим исключение в которое завернём весь получившийся результат $finalOutput
        if (defined('CODECEPTION_FUNCTIONAL_TEST')) {
            throw new TestResponseException($finalOutput);
        }

        if (!isset($GLOBALS['argv'])) {
            // Выводим заголовки, только если это не cli режим
            foreach (Response::getHeaders() as $header) {
                header($header);
            }
        }

        print $finalOutput;

        if (!isset($GLOBALS['argv'])) {
            // Работаем с сессией, только если это не cli режим
            if (session_id()) {
                session_write_close();
            }
        }

        flush();

        //Чтобы flush сработал и браузер сразу же отобразил содержимое нужно правильно растроить буферы в веб-сервере
        //Для IIS нужно сделать как описано здесь: http://php.net/manual/ru/function.flush.php#113360
        //Для nginx в секции http поставить keepalive_timeout 0; и уменьшить fastcgi_buffers, например:
        //fastcgi_buffers 8 2k;
        //fastcgi_buffer_size 2k;
        //Также в nginx нужно отключить gzip  off; иначе Content-Length посчитанный выше окажется не верным
        //Чтобы POST запросы на сохранение быстро редиректили надо также в php.ini поставить output_buffering = 0


        if (!isset($GLOBALS['argv']) && User::isAuth() && !isset($_REQUEST['nouserrefreshtime'])) {
            User::updateLastRefreshDateTime();
            VisitLog::write();
        }

        exit();

    }

    /**
     * Обновляет необходимые кэши в правильном порядке
     * Функцию нужно переопределить в классе потомке, чтобы добавить свои классы кэшей
     */
    protected function flushCaches()
    {
        if (self::$flushFilesFolder) {
            FilesFolder::flushCacheNotEmpty();
        }
    }

    public static function getVersion(): string
    {
        static $version;
        if ($version === null) {
            if (file_exists(LIGHT_DIR_ROOT . '/version.txt')) {
                $version = trim(file_get_contents(LIGHT_DIR_ROOT . '/version.txt'));
            } else {
                $version = filemtime(LIGHT_DIR_LIGHTPUBLIC . '/js/build/lightbuild.js');
            }
        }
        return (string)$version;
    }
}
