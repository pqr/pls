<?php
use Light\Config;

Config::$mysqlHost = 'localhost';
Config::$mysqlUser = 'root';
Config::$mysqlPassword = 'test';
Config::$mysqlDb = 'pls_test';
Config::$mysqlPort = '3306';
Config::$title = 'TEST';
Config::$filesfolderLocal = dirname(__DIR__) . '/uploads';
Config::$filesfolder = '';
