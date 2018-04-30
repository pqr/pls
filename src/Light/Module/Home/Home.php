<?php

namespace Light\Module\Home;

use Light\HomeBase\HomeBlocksPage;
use Light\Response;
use Light\User;

class Home
{
    /**
     * Можно переопределить в классе потомке в проекте, чтобы добавить что-то своё
     */
    protected function printHeader()
    {
        $headTxt = 'Welcome, ';
        $headTxt .= '<span id="home-username">' . User::getName() . '</span>';
        $headTxt .= '<span id="home-userright"> (' . User::getGroupName() . ')</span>';
        Response::setPageHeadHtml($headTxt);
    }

    /**
     * Можно переопределить в классе потомке в проекте, чтобы добавить что-то своё
     */
    protected function printBody()
    {
        HomeBlocksPage::printHomeBlocks(User::getId());
    }

    public function __invoke()
    {
        if (isset($_GET['class'])) {

            HomeBlocksPage::proceedOneHomeBlockClass(User::getId(), $_GET['class']);

        } else {

            Response::setPageTitle('Home');
            $this->printHeader();
            $this->printBody();

        }
    }
}
