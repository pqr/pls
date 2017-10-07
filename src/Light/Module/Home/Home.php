<?php
namespace Light\Module\Home;

use Light\HomeBase\HomeBlocksPage;
use Light\Request;
use Light\Response;
use Light\User;

class Home
{
    protected function printHomePage()
    {
        Response::setPageTitle('Home');
        $this->printHeader();
        $this->printBody();
    }

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

            if (Request::isXls()) {
                //Запрос отдельно взятого блока Home - это может быть только отправка в Excel!
                HomeBlocksPage::sendExcel(User::getId(), $_GET['class']);
            } else {
                //Запрос на обновление таблицы через ajax
                HomeBlocksPage::tableAjaxReload(User::getId(), $_GET['class']);
            }

        } else {

            $this->printHomePage();

        }
    }
}
