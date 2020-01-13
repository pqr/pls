<?php

namespace Light;

class Ico
{
    public static function i(string $cssClass, string $prefixClass = 'fa'): string
    {
        return "<i class=\"$prefixClass $cssClass\"></i>";
    }

    public static function copy(): string
    {
        return self::i('fa-copy', 'far');
    }

    public static function externalLink(): string
    {
        return self::i('fa-external-link-alt', 'fas');
    }

    public static function excel(): string
    {
        return self::i('fa-file-excel', 'far');
    }

    public static function plus(): string
    {
        return self::i('fa-plus', 'fas');
    }

    public static function upload(): string
    {
        return self::i('fa-upload', 'fas');
    }


    public static function uploadCloud(): string
    {
        return self::i('fa-cloud-upload-alt', 'fas');
    }

    public static function download(): string
    {
        return self::i('fa-download', 'fas');
    }

    public static function print(): string
    {
        return self::i('fa-print', 'fas');
    }

    public static function word(): string
    {
        return self::i('fa-file-word', 'far');
    }

    public static function pdf(): string
    {
        return self::i('fa-file-pdf', 'far');
    }

    public static function mail(): string
    {
        return self::i('fa-envelope icon-mail', 'far');
    }

    public static function mailRed(): string
    {
        return self::i('fa-envelope icon-red', 'far');
    }

    public static function mailBlue(): string
    {
        return self::i('fa-envelope icon-blue', 'far');
    }

    public static function mailGreen(): string
    {
        return self::i('fa-envelope icon-green', 'far');
    }

    public static function search(): string
    {
        return self::i('fa-search icon-search', 'fas');
    }

    public static function edit(): string
    {
        return self::i('fa-pencil-alt icon-edit');
    }

    public static function delete(): string
    {
        return self::i('fa-trash-alt icon-delete', 'far');
    }

    public static function info(): string
    {
        return self::i('fa-info-circle icon-info', 'fas');
    }

    public static function archive(): string
    {
        return self::i('fa-archive', 'fas');
    }

    public static function folder(bool $hasFiles): string
    {
        if ($hasFiles) {
            $cssClass = 'fa-folder icon-folderfull';
            $prefixClass = 'fas';
        } else {
            $cssClass = 'fa-folder icon-folderempty';
            $prefixClass = 'far';
        }
        return self::i($cssClass, $prefixClass);
    }
}
