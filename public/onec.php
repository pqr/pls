<?php

use Light\Exceptions\ForceSendResponse;
use Light\Request;
use Light\Response;

ini_set('display_errors', 1);
error_reporting(E_ALL);

include __DIR__ . '/../vendor/autoload.php';

define('PROJECT_NAMESPACE', 'Forward');
// include __DIR__ . '/../src/Light/Autoloader.php';

include __DIR__ . '/../config/config.php';

$app = new Forward\Application;
$resultFromModule = '';
try {
    Request::fromGlobals();
    Response::new();
    $importModule = new \Forward\Classes\ImportFromOneC\ImportFromOneC();
    $importModule();
    \Light\ApplicationAbstract::terminate();
} catch (ForceSendResponse $e) {
    // Внутри модуля (внутри proceedModule()) могло быть выброшено специальное исключение
    // для раннего завершения работы
    $resultFromModule = $e->getResultFromModule();
}

$finalPsr7Response = $app->getFinalPsr7Response($resultFromModule);

$app->beforeSend();

$app->emitResponse($finalPsr7Response);

$app->finalize();


