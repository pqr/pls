<?php

namespace Light;

// Текущий файл находится в <rootdir>/src/Light/ApplicationAbstract.php - нужно сделать два раза dirname, чтобы получить <rootdir>
define('LIGHT_DIR_ROOT', dirname(__DIR__, 2));
define('LIGHT_DIR_PROJECT', LIGHT_DIR_ROOT . '/src/' . PROJECT_NAMESPACE);
define('LIGHT_DIR_LIGHT', LIGHT_DIR_ROOT . '/src/Light');
define('LIGHT_DIR_TEMP', LIGHT_DIR_ROOT . '/temp');
define('LIGHT_DIR_LOGS', LIGHT_DIR_ROOT . '/logs');
define('LIGHT_DIR_PUBLIC', LIGHT_DIR_ROOT . '/public');
define('LIGHT_DIR_LIGHTPUBLIC', LIGHT_DIR_ROOT . '/src/Light/public');

use Light\Codeception\TestResponseException;
use Light\Exceptions\ForceSendResponse;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

abstract class ApplicationAbstract
{
    /** @var ApplicationAbstract */
    public static $app;

    /** @var Timer переменная таймер для измерения времени генерации всей страницы */
    private static $timer;

    /** @var ServerRequestInterface */
    protected $request;

    // С помощью вызова ApplicationAbstract::onBeforeSend(callable $handler) можно добавить обработчики перед отправкой
    private $handlersBeforeSend = [];
    // С помощью вызова ApplicationAbstract::onAfterSend(callable $handler) можно добавить обработчики после отправки
    // (например, какие-то операции записи в логи или обновление кешей, которые пользователю не обязательно нужно ждать)
    private $handlersAfterSend = [];

    public function __construct()
    {
        self::$app = $this;
        self::$timer = new Timer();

        // Чтобы при создании директорий с помощью mkdir можно было выставить права 0777
        // https://stackoverflow.com/questions/3997641/why-cant-php-create-a-directory-with-777-permissions
        umask(0);

        // Зарегистрируем обработчик ошибок или  rollbar
        if (!defined("CODECEPTION_FUNCTIONAL_TEST")) {
            ErrorHandler::init();
        }

        $this->initDb();
    }

    public static function getApp()
    {
        return self::$app;
    }

    public static function isCli()
    {
        return isset($GLOBALS['argv']);
    }

