<?php
namespace Light\Codeception;

use Codeception\Lib\Connector\Universal;

use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;

class LightConnector extends Universal
{
    /**
     * @param Request $request
     * @return Response
     */
    public function doRequest($request)
    {
        if ($this->mockedResponse) {
            $response = $this->mockedResponse;
            $this->mockedResponse = null;
            return $response;
        }

        $_COOKIE = $request->getCookies();
        $_SERVER = $request->getServer();
        $_FILES = $this->remapFiles($request->getFiles());

        $uri = str_replace('http://localhost', '', $request->getUri());

        $_REQUEST = $this->remapRequestParameters($request->getParameters());
        if (strtoupper($request->getMethod()) == 'GET') {
            $_GET = $_REQUEST;
            $_POST = [];
        } else {
            $_POST = $_REQUEST;
            $_GET = [];
        }

        $uriQuery = ltrim(parse_url($request->getUri(), PHP_URL_QUERY), '?');
        parse_str($uriQuery, $params);
        foreach ($params as $k => $v) {
            $_GET[$k] = $v;
            $_REQUEST[$k] = $v;
        }

        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['QUERY_STRING'] = $uriQuery;
        $_SERVER['SCRIPT_NAME'] = 'index.php';

        $_SESSION['lightauth'] = 'admin';
        $_SESSION['csrf_token'] = 'abc';

        try {
            $appClass = PROJECT_NAMESPACE .'\\Application';
            $app = new $appClass;
            $app();
        } catch (TestResponseException $e) {
            $content = $e->getMessage();
        }

        $_SESSION = [];
        $_FILES = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_REQUEST = [];

        $headers = [];
        $status = 200;
        $php_headers = \Light\Response::getHeaders();
        foreach ($php_headers as $value) {
            if (strpos($value, 'HTTP/1.1 ') === 0) {
                $status = substr($value,9, 3);
            }
            // Get the header name
            $parts = explode(':', $value);
            if (count($parts) > 1) {
                $name = trim(array_shift($parts));
                // Build the header hash map
                $headers[$name] = trim(implode(':', $parts));
            }
        }
        $headers['Content-type'] = isset($headers['Content-type'])
            ? $headers['Content-type']
            : "text/html; charset=UTF-8";

        $response = new Response($content, $status, $headers);
        return $response;
    }

}
