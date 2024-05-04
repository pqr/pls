<?php

namespace Light\ModuleBase;


use Light\ApplicationAbstract;
use Light\Config;
use Light\Field\FieldAbstract as F;
use Light\FilesFolder;
use Light\Ico;
use Light\Link;
use Light\ObjAbstract;
use Light\PrintForm\PrintForm;
use Light\Request;
use Light\Response;
use Light\Session;
use Light\Template;

/**
 * Этот trait безусловно подключается к ModuleAbstract.
 * Код вынесен в этот trait исключительно чтобы облегчить файл ModuleAbstract.php
 */
trait PrintFormsTrait
{
    protected $printForms = [];

    /**
     * Метод для переорпделения в классах потомках. Здесь нужно последовательно вызывать $this->addPrintForm()
     * для добавления новых печатных форм
     */
    protected function initPrintForms()
    {
    }

    /**
     * Метод используется для добавления печатной формы описанной в собственном классе
     * В качестве единственного параметра нужно передать класс унаследованный от PrintForm
     * @param PrintForm $printForm
     */
    final protected function addPrintForm(PrintForm $printForm)
    {
        $this->printForms[$printForm->getKey()] = $printForm;
    }

    private function printFormsAddMassaction(): void
    {
        if ($this->printForms) {
            // Если в классе потомке были добавлены какие-то опции печати, то добавим massaction действие
            // Это массовое действие соответственно вызовет метод massactionPrint, в котором будет выведен диалог
            // для выбора формы печати подходящей всем выбранным объектам
            $text = Config::$lang === 'ru' ? 'Печать (выбрать форму...)' : 'Print (select form...)';
            $optgroup = Config::$lang === 'ru' ? 'Печать' : 'Print';
            $this->addMassaction('Print', $text, '', $optgroup);
        }
    }

    private function printFormsSubmenuLinks(): array
    {
        if (!$this->printForms) {
            return [];
        }

        $links = [];

        // Начало ссылки на печать
        $linkBase = Link::create($this->getModule())
            ->view($this->_obj->getId())
            ->func('Print');

        /**
         * @var string $printFormName
         * @var PrintForm $printForm
         */
        foreach ($this->printForms as $printFormName => $printForm) {
            if ($printForm->allowSingle && $printForm->testForObj($this->_obj)) {
                $link = clone $linkBase;
                $link->innerHtml($printForm->getIcon() . ' ' . $printForm->getTitle())
                    ->params(['form' => $printFormName])
                    ->blank($printForm->isLinkBlank());
                if ($printForm->hasFieldset()) {
                    // Делаем ссылку на ajax диалог, чтобы заполнить нужены для данной формы поля
                    $link->ajax();
                } else {
                    // Эта ссылка сразу будет делать post запрос на генерацию печачтной формы
                    $link->post();
                }
                $links[] = $link;
            }
        }

        return $links;
    }

    private function beforePrint()
    {
        // Инициализируем внутренний массив $this->_printForms
        // чтобы при распечатке были известны параметры формы (шаблон, сохранять ли в pdf и проч.)
        // Проверим, есть ли такая форма, которую запросил пользователь
        $this->initPrintForms();

        // Имя печтной формы, которую нужно распечатать передаётся в $_GET параметре
        $form = $_GET['form'];
        if (!isset($this->printForms[$form])) {
            throw new \InvalidArgumentException("Неизвестная печатная форма $form");
        }

        /** @var PrintForm $printForm */
        $printForm = $this->printForms[$form];

        // Проверим, подходит ли эта форма для данного объекта?
        if (!$printForm->testForObj($this->_obj)) {
            Session::flashError('Печатная форма ' . $printForm->getTitle() . ' не подходит для данного объекта');
            $this->redirectToView();
        }

        return $printForm;

    }

    /**
     * Метод запускается если у печатной формы определён какой-то fieldset и, соответственно, при клике на ссылку в меню
     * отправляется ajax GET запрос, чтобы вывести форму
     */
    private function viewGetPrint(): void
    {
        $printForm = $this->beforePrint();

        $formAction = Link::create($this->getModule())
            ->view($this->_obj->getId())
            ->func('Print')
            ->params(['form' => $printForm->getKey()])
            ->getHref();

        $target = $printForm->isLinkBlank() ? ' target="blank"' : '';
        $html = '<form action="' . $formAction . '" method="post"' . $target . '>'
            . Template::csrfHidden();

        $data = $printForm->getDataForAjaxDialog($this->_obj);
        $fieldset = $printForm->getFieldset();
        $tdata = $fieldset->getTplData($data, F::MODE_EDIT);
        Template::setTdata($tdata);
        $html .= '<table class="form">';
        foreach ($fieldset as $field) {
            $html .= Template::c($field->key);
        }
        $html .= '</table></form>';

        $buttonOkText = Config::$lang === 'ru' ? 'Распечатать' : 'Print';
        Response::showDialog($html, $buttonOkText);
    }

