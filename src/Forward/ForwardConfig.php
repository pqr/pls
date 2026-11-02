<?php
namespace Forward;

class ForwardConfig
{
    public static $onecFtpFolderBase;
    public static $vinvoiceZipPass;
    public static $onecIp;
    public static $onecPort;
    /** Логин HTTP Basic Auth сервера 1С; пустая строка отключает авторизацию. */
    public static string $onecBasicAuthUsername = '';
    /** Пароль HTTP Basic Auth сервера 1С. */
    public static string $onecBasicAuthPassword = '';
    public static $onecAuthToken;
}
