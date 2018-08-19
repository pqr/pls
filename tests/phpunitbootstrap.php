<?php
require __DIR__ . '/../vendor/autoload.php';

define('PROJECT_NAMESPACE', 'Forward');

include __DIR__ . '/../config/config.php';

// Чтобы загрузить глобкальные Define
require_once __DIR__ . '/../src/Light/ApplicationAbstract.php';

\Light\Db::connect();