    /**
     * Метод вызывается при Post запросе, который может прийти от ссылки из submenu, а может прийти из диалога
     * с дополнительными параметрами для печати (см. как рендерится этот диалог в ModuleAbstract::viewGetPrint())
     */
    private function viewPostPrint(): void
    {
        try {

            $printForm = $this->beforePrint();

            // Запустим процесс описанный в классе PrintForm (или в его наследнике)
            // Для этого нам нужно передать в метод proceedPrint путь к папке текущего объекта
            $relative = $this->getFolderRelative($this->_obj->getData());
            $targetDir = FilesFolder::getAbsolute($relative);
            $printForm->proceedPrint($this->_obj, $targetDir);

        } catch (\Throwable $e) {
            Session::flashError($e->getMessage());
            $this->redirectToView();
        }

        ApplicationAbstract::terminate();
    }

    /**
     * Выводи диалоговое окно где пользователь должен выбрать одну из доступных печатных форм
     * Алогритм проходит по всем отмеченным объектам и находит такое подмножество форм, которое удовлетворяет всем
     * @param array $ids
     * @throws \Exception
     */
    protected function massactionPrint(array $ids)
    {
        $this->initPrintForms();
        $objBase = $this->getObj();

        $printForms = $this->printForms;

        // Загрузим все объекты по выделенным галочками и на их основе определим набор печатных форм, которые удовлетворяют
        // всем объектам
        // Скопируем массив всех печатных форм, чтобы поочерёдно удалять печатные формы, которые не подходят
        foreach ($ids as $id) {

            /** @var ObjAbstract $obj */
            $obj = clone $objBase;
            $obj->setId($id);
            if (!$obj->loadData()) {
                continue;
            }

            // Тестируем все печатные формы на текущем объекте
            /**
             * @var string $key
             * @var PrintForm $printForm
             */
            foreach ($printForms as $key => $printForm) {
                if (!($printForm->allowMultiple || $printForm->allowMultipleAsOne)
                    || !$printForm->testForObj($obj)) {
                    // Форма не предназначена для массовой печати
                    // или не подходит данному объету - убираем её из массива
                    unset($printForms[$key]);
                }
            }

            if (!$printForms) {
                // Если в процессе проверки все печатные формы были удалены из массива - прерываем цикл проверки
                break;
            }
        }

        if (!$printForms) {
            $msg = Config::$lang === 'ru' ? 'Нет печатной формы подходящей для всех выделенных строк' : 'No print form available';
            MassactionUtils::sendErrorDialog($msg);
            return;
        }

        // Выведем диалог с выбором печатной формы
        $html = '';

        foreach ($printForms as $key => $printForm) {

            if ($printForm->allowMultipleAsOne) {
                $html .= $this->getMassactionPrintButton($ids, $printForm, true);
            }

            if ($printForm->allowMultiple) {
                $html .= $this->getMassactionPrintButton($ids, $printForm, false);
            }

        }

        Response::showDialog($html);

    }

    private function getPrintSubmitLink(PrintForm $printForm, bool $multipleAsOne): Link
    {
        $link = Link::create($this->getModule())
            ->proceed('MassactionPrintSubmit')
            ->params(['form' => $printForm->getKey()]);

        if ($multipleAsOne) {
            $link->params(['multipleAsOne' => 1]);
        }
        return $link;
    }

    private function getMassactionPrintButton(array $ids, PrintForm $printForm, bool $multipleAsOne): string
    {
        $icon = $printForm->getIcon();
        $title = $printForm->getTitle();
        $suffix = $printForm->allowMultipleAsOne && !$multipleAsOne ? ' (по одному на запись)' : '';
        $innerHtml = $icon . '&nbsp;' . $title . $suffix;

        $link = $this->getPrintSubmitLink($printForm, $multipleAsOne)
            ->innerHtml($innerHtml)
            ->blank($printForm->isLinkBlank())
            ->btnFlat()
            ->post()
            ->dataPost(['CheckedIds' => implode(',', $ids)])
            ->ajax($printForm->getFieldset()->count());

        return '<p>' . $link->getHtml() . '</p>';
    }

