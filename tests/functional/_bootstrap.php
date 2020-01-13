<?php
use Light\Config;

define('CODECEPTION_FUNCTIONAL_TEST', 1);

if (isset($_SERVER['argv'])) {
    foreach($_SERVER['argv'] as $i => $token) {
        if ($token === '--skip-group') {
            $nextToken = $_SERVER['argv'][$i + 1] ?? '';
            if ($nextToken === 'pdf') {
                // Отключаем генерацию PDF в глобальном конфиге приложения
                Config::$wkhtmltopdfPath = '';
                break;
            }
        }
    }
}
