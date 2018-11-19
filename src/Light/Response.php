<?php

namespace Light;

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

    public static function reset()
    {
        self::$pageTitle = '';
        self::$masterTemplate = 'Master.tpl.php';
        self::$pageHeadHtml = '';
        self::$pageHeadRightHtml = '';
        self::$jsdata = [];
        self::$printMainmenu = true;
        self::$isRedirected = false;
        self::$headers = [];
    }

    public static function header($header)
    {
        self::$headers[] = $header;
    }

    public static function getHeaders()
    {
        return self::$headers;
    }

    /**
     * Перенаправляет пользователя на другую страницу и завершает приложение
     * @param string $module
     * @param array $params
     * @param bool $blank
     */
    public static function redirect(string $module, array $params = [], $blank = false)
    {
        $url = Link::create($module)->params($params)->getHref();
        self::redirectUrl($url, $blank);
    }

    public static function redirectToFilesFolder(string $relativePath)
    {
        $url = FilesFolder::getUrl($relativePath);
        self::redirectUrl($url);
    }

    public static function redirectUrl($url, $blank = false)
    {
        self::$isRedirected = true;
        if (Request::isAjax()) {
            Response::header('HTTP/1.1 200');
            if ($blank) {
                Response::header('x-light-redirect-blank: ' . $url);
            } else {
                Response::header('x-light-redirect: ' . $url);
            }
        } else {
            Response::header('Location: ' . $url);
        }
        ApplicationAbstract::terminate();
    }

    public static function redirectWithPost(string $module, array $params = null, array $postData = null, $blank = false)
    {
        self::$isRedirected = true;
        $link = Link::create($module);
        if ($params) {
            $link->params($params);
        }
        $url = $link->getHref();
        if (Request::isAjax()) {
            Response::header('x-light-redirect-post: ' . $url);
            print json_encode($postData);
        } else {
            print '<form action="' . $url . '" method="post"' . ($blank ? ' target="_blank"' : '') . ' id="redirectpost">';
            print Template::csrfHidden();
            foreach ($postData as $key => $val) {
                print '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($val) . '">';
            }
            print '</form>';
            print '<script>document.getElementById("redirectpost").submit();</script>';
        }
        ApplicationAbstract::terminate();
    }

    public static function redirectToList(string $module, array $filters = null)
    {
        $postData = [];
        if ($filters) {
            $postData['filterValues'] = json_encode($filters);
        }
        self::redirectWithPost($module, null, $postData);
    }

    public static function sendHeaderJsReload()
    {
        self::$isRedirected = true;
        Response::sendHeaderJsEval('location.reload()');
    }

    public static function sendHeaderJsReloadTable($tableName, array $additionalData = [])
    {
        $reloadDataJs = $additionalData ? json_encode($additionalData) : '';
        Response::sendHeaderJsEval('Light.table.get(' . json_encode($tableName) . ').reload(' . $reloadDataJs . ')');
    }

    public static function isRedirected()
    {
        return self::$isRedirected;
    }

    public static function abort($status, $msg = null)
    {
        Response::header('HTTP/1.1 ' . $status);
        print $msg;
        ApplicationAbstract::terminate();
    }

    public static function sendErrorMsg($msg)
    {
        if (Request::isAjax()) {
            Response::showDialog($msg);
            Response::abort(400);
        } else {
            Session::flashError($msg);
        }
    }

    public static function json($data, $status = null)
    {
        if ($status !== null) {
            Response::header('HTTP/1.1 ' . $status);
        }
        Response::header("Content-type: application/json");
        print json_encode($data, JSON_UNESCAPED_UNICODE);
        ApplicationAbstract::terminate();
    }

    public static function sendHeaderFlashMsg($type, $msg)
    {
        $messages = [['type' => $type, 'msg' => $msg]];
        Response::header('x-light-flashmsg: ' . json_encode($messages));
    }

    public static function sendToast(string $type, string $msg)
    {
        $messages = [['type' => $type, 'msg' => $msg]];
        Response::header('x-light-toastmsg: ' . json_encode($messages));
    }

    public static function sendToastMessagess(array $messages)
    {
        Response::header('x-light-toastmsg: ' . json_encode($messages));
    }

    public static function sendFlashMssagesInBody(array $messages)
    {
        Response::header('x-light-flashmsg-body: yes');
        print json_encode($messages);
        ApplicationAbstract::terminate();
    }

    public static function sendHeaderJsEval($jsCode)
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
        $afterClose = null
    ) {
        Response::header('x-light-showdialog: showdialog');

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

        print json_encode([
            'html' => $html,
            'dialogOptions' => $dialogOptions
        ]);
        ApplicationAbstract::terminate();
    }

    public static function haveMasterTemplate()
    {
        return (bool)self::$masterTemplate;
    }

    public static function setMasterTemplate($masterTemplate)
    {
        self::$masterTemplate = $masterTemplate;
    }

    public static function getMasterTemplate()
    {
        return self::$masterTemplate;
    }

    public static function disableMainmenu()
    {
        self::$printMainmenu = false;
    }

    public static function isMainmenuEnabled()
    {
        return self::$printMainmenu;
    }

    public static function setPageTitle($title)
    {
        self::$pageTitle = strip_tags($title);
    }

    public static function getFullTitle()
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

    public static function setPageHeadHtml($txt)
    {
        self::$pageHeadHtml = $txt;
    }

    public static function setPageHeadRightHtml($rightHtml)
    {
        self::$pageHeadRightHtml = $rightHtml;
    }

    public static function getPageHeadHtml()
    {
        return self::$pageHeadHtml;
    }

    public static function getPageHeadRightHtml()
    {
        return self::$pageHeadRightHtml;
    }

    public static function addJsData(array $jsdata)
    {
        self::$jsdata = array_merge(self::$jsdata, $jsdata);
    }

    public static function getJsData()
    {
        return self::$jsdata;
    }

    public static function streamFileFromContent(string $content, string $downloadFilename)
    {
        $extension = pathinfo($downloadFilename, PATHINFO_EXTENSION);
        switch (mb_strtolower($extension, 'UTF-8')) {
            case 'pdf':
                $contentType = 'application/pdf';
                break;

            case 'doc':
                $contentType = 'application/msword';
                break;

            case 'docx':
                $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                break;

            case 'xlsx':
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;

            case 'xls':
                $contentType = 'application/vnd.ms-excel';
                break;

            case 'gif':
                $contentType = 'image/gif';
                break;

            case 'png':
                $contentType = 'image/png';
                break;

            case 'jpg':
                $contentType = 'image/jpg';
                break;

            case 'zip':
                $contentType = 'application/zip';
                break;

            case 'json':
                $contentType = 'application/json';
                break;

            default:
                $contentType = 'text/plain';
        }

        Response::header('Content-Type: ' . $contentType);
        Response::header('Content-Length: ' . strlen($content));
        Response::header('Content-Disposition: attachment;filename*=UTF-8\'\'' . rawurlencode(File::getValidFilename($downloadFilename)));
        Response::header('Cache-Control: max-age=0');

        print $content;

        Response::setMasterTemplate(null);
    }

    public static function streamFileFromFilesystem(string $filepathAbs, string $downloadFilename = null, bool $preview = false)
    {
        $filepathAbsEnc = File::encode($filepathAbs);

        if (!$downloadFilename) {
            $downloadFilename = pathinfo($filepathAbsEnc, PATHINFO_BASENAME);
        }

        // используем стандартное расширенеи PHP, нужно включить php_fileinfo.dll
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $filepathAbsEnc);
        finfo_close($finfo);

        Response::header('Content-Type: ' . $contentType);
        Response::header('Content-Length: ' . filesize($filepathAbsEnc));
        if (!$preview) {
            Response::header('Content-Disposition: attachment;filename*=UTF-8\'\'' . rawurlencode(File::getValidFilename($downloadFilename)));
        }
        Response::header('Cache-Control: max-age=0');

        readfile($filepathAbsEnc);

        Response::setMasterTemplate(null);
    }
}
