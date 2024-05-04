<?php

namespace Light\ModuleBase;

use Light\Link;
use Light\Misc;
use Light\Msg;
use Light\Request;
use Light\Response;

/**
 * Действия massaction можно задавать в виде функций в классе модуля (extends ModuleAbstract)
 * либо в виде отдельных классов.
 * Во втором случае нужно использовать данный MassactionAbstract в качестве базового класса
 * Class MassactionAbstract
 * @package Light\ModuleBase
 */
abstract class MassactionAbstract
{
    protected $key;

    public $cssClass;
    public $text;
    public $optgroup;
    public $blank = false;

    /**
     * @return mixed
     */
    abstract public function __invoke(array $ids);

    public function getKey(): string
    {
        // Если в калссе потомке вручную не задан какой-то особенный уникальный ключ, то используем имя класса
        return $this->key ?? Misc::getClass($this);
    }

    public function hasAccess(): bool
    {
        return true;
    }

    /**
     * Возвращает объект Link на текущий Massaction - удобно для формирования form action
     * @return Link
     */
    protected function getLink(array $params = []): Link
    {
        $link = Link::create(Request::getModule());
        $link->massaction($this->getKey())
            ->ajax()
            ->params($params);

        if (isset($_GET['class']) && Request::getModule() === 'Reportmenu') {
            $link->params(['class' => $_GET['class']]);
        }

        return $link;
    }

    /**
     * @param Msg|string $msg
     * @param $reloadTable
     */
    protected function showDialogSuccess($msg, bool $reloadTable = true): void
    {
        // Обновим таблицу, отправив специальный заголовок, который на стороне клиента вызовет перезагрузку
        // Перезагрузка таблицы будет происходить в фоновом режиме, пока пользователь читает диалоговое окно о результатах
        if ($reloadTable) {
            $tableName = $this->getReloadTableName();
            Response::sendHeaderJsReloadTable($tableName);
        }
        $bodyHtml = $msg instanceof Msg ? $msg->getHtml() : $msg;
        Response::showDialog($bodyHtml);
    }

    protected function getReloadTableName(): string
    {
        if (isset($_GET['class']) && Request::getModule() === 'Reportmenu') {
            return 'Report' . $_GET['class'];
        }

        return Request::getModule();
    }
}
