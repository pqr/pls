<?php


namespace Light\ModuleBase\Actions;


use Light\Ico;
use Light\Link;
use Light\ModuleSession;
use Light\Msg;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;

abstract class ActionAbstract
{
    public $onlyAjax = false;
    public $onlyPost = false;
    public $submenuTitle = '';
    public $submenuIcon = '';

    /** @var ObjAbstract */
    protected $obj;

    /**
     * @var Msg
     */
    protected $msgBuff;

    public function __construct(ObjAbstract $obj, Msg $msgBuff)
    {
        $this->obj = $obj;
        $this->msgBuff = $msgBuff;
    }

    /**
     * Метод для переопределения в классах потомках
     * @return bool
     */
    public function hasAccess(): bool
    {
        return true;
    }

    /**
     * Метод для переопределения в классах потомках.
     * @return string
     */
    public function getSubmenuHtml(): string
    {
        return Ico::i($this->submenuIcon) . ' ' . $this->submenuTitle;
    }

    public function obj()
    {
        return $this->obj;
    }

    protected function redirectToView()
    {
        Response::redirect(Request::getModule(),
            ['id' => $this->obj->getId()] + ModuleSession::getModuleSessionUrlParams()
        );
    }

    /**
     * Возвращает объект Link на текущий Action - удобно для формирования form action
     * @param array $params
     * @return Link
     */
    protected function getLink(array $params = []): Link
    {
        $link = Link::create(Request::getModule());
        if ($this->obj->getId()) {
            $link->view($this->obj->getId());
        }
        $link->action(get_class($this))
            ->ajax($this->onlyAjax)
            ->post($this->onlyPost)
            ->params($params);

        return $link;
    }

    abstract public function __invoke();
}
