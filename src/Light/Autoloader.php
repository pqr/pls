<?php
function lightAutoload($class)
{
    static $srcDir;
    if (!$srcDir) {
        $srcDir = dirname(__DIR__);
    }
    if (strpos($class, 'Light\\') === 0 || strpos($class, PROJECT_NAMESPACE .'\\') === 0) {
        include $srcDir . '/' . str_replace('\\', '/', $class) . '.php';
    }
}

spl_autoload_register('lightAutoload', true, true);