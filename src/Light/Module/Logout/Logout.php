<?php
namespace Light\Module\Logout;

use Light\Request;
use Light\Response;

class Logout
{
    public function __invoke()
    {
        if (!Request::isPost()) {

            Response::abort(403, 'Logout should be POST');

        }

        session_destroy();
        Response::redirect('Login');

    }

}
