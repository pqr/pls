<?php

namespace Light;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as PSR7Response;
use Psr\Http\Message\ResponseInterface;

class Response
{
    private static $pageTitle = '';
    private static $masterTemplate = 'Master.tpl.php';
    private static $pageHeadHtml = '';
    private static $pageHeadRightHtml = '';
    private static $jsdata = [];
    private static $printMainmenu = true;
    private static $isRedirected = false;
    private static $headers = [];

    /** @var ResponseInterface */
    public static $psr7;

    public static function new(): void
    {
        self::$pageTitle = '';
        self::$masterTemplate = 'Master.tpl.php';
        self::$pageHeadHtml = '';
        self::$pageHeadRightHtml = '';
        self::$jsdata = [];
        self::$printMainmenu = true;
        self::$isRedirected = false;
        self::$headers = [];

        self::$psr7 = new PSR7Response();
    }

    /**
     * @param $header
     * @deprecated use Response::withHeader($name, $value)
     */
    public static function header($header): void
    {
        if (str_contains((string) $header, ':')) {
            [$name, $value] = explode(':', (string) $header, 2);
            self::withHeader($name, $value);
        }
    }

    public static function withStatus(int $code, $reasonPhrase = ''): void
    {
        self::$psr7 = self::$psr7->withStatus($code, $reasonPhrase);
    }

    /**
     * @param string|string[] $value
     */
    public static function withHeader(string $name, $value): void
    {
        self::$psr7 = self::$psr7->withHeader($name, $value);
    }

    public static function renderMasterTemplate(string $content): void
    {
        $body = Template::parseTpl(
            self::$masterTemplate,
            ['output' => $content]
        );

        self::withBodyString($body);
    }

    public static function withBodyString($body): void
    {
        $psr17Factory = new Psr17Factory();
        $responseBody = $psr17Factory->createStream((string)$body);
        self::$psr7 = self::$psr7->withBody($responseBody);
    }

    public static function withJson($data): void
    {
        self::withHeader('Content-type', 'application/json');
        $string = Misc::json($data);
        self::withBodyString($string);
    }

    /**
     * Перенаправляет пользователя на другую страницу и завершает приложение
     * @param bool $blank
     */
    public static function redirect(string $module, array $params = [], $blank = false): void
    {
        $url = Link::create($module)->params($params)->getHref();
        self::redirectUrl($url, $blank);
    }

    public static function redirectToFilesFolder(string $relative): void
    {
        $url = FilesFolder::getUrl($relative);
        self::redirectUrl($url);
    }

    public static function redirectUrl($url, $blank = false): void
    {
        self::$isRedirected = true;
        if (Request::isAjax()) {
            $headerName = $blank ? 'x-light-redirect-blank' : 'x-light-redirect';
        } else {
            $headerName = 'Location';
        }
        Response::withHeader($headerName, $url);
        ApplicationAbstract::terminate();
    }

    public static function redirectWithPost(
        string $module,
        array $params = null,
        array $postData = null,
        $blank = false
    ): void {
        self::$isRedirected = true;
        $link = Link::create($module);
        if ($params) {
            $link->params($params);
        }
        $url = $link->getHref();

        if (Request::isAjax()) {

            Response::withHeader('x-light-redirect-post', $url);
            Response::withJson($postData);

        } else {

            $formHtml = '<form action="' . $url . '" method="post"' . ($blank ? ' target="_blank"' : '') . ' id="redirectpost">';
            $formHtml .= Template::csrfHidden();
            foreach ($postData as $key => $val) {
                $formHtml .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars((string) $val) . '">';
            }
            $formHtml .= '</form>';
            $formHtml .= '<script>document.getElementById("redirectpost").submit();</script>';

            Response::withBodyString($formHtml);

        }
        ApplicationAbstract::terminate();
    }

    public static function redirectToList(string $module, array $filters = null): void
    {
        $postData = [];
        if ($filters) {
            $postData['filterValues'] = Misc::json($filters);
        }
        self::redirectWithPost($module, null, $postData);
    }

    public static function sendHeaderJsReload(): void
    {
        self::$isRedirected = true;
        Response::sendHeaderJsEval('location.reload()');
    }

    public static function sendHeaderJsReloadTable($tableName, array $additionalData = []): void
    {
        $reloadDataJs = $additionalData ? Misc::json($additionalData) : '';
        Response::sendHeaderJsEval('Light.table.get(' . Misc::json($tableName) . ').reload(' . $reloadDataJs . ')');
    }

    public static function isRedirected(): bool
    {
        return self::$isRedirected;
    }

    public static function abort($status, $msg = null): void
    {
        self::withStatus($status);
        if ($msg) {
            self::withBodyString($msg);
        }
        ApplicationAbstract::terminate();
    }

    public static function sendErrorMsg($msg): void
    {
        if (Request::isAjax()) {
            Response::showDialog($msg);
            Response::abort(400);
        } else {
            Session::flashError($msg);
        }
    }

    public static function json($data, $status = null): void
    {
        if ($status !== null) {
            self::withStatus($status);
        }
        Response::withJson($data);
        ApplicationAbstract::terminate();
    }

    public static function sendHeaderFlashMsg($type, $msg): void
    {
        $messages = [['type' => $type, 'msg' => $msg]];
        Response::withHeader('x-light-flashmsg', Misc::json($messages));
    }

