<?php
// Отдельный загрузичк для обобщённых классов тестов, которые являются базовыми для acceptance и functinal тестирования
spl_autoload_register(function ($class) {
    if (strpos($class, 'tests\\') === 0) {

        include dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    }
});

require __DIR__ . '/../vendor/autoload.php';

define('PROJECT_NAMESPACE', 'Forward');
// require __DIR__ . '/../src/Light/Autoloader.php';

include __DIR__ . '/../config/config.php';
