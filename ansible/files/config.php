<?php
use Light\Config;

Config::$mysqlHost = '{{ mysql_host }}';
Config::$mysqlUser = '{{ mysql_user }}';
Config::$mysqlPassword = '{{ mysql_pass }}';
Config::$mysqlDb = '{{ mysql_db }}';
Config::$mysqlPort = '3306';
Config::$title = 'PLS';
Config::$filesfolderLocal = '{{ filesfolder }}';

Config::$sentry = [
    'dns' => '{{ sentry_dns }}',
    'dns_js' => '{{ sentry_dns_js }}',
    'options' => [
        'environment' => 'production'
    ]
];

Config::$localUrl = '{{ url }}';

Config::$debug = false;
Config::$jslog = false;

Config::$wkhtmltopdfPath = 'wkhtmltopdf';
Config::$ghostScriptPath = 'gs';
Config::$imageMagickPath = 'convert';

Config::$mailFrom = '{{ mail_from }}';
Config::$swiftmailerHost = '{{ mail_host }}';
Config::$swiftmailerPort = '{{ mail_port }}';
Config::$swiftmailerSecuriy = '{{ mail_security }}';
Config::$swiftmailerUser = '{{ mail_user }}';
Config::$swiftmailerPassword = '{{ mail_pass }}';

\Forward\ForwardConfig::$path7z = '7z';
\Forward\ForwardConfig::$onecFtpFolderBase = '{{ ftpfolder }}';
\Forward\ForwardConfig::$vinvoiceZipPass = '{{ vinvoice_zip_pass }}';
\Forward\ForwardConfig::$onecIp = '{{ vpnbuh_onec_ip }}';
