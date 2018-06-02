<?php

namespace Light\Module\Home;

use Light\HomeBase\HomeBlocksPage;
use Light\Js;
use Light\Request;
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

        } elseif (Request::isAjax()) {

            // Это ajax запрос за реальную загрузку контента для Home
            $this->printBody();

        } else {

            // Первоначально рендерим только шапку
            Response::setPageTitle('Home');
            $this->printHeader();
            print '<div id="homecontent"></div>';

            // Реальный контент будет загружен асинхронно отдельным ajax запросом
            Js::addLight('Home.js');

        }
    }
}