    private function checkCsrf(): void
    {
        if (defined("CODECEPTION_FUNCTIONAL_TEST")) {
            return;
        }

        if (Request::getModule() !== 'Login' && User::getId()) {
            if (!isset($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = md5(time() . random_int(0, mt_getrandmax()) . User::getId());
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
                            Rollbar::log(Level::ERROR, new \Exception('wrong csrf token'));
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
    private function checkModuleExists(): void
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

    private function checkModuleAccess(): void
    {
        $module = Request::getModule();

        if ($module === 'Login' || $module === 'Bot') {
            // К модулям Login и Bot доступ есть всегда, не проверяем User::isAuth().
            // Модуль Login понятно почему - должен отображать форму для не зарегистрированных пользователей
            // Модуль Bot имеет свою схему авторизации через $_GET['telegramid'], поэтому на данном этапе
            // пользователь не авторизован и это нормально - пропускаем его внутрь модуля Bot
            return;
        }

        if (!User::isAuth()) {
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

    public static function setParam($name, $value): void
    {
        $data = [
            'CreatedBy' => User::getId(),
            'CreatedDateTime' => date('Y-m-d H:i:s'),
            'Name' => $name,
            'Value' => $value,
        ];
        // Используем insert on duplicate key update (обновляем CreatedBy, CreatedDateTime, Value)
        Db::insertRow($data, 'system_param', false, false, ['CreatedBy', 'CreatedDateTime', 'Value']);
    }

    public static function getParam($name)
    {
        return Db::getOne("SELECT Value FROM system_param WHERE Name=" . Db::escString($name));
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

    private function initDb(): void
    {
        //Инициализируем singleton объект базы
        Db::connect();
    }

    public static function getAutolaunchFullClassName(string $cliName)
    {
        if (str_starts_with($cliName, 'Light/')) {
            // Если имя Autolaunch скприта начинается с Light/, значит будем искать соответсвующий класс в папке движке
            return 'Light\\Autolaunch\\' . str_replace('Light/', '', $cliName);
        }

        // Это какой-то Autolaunch из проекта
        return PROJECT_NAMESPACE . "\\Autolaunch\\$cliName";
    }

    public function runAutolaunchCli(): void
    {
        //Попробуем запустить этот Autolaunch скрипт
        $autolaunchCliName = $GLOBALS['argv'][1] ?? null;
        if (!$autolaunchCliName) {
            print "Autolaunch name not provided";
            exit(1);
        }

        $this->init();

        $className = self::getAutolaunchFullClassName($autolaunchCliName);

        /** @var AutolaunchAbstract $autolaunchObj */
        $autolaunchObj = new $className;
        $autolaunchObj->proceedAutomaticRun();

        $this->finalize();
    }

    public function __invoke(): void
    {
        // Объект PSR-7 Request не передаём в явном виде - он будет создан fromGlobals()
        // В Codeception окружении метод handleHttpRequest() запускается с явной передачей PSR-7 Request
        $psr7Response = $this->handleHttpRequest();

        $this->beforeSend();

        $this->emitResponse($psr7Response);

        $this->finalize();
    }

    public function handleHttpRequest(ServerRequestInterface $psr7Request = null): ResponseInterface
    {
        if ($psr7Request !== null) {
            Request::$psr7 = $psr7Request;
        } else {
            Request::fromGlobals();
        }

        Response::new();

        //В качестве обработчика сессий используем самописный класс реализующий интерфейс SessionHandlerInterface
        //http://php.net/manual/en/class.sessionhandlerinterface.php
        //В этом самописном классе будем сохранять сессии в базу данных в таблицу phpsession
        if (!defined("CODECEPTION_FUNCTIONAL_TEST")) {
            session_set_save_handler(new Session);
            session_start();
        }

        try {

            // Пытаемся инициализировать пользователя на основе данных сессии
            User::loadFromSession();

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
            $resultFromModule = $this->proceedModule();

            if (Request::isAjax()
                || Request::isXls()
                || !Response::haveMasterTemplate()
                || Response::isRedirected()) {


            } elseif (\is_string($resultFromModule) && $resultFromModule !== '') {

                Intro\IntroManager::show();

                Response::renderMasterTemplate($resultFromModule);
                $resultFromModule = null;

            }

        } catch (ForceSendResponse $e) {

            // Внутри модуля (внутри proceedModule()) могло быть выброшено специальное исключение
            // для раннего завершения работы
            $resultFromModule = $e->getResultFromModule();

        } catch (\Throwable $e) {

            if (Config::$debug) {

                // В режиме отладки выбрасываем исключение до конца, чтобы отобразить его на экране
                throw $e;

            } else {

                // В Production режиме логируем и показываем пользователю лишь кратое сообщение
                ErrorHandler::reportException($e);
                $resultFromModule = $e->getMessage();
            }

        }

        $finalPsr7Response = $this->getFinalPsr7Response($resultFromModule);

        // Сессию нужно записать и закрыть обязательно до отправки ответа
        // Но после формирования $finalPsr7Response (в момент формирования flash сообещения могут быть очищены и это правильно)
        if (session_id() && !self::isCli()) {
            session_write_close();
        }

        // Записываем все накопившиеся flash сообщения в таблицу user_flash
        Session::writeFlash();

        // Возвращаем PSR-7 ответ
        // Функция вызывавшая handleHttpRequest() может этот ответ либо отправить в браузер
        // либо проверить в Codeception
        // либо передать ещё куда-то (если, например, используется ReactPHP / RoadRunner)
        return $finalPsr7Response;
    }

    /**
     * Запускает модуль (происходит автоматический поиск в папке Module в проекте, потом в Light/Module)
     * @return null|string|array|ResponseInterface
     */
    private function proceedModule()
    {
        $module = Request::getModule();
        $class = ModuleRegister::getModuleClass($module);

        // Модуль внутри себя может делать print, поэтому оборачиваем в буфер вывода
        ob_start();
        $response = $this->proceedModuleClass($class);
        $output = ob_get_clean();

        if ($response !== null) {
            // Ответ из модуля получен в явном виде - возвращаем именно его
            return $response;
        }

        if ($output !== '') {
            // Ответ из модуля получен в неявном виде в результате различных print операций - возвращаем этот текст
            return $output;
        }

        // Ответа из модуля нет - значит он писал непосредственно в Response, пока вернём null
        return null;
    }

    private function proceedModuleClass(string $class)
    {
        $proceedStatic = Request::getQueryParam('ps');
        if ($proceedStatic) {
            return $this->proceedModuleStatic($class, $proceedStatic);
        }

        $autocompleteMethod = Request::getQueryParam('ac');
        if ($autocompleteMethod) {
            return $this->proceedModuleAutocomplete($class, $autocompleteMethod);
        }

        $module = new $class();
        $proceedMethod = Request::getQueryParam('p');
        if ($proceedMethod) {
            // Если в url был параметр p, значит нужно запустить особый метод proceed внутри этого класса
            if (stripos((string) $proceedMethod, 'post') === 0
                && !Request::isPost()
            ) {
                // функции, которые начинаются со слова post разрешено вызывать только
                // методом POST. Это функции которые меняют что-то в базе,
                // и они обязательно должны вызываться как POST!
                Response::abort(403, 'Only POST is allowed for method proceed' . $proceedMethod);
            }

            $fillProceedMethod = 'proceed' . ucfirst((string) $proceedMethod);
            return $module->{$fillProceedMethod}();
        }

        return $module();
    }

    private function proceedModuleStatic(string $class, string $proceedStatic)
    {
        //В параметре ps можно передать имя статического модуля,
        // в этом случае объект класса модуля не создаётся,
        // а у казанный в ps метод вызывается статически
        if (stripos($proceedStatic, 'post') === 0
            && !Request::isPost()
        ) {
            // функции, которые начинаются со слова post разрешено вызывать только
            // методом POST. Это функции которые меняют что-то в базе,
            // и они обязательно должны вызываться как POST!
            Response::abort(403, 'Only POST is allowed for method proceed' . $proceedStatic);
        }

        return call_user_func($class . '::proceed' . ucfirst($proceedStatic));
    }

    private function proceedModuleAutocomplete(string $class, string $autocompleteMethod)
    {
        //Специальный метод для Autocomplete - вызывается статически и возвращает массив для автокомплита
        //Массив двухмерный: проиндексированный массив со строками и внутри ассоциативный массив с полями
        //важное поле во вложенном массиве - 'value' - оно будет подставлено в input средствами JS
        $value = trim((string) Request::getQueryParam('term'));
        return call_user_func($class . '::ac' . ucfirst($autocompleteMethod), $value);
    }

    public static function getWorkingTime()
    {
        return self::$timer->get();
    }

    /**
     * Этот метод вызывается для раннего завершения работы приложения
     * В итоге он выбрасывает исключение ForceSendRequest
     *
     * Но лучше не использовать раннее завершение, а возвращать результат работы модуля явно
     * с помощью return из всех функций контроллера
     *
     * @param $resultFromModule
     * @throws ForceSendResponse
     *
     */
    public static function terminate($resultFromModule = null): void
    {
        // Проверим, возможно есть дополнительные буферы вывода, которые нужно прочитать и приконкатинировать в $resultFromModule?
        while (ob_get_level()) {
            $additionalOutput = ob_get_clean();
            if ($additionalOutput) {
                // Конкатенацию делаем только если $resultFromModule был null или строкой изначально
                if ($resultFromModule === null) {
                    $resultFromModule = '';
                }
                if (\is_string($resultFromModule)) {
                    $resultFromModule .= $additionalOutput;
                }
            }
        }

        throw new ForceSendResponse($resultFromModule);
    }

    /**
     * @param null|string|array|ResponseInterface $resultFromModule
     * @return ResponseInterface
     */
    public function getFinalPsr7Response($resultFromModule): ResponseInterface
    {
        if ($resultFromModule instanceof ResponseInterface) {

            // Пришел конкретный PSR-7 Response - его и будем отправлять
            return $resultFromModule;

        }

        // Конкретного PSR-7 Response в явном виде не получен, значит будем использовать тот,
        // что накоплен в статической переменной Response::$psr7
        if (\is_string($resultFromModule)) {

            Response::withBodyString($resultFromModule);

        } elseif (\is_array($resultFromModule)) {

            Response::withJson($resultFromModule);

        }

        if (Request::isAjax() && !Response::isRedirected()) {
            // Запрос пришел через ajax - отправим все накопившиеся flash сообщения обратно через ajax
            $messagess = Session::flashGetAllAndClear();
            if ($messagess) {
                $header = Session::$showAsToast ? 'x-light-toastmsg' : 'x-light-flashmsg';
                Response::withHeader($header, Misc::json($messagess));
            }
        }

        return Response::$psr7;
    }

    public function emitResponse($finalPsr7Response): void
    {
        // Если же не CLI, то нужно отправить с помощью эмиттера
        (new SapiEmitter())->emit($finalPsr7Response);
        flush();

        // https://secure.php.net/manual/en/function.fastcgi-finish-request.php
        // https://symfony.com/doc/current/components/http_kernel.html#the-kernel-terminate-event
        // https://github.com/symfony/symfony/blob/0aa4273b39eeb2b4184d4b62ab76bf490eef15ec/src/Symfony/Component/HttpFoundation/Response.php#L378
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    public function beforeSend(): void
    {
        $this->runHandlers($this->handlersBeforeSend);
    }

    public function finalize(): void
    {
        if (!isset($_REQUEST['nouserrefreshtime']) && !self::isCli() && User::isAuth()) {
            User::updateLastRefreshDateTime();
            LoggerCsv::logVisit();
        }

        // Обработка всех хендлеров afterSend может быть долгой.
        // Перед её началом сразу сбросим накопившиеся сообщения в dblog
        DbLog::flush();

        $this->runHandlers($this->handlersAfterSend);

        // Все новые сообщения для dblog, которые будут накоплены в процессе afterSend буду
        // автоматически записаны в обработчике register_shutdown_function (см. DbLog.php)

        // Если установлен специальный cookie, то запишем логи об этом sql запросе
        // Запишем логи об ошибке в SQL запросе в CSV файл
        if (isset($_COOKIE['sqllog']) && $_COOKIE['sqllog']) {
            LoggerCsv::logSqlQueries();
        }
    }

    private function runHandlers(array $handlers): void
    {
        foreach ($handlers as $handler) {
            try {
                $handler();
            } catch (\Throwable $e) {
                ErrorHandler::reportException($e);
            }
        }
    }

    public static function onBeforeSend(callable $handler): void
    {
        self::$app->handlersBeforeSend[] = $handler;
    }

    public static function onAfterSend(callable $handler): void
    {
        self::$app->handlersAfterSend[] = $handler;
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
