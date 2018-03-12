<?php

namespace Light;

//Проверим, задан ли конфиг для rollbar - там обяхательно должен быть access token
use Rollbar\Payload\Level;
use Rollbar\Rollbar;

if (isset(Config::$rollbar['access_token']) && Config::$rollbar['access_token']) {

    $config = Config::$rollbar;
    $config['person_fn'] = function () {
        return ['id' => (string)User::getId(), 'username' => User::getLogin()];
    };
    if (!isset($config['allow_exec'])) {
        // Чтобы Rollbar не делал exec() с запуском git для получения хэша коммита
        $config['allow_exec'] = false;
    }
    $config['code_version'] = ApplicationAbstract::getVersion();

    Rollbar::init($config);

} else {

    //Используем старую добрую схему с запись ошибок в csv файлы
    set_error_handler('Light\errorHandlerCsvLog', E_ALL & ~E_DEPRECATED);

    register_shutdown_function(
        function () {
            $lastError = error_get_last();
            if ($lastError !== null && ($lastError['type'] === E_ERROR || $lastError['type'] === E_USER_ERROR)) {

                $header = [
                    'DateTime',
                    'UserID',
                    'User Name',
                    'Module',
                    'Query String',
                    'File',
                    'Line',
                    'Description'
                ];

                $data = [
                    date('Y-m-d H:i:s'),
                    User::getId(),
                    User::getName(),
                    Request::getModule(),
                    $_SERVER['QUERY_STRING'] ?? '',
                    $lastError['file'],
                    $lastError['line'],
                    $lastError['message']
                ];

                require_once __DIR__ . '/CsvLog.php';
                require_once __DIR__ . '/File.php';
                CsvLog::write('fatalerror', $header, $data);
            }
        }
    );
}

function reportException(\Exception $e)
{
    if (Config::$rollbar) {
        Rollbar::log(Level::ERROR, $e);
    } elseif (Config::$sentry && Config::$sentry['dns']) {
        // TODO: сделать отправку в Sentry
        errorHandlerCsvLog($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    } else {
        errorHandlerCsvLog($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
    }
}

function errorHandlerCsvLog($errno, $errstr, $errfile, $errline)
{
    $header = [
        'DateTime',
        'UserID',
        'User Name',
        'Module',
        'Query String',
        'ErrNo',
        'File',
        'Line',
        'Description'
    ];

    $data = [
        date('Y-m-d H:i:s'),
        User::getId(),
        User::getName(),
        Request::getModule(),
        $_SERVER['QUERY_STRING'] ?? '',
        $errno,
        $errfile,
        $errline,
        $errstr
    ];

    //Когда проваливаемся в ошбику - иногда перестаёт работать Autoloader
    require_once __DIR__ . '/CsvLog.php';
    require_once __DIR__ . '/File.php';
    CsvLog::write('error', $header, $data);

    //The error handler must return FALSE to populate $php_errormsg
    //http://www.php.net/manual/en/function.set-error-handler.php
    return false;
}
