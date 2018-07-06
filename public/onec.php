<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../vendor/autoload.php';

define('PROJECT_NAMESPACE', 'Forward');
// include __DIR__ . '/../src/Light/Autoloader.php';

include __DIR__ . '/../config/config.php';

$app = new Forward\Application;

$importModule = new \Forward\Classes\ImportFromOneC\ImportFromOneC();
$importModule();

\Light\ApplicationAbstract::terminate();
