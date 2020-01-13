<?php


namespace Light\ModuleBase;


use Light\Config;
use Light\Ico;
use Light\Link;
use Light\ModuleRegister;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\Template;
use Light\User;

/**
 * Этот trait безусловно подключается к ModuleAbstract.
 * Код вынесен в этот trait исключительно чтобы облегчить файл ModuleAbstract.php
 */
trait ViewEditDialogsTrait
{
    /**
     * Удобная фнуция, которую можно вызвать сделав ajax get запрос по адресу ?a=<module>&id=...&func=Dialog#ajax
     * В итоге движок покажет диалоговое окно как бы в режиме View
     */
    protected function viewGetDialog()
    {
        if (!Request::isAjax()) {
            $this->redirectToView();
            return;
        }

        $this->_state = self::STATE_VIEW;

        $html = $this->getViewEditDialogHeader();

        // Добавляем кнопки подменю включая кнопки печати
        $this->initViewSubmenu();
        $this->initPrintForms();
        $html .= $this->getSubmenuHtml();
        $html .= $this->getViewEditDialogContent();

        // Отправляем в диалоговом окне
        Response::showDialog($html
            , null
            , null
            , Config::$lang === 'ru' ? 'Закрыть' : 'Close'
        );
    }

    /**
     * Удобная фнуция, которую можно вызвать сделав ajax get запрос по адресу ?a=<module>&id=...&func=DialogEdit#ajax
     * В итоге движок покажет диалоговое окно в режиме Edit
     */
    protected function viewGetDialogEdit()
    {
        if (!Request::isAjax()) {
            $this->redirectToView();
            return;
        }

        // Проверим права на запись текущего объекта
        if (!$this->allowEdit || !User::hasWriteAccess() || !$this->hasWriteAccessRightToObj($this->_obj)) {
            // Если прав на запись нет, то выведем диалог в режиме view
            $this->viewGetDialog();
            return;
        }

        $this->_state = self::STATE_EDIT;

        $html = $this->getViewEditDialogHeader();

        // Добавляем кнопки подменю включая кнопки печати
        $this->initPrintForms();
        $this->initViewSubmenu();
        $html .= $this->getSubmenuHtml();

        $formActionUrl = $this->getEditFromActionUrl();
        $html .= '<form action="' . $formActionUrl . '" id="edit-form" name="edit-form" onsubmit="return false;" method="post">';
        $html .= $this->getViewEditDialogContent();
        $html .= '</form>';

        // Отправляем в диалоговом окне
        $textSave = Config::$lang === 'ru' ? 'Сохранить' : 'Save';
        Response::showDialog($html, "$textSave $this->textSaveBtn");
    }

    protected function getViewEditDialogHeader(): string
    {
        $module = $this->getModule();

        /** @var ObjAbstract $obj */
        $obj = $this->_obj;
        $id = $obj->getId();

        $openExternalText = Config::$lang === 'ru' ? 'Открыть в новом окне' : 'Open in new window';
        $link = Link::create($module, $openExternalText . ' ' . Ico::externalLink());

        if ($this->isEditDialog()) {
            $link->edit($id);
        } else {
            $link->view($id);
        }

        $link->blank()
            ->getHtml();

        $moduleName = ModuleRegister::getModuleName($module);
        $headLink = "$moduleName <strong>$obj</strong> <span class='note'>(ID: $id)</span>";

        return '<div style="margin-bottom:10px; display: flex;  justify-content: space-between;">'
            . '<h3 style="padding:0; margin: 0px">' . $headLink . '</h3>'
            . $link
            . '</div>';
    }

    protected function getViewEditDialogContent(): string
    {
        // Чтобы работали ajax ссылки на изменение полей, обернём в дополнительный div с параметрами для ajax запросов
        $ajaxChangeFieldUrl = $this->getAjaxChangeFieldUrl();
        return '<div data-ajaxchangefieldurl="' . $ajaxChangeFieldUrl . '">'
            . $this->getViewEditContentHtml()
            . '<div>';
    }

    protected function isViewDialog()
    {
        return Request::isAjax() && Request::isGet() && isset($_GET['func']) && $_GET['func'] === 'Dialog';
    }

    protected function isEditDialog()
    {
        return Request::isAjax() && Request::isGet() && isset($_GET['func']) && $_GET['func'] === 'DialogEdit';
    }
}
