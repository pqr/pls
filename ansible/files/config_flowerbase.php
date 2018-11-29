<?php
use Light\Config;

Config::$mysqlHost = '{{ mysql_host_flowerbase }}';
Config::$mysqlUser = '{{ mysql_user }}';
Config::$mysqlPassword = '{{ mysql_pass }}';
Config::$mysqlDb = '{{ mysql_db_flowerbase }}';
Config::$mysqlPort = '3306';
Config::$title = 'Flowerbase';
Config::$filesfolderLocal = '{{ filesfolder_flowerbase }}';
Config::$lang = 'en';

Config::$sentry = [
    'dns' => '{{ sentry_dns }}',
    'dns_js' => '{{ sentry_dns_js }}',
    'options' => [
        'environment' => 'flowerbase'
    ]
];

Config::$localUrl = '{{ url_flowerbase }}';

Config::$debug = false;
Config::$jslog = false;

Config::$wkhtmltopdfPath = 'wkhtmltopdf';
Config::$ghostScriptPath = 'gs';
Config::$imageMagickPath = 'convert';

Config::$swiftmailerHost = '{{ mail_host }}';
Config::$swiftmailerPort = '{{ mail_port }}';
Config::$swiftmailerSecuriy = '{{ mail_security }}';
Config::$swiftmailerUser = '{{ mail_user }}';
Config::$swiftmailerPassword = '{{ mail_pass }}';

\Forward\ForwardConfig::$vinvoiceZipPass = '{{ vinvoice_zip_pass }}';
