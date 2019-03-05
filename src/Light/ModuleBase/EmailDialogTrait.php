<?php


namespace Light\ModuleBase;


use Light\EmailDialog\EmailDialogAbstract;
use Light\Link;
use Light\Misc;

/**
 * Этот trait нужно вручную подключить к тому модулю, в котором используются диалоги для отправки E-mail
 */
trait EmailDialogTrait
{
    protected $emailDialogs = [];

    /**
     * Метод для переорпделения в классах потомках. Здесь нужно последовательно вызывать $this->addEmailDialog()
     * для добавления новых диалогов для отправки писем
     */
    protected function initEmailDialogs()
    {
    }

    /**
     * Метод используется для добавления E-mail диалога описанного в собственном классе
     * В качестве единственного параметра нужно передать класс унаследованный от EmailDialogAbstract
     * @param EmailDialogAbstract $emailDialogAbstract
     */
    final protected function addEmailDialog(EmailDialogAbstract $emailDialogAbstract)
    {
        $class = Misc::getClass($emailDialogAbstract);
        $this->emailDialogs[$class] = $emailDialogAbstract;
    }

    protected function emailDialogSubmenuLinks(): array
    {
        if (!$this->emailDialogs) {
            return [];
        }

        $links = [];

        $linkBase = Link::create($this->getModule())
            ->view($this->_obj->getId())
            ->func('EmailDialog');

        /**
         * @var string $class
         * @var EmailDialogAbstract $emailDialog
         */
        foreach ($this->emailDialogs as $class => $emailDialog) {
            $link = clone $linkBase;
            $link->innerHtml($emailDialog->getIcon() . ' ' . $emailDialog->getTitle())
                ->params(['EmailDialogClass' => $class, 'dialog' => 1])
                ->ajax();

            $links[] = $link;
        }

        return $links;
    }
}
