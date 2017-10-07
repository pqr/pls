<?php
// Here you can initialize variables that will be available to your tests
\Light\Db::connect(
    \Light\Config::$mysqlHost,
    \Light\Config::$mysqlUser,
    \Light\Config::$mysqlPassword,
    \Light\Config::$mysqlDb,
    \Light\Config::$mysqlPort
);