<?php

namespace Light;

use Rollbar\Payload\Level;
use Rollbar\Rollbar;

class ErrorHandler
{
    private static $hasRollbar = false;
    private static $hasSentry = false;

    public static function init()
    {
        if (defined("CODECEPTION_FUNCTIONAL_TEST")) {
            return;
        }

        if (isset(Config::$rollbar['access_token']) && Config::$rollbar['access_token']) {
            self::initRollar();
        }

        if (Config::$sentry && Config::$sentry['dns']) {
            self::initSentry();
        }

        if (!self::$hasRollbar && !self::$hasSentry) {
            self::initCsv();
        }
    }

    public static function setUser(int $userId, string $username, string $email)
    {
        if (self::$hasRollbar) {
            Rollbar::configure([
                'person' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                ],
            ]);
        }

        if (self::$hasSentry) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($userId, $username, $email): void {
                $scope->setUser([
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                ]);
            });
        }
    }

    public static function reportSqlError($query, $errMsg)
    {
        if (self::$hasRollbar) {
            Rollbar::log(Level::ERROR, new \Exception('SQL: ' . $errMsg), ['query' => $query]);
        }

        if (self::$hasSentry) {
            \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($query, $errMsg): void {
                $scope->setExtra('query', $query);
                \Sentry\captureException(new \Exception('SQL: ' . $errMsg));
            });
        }

        if (!self::$hasRollbar && !self::$hasSentry) {
            LoggerCsv::logSqlError($query, $errMsg);
        }
    }

    public static function reportException(\Throwable $e, array $extra = [])
    {
        if (self::$hasRollbar) {
            Rollbar::log(Level::ERROR, $e, $extra);
        }
        if (self::$hasSentry) {
            self::reportSentryException($e, $extra);
        }

        if (!self::$hasRollbar && !self::$hasSentry) {
            self::recordCsv($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
        }
    }

    public static function reportError($message, array $extra = [])
    {
        if (self::$hasRollbar) {
            Rollbar::log(Level::ERROR, $message, $extra);
        }

        if (self::$hasSentry) {
            self::reportSentryException(new \Exception($message), $extra);
        }
    }

    private static function reportSentryException(\Throwable $e, array $extra = [])
    {
        \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($e, $extra): void {
            if ($extra) {
                foreach ($extra as $key => $val) {
                    $scope->setExtra($key, $val);
                }
            }
            \Sentry\captureException($e);
        });
    }

    private static function initRollar()
    {
        $config = Config::$rollbar;
        $config += [
            'allow_exec' => false, // Чтобы Rollbar не делал exec() с запуском git для получения хэша коммита,
            'code_version' => ApplicationAbstract::getVersion(),
            'include_error_code_context' => true,
            'include_exception_code_context' => true,
            'include_raw_request_body' => true,
            'root' => LIGHT_DIR_ROOT,
            'send_message_trace' => true,
        ];

        Rollbar::init($config);

        self::$hasRollbar = true;
    }

    private static function initSentry()
    {
        $options = Config::$sentry['options'] ?? [];
        $options += [
            'dsn' => Config::$sentry['dns'],
            'release' => ApplicationAbstract::getVersion(),
            'attach_stacktrace' => true,
        ];
        \Sentry\init($options);

//        $options = Config::$sentry['options'] + [
//                'release' => ApplicationAbstract::getVersion(),
//                'app_path' => LIGHT_DIR_ROOT . '/src',
//                'prefixes' => [LIGHT_DIR_ROOT],
//            ];
        self::$hasSentry = true;
    }

    private static function initCsv()
    {
        //Используем старую добрую схему с запись ошибок в csv файлы
        set_error_handler(self::class . '::recordCsv', E_ALL & ~E_DEPRECATED);

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
                        'Description',
                    ];

                    $data = [
                        date('Y-m-d H:i:s'),
                        User::getId(),
                        User::getName(),
                        Request::getModule(),
                        $_SERVER['QUERY_STRING'] ?? '',
                        $lastError['file'],
                        $lastError['line'],
                        $lastError['message'],
                    ];

                    require_once __DIR__ . '/LoggerCsv.php';
                    require_once __DIR__ . '/File.php';
                    LoggerCsv::write('fatalerror', $header, $data);
                }
            }
        );
    }

    public static function recordCsv($errno, $errstr, $errfile, $errline)
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
            'Description',
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
            $errstr,
        ];

        //Когда проваливаемся в ошбику - иногда перестаёт работать Autoloader
        require_once __DIR__ . '/LoggerCsv.php';
        require_once __DIR__ . '/File.php';
        LoggerCsv::write('error', $header, $data);

        //The error handler must return FALSE to populate $php_errormsg
        //http://www.php.net/manual/en/function.set-error-handler.php
        return false;
    }

}
