<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_SERVER['HTTP_USER_AGENT'])) {
    // Убираем из HTTP_USER_AGENT признак OS X, чтобы у Мишы вместо нативных папок smb:// заработали папки через elFinder
    $_SERVER['HTTP_USER_AGENT'] = str_replace('OS X', '', $_SERVER['HTTP_USER_AGENT']);
}

include __DIR__ . '/../vendor/autoload.php';

define('PROJECT_NAMESPACE', 'Forward');
include __DIR__ . '/../src/Light/Autoloader.php';

include __DIR__ . '/../config/config.php';

$app = new Forward\Application;

if (isset($GLOBALS['argv'])) {
    $app->runAutolaunchCli();
} else {
    $app();
}