<?php

namespace Light\Codeception;

use Codeception\Lib\Connector\Shared\PhpSuperGlobalsConverter;
use Light\ApplicationAbstract;
use Light\Css;
use Light\Js;
use Light\User;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\BrowserKit\AbstractBrowser;

class LightConnector extends AbstractBrowser
{
    use PhpSuperGlobalsConverter;

    /**
     * @param Request $request
     * @return Response
     * @throws \Throwable
     */
    public function doRequest($request)
    {
        $_COOKIE = $request->getCookies();
        $_SERVER = $request->getServer();
        $_FILES = $this->remapFiles($request->getFiles());

        $uri = str_replace('http://localhost', '', (string) $request->getUri());

        $_REQUEST = $this->remapRequestParameters($request->getParameters());
        if (strtoupper((string) $request->getMethod()) == 'GET') {
            $_GET = $_REQUEST;
            $_POST = [];
        } else {
            $_POST = $_REQUEST;
            $_GET = [];
        }

        $uriQuery = ltrim(parse_url((string) $request->getUri(), PHP_URL_QUERY), '?');
        parse_str($uriQuery, $params);
        foreach ($params as $k => $v) {
            $_GET[$k] = $v;
            $_REQUEST[$k] = $v;
        }

        $_SERVER['REQUEST_METHOD'] = strtoupper((string) $request->getMethod());
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['QUERY_STRING'] = $uriQuery;
        $_SERVER['SCRIPT_NAME'] = 'index.php';

        $_SESSION['lightauth'] = 'admin';
        $_SESSION['csrf_token'] = 'abc';

        // Выставляем значения по умолчанияю во все статические переменные
        // внутри объекта Response (важно для функциональных тестов)
        User::resetCache();
        \Light\Request::fromGlobals();
        \Light\Response::new();
        Js::reset();
        Css::reset();

        $appClass = PROJECT_NAMESPACE . '\\Application';
        /** @var ApplicationAbstract $app */
        $app = new $appClass;
        $psr7Response = $app->handleHttpRequest(\Light\Request::$psr7);

        $_SESSION = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];

        $response = new Response($psr7Response->getBody(), $psr7Response->getStatusCode(), $psr7Response->getHeaders());
        return $response;
    }

}
