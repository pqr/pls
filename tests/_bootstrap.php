<?php
// Отдельный загрузичк для обобщённых классов тестов, которые являются базовыми для acceptance и functinal тестирования
use Light\Db;

spl_autoload_register(function ($class) {
    if (strpos($class, 'tests\\') === 0) {

        include dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    }
});

require __DIR__ . '/../vendor/autoload.php';

define('PROJECT_NAMESPACE', 'Forward');

include __DIR__ . '/../config/config.php';

// Включаем файл чтобы определить все констаны с директориями движка
include __DIR__ . '/../src/Light/ApplicationAbstract.php';

Db::connect();
