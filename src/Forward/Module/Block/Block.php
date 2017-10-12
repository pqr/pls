<?php
namespace Forward\Module\Block;

use Forward\Application;
use Light\Request;
use Light\Response;
use Light\Session;
use Light\User;

class Block
{
    public function __invoke()
    {
        $title = 'Hide/Show Offshore';
        Response::setPageTitle($title);
        Response::setPageHeadHtml($title);

        $blocked = (bool)Application::getParam('blocked');
        print '<p>Current offshore status: ' . ($blocked ? '<strong class=bad>HIDDEN</strong>' : '<span class=good>available</span>') . '</p>';

        if ($blocked) {
            print '<p><a href="?a=Block&ps=Unblock" class="good">Click here to make available again >>></a></p>';
            } else {
            print '<a href="?a=Block&ps=Block" class="btn bgcolor-btn-danger">Hide all Offshore info</a>';
        }
    }

    public static function proceedBlock()
    {
        if (User::hasWriteAccess()) {
            Application::setParam('blocked', '1');
            Session::flashSuccess('All Offshore information is now hidden');
        }
        Response::redirect(Request::getModule());
    }

    public static function proceedUnblock()
    {
        if (User::hasWriteAccess()) {
            Application::setParam('blocked', '0');
            Session::flashSuccess('Offshore information is available again');
        }
        Response::redirect(Request::getModule());
    }
}