    /**
     * Метод вызывается после того, как пользователь нажал на конкретную кнопку печатной формы в диалоге выбора печатных
     * форм. Внутри этого метода мы либо запускаем массовую печать отмеченных галочками обёектов,
     * либо выводим дополнительный диалог с настройками формы (если у печатной формы определён fieldset)
     * @throws \Light\Codeception\TestResponseException
     * @throws \BadMethodCallException
     * @throws \Exception
     */
    public function proceedMassactionPrintSubmit(): void
    {
        $this->initPrintForms();
        $form = $_GET['form'] ?? '';
        if (!isset($this->printForms[$form])) {
            $msg = Config::$lang === 'ru' ? 'Неизвестная печатная форма' : 'Unknown print form';
            throw new \InvalidArgumentException("$msg: $form");
        }

        $objBase = $this->getObj();
        $ids = $this->getMassactionIdsFromPost();

        /** @var PrintForm $printForm */
        $printForm = $this->printForms[$form];
        $objects = [];
        $targetDirs = [];

        $multipleError = $printForm->testMultiple($ids);
        if ($multipleError) {
            MassactionUtils::sendErrorDialog($multipleError, $printForm->isLinkBlank());
            return;
        }

        foreach ($ids as $id) {
            /** @var ObjAbstract $obj */
            $obj = clone $objBase;
            $obj->setId($id);

            // Попробуем загрузить объект и ещё раз на всякий случай проверим, подходит ли он для данной формы?
            // (за то время, пока пользователь выбирал фомру в диалоге, сами объекты могли измениться)
            if ($obj->loadData() && $printForm->testForObj($obj)) {

                $objects[] = $obj;

                $relative = $this->getFolderRelative($obj->getData());
                $targetDirs[] = FilesFolder::getAbsolute($relative);

            }
        }

        if (!$objects) {
            // Все переданные в ids объекты либо уже не существуют, либо уже не подходят для печати в выбанной печатной форме
            $msg = Config::$lang === 'ru' ? 'Выбранные записи не подходят для печатной формы' : 'Not sutable for print form';
            $printFormTitle = $printForm->getTitle();
            MassactionUtils::sendErrorDialog("$msg: $printFormTitle", $printForm->isLinkBlank());
            return;
        }

        if (!isset($_GET['fromDialog']) && $printForm->getFieldset()->count()) {
            // Для данной формы задан какой-то fieldset, значит нужно вывести дополнительный диалог
            $this->massactionPrintShowFieldsDialog($ids, $objects, $printForm, isset($_GET['multipleAsOne']));
            return;
        }

        // Похоже у печатной формы не было никакого fieldset, значит запускаем генерацию pdf/doc или html файлов
        if (!Request::isPost()) {
            throw new \BadMethodCallException('HTTP method for print forms should be POST (' . $printForm->getKey() . ')');
        }

        if (isset($_GET['multipleAsOne'])) {
            $printForm->proceedPrintMultipeAsOne($objects);
        } else {
            $printForm->proceedPrintMultiple($objects, $targetDirs);
        }
        ApplicationAbstract::terminate();
    }

    /**
     * Если у выбранной печатной формы определён fieldset, значит нужно вывести диалог с этим fieldset, чтобы
     * пользователь указал дополнительные данные перед выводом на печать.
     *
     * Этот метод как раз выводит такой диалог. Он запускается после того, как пользователь выбран конкретную печатную
     * форму которую хочет массво распечатать по отмеченным галочками объектам
     *
     * @param array $ids
     * @param array $objects
     * @param PrintForm $printForm
     * @param bool $multipleAsOne
     */
    private function massactionPrintShowFieldsDialog(
        array $ids,
        array $objects,
        PrintForm $printForm,
        bool $multipleAsOne
    ): void
    {
        $formAction = $this->getPrintSubmitLink($printForm, $multipleAsOne)
            ->params(['fromDialog' => 1]);

        $target = $printForm->isLinkBlank() ? ' target="blank"' : '';
        $html = '<form action="' . $formAction->getHref() . '" method="post"' . $target . '>'
            . MassactionUtils::getHtmlHiddenCheckedIds($ids)
            . Template::csrfHidden();

        $data = $printForm->getDataForAjaxDialogMultiple($ids, $objects);
        $fieldset = $printForm->getFieldset();
        $tdata = $fieldset->getTplData($data, F::MODE_EDIT);
        Template::setTdata($tdata);
        $html .= '<table class="form">';
        foreach ($fieldset as $field) {
            $html .= Template::c($field->key);
        }
        $html .= '</table></form>';

        $buttonOkText = Config::$lang === 'ru' ? 'Печать' : 'Print';
        Response::showDialog($html, $buttonOkText);
    }
}