    public static function sendToast(string $type, string $msg): void
    {
        $messages = [['type' => $type, 'msg' => $msg]];
        Response::withHeader('x-light-toastmsg', Misc::json($messages));
    }

    public static function sendToastMessagess(array $messages): void
    {
        Response::withHeader('x-light-toastmsg', Misc::json($messages));
    }

    public static function sendFlashMssagesInBody(array $messages): void
    {
        Response::withHeader('x-light-flashmsg-body', 'yes');
        Response::withJson($messages);
        ApplicationAbstract::terminate();
    }

    public static function sendHeaderJsEval($jsCode): void
    {
        Response::header('x-light-jseval: ' . $jsCode);
    }

    public static function showDialog(
        $html,
        $buttonOkText = null,
        $buttonOkHandler = null,
        $buttonCancelText = null,
        array $buttons = null,
        $width = null,
        $height = null,
        $top = null,
        $afterClose = null,
        $afterOpen = null
    ): void {

        Response::withHeader('x-light-showdialog', 'showdialog');

        $dialogOptions = [];
        if ($buttonOkText !== null) {
            $dialogOptions['buttonOkText'] = $buttonOkText;
        }
        if ($buttonOkHandler !== null) {
            $dialogOptions['buttonOkHandler'] = $buttonOkHandler;
        }
        if ($buttonCancelText !== null) {
            $dialogOptions['buttonCancelText'] = $buttonCancelText;
        }
        if ($buttons !== null) {
            $dialogOptions['buttons'] = $buttons;
        }
        if ($width !== null) {
            $dialogOptions['width'] = $width;
        }
        if ($height !== null) {
            $dialogOptions['height'] = $height;
        }
        if ($top !== null) {
            $dialogOptions['top'] = $top;
        }
        if ($afterClose !== null) {
            $dialogOptions['afterClose'] = $afterClose;
        }
        if ($afterOpen !== null) {
            $dialogOptions['afterOpen'] = $afterOpen;
        }

        self::withJson([
            'html' => $html,
            'dialogOptions' => $dialogOptions,
        ]);

        ApplicationAbstract::terminate();
    }

    public static function haveMasterTemplate(): bool
    {
        return (bool)self::$masterTemplate;
    }

    public static function setMasterTemplate(?string $masterTemplate): void
    {
        self::$masterTemplate = $masterTemplate;
    }

    public static function disableMainmenu(): void
    {
        self::$printMainmenu = false;
    }

    public static function isMainmenuEnabled(): bool
    {
        return self::$printMainmenu;
    }

    public static function setPageTitle($title): void
    {
        self::$pageTitle = strip_tags((string) $title);
    }

    public static function getFullTitle(): string
    {
        $title = Config::$title;

        if (self::$pageTitle) {
            //Если у данной страницы был вручную опередлён дополнительный title - добавим его
            $title .= ' - ' . self::$pageTitle;

        } else {
            //Иначе попробуем добавить имя модуля
            $moduleName = ModuleRegister::getModuleName(Request::getMehod());
            if ($moduleName) {
                $title .= ' - ' . $moduleName;
            }
        }

        return $title;
    }

    public static function setPageHeadHtml($txt): void
    {
        self::$pageHeadHtml = $txt;
    }

    public static function setPageHeadRightHtml($rightHtml): void
    {
        self::$pageHeadRightHtml = $rightHtml;
    }

    public static function getPageHeadHtml(): string
    {
        return self::$pageHeadHtml;
    }

    public static function getPageHeadRightHtml(): string
    {
        return self::$pageHeadRightHtml;
    }

    public static function addJsData(array $jsdata): void
    {
        self::$jsdata = array_merge(self::$jsdata, $jsdata);
    }

    public static function getJsData(): array
    {
        return self::$jsdata;
    }

    public static function streamFileFromContent(string $content, string $downloadFilename): void
    {
        $extension = pathinfo($downloadFilename, PATHINFO_EXTENSION);
        $contentType = match (mb_strtolower($extension, 'UTF-8')) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'jpg' => 'image/jpg',
            'zip' => 'application/zip',
            'json' => 'application/json',
            default => 'text/plain',
        };

        Response::withHeader('Content-Type', $contentType);
        Response::withHeader('Content-Disposition',
            'attachment;filename*=UTF-8\'\'' . rawurlencode(File::getValidFilename($downloadFilename)));
        Response::withHeader('Cache-Control', 'max-age=0');

        Response::withBodyString($content);
    }

    public static function streamFileFromFilesystem(
        string $filepathAbs,
        string $downloadFilename = null,
        bool $preview = false
    ): void {
        if (!$downloadFilename) {
            $downloadFilename = pathinfo($filepathAbs, PATHINFO_BASENAME);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $filepathAbs);
        finfo_close($finfo);

        Response::withHeader('Content-Type', $contentType);

        $contentDisposition = '';
        if (!$preview) {
            $contentDisposition .= 'attachment;';
        }
        $contentDisposition .= 'filename*=UTF-8\'\'' . rawurlencode(File::getValidFilename($downloadFilename));
        Response::withHeader('Content-Disposition', $contentDisposition);

        Response::withHeader('Cache-Control', 'max-age=0');

        $psr17Factory = new Psr17Factory();
        $responseBody = $psr17Factory->createStreamFromFile($filepathAbs);
        self::$psr7 = self::$psr7->withBody($responseBody);
    }
}
