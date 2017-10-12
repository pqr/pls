<?php

namespace Forward\Module\Block;

use Forward\Application;
use Light\Db;
use Light\Response;
use Light\Session;

class Block
{
    public function __invoke()
    {
        $title = 'Block Base';
        Response::setPageTitle($title);
        Response::setPageHeadHtml($title);

        print '<p>Here you can block the database. To unblock it you need to visit special address (copy the link into safa place) and enter your Login/Password:<br>';
        print '<textarea style="width: 100%">https://forwardlog.net/?a=Login&next=' . rawurlencode('a=Block&ps=Unblock') . '</textarea>';
        print '<p>';

        print '<a href="?a=Block&ps=Block" class="btn bgcolor-btn-danger">Block Database</a>';
    }

    public static function proceedBlock()
    {
        Application::setParam('blocked', '1');
        Db::query("UPDATE user SET SessionID=''");
        session_destroy();
        Response::redirectUrl('http://yandex.ru');
    }

    public static function proceedUnblock()
    {
        Application::setParam('blocked', '0');
        Session::flashSuccess('Unbloked');
        Response::redirect('Home');
    }
}
