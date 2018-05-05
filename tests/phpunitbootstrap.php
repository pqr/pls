<?php
require __DIR__ . '/../vendor/autoload.php';

define('PROJECT_NAMESPACE', 'Forward');
// require __DIR__ . '/../src/Light/Autoloader.php';

include __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../src/Light/ApplicationAbstract.php';

\Light\Db::connect(
    \Light\Config::$mysqlHost,
    \Light\Config::$mysqlUser,
    \Light\Config::$mysqlPassword,
    \Light\Config::$mysqlDb,
    \Light\Config::$mysqlPort
);

//include __DIR__ . '/../src/Light/ErrorHandler.php';
