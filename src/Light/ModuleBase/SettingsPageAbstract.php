<?php


namespace Light\ModuleBase;


use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Link;
use Light\ModuleRegister;
use Light\Request;
use Light\Response;
use Light\Session;
use Light\Template;
use Light\Traits\FieldsetAwareTrait;
use Light\User;

class SettingsPageAbstract
{
    use FieldsetAwareTrait;

    /** @var Fieldset */
    protected $fieldset;

    /** @var array */
    protected $data;

    public function __invoke()
    {
        // Отображение в режиме View
        $this->loadData();
        $tdata = $this->getTemplateData();
        $template = $this->getTemplate();
        $formInnerHtml = Template::parseTpl($template, $tdata);

        $title = $this->getPageTitle();
        Response::setPageTitle($title);
        Response::setPageHeadHtml($title);

        $description = $this->getDescription();
        if ($description) {
            print '<p>' . $description . '</p>';
        }

        $formAction = Link::create(Request::getModule())
            ->proceed('submit')
            ->ajax()
            ->getHref();

        print '<form action="' . $formAction . '" method="post">';
        print Template::csrfHidden();
        print $formInnerHtml;
        print '<div class="formactions_fixed">';
        print '<button type="submit" class="waves-effect btn btn-save bgcolor-btn-primary" title="Ctrl + S">';
        print Config::$lang === 'ru' ? 'Сохранить' : 'Save';
        print '</button>';
        print '</div>';
        print '</form>';
    }

    protected function getTemplateData(): array
    {
        return $this->getFieldset()->getTplData($this->data, F::MODE_EDIT);
    }

    protected function getPageTitle()
    {
        return ModuleRegister::getModuleName(Request::getModule());
    }

    protected function getDescription(): string
    {
        return '';
    }

    protected function loadData()
    {
        $fieldKeys = $this->fieldset->getKeys();
        $this->data = Db::getAssoc("SELECT Name, Value FROM system_param WHERE Name IN (@a)", $fieldKeys);
    }

    protected function getTemplate(): string
    {
        // Проверим, есть ли шаблон для отображения этого модуля?
        $module = Request::getModule();
        $templateFile = $module . '.tpl.php';
        if (!Template::findfile($templateFile)) {
            $templateFile = 'module/auto.tpl.php';
        }

        return $templateFile;
    }

    public function getFieldset()
    {
        if (!$this->fieldset) {
            $this->fieldset = new Fieldset();
        }

        return $this->fieldset;
    }

    public function proceedSubmit()
    {
        if (!Request::isPost()) {
            Response::redirect(Request::getModule());
        }

        if (!User::hasWriteAccess(Request::getModule())) {
            $msg = Config::$lang === 'ru' ? 'Нет доступа на изменение' : 'No write access';
            Session::flashError($msg);
            Response::abort(403, $msg);
            return;
        }

        try {

            $dataFromRequest = $this->getDataFromPost();
            $this->updateDb($dataFromRequest);

            $msg = Config::$lang === 'ru' ? 'Сохранено' : 'Saved';
            Session::flashSuccess($msg);

            // Полностью обновим страницу с помощью редиректа, чтобы значения формы заново загрузились из базы
            // таким образом пользователь будет уверен, что всё сохранилось как надо
            Response::redirect(Request::getModule());

        } catch (\Throwable $e) {

            $msg = Config::$lang === 'ru' ? 'Не удалось сохранить' : 'Error while saving';
            Session::flashError($msg . ' ' . $e->getMessage());
            \Light\ErrorHandler::reportException($e);
            Response::abort(500);

        }
    }

    protected function getDataFromPost(): array
    {
        return $this->getDataFromRequest($_POST);
    }

    protected function updateDb(array $dataFromRequest)
    {
        foreach ($dataFromRequest as $fieldKey => $value) {
            ApplicationAbstract::setParam($fieldKey, $value);
        }
    }
}
