<?php

namespace Light;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

class Request
{
    /** @var ServerRequestInterface */
    public static $psr7;

    public static function fromGlobals(): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();

        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $serverRequest = $creator->fromGlobals();
        self::$psr7 = $serverRequest;

        return $serverRequest;
    }

    public static function getMehod()
    {
        if (!self::$psr7) {
            return null;
        }
        return self::$psr7->getMethod();
    }

    public static function isGet()
    {
        return self::getMehod() === 'GET';
    }

    public static function isPost()
    {
        return self::getMehod() === 'POST';
    }

    public static function getQueryParam(string $param)
    {
        if (!self::$psr7) {
            return null;
        }
        return self::$psr7->getQueryParams()[$param] ?? null;
    }

    public static function getServerParam(string $param)
    {
        if (!self::$psr7) {
            return null;
        }
        return self::$psr7->getServerParams()[$param] ?? null;
    }

    public static function isAjax(): bool
    {
        $requestedWith = self::getServerParam('HTTP_X_REQUESTED_WITH');
        return $requestedWith === 'XMLHttpRequest';
    }

    public static function isXls(): bool
    {
        return (bool)self::getQueryParam('xls');
    }

    public static function getModule(): string
    {
        return self::getQueryParam('a') ?? '';
    }
}
