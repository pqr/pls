<?php
// Конфиг подготовленный для запуска тестов на bitbucket-pipelines
// Перед запуском тестов этот файл будет переименован в config.php
use Light\Config;

Config::$mysqlHost = '127.0.0.1';
Config::$mysqlUser = 'root';
Config::$mysqlPassword = 'test';
Config::$mysqlDb = 'pls_test';
Config::$mysqlPort = '3306';
Config::$title = 'PLS TEST';
Config::$filesfolderLocal = dirname(__DIR__) . '/uploads';
