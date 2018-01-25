<?php
namespace Light;
/**
 * Класс содержащий все настройки в виде статических свойст - для переопределения в проекте
 */
class Config
{
    public static $mysqlHost;
    public static $mysqlUser;
    public static $mysqlPassword;
    public static $mysqlDb;
    public static $mysqlPort = 3306;

    public static $encryptPasswords = false;

    public static $localUrl = 'http://localhost';

    /** @var string дополнительное слово для строки заголовка, на development компьютере удобно установить в 'DEV' */
    public static $title;
    /** @var string сетевой путь до расшаренной папки */
    public static $filesfolder;
    /** @var string локальный путь до расшаренной папки */
    public static $filesfolderLocal;

    public static $apiSectetToken = 'fjkal333e23vfe4dwe';

    /** @var bool если установить этот флаг в true, то на ошибки sql будет выкиываться исключение (удобно для тестов) */
    public static $sqlErrorThrowException = false;

    /** @var bool если true, то будут использованы не сжатые debug версии js библиотек */
    public static $debug = true;

    /** @var bool $jslog если стоит true, то после загрузки страницы на сервер будет отправляться ajax запрос с информацией
     * о клиенте: версия браузера и разрешение экрана. Эти логи записываются в logs/jslog/ */
    public static $jslog = false;

    /** @var array конфигурационный массив для rollbar */
    public static $rollbar;

    /** @var string ключ доступ к okmeter */
    public static $okmeterKey;

    /** @var array конфигурационный массив для sentry.io */
    public static $sentry;

    /** @var array массив для внесения любых других параметров необходимых проекту */
    public static $projectParams = [];

    /** @var string путь к исполняемому файлу hg - нужно для модуля Console */
    public static $hgPath = 'hg';

    /** @var string путь к исполняемому файлу утилиты wkhtmltopdf, используемой для генерации PDF */
    public static $wkhtmltopdfPath = 'wkhtmltopdf';

    /** @var string путь к исполняемому файлу gswin64c.exe - используется для разбивки PDF на маленькие файлы и для перевода PDF в PNG */
    public static $ghostScriptPath = 'gsc';
    /** @var string путь к исполняемому файлу pdftk - используется для склейки нескольких PDF файлов https://www.pdflabs.com/tools/pdftk-server/ */
    public static $pdftkPath = 'pdftk';
    /** @var string путь к исполняемому файлу convert входящему в ImageMagic - используется для наложения картинки печати на PNG изображения и склеивания PNG в PDF */
    public static $imageMagickPath = 'convert';

    /** @var string путь к папке с бекапами - нужно для модуля Console */
    public static $backupFolder;

    /** @var string параметры для Swiftmailer */
    public static $mailFrom;
    public static $swiftmailerHost;
    public static $swiftmailerPort = 25;
    public static $swiftmailerSecuriy; //можно указать ssl
    public static $swiftmailerUser;
    public static $swiftmailerPassword;


    /** @var string имя пользователя linux из под которого запускаются cron задачи
     * это имя пользователя используется при формировании таблицы autolaunch, чтобы узнать, какие задачи запускаются
     * по расписанию с помощью команды shell_exec('sudo -u ad -S crontab -l');
     *
     * Чтобы это сработало у пользователя www-data должны быть права запускать sudo без пароля, для этого нужно из консоли
     * выполнить команду
     * sudo visudo
     * и добавить в открывшийся файл строку
     * www-data ALL=(ALL) NOPASSWD: ALL
     */
    public static $crontabUser = 'ad';

    public static $defaultDateFormat = 'd.m.Y';
    public static $defaultDateTimeFormat = 'd.m.Y H:i:s';
    public static $lang = 'ru';
    public static $showRatesInMenu = true;
    public static $useInputTypeDate = false;

}
