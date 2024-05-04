<?php

namespace Light\ModuleBase;

use InvalidArgumentException;
use Light\ApplicationAbstract;
use Light\Config;
use Light\Db;
use Light\ElfinderPage;
use Light\ErrorHandler;
use Light\Field\FieldAbstract;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldDate;
use Light\Field\FieldDateTime;
use Light\Field\FieldMoney;
use Light\Field\FieldMoneyWithCurrency;
use Light\Field\FieldOptionsAbstract;
use Light\Field\FieldSelect;
use Light\Fieldset;
use Light\FilesFolder;
use Light\Ico;
use Light\Intro\IntroManager;
use Light\Js;
use Light\Link;
use Light\Metric;
use Light\Misc;
use Light\ModuleBase\Actions\ActionAbstract;
use Light\ModuleBase\Actions\AjaxChangeField;
use Light\ModuleRegister;
use Light\Msg;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\Select2ajaxController;
use Light\Session;
use Light\SubtableBase\Subtable;
use Light\Table;
use Light\Template;
use Light\Timer;
use Light\Traits\ModuleSessionTrait;
use Light\Traits\TableExcelExportable;
use Light\Traits\TableFilterable;
use Light\Traits\TableLayoutable;
use Light\Traits\TablePaginatable;
use Light\Traits\TableSortable;
use Light\Traits\TableWithFoldersTrait;
use Light\User;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use Throwable;

abstract class ModuleAbstract extends Table
{
    use TablePaginatable, TableFilterable, TableSortable, TableLayoutable, TableExcelExportable,
        TableWithFoldersTrait, ModuleSessionTrait, MassactionsTrait,
        ViewEditDialogsTrait, PrintFormsTrait, CopyModesTrait, BadgesTrait, DeleteTrait;

    public const STATE_LIST = 'list';
    public const STATE_LIST_EXCEL = 'excel';
    public const STATE_ADDNEW = 'addnew';
    public const STATE_VIEW = 'view';
    public const STATE_EDIT = 'edit';
    public const STATE_LOG = 'log';
    public const STATE_DELETE = 'delete';
    public const STATE_SUBMIT = 'submit';
    public const STATE_LIST_FUNC = 'list_func';
    public const STATE_VIEW_ELFINDER = 'view_elfinder';
    public const STATE_VIEW_FUNC = 'view_func';
    public const STATE_ACTION = 'action';
    public const STATE_MULTIPLEUPDATESUBMIT = 'multiupdate';
    public const STATE_SUBTABLE_RELOAD = 'subtable_reload';
    public const STATE_SUBTABLE_EXCEL = 'subtable_excel';
    public const STATE_SUBTABLE_FUNC = 'subtable_func';

    //Общие настройки для всех режимов
    protected $allowAdd = true;
    protected $allowEdit = true;
    protected $allowLog = true;
    protected $allowDelete = true;

    public $allowTablePrintHeadBlock = true;

    // Переопределяем значение в true, чтобы работала перезагрузка отдельных строк
    // см метод proceedListRenderRowForAjax()
    public $allowTableReloadRow = true;

    //Использовать ли выпадающий список с выбором действия после сохранения?
    protected $continmethod = false;

    // Текст, который будет выводиться на кнопке Save полсе слова "Сохранить"
    // например, чтобы выводилось "Сохранить Перевозку", нужно указать $this->textSaveBtn = "Перевозку"
    protected $textSaveBtn = '';

    //Внутренние переменные состояния
    protected $_saveErrors = []; //Массив имя поля => сообщение об ошибке
    protected $_justInserted = false;
    protected $_state;

    protected $submenuLinks = [];
    protected $listLinks = [];

    /**
     * @var array(SubtableName)
     * Массив с именами подтаблиц. Автоматизация следующая:
     * - будут подключены файлы Subtable/<модуль>/<имя подтаблицы>.php
     * - в режиме List будет вызван метод подтаблицы initFieldsFromSubtables
     * - в функциях proceedSubmit, proceedDelete, getTplData будут созданы экземпляры этих классов и запущены соотвесвующие функции
     */
    private $subtables = [];

    private $_queryTime = 0;

    /** @var Msg */
    protected $flash;

    public function __construct()
    {
        $module = $this->getModule();
        parent::__construct($module);

        $this->flash = Session::getMsgBuff();
        if (!$this->tableReloadUrl) {
            $this->tableReloadUrl = Link::create($module)->getHref();
        }
        if (!$this->folderModule) {
            $this->folderModule = $module;
        }

        $this->initSubtables();
    }

    /**
     * Метод для переопределения в классах потомках
     * В нём нужно вызывать $this->addSubtable(className) или $this->addSubtableReadonly(className)
     */
    protected function initSubtables()
    {
    }

    protected function getModule()
    {
        return Misc::getModule($this) ?: Request::getModule();
    }

    protected function obj()
    {
        return $this->_obj;
    }

    /**
     * Основный метод proceed осуществляет только функции роутинга - проверяет входящие параметры строки запроса и
     * вызывает соответсвующий метод proceedList, proceedView, proceed...
     * Никаких проверок прав доступа или существования объекта в глобальном методе proceed не происходит - это всё должны
     * делать конкретные методы proceed
     */
    public function __invoke()
    {
        if (Request::getQueryParam('select2ajax')
            && (Request::getQueryParam('fromFilters') === null
                || Request::getQueryParam('fromFilters') === $this->getTableName())) {
            return $this->proceedSelect2ajax();
        }

        if (isset($_GET['addnew'])) {
            return $this->proceedAddNew();
        }

        if (isset($_GET['id'])) {
            return $this->proceedView();
        }
        if (isset($_GET['editid'])) {
            return $this->proceedEdit();
        }

        if (isset($_GET['logid'])) {
            return $this->proceedLog();
        }

        if (isset($_GET['submitid'])) {
            return $this->proceedSubmit();
        }

        if (isset($_GET['delid'])) {
            return $this->proceedDelete();
        }

        return $this->proceedList();
    }

    protected function proceedAddNew()
    {
        $this->_state = self::STATE_ADDNEW;

        //Проверим права на запись в этот модуль
        if (!$this->allowAdd || !User::hasWriteAccess()) {
            //При обычном запросе сделаем редирект на режим просмотра с flash сообщенеим
            Session::flashError('No acces to add new');
            $this->redirectToList();
        }

        $this->initCssJs();

        //Если в GET переданы параметры copymode и copyfrom - инициализируем текущий объект
        if (isset($_GET['copymode'], $_GET['copyfrom'])) {
            $this->initCopyModes();
            $this->initDataForCopy($_GET['copymode'], (int)$_GET['copyfrom']);
        }

        $initialData = $this->getInitialDataFromRequest();
        $this->initDataFromGetParams($initialData);

        $module = $this->getModule();
        $moduleName = ModuleRegister::getModuleName($module);

        $moduleSessionUrlParams = $this->getModuleSessionUrlParams();
        $link = Link::create($module, $moduleName)->params($moduleSessionUrlParams);
        $headText = "$link - <strong>Add New</strong>";
        Response::setPageHeadHtml($headText);
        $this->initAddNewJsData();

        $formActionUrlParams = ['submitid' => 0] + $this->getModuleSessionUrlParams();
        $tdata = [
            'formActionUrl' => Link::create($module)->params($formActionUrlParams)->getHref(),
            'cancelUrl' => Link::create($module)->params($moduleSessionUrlParams)->getHref(),
            'content' => $this->getViewEditContentHtml(),
            'formactions' => $this->getFormactionsHtml(),
            'fields' => $this->getFieldsForFormJs(),
        ];

        Template::includeTpl('module/addnew.tpl.php', $tdata);
    }

    private function getInitialDataFromRequest(): array
    {
        $initialData = [];
        if (isset($_GET['initialData'])) {
            try {
                $initialData = json_decode((string) $_GET['initialData'], true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($initialData)) {
                    $initialData = [];
                }
            } catch (\Exception $e) {
                ErrorHandler::reportException($e);
            }
        }
        return $initialData;
    }

    protected function initDataFromGetParams(array $initialData)
    {
        foreach ($initialData as $key => $val) {
            if (is_array($val) && $this->hasSubtable($key)) {
                // Это похоже на подтаблицу!
                $subtable = $this->getSubtable($key);
                $subtable->setSubtableDataAdditional($val);
                continue;
            }

            $this->_obj->mergeData([$key => $val]);
        }
    }

    protected function redirectToList()
    {
        Response::redirect($this->getModule(), $this->getModuleSessionUrlParams());
    }

    protected function initCssJs()
    {
        //Проверим, есть ли файлы для этого модуля в зависимости от текущего режима
        $module = $this->getModule();
        $sufffix = '';
        switch ($this->_state) {
            case self::STATE_LIST:
                $sufffix = 'List';
                break;
            case self::STATE_VIEW:
                $sufffix = 'View';
                break;
            case self::STATE_ADDNEW:
            case self::STATE_EDIT:
                $sufffix = 'Edit';
        }

        if ($sufffix) {

            $jsfile = "Module/$module/{$module}{$sufffix}.js";
            $jsfilepath = LIGHT_DIR_PUBLIC . "/js/$jsfile";
            if (file_exists($jsfilepath)) {
                Js::add($jsfile);
            }

        }

        if (isset($_GET['print']) && $this->isStateView() && Request::isGet()) {
            Js::addLight("PrintPage.js");
        }
    }


    /**
     * Возвращает массив параметров, которые нужно передать в JS окружение через атрибут data-params
     */
    protected function initAddNewJsData()
    {
        Response::addJsData(['state' => 'addnew']);
    }

    private function getFieldsForFormJs()
    {
        //В JavaScript окружение нужно передать типы полей
        $filteredFieldset = $this->getFieldset()->filterByMode(F::MODE_VIEW | F::MODE_EDIT);
        return $filteredFieldset->getFieldsForFormJs();
    }

    protected function getViewEditContentHtml()
    {
        $template = $this->getViewEditTemplate();
        return Template::parseTpl($template, $this->getTplData());
    }

    protected function getViewEditTemplate()
    {
        // Проверим, есть ли шаблон для отображения объектов этого модуля?
        $module = $this->getModule();
        $templateFile = $module . '.tpl.php';
        if (!Template::findfile($templateFile)) {
            $templateFile = 'module/auto.tpl.php';
        }

        return $templateFile;
    }


    /**
     * @return array
     */
    protected function getTplData()
    {
        if (!$this->allowEdit || !User::hasWriteAccess() || ($this->_obj->getId() && !$this->hasWriteAccessRightToObj($this->_obj))) {
            $this->disableAllFieldsEdit();
        }

        // Если есть поле Color и режим View - добавим ему параметр source
        if ($this->_state === self::STATE_VIEW && $this->hasField('Color')) {
            $this->getField('Color')->setParam(
                'source',
                Link::create($this->getModule())->view($this->_obj->getId())->action(AjaxChangeField::class)->getHref(),
            );
        }

        $fieldMode = $this->_state === self::STATE_VIEW ? F::MODE_VIEW : F::MODE_EDIT;
        // $filteredFieldset = $this->getFieldset()->filterByMode(F::MODE_VIEW | F::MODE_EDIT);
        $filteredFieldset = $this->getFieldset();

        $data = $this->_obj->getData();
        if ($this->_state === self::STATE_ADDNEW) {
            //В режиме Add New изначальные данные для формы могли быть заданы в url
            //Однако, проблема с чекбоксами - если их нет в $_GET, то они станут якобы сняты
            //А на самом деле может быть они должны получить свои параметры из default!
            $fieldsetWithoutCheckboxes = new Fieldset();
            foreach ($this->getFieldset() as $field) {
                $type = $field->getType();
                if ($type !== F::TYPE_CHECKBOX
                    && $type !== F::TYPE_CHECKBOX_SET
                    && !($field instanceof FieldSelect && $field->multiple)) {
                    $fieldsetWithoutCheckboxes->addField($field);
                }
            }
            $data = array_merge($data, $fieldsetWithoutCheckboxes->getDataFromRequest($_GET));
        }

        $tdata = $filteredFieldset->getTplData($data, $fieldMode);

        //В ModuleAbstract создадим ещё более общие переменные в tdata, которые будут формировать вёрстку всего блока
        $tdata['_subtables'] = $this->getTplDataSubtables();

        $tdata['_state'] = $this->_state;
        $tdata['_id'] = $this->_obj->getId();

        return $tdata;
    }

    protected function getTplDataSubtables()
    {
        $tdataSubtables = [];
        //Добавим в шаблонные переменные все зарегистрированные подтаблицы
        foreach ($this->subtables as $subtableClass => ['readonly' => $readonly]) {
            // В качестве ключа для шаблонизатора используем короткое имя класса подтаблицы (без неймспейсов)
            $subtableName = Misc::getClass($subtableClass);
            $subtable = $this->getSubtable($subtableClass);
            $tdataSubtables[$subtableName] = [
                'obj' => $subtable,
                'readonly' => $readonly,
            ];
        }

        return $tdataSubtables;
    }

    private $_alertSaveButton = [];

    protected function setAlertSaveButton(
        ?string $text,
        bool $hidden = false,
        string $icon = 'fa-exclamation-triangle',
        string $color = '#b35900'
    ) {
        if ($text) {
            $this->_alertSaveButton = [$text, $hidden, $icon, $color];
        } else {
            $this->_alertSaveButton = [];
        }
    }

    protected function getSaveBtnHtml(): string
    {
        // Кнопку "Save" специально делаем button type="button"
        // чтобы форма не отрпавлялась случайно при нажатии enter на лубом из полей
        $textSave = Config::$lang === 'ru' ? 'Сохранить' : 'Save';
        return '<button type="button" class="waves-effect btn btn-save bgcolor-btn-primary" title="Ctrl + S">'
            . "$textSave {$this->textSaveBtn}"
            . '</button>';
    }

    protected function getCancelBtnHtml(): string
    {
        $textCancel = Config::$lang === 'ru' ? 'Отмена' : 'Cancel';
        return '<a href="javascript: history.back()" class="btn-flat waves-effect cancel-link">'
            . $textCancel
            . '</a>';
    }

    protected function getContinmethodHtml(): string
    {
        if (!$this->continmethod) {
            return '';
        }

        $continmetods = [
            'list' => 'Back to list',
            'view' => 'View this',
            'edit' => 'Edit this',
        ];

        if ($this->allowAdd) {
            $continmetods['addnew'] = 'Add new';
        }

        $continmethod = User::getParam($this->getModule() . '_continmethod') ?? 'view';

        return F::createSelect('_continmethod', [
            'editWidth' => 'auto',
            'empty' => false,
            'options' => $continmetods,
        ])->getHtmlInput($continmethod);
    }

    protected function getFormactionsAlertHtml(): string
    {
        if (!$this->_alertSaveButton) {
            return '';
        }
        [$text, $hidden, $icon, $color] = $this->_alertSaveButton;
        return '<div class="alert-save-button ' . ($hidden ? 'display-none' : '') . '" style="color: ' . $color . ';">' . Ico::i($icon) . ' ' . $text . '</div>';
    }

    private function getFormactionsHtml()
    {
        return '<div class="formactions_fixed">'
            . $this->getFormactionsAlertHtml()
            . $this->getSaveBtnHtml()
            . $this->getCancelBtnHtml()
            . $this->getContinmethodHtml()
            . '</div>';
    }

    /**
     * Метод вызывается если в $_GET запросе есть параметр id
     * Далее в этом методе попробуем разобраться что именно хочет сделать пользователь
     */
    protected function proceedView()
    {
        $id = (int)$_GET['id'];
        // Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
        if (!$this->loadObjAndCheck($id)) {
            // Внутри произошел redirect и метод loadObjAndCheck() вернул false
            return null;
        }

        // Проверим дополнительные GET параметры относящиеся к режиму просмотра
        if (Request::getQueryParam('subtable')) {
            // Методы работы с subtable
            return $this->proceedViewSubtable();
        }

        if (Request::getQueryParam('elfinder')) {
            // Отображаем окно с папкой (elfinder)
            return $this->proceedViewElfinder();
        }

        if (Request::getQueryParam('func')) {
            // Попытка вызвать пользовательскую функцию из режима просмотра - это может быть ajax запрос или печать
            // документа или вывод в Excel особой формы
            return $this->proceedViewFunc();
        }

        if (Request::getQueryParam('action')) {
            return $this->proceedAction();
        }

        return $this->proceedViewPage();
    }

    /**
     * Загружает данные в объект $this->_obj, проверяет права доступа к нему у текущего пользователя
     * Если объект не найден или нет доступа, то перенаправляет на режим List
     * @return bool
     */
    protected function loadObjAndCheck(int $id): bool
    {
        $this->_obj->setId($id);

        $errMsg = null;

        // Проверим существование объекта в базе, попробовав его загрузить
        try {

            if (!$this->_obj->loadData()) {

                $errMsg = Config::$lang === 'ru' ? "Запись с ID=$id не найдена" : "Record with ID=$id not found";

            } elseif (!$this->hasAccessRightToObj($this->_obj)) {

                $errMsg = Config::$lang === 'ru' ? "Нет доступа к записи ID=$id" : "No access to record ID=$id";

            }

        } catch (\Exception $e) {

            // Случилось что-то экстроординарное при загрузке объекта
            ErrorHandler::reportException($e);
            $errMsg = $e->getMessage();
        }

        if ($errMsg) {
            $this->flash->addError($errMsg);
            $this->redirectToList();
            return false;
        }

        return true;
    }

    private function proceedViewSubtable(): void
    {
        $subtableName = $_GET['subtable'];
        $subtableClass = PROJECT_NAMESPACE . '\\Module\\' . $this->getModule() . '\\Subtables\\' . $subtableName;
        //проверим, что такая подтаблица сщуетсвует
        if (!isset($this->subtables[$subtableClass])) {
            Session::flashError('Unknown subtable ' . $subtableName);
            $this->redirectToView();
        }

        /** @var $subtable Subtable */
        $subtable = $this->getSubtable($subtableClass);

        if (Request::isAjax()) {

            if (isset($_REQUEST['fieldKey'])) {
                $this->proceedViewSubtableChangeField($subtable);
                return;
            }

            // Перезагрузка через ajax при изменении Table Layout
            $subtable->proceedAjaxReload();

        } elseif (Request::isXls()) {

            // Отправка Excel файла
            $subtable->proceedExcel();

        } else {

            // Не удалось определить действие с подтаблицей, сообщим об этом пользователю
            Session::flashError('Bad subtable request');
            $this->redirectToView();

        }
    }

    protected function proceedViewSubtableChangeField(Subtable $subtable)
    {
        // Изменение какого-то поля через ajax
        $fieldKey = $_REQUEST['fieldKey'];
        $field = $subtable->getFieldset()->getField($fieldKey);
        if (!$field) {
            Response::abort(400, "Field not found");
        }

        if (!$this->hasWriteAccessAjaxChangeFieldSubtable($field)) {
            Response::abort(403, 'Edit not allowed');
        }

        $oldData = [];
        if (Request::isPost()) {
            $oldData = $this->getOldDataBeforeSubmitOrDelete($this->_obj);

            // Загрузим данные подтаблиц внутрь самого obj, чтобы потом было удобно сравнивать
            $this->_obj->loadSubtablesForChangeDetection();
        }

        $affectedRows = $subtable->proceedChangeField($this->flash);

        if (Request::isPost()) {

            // Загрузим обновлённые данные подтаблиц внутрь самого obj
            $this->_obj->loadSubtablesForChangeDetection();

            $this->_obj->afterSubmit($this->flash);
            $this->afterSubmit($this->_obj, [], $affectedRows, $oldData, $this->flash, false);
        }
    }

    protected function proceedViewElfinder()
    {
        $this->_state = self::STATE_VIEW_ELFINDER;

        if (!$this->folders) {
            Response::abort(403, 'Module without folders');
            return;
        }

        // На всякий случай создадим папку, если её ещё нет на сервере
        $this->createFolder();
        $module = $this->getModule();
        $id = $this->_obj->getId();

        // Чтобы правильно работала функция basename с файлами на русском языке
        // Перед этим нужно установить локаль в операционную систему:
        // https://askubuntu.com/questions/76013/how-do-i-add-locale-to-ubuntu-server
        // locale -a
        // sudo locale-gen ru_RU
        // sudo locale-gen ru_RU.UTF-8
        // sudo update-locale
        // sudo service php7.1-fpm restart

        // Чтобы при скачивании архива имена файлов внутри архива были правильными, на сервере нужно установить zip
        // apt install zip

        if ($_GET['elfinder'] === 'dialog') {
            // Это первичный запрос на рендер каркаса диалога (пока без содержимого с файлами)
            $textFolder = Config::$lang === 'ru' ? 'Папка' : 'Folder';
            $moduleName = ModuleRegister::getModuleName($this->getModule());
            $title = "$textFolder: $moduleName " . strip_tags((string) $this->_obj->getDescriptionHtml());
            $apiUrl = Link::create($module)->view($id)->params(['elfinder' => 'api'])->getHref();
            ElfinderPage::renderPage($title, $apiUrl);
            return;
        }

        $relative = $this->getFolderRelative($this->_obj->getData());
        if (!$relative) {
            Response::abort(404);
            return;
        }

        if ($_GET['elfinder'] === 'api') {
            // Пришел API запрос от окна с elfiner (это может быть API запрос на отображение списка файлов
            // или upload нового файла
            $path = FilesFolder::getAbsolute($relative);
            $downloadUrl = Link::create($module)->view($id)->params(['elfinder' => 'download'])->getHref() . '&file=';
            $hasWriteAccess = $this->hasWriteAccessRightToFolder($this->_obj);
            ElfinderPage::proceedApi($path, $downloadUrl, $hasWriteAccess);
            return;
        }

        if ($_GET['elfinder'] === 'download' && isset($_GET['file'])) {

            // Проверим, чтобы в имени скачиваемого файла file не было ..
            $test = '/' . str_replace('\\', '/', (string) $_GET['file']) . '/';
            if (str_contains($test, '/../')) {
                throw new InvalidArgumentException("file contains ..: " . $_GET['file']);
            }

            $file = trim((string) $_GET['file'], '/');
            FilesFolder::streamFile("$relative/$file");
            ApplicationAbstract::terminate();
            return;
        }
    }

    protected function redirectToView()
    {
        $urlParams = ['id' => $this->_obj->getId()] + $this->getModuleSessionUrlParams();
        Response::redirect($this->getModule(), $urlParams);
    }

    protected function proceedViewFunc()
    {
        $this->_state = self::STATE_VIEW_FUNC;

        $func = ucfirst((string) $_GET['func']);
        $postOrGet = Request::isPost() ? 'Post' : 'Get';

        $method = "view{$postOrGet}{$func}";
        if (!method_exists($this, $method)) {
            Session::flashError('Unknown method ' . $method);
            $this->redirectToView();
            return;
        }

        // Метод найден, вызовем его!
        $this->{$method}();
    }

    protected function proceedAction()
    {
        $this->_state = self::STATE_ACTION;

        $actionName = Request::getQueryParam('action');
        if (!$actionName) {
            $this->redirectWhenWrongAction();
            return null;
        }

        $action = $this->createViewActionInstance($actionName);
        if (!$action) {
            Session::flashError('Unknown action ' . htmlspecialchars((string) $actionName));
            $this->redirectWhenWrongAction();
            return null;
        }

        /** @var ActionAbstract $action */
        if ($action->onlyAjax && !Request::isAjax()) {
            $this->redirectWhenWrongAction();
            return null;
        }

        if ($action->onlyPost && !Request::isPost()) {
            $this->redirectWhenWrongAction();
            return null;
        }

        if (!$action->hasAccess()) {
            Session::flashError('No access to <strong>' . $action->getSubmenuHtml() . '</strong>');
            $this->redirectWhenWrongAction();
            return null;
        }

        $result = $action();

        if ($action instanceof AjaxChangeField && Request::isPost()) {
            $this->afterSubmit($this->_obj, [], $this->_obj->getAffectedRows(), [], $this->flash, false);
        }

        if (Request::isAjax()) {
            Session::$showAsToast = true;
        }

        return $result;
    }

    protected function createViewActionInstance(string $actionName): ?ActionAbstract
    {
        $actionName = ucfirst($actionName);
        $namespace = Misc::getNamespace($this);
        // Сначала ищем класс в папке текущего модуля, потом в Light
        $classNames = ["$namespace\\Actions\\$actionName", 'Light\\ModuleBase\\Actions\\' . $actionName];
        $className = Misc::getFirstClassExists($classNames);
        if (!$className) {
            return null;
        }

        /** @var ActionAbstract $action */
        return new $className($this->_obj, $this->flash);
    }

    private function redirectWhenWrongAction(): void
    {
        if ($this->_obj->getId()) {
            $this->redirectToView();
        } else {
            $this->redirectToList();
        }
    }

    protected function proceedViewPage()
    {
        $this->_state = self::STATE_VIEW;

        //Наконец, это обычный режим view
        $this->initCssJs();

        $headText = $this->getPageHeadHtml();
        Response::setPageHeadHtml($headText);
        Response::setPageTitle($headText);

        $this->initViewJsData();

        if ($this->folders) {
            // При входе в режиме View на всякий случай запустим процедуру создания папки под данную запись
            // если она по каким-то причинам отсутсвует (в нормальной ситуации папка должна быть создана в proceedSubmit)
            ApplicationAbstract::onAfterSend(function () {
                // Создание папки запустим в асинхронном режиме после отправки отрендеренной страницы пользователю
                // т.к. сетевой диск может тормозить и даже проверка папки на существование может тормозить
                $this->createFolder();
            });
        }

        $this->addModuleSessionNextPrevLinks($this->_obj->getId());
        $this->initViewSubmenu();
        $this->initViewIntro();
        $this->initViewFlash();
        $this->initPrintForms();
        $this->initCopyModes();

        $this->prefetchSelectOptionsView();

        $tdata = [
            'id' => $this->_obj->getId(),
            'module' => $this->getModule(),
            'tabsmenuItems' => $this->getViewEditLogTabsmenuItems(),
            'submenuHtml' => $this->getSubmenuHtml(),
            'content' => $this->getViewEditContentHtml(),
            'ajaxChangeFieldUrl' => $this->getAjaxChangeFieldUrl(),
        ];

        Template::includeTpl(__DIR__ . '/../templates/module/view.tpl.php', $tdata);
    }

    protected function prefetchSelectOptionsView()
    {
        $objData = $this->_obj->getData();
        foreach ($this->getFieldset() as $field) {
            if ($field instanceof FieldOptionsAbstract && $field->hasMode(F::MODE_VIEW)) {
                $fieldKey = $field->key;
                if (array_key_exists($fieldKey, $objData)) {
                    $value = $objData[$fieldKey];
                    $field->prefetchOptions([$value]);
                }
            }
        }
    }

    protected function prefetchSelectOptionsForWriteCompareDbLog(array $oldData, array $newData)
    {
        foreach ($this->getFieldset() as $field) {
            if ($field instanceof FieldOptionsAbstract && $field->hasMode(F::MODE_LOG)) {
                $fieldKey = $field->key;
                $values = [];
                if (array_key_exists($fieldKey, $oldData)) {
                    $values[] = $oldData[$fieldKey];
                }
                if (array_key_exists($fieldKey, $newData)) {
                    $values[] = $newData[$fieldKey];
                }
                if ($values) {
                    $field->prefetchOptions($values);
                }
            }
        }
    }

    protected function getPageHeadHtml($stateText = '')
    {
        $module = $this->getModule();
        $moduleName = ModuleRegister::getModuleName($module);

        $link = Link::create($module, $moduleName)
            ->params($this->getFindidUrlParams() + $this->getModuleSessionUrlParams());

        $headText = "$link - ";
        if ($stateText) {
            $headText .= $stateText . ' ';
        }
        $headText .= '<strong>' . $this->_obj->getDescriptionHtml() . '</strong>';
        $headText .= ' (ID: ' . $this->_obj->getId() . ')';

        return $headText;
    }

    /**
     * Передаёт массив параметров в JS окружение
     */
    protected function initViewJsData()
    {
        Response::addJsData(
            [
                'state' => 'view',
                'id' => $this->_obj->getId(),
                'moduleSessionParams' => http_build_query($this->getModuleSessionUrlParams()),
            ]
        );
    }

    /**
     * Метод для переопределения в классах потоках.
     * Здесь нужно последовательно вызывать метод $this->addSubmenuLink()
     */
    protected function initViewSubmenu()
    {
    }

    /**
     * Метод для переопределения в классах потоках. Здесь нужно последовательно вызывать метод $this->addIntro()
     */
    protected function initViewIntro()
    {
    }

    /**
     * Метод для переопределения в классах потоках. Здесь нужно последовательно вызывать метод Session::flash()
     */
    protected function initViewFlash()
    {
    }

    protected function getViewEditLogTabsmenuItems()
    {
        $out = [];

        $module = $this->getModule();
        $id = $this->_obj->getId();
        $msUrlParams = $this->getModuleSessionUrlParams();
        $subtablesFindidUrlParams = $this->getSubtablesFindidUrlParams();

        $out[] = [
            'text' => 'List',
            'url' => Link::create($module)->params($this->getFindidUrlParams() + $msUrlParams)->getHref(),
            'active' => false,
        ];

        $viewEditLogParams = $msUrlParams + $subtablesFindidUrlParams;

        $out[] = [
            'text' => 'View',
            'url' => Link::create($module)->view($id)->params($viewEditLogParams)->getHref(),
            'active' => $this->_state === self::STATE_VIEW,
        ];

        if ($this->allowEdit && User::hasWriteAccess() && $this->hasWriteAccessRightToObj($this->_obj)) {
            $out[] = [
                'text' => 'Edit',
                'url' => Link::create($module)->edit($id)->params($viewEditLogParams)->getHref(),
                'active' => $this->_state === self::STATE_EDIT,
            ];
        }

        if ($this->allowLog && User::hasLogInPlaceAccess() && $this->hasLogAccessRightToObj($this->_obj)) {
            $out[] = [
                'text' => 'Log ',
                'url' => Link::create($module)->log($id)->params($viewEditLogParams)->getHref(),
                'active' => $this->_state === self::STATE_LOG,
            ];
        }

        if ($this->allowDelete && User::hasDeleteAccess() && $this->hasDeleteAccessRightToObj($this->_obj)) {
            $out[] = [
                'text' => 'Delete',
                'url' => Link::create($module)->params(['delid' => $id] + $viewEditLogParams)->ajax()->getHref(),
                'linkCssClass' => 'del-link',
            ];
        }

        return $out;
    }

    /**
     * Возвращает массив ссылок, которые нужно вывести в submenu.
     * Этот массив формируется из стандартных ссылок:
     * - ссылка на папку
     * - кнопки копирования
     * - кнопки печати
     * - кнопки E-mail диалогов
     * а также дополнительные ссылки добавленные пользователем в метод initViewSubmenu()
     * @return string
     */
    protected function getSubmenuHtml(): string
    {
        $links = [];
        if ($this->folders) {
            $objData = $this->_obj->getData();
            $links[] = $this->fieldFolder()->getHtml('', $objData);
        }

        // Добавим ссылки на различные режимы копирования
        if ($this->isStateView()) {

            if (method_exists($this, 'filesDialogSubmenuLink')) {
                // К этому модулю подключён FilesDialogTrait
                /** @var FilesDialogTrait $moduleWithFilesDialog */
                $moduleWithFilesDialog = $this;
                $links[] = (string)$moduleWithFilesDialog->filesDialogSubmenuLink();
            }

            // Добавим ссылки на копирование, если в данном модуле определены режимы копирования
            $links = array_merge($links, $this->copyModesSubmenuLinks());

            // Добавим ссылки на печать
            $links = array_merge($links, $this->printFormsSubmenuLinks());
        }

        // В конце добавим все ссылки определённые пользователем вручную
        $links = array_merge($links, $this->submenuLinks);

        $rightHtml = $this->getSubmenuCreatedLinkHtml();

        return '<div class="submenu"><div>' . implode('', $links) . '</div><div>' . $rightHtml . '</div></div>';
    }

    protected function getSubmenuCreatedLinkHtml(): string
    {
        $id = $this->_obj->getId();
        $created = Db::getRow(
            "SELECT u.Name, t.CreatedBy, t.CreatedDateTime
                 FROM user u JOIN {$this->getDbTable()} t ON u.ID = t.CreatedBy
                 WHERE t.ID = $id"
        );
        if (!$created) {
            return '';
        }
        $mailSubject = strip_tags((string) $this->_obj->getDescriptionHtml());
        $mailBody = $this->_obj->getLinkView()->absolute()->getHref() . "\n\n";
        $beautyDateTime = FieldDateTime::beautify($created['CreatedDateTime']);
        $beautyDate = FieldDate::beautify($created['CreatedDateTime']);
        $title = Config::$lang === 'ru' ? 'Создано' : 'Created by';
        $title .= ': ' . $created['Name'] . ' @ ' . $beautyDateTime;
        $linkText = htmlspecialchars((string) $created['Name']) . ' @ ' . $beautyDate;
        return Link::userDialog($created['CreatedBy'], $linkText, $mailSubject,
            $mailBody)
            ->cssClass('CreatedLink')
            ->title($title)
            ->getHtml();
    }

    final protected function isStateView()
    {
        return $this->_state == self::STATE_VIEW;
    }

    protected function proceedEdit()
    {
        $id = (int)$_GET['editid'];
        // Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
        if (!$this->loadObjAndCheck($id)) {
            // Внутри произошел redirect и метод loadObjAndCheck() вернул false
            return null;
        }

        $this->_state = self::STATE_EDIT;

        // Проверим права на запись текущего объекта
        if (!$this->allowEdit || !User::hasWriteAccess() || !$this->hasWriteAccessRightToObj($this->_obj)) {
            //При обычном запросе сделаем редирект на режим просмотра с flash сообщенеим
            Session::flashError('No access to edit');
            $this->redirectToView();
        }

        $this->initCssJs();

        $headText = $this->getPageHeadHtml('Edit');
        Response::setPageHeadHtml($headText);
        Response::setPageTitle($headText);

        $this->initEditJsData();
        $this->addModuleSessionNextPrevLinks($this->_obj->getId(), 'editid');
        $this->initEditSubmenu();
        $this->initEditFlash();
        $this->initEdit();

        $tdata = [
            'module' => $this->getModule(),
            'tabsmenuItems' => $this->getViewEditLogTabsmenuItems(),
            'submenuHtml' => $this->getSubmenuHtml(),
            'formActionUrl' => $this->getEditFromActionUrl(),
            'content' => $this->getViewEditContentHtml(),
            'formactions' => $this->getFormactionsHtml(),
            'fields' => $this->getFieldsForFormJs(),
            'ajaxChangeFieldUrl' => $this->getAjaxChangeFieldUrl(),
        ];

        Template::includeTpl(__DIR__ . '/../templates/module/edit.tpl.php', $tdata);
    }

    protected function getEditFromActionUrl()
    {
        $formActionUrlParams = ['submitid' => $this->_obj->getId()]
            + $this->getModuleSessionUrlParams()
            + $this->getSubtablesFindidUrlParams();
        return Link::create($this->getModule())->params($formActionUrlParams)->getHref();
    }

    protected function proceedSelect2ajax(): ?array
    {
        if (!Request::isAjax()) {
            $this->redirectToList();
            return null;
        }

        if (isset($_GET['fromFilters'])) {
            // Внутри proceedFilters есть обработка поля select2ajax
            $this->proceedFilters();
            return null;
        }

        if (isset($_GET['fromDeleteDialogId'])) {
            // Запрос на получение элементов для select2ajax пришел из диалога удаления, когда текущий элемент
            // требуется заменить на какой-то новый
            $field = $this->whileDeleteFieldForReplacement((int)$_GET['fromDeleteDialogId']);
            /** @var FieldSelect $field */

            $select2ajaxController = new Select2ajaxController();
            return $select2ajaxController->proceed($field, Request::$psr7);
        }

        // Иначе ищем поле среди полей объекта или подтаблиц
        $fieldKey = $_GET['select2ajax'];

        $res = $this->getFieldset()->proceedSelect2ajax($fieldKey);
        if ($res === null) {
            // Если поле не найдено среди полей объекта, возможно это поле в подтаблице?
            preg_match_all('/(\w+)\[(\d+)\]\[(\w+)\]/', (string) $fieldKey, $mathes);
            if (count($mathes) === 4 && isset($mathes[1][0]) && $mathes[1][0]) {
                $subtableName = $mathes[1][0];
                // Отрезаем хвост "Subtable", который фигурирует в имени поля пришедшем в $_GET
                if (substr((string) $subtableName, -1 * strlen('Subtable')) === 'Subtable') {
                    $subtableName = substr((string) $subtableName, 0, -1 * strlen('Subtable'));
                }
                $subtableClass = PROJECT_NAMESPACE . '\\Module\\' . $this->getModule() . '\\Subtables\\' . $subtableName;
                if ($this->hasSubtable($subtableClass)) {
                    $subtable = $this->getSubtable($subtableClass);
                    $fieldKey = $mathes[3][0] ?? '';
                    $res = $subtable->getFieldset()->proceedSelect2ajax($fieldKey);
                }
            }
        }

        return $res;
    }

    /**
     * Возвращает массив параметров, которые нужно передать в JS окружение через атрибут data-params
     */
    protected function initEditJsData()
    {
        Response::addJsData(
            [
                'state' => 'edit',
                'id' => $this->_obj->getId(),
                'moduleSessionParams' => $this->getModuleSessionUrlParams(),
            ]
        );
    }

    /**
     * Метод для переопределения в классах потоках
     */
    protected function initEditFlash()
    {
    }

    /**
     * Метод для переопределения в классах потоках
     */
    protected function initEdit()
    {
    }

    /**
     * Метод для переопределения в классах потоках
     */
    protected function initEditSubmenu()
    {
    }

    /**
     * Метод для переопределения в классах потоках
     */
    protected function initEditIntro()
    {
    }

    protected function getLogTable()
    {
        return new ModuleAbstractLog($this->_obj->getId());
    }

    protected function proceedLog()
    {
        $this->_state = self::STATE_LOG;

        $id = (int)$_GET['logid'];

        //Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
        if (!$this->loadObjAndCheck($id)) {
            // Внутри произошел redirect и метод loadObjAndCheck() вернул false
            return null;
        }

        if (!$this->allowLog || !User::hasLogInPlaceAccess() || !$this->hasLogAccessRightToObj($this->_obj)) {
            Session::flashError('No access to log');
            $this->redirectToView();
            return null;
        }

        //В момент создания объект CrudModuleLog загрузит данные из базы и инициализирует фильты
        $logTable = $this->getLogTable();

        if (Request::isXls()) {

            $logTable->sendExcel();

        } elseif (Request::isAjax()) {

            print $logTable->getHtmlTableForAjax();

        } else {

            $this->initCssJs();

            $headText = $this->getPageHeadHtml('Log');
            Response::setPageHeadHtml($headText);
            Response::setPageTitle($headText);

            Response::addJsData(['state' => 'log', 'id' => $id]);
            $this->addModuleSessionNextPrevLinks($id, 'logid');

            $tdata = [
                'table' => $logTable->getHtmlTable(),
                'tabsmenuItems' => $this->getViewEditLogTabsmenuItems(),
                'filters' => $logTable->getFiltersHtml(),
            ];

            Template::includeTpl('module/log.tpl.php', $tdata);
        }
    }

    protected function proceedSubmit()
    {
        $this->_state = self::STATE_SUBMIT;

        $id = (int)$_GET['submitid'];

        if (!Request::isPost() || !Request::isAjax()) {
            // Непонятно как пользователь сформировал не POST или не AJAX запрос (возможно, его разлогинило, а потом перезашел)
            // Просто перенаправим на режим view
            $urlParams = [];
            if ($id) {
                $urlParams = ['id' => $id];
            }
            Response::redirect($this->getModule(), $urlParams);
            return null;
        }

        // Проверим, есть ли права на запись в этот модуль?
        if (!$this->allowEdit || !User::hasWriteAccess()) {
            $this->riseSaveErorr('No access to edit');
            $this->abortSubmit(403);
            return null;
        }

        if ($id === 0) {

            // Проверим можно ли вообще добавлять новые элементы, если это режим добавления?
            if (!$this->allowAdd) {
                $this->riseSaveErorr('Add new records not allowed');
                $this->abortSubmit(403);
                return null;
            }

        } else {

            if (!$this->loadObjAndCheck($id)) {
                // Внутри произошел redirect и метод loadObjAndCheck() вернул false
                return null;
            }

            //Проверим права доступа к редактированию именно этого объекта
            if (!$this->hasWriteAccessRightToObj($this->_obj)) {
                $this->riseSaveErorr("No access to edit record with ID=$id");
                $this->abortSubmit(403);
                return null;
            }
        }

        $timer = new Timer();

        $dataFromPost = $this->getDataFromPost();
        $this->validateUnique($dataFromPost);
        $this->validateMandatory($dataFromPost);
        $this->validateMandatorySubtables($dataFromPost);
        $this->afterValidation($dataFromPost, $this->_saveErrors);

        if ($this->hasCriticalErrors()) {
            $this->abortSubmit(400);
            return null;
        }

        // Были ли другие ошибки? Если были, нажал ли пользователь кнопку Save Anyway?
        if (!isset($_REQUEST['saveanyway']) && count($this->_saveErrors) > 0) {
            $this->abortSubmit(400);
            return null;
        }

        $oldData = $this->getOldDataBeforeSubmitOrDelete($this->_obj);
        $this->_obj->loadSubtablesForChangeDetection();

        // Оставим только те поля, у которых есть F::MODE_SUBMIT
        $fieldsData = $this->_obj->filterDataByModeSubmit($dataFromPost);

        // Проверим по оставшимся полям функцию editAccessFunction
        $fieldsData = $this->_obj->filterDataByEditAccessFunction($fieldsData);

        $this->prefetchSelectOptionsForWriteCompareDbLog($oldData, $fieldsData);

        try {
            Db::begin();

            if ($this->_obj->getId() === 0) {
                $this->_obj->insertDb($fieldsData);
                $this->_justInserted = true;
                $affectedRows = 1;
            } else {
                $affectedRows = $this->_obj->updateDb($fieldsData);
            }

            $subtablesAffectedRows = $this->proceedSubmitSubtables();
            if ($subtablesAffectedRows) {
                // Если в подтаблицах что-то поменялось, заново загрузим основной объект, т.к. в нём могут быть
                // вычисляемые или кешируюище поля зависящие от подтаблиц, это может быть важно
                $this->_obj->loadData();
            }

            $affectedRows += $subtablesAffectedRows;

            if ($this->folders) {
                $this->createFolder();
            }

            $afterSubmitTimer = new Timer();

            $this->_obj->loadSubtablesForChangeDetection();
            $this->_obj->afterSubmit($this->flash);
            $this->afterSubmit($this->_obj, $dataFromPost, $affectedRows, $oldData, $this->flash, false);

            Metric::newrelicMetric($this->getModule() . '/afterSubmit', $afterSubmitTimer->getMs());

            Db::commit();

        } catch (Throwable $e) {

            ErrorHandler::reportException($e);
            $this->riseSaveErorr($e->getMessage());
            $this->abortSubmit(500);
            return null;

        }

        $textSaved = Config::$lang === 'ru' ? 'сохранён' : 'saved';
        $msg = "<strong>{$this->_obj}</strong> $textSaved";
        $this->flash->addSuccess($msg);

        Metric::newrelicMetric($this->getModule() . '/save', $timer->getMs());

        $this->redirectAfterSubmit();
    }

    protected function redirectAfterSubmit()
    {
        $module = $this->getModule();

        if ($this->continmethod) {
            $continmethod = $_POST['_continmethod'] ?? 'view';
            User::setParam($module . '_continmethod', $continmethod);
        } else {
            $continmethod = 'view';
        }

        $urlParams = [];

        if ($continmethod === 'list') {
            $urlParams = $this->getFindidUrlParams();
        } elseif ($continmethod === 'edit') {
            $urlParams['editid'] = $this->_obj->getId();
            $urlParams += $this->getSubtablesFindidUrlParams();
        } elseif ($continmethod === 'addnew') {
            $urlParams['addnew'] = 1;
        } else {
            // По умолчанию переходим в режим просмотра только что созданного объекта
            $urlParams['id'] = $this->_obj->getId();
            $urlParams += $this->getSubtablesFindidUrlParams();
        }

        if ($continmethod !== 'addnew') {
            $urlParams += $this->getModuleSessionUrlParams();
        }

        Response::redirect($module, $urlParams);
    }

    /**
     * Метод запускается перед сохранением объекта (перед $this->proceedInsertUpdateObj($dataFromPost))
     * Старые данные сохраняются во временную переменную, чтобы потом быть передаными в метод afterSubmit()
     * Иногда, старые данные нужно расширить, например, данными из подтаблиц - тогда этот метод можно переопределить в
     * классах потомках
     * @return array
     * @deprecated
     */
    public function getOldDataBeforeSubmitOrDelete(ObjAbstract $obj)
    {
        $subtablesToLoad = [];
        foreach ($this->subtables as ['class' => $subtableClass]) {
            // Напрямую создаём объект подтаблицы (не используя метод $this->getSubtable(), чтобы получить подтаблицу
            // для указанного $obj (особенно важно для Multiple Update)
            $subtable = new $subtableClass($obj);
            if ($subtable instanceof Subtable) {
                $subtablesToLoad[] = $subtable;
            }
        }
        return $obj->getDataWithSubtables($subtablesToLoad);
    }

    private function abortSubmit(int $code): void
    {
        Response::json($this->_saveErrors, $code);
    }

    protected function getDataFromPost()
    {
        return $this->getFieldset()->filterByMode(F::MODE_EDITSUBMIT)->getDataFromRequest($_POST);
    }

    protected function validateUnique(array $dataFromPost)
    {
        $nameFieldKey = $this->getNameFieldKey();
        if (isset($dataFromPost[$nameFieldKey]) && $dataFromPost[$nameFieldKey] != '') {
            //Проверим существование такого имени в базе по полю $nameFieldKey
            $this->validateUniqueInName($dataFromPost[$nameFieldKey], $nameFieldKey);

            if ($this->hasField('Synonyms')) {
                $this->validateUniqueInSynonyms($dataFromPost[$nameFieldKey], $nameFieldKey);
            }
        }

        if (isset($dataFromPost['Synonyms'])
            && $dataFromPost['Synonyms'] !== ''
            && $this->hasField('Synonyms')
        ) {
            $synArr = explode("\n", (string) $dataFromPost['Synonyms']);
            $synArr = array_map('trim', $synArr);
            $synArr = array_filter($synArr);

            foreach ($synArr as $v) {
                $this->validateUniqueInName($v, 'Synonyms');
                $this->validateUniqueInSynonyms($v, 'Synonyms');
            }
        }
    }

    protected function getNameFieldKey()
    {
        return $this->_obj->getNameFieldKey();
    }

    /**
     * @param string $name текущее значение поля Name, которое ввёл пользователь
     * @param string $fieldKey ключ поля которое подсветить в случае ошибки
     */
    protected function validateUniqueInName($name, $fieldKey = null)
    {
        $nameFieldKey = $this->getNameFieldKey();

        $id = $this->_obj->getId();
        $query = "SELECT ID
                  FROM " . $this->getDbTable() . "
                  WHERE " . Db::quote($nameFieldKey) . " = " . Db::escString($name) . "
                      AND ID != $id";

        if ($existId = Db::getOne($query)) {
            if ($fieldKey === null) {
                $fieldKey = $nameFieldKey;
            }
            $this->riseSaveErorrAlreadyExists($name, $existId, $fieldKey);
        }
    }

    /**
     * @param string $name текущее значение поля Name, которое ввёл пользователь
     * @param int $existId найденное значение уже существующего элемента в базе
     * @param string $fieldKey ключ поля которое подсветить в случае ошибки
     * @return void
     */
    protected function riseSaveErorrAlreadyExists($name, $existId, $fieldKey)
    {
        $alreadyExistsText = Config::$lang === 'ru' ? 'уже существует' : 'already exists';
        $viewText = Config::$lang === 'ru' ? 'посмотреть...' : 'view...';
        $msg = ' <strong>' . htmlspecialchars($name) . '</strong> '
            . $alreadyExistsText
            . ', '
            . Link::create($this->getModule(), $viewText)->view($existId)->blank()->getHtml();
        $this->riseSaveErorr($msg, $fieldKey);
    }

    /**
     * @param string $name текущее значение поля Name, которое ввёл пользователь
     * @param string $fieldKey ключ поля которое подсветить в случае ошибки
     */
    protected function validateUniqueInSynonyms($name, $fieldKey = null)
    {
        $query = "SELECT ID
                  FROM " . $this->getDbTable() . "
                  WHERE CONCAT('\n', Synonyms, '\n') LIKE '%\n" . Db::esc($name) . "\n%'
                      AND ID != " . $this->_obj->getId();

        if ($existId = Db::getOne($query)) {
            if ($fieldKey === null) {
                $fieldKey = $this->getNameFieldKey();
            }
            $this->riseSaveErorrAlreadyExists($name, $existId, $fieldKey);
        }
    }

    protected function validateMandatory(array $dataFromPost)
    {
        // Проверим заполненность полей, помеченных как editMandatory
        $errors = $this->getFieldset()->validateMandatory($dataFromPost, $this->_obj->getData());
        foreach ($errors as $error) {
            $this->riseSaveErorr($error['msg'], $error['fieldKey']);
        }
    }

    protected function validateMandatorySubtables(array $dataFromPost)
    {
        // Далее проверим заполненность полей внутри подтаблиц
        if (!$this->subtables) {
            return;
        }

        // Перебираем все подтаблицы
        foreach ($this->subtables as $subtableClass => ['readonly' => $readonly]) {

            if ($readonly) {
                continue;
            }

            $subtable = $this->getSubtable($subtableClass);
            foreach ($subtable->getNotDeletedDataFromReqeust() as ['fieldsData' => $rowData, 'rowNum' => $rowNum]) {
                $errors = $subtable->getFieldset()->validateMandatory($rowData);
                if (!$errors) {
                    continue;
                }

                $lineObj = clone $subtable->getObj();
                foreach ($errors as $error) {
                    $fieldKey = $this->getTableName() . 'Subtable[' . $rowNum . '][' . $error['fieldKey'] . ']';
                    $msg = $error['msg'];
                    $lineDescription = $lineObj->setData($rowData)->getDescriptionHtml();
                    if ($lineDescription) {
                        $msg .= " ($lineDescription)";
                    }
                    $this->riseSaveErorr($msg, $fieldKey);
                }
            }
        }
    }

    /**
     * Метод для переопределения в классах потомках, если нужно сделать что-то особенное после валидации данных
     * Например, если нет ошибок отправить пользователю ещё какой-то промежуточный диалог-подтверждение или записать логи
     *
     * @param array $saveErrors ошибки, которые были найдены привалидации, передаются сюда в явном виде для удобства.
     * Их можно было бы не передавать, т.к. доступ к ошибкам можно получить через $this->_saveErrors, но это менее наглядно
     */
    protected function afterValidation(array $dataFromPost, array $saveErrors)
    {
    }

    /**
     * Метод проверяет массив $this->_saveErrors на наличие критических ошибок
     * Если критических ошибок нет, то возвращает false - это значит можно продолжить сохранение
     */
    protected function hasCriticalErrors()
    {
        foreach ($this->_saveErrors as $error) {
            //Как только найдём первую критическую ошибку, возвращаем true
            if ($error['critical']) {
                return true;
            }
        }

        return false;
    }

    protected function proceedSubmitSubtables()
    {
        $affectedRows = 0;
        if ($this->subtables) {
            //Перебираем все подтаблицы и вызываем метод getDataFromRequest у каждой из них
            foreach ($this->subtables as $subtableClass => ['readonly' => $readonly]) {
                if (!$readonly) {
                    /** @var Subtable $subtable */
                    $subtable = $this->getSubtable($subtableClass);
                    $affectedRows += $subtable->proceedSubmit($this->flash);
                }
            }
        }

        return $affectedRows;
    }

    /**
     * Вызывается после создания ноыой записи из Add New и при редактировании записи через Edit или Multiple Update
     * (иными словами самый универсальный метод - вызывается всегда! Если нужно сделать какое-то обновление кеширующих
     * данных во всех случаях, этот метод подходит лучше всего)
     * @param int $affectedRows
     * @param bool $fromMultipleUpdate
     *
     */
    protected function afterSubmit(
        ObjAbstract $obj,
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff,
        $fromMultipleUpdate
    ) {
    }

    /**
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws Exception
     */
    protected function proceedList()
    {
        // Сначала проверяем все невозможные ситуации (которые должены приводить к редиректу обратно на list)

        if (!$this->allowExportToExcel && Request::isXls()) {
            // Если в параметрах url передан xls=1, но при этом экспорт в Excel запрещён, то просто делаем редирект
            $this->redirectToList();
            return;
        }

        if (isset($_GET['loadTableLayoutSettings'])) {
            $this->proceedTableLayoutSettingsDialog();
            return;
        }

        if (isset($_GET['action'])) {
            return $this->proceedAction();
        }

        if (isset($_GET['renderRowForAjax']) && $_GET['renderRowForAjax'] === $this->getTableName() && Request::isAjax()) {
            // Пользователь поменял какое-то поле с помощью changeField в режиме таблицы и теперь нужно перезагрузить всю строку
            $this->proceedListRenderRowForAjax();
            return;
        }

        $this->proceedFilters();

        if (isset($_POST['filterValues']) && !Request::isAjax()) {
            // Пы попали на эту страницу каким-то POST запросом устанавилвающим значения фильтров
            // Чтобы при нажатии кнопки "Назад" в браузере не было смущающего сообщения, сделаем
            // редирект с помощью GET (фильтры уже сохранены в настройках пользователя благодаря $this->proceedFilters();
            // и они не потеряются)
            Response::redirectUrl($_SERVER['REQUEST_URI']);
            return;
        }

        if (Request::isXls()) {
            $this->proceedListSendExcel();
            return;
        }

        if (Request::isAjax()) {
            $this->proceedListRenderTableForAjax();
            return;
        }

        // При первоначальном рендеринге не загружаем данные
        // Данные появятся на экране пользователя в результате ajax запроса, который произойдёт сразу после загрузки
        // страницы
        $this->proceedListRenderInitialPage();
    }

    protected function proceedTableLayoutSettingsDialog()
    {
        $this->initFieldsFromSubtables();
        $this->initList();
        $this->initLayoutable();
        $this->showTableLayoutSettingsDialog();
    }

    /**
     * При первоначальном заходе на страницу ?a=<module> мы рендерим фильтры, но не выводим содерижмое таблицы
     * (даже не загружаем данные этой таблицы).
     *
     * Таблица будет загружена отдельным ajax запросом (см. renderListTable())
     * @throws \Exception
     */
    protected function proceedListRenderInitialPage()
    {
        $this->_state = self::STATE_LIST;

        $moduleName = ModuleRegister::getModuleName($this->getModule());
        Response::setPageHeadHtml('<strong>' . $moduleName . '</strong>');
        Response::setPageTitle($moduleName);

        $this->initCssJs();
        // $this->initList();
        $this->initListJsData();
        $this->initListIntro();
        $this->initListFlash();
        $this->initListLinks();
        $this->initListTableLinkModule();

        //Это обычный запрос на рендринг таблицы в режиме List
        $tdata = [
            'table' => $this->getHtmlTableDivWrapper() . '</div>',
            'linksHtml' => $this->getListLinksHtml(),
            'badgeMessagess' => $this->getListBadgeMessagess(),
            'filters' => $this->getFiltersHtml(),
        ];

        Template::includeTpl('module/list.tpl.php', $tdata);
    }

    protected function getAjaxChangeFieldUrl(): string
    {
        return Link::create($this->getModule())->action(AjaxChangeField::class)->getHref() . '&id=';
    }

    protected function initListTableLinkModule()
    {
        // Параметр из класса Table: имя модуля на который автоматически делать ссылки в таблице
        // Этот параметр также определяем модуль для перехода при двойном клике

        // Используется как при рендере div обёртки воркуг таблицы, так и при рендере отдельных ячеек
        $this->tableLinkModule = $this->tableLinkModule ?: $this->getModule();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws Exception
     */
    protected function proceedListSendExcel()
    {
        $this->_state = self::STATE_LIST_EXCEL;

        $this->prepareListFieldsBeforeRender();
        $this->initSortable();

        $this->tableLoadData();
        $this->sendExcel();
    }

    /**
     * Этот метод вызывается либо при рендеринге html вида таблицы с помощью ajax запроса от бразуера
     * либо пре экспорте таблицы в Excel
     */
    protected function proceedListRenderTableForAjax()
    {
        $this->_state = self::STATE_LIST;

        $this->prepareListFieldsBeforeRender();

        $this->initSortable();
        $this->initPaginatable();
        $this->initListTableLinkModule();

        $this->tableLoadData();

        print $this->getHtmlTableForAjax();
    }

    /**
     * Метод вызывается после изменения поля в строке с помощью ajax
     */
    public function proceedListRenderRowForAjax(): void
    {
        $id = (int)$_GET['rowId'];
        if (!$id) {
            Response::abort(400);
            return;
        }

        $this->prepareListFieldsBeforeRender();
        $this->initListTableLinkModule();

        $this->tableLoadListRowForAjax($id);

        if (!$this->tableData) {
            Response::abort(404);
            return;
        }

        print $this->getHtmlTableTrForAjax();
    }

    protected function tableLoadListRowForAjax(int $id)
    {
        $query = $this->getDataQuery([$id]);
        $this->tableData = Db::getAll($query);
    }

    /**
     * Метод используется в трёх сценариях:
     * 1) При рендеринге таблицы в html
     * 2) При отправке таблицы в Excel
     * 3) При ajax рендеринге отдельной строки
     */
    protected function prepareListFieldsBeforeRender()
    {
        // В рамках подготовки списка полей перед выводом на экран нужно инициализировать все massactions,
        // только тогда будет понтно, выводить ли колонку с чекбоксами?
        if (!Request::isXls()) {
            $this->initMassactions();

            if (method_exists($this, 'filesDialogAddMassaction')) {
                /** @var FilesDialogTrait $moduleWithFilesDialog */
                $moduleWithFilesDialog = $this;
                $moduleWithFilesDialog->filesDialogAddMassaction();
            }

            // Добавляем опции печати (они также отображаются в массовых действиях)
            $this->initPrintForms();
            $this->printFormsAddMassaction();
        }

        // Если у пользователя нет прав на редактирование в этом модуле - нужно закрыть для редактирования все поля
        // Это особо актуально для полей с ajax редактированием, чтобы они не рендрились в таблицу как редактируемые
        if (!Request::isXls() && (!$this->allowEdit || !User::hasWriteAccess())) {
            $this->disableAllFieldsEdit();
        }

        // Регистрирует поля типа Folder, Checkbox, Del, ...
        $this->initTableFieldsAdditional();
        // Добавим поля, которые объявлены как кеширующие в подтаблицах
        $this->initFieldsFromSubtables();
        // Метод initList() переопределяется в классх потоках - там добавляются поля необходимые только для режима List
        $this->initList();

        $this->initLayoutable();
    }

    protected function initListJsData()
    {
        $findid = $this->getTableFindIdFromRequest();
        $pageFromModuleSession = $this->getTablePageFromModuleSession($findid);

        Response::addJsData([
            'state' => 'list',
            'listInitialFindid' => $this->getTableFindIdFromRequest(),
            'listInitialPage' => $pageFromModuleSession ?? $this->getTablePage(),
        ]);
    }

    protected function initListIntro()
    {
    }

    /**
     * Метод для переопределения в классах потомках для вывода Flash уведомлений над списком.
     * Удобно, когда нужно вывести какие-то сообщения о счетах ожидающих выгрузки или проверки.
     * Особенность этого метода в том, что он запускается только при рендеринге HTML страницы List
     * (и не запускается при различных ajax запросах в режиме лист,
     * не запускается при ajax обновлении таблицы, не запускается при экспорте в Excel)
     * Внутри нужно вызывать Session::flashSuccess(...)
     */
    protected function initListFlash()
    {
    }

    protected function addIntro($selector, $text)
    {
        IntroManager::addIntro($selector, $text, $this->_state);
    }

    /**
     * Метод для переопределения в классах потомках - здесь нужно вызывать метод $this->addListLink()
     */
    protected function initListLinks()
    {
    }

    final protected function addListLink(Link $link)
    {
        $this->listLinks[] = $link;
    }

    protected function getListLinksHtml(): string
    {
        $links = [];
        if ($this->allowAdd && User::hasWriteAccess()) {
            $links[] = $this->getAddNewLink();
        }

        /** @var Link $link */
        foreach ($this->listLinks as $link) {
            $link->cssClass('btn-flat waves-effect');
        }

        $links = array_merge($links, $this->listLinks);

        return implode(' ', $links);
    }

    protected function getAddNewLink(): Link
    {
        return Link::create($this->getModule(), '+')
            ->addNew()
            ->cssClass('btn-floating btn waves-effect btn-addnew');
    }

    protected function isStateList()
    {
        return $this->_state == self::STATE_LIST;
    }

    final protected function isStateAddNew()
    {
        return $this->_state == self::STATE_ADDNEW;
    }

    final protected function isStateEdit()
    {
        return $this->_state == self::STATE_EDIT;
    }

    final protected function isStateSubmit()
    {
        return $this->_state == self::STATE_SUBMIT;
    }

    final protected function isStateLog()
    {
        return $this->_state == self::STATE_LOG;
    }

    protected function getExcelFilename()
    {
        return ModuleRegister::getModuleName($this->getModule());
    }

    protected function getExcelWorksheetName()
    {
        return ModuleRegister::getModuleName($this->getModule());
    }

    final protected function addSubmenuLink(Link $link)
    {
        if ($link->module === $this->getModule()) {
            $link->params($this->getModuleSessionUrlParams());
        }
        $this->submenuLinks[] = $link;
    }

    final protected function addSubmenuLinkViewFunc($innerHtml, string $func): Link
    {
        $link = Link::create($this->getModule(), $innerHtml)
            ->view($this->_obj->getId())
            ->func($func);

        $this->addSubmenuLink($link);

        // Метод возващает ссылку, чтобы к ней можно было добавить опций по месту вызова
        // $this->addSubmenuLinkViewFunc(..., ...)->params(...)->ajax()->post()->...
        return $link;
    }

    final protected function addSubmenuAction(string $actionClass): Link
    {
        /** @var ActionAbstract $action */
        $action = new $actionClass($this->_obj, $this->flash);

        $link = Link::create($this->getModule(), $action->getSubmenuHtml())
            ->view($this->_obj->getId())
            ->action($actionClass);

        /** @var ActionAbstract $actionClass */
        if ($action->onlyAjax) {
            $link->ajax();
        }
        if ($action->onlyPost) {
            $link->post();
        }

        // Добавляем в подменю, только если есть доступ к этому Action
        if ($action->hasAccess()) {
            $this->addSubmenuLink($link);
        }

        // Метод возващает ссылку, чтобы к ней можно было добавить опций по месту вызова
        // $this->addSubmenuAction(..., ...)->params(...)->ajax()->post()->...
        return $link;
    }

    final protected function riseSaveWarning($msg, $fieldKey = '')
    {
        $this->riseSaveErorr($msg, $fieldKey, false);
    }

    /**
     * Добавляет сообщение в массив ошибок при сохранении
     * @param string $msg текстовое сообщение для вывода в диалоговое окно
     * @param string $fieldKey имя поля, которое заполнено не верно
     * @param bool $critical если этот параметр false - ошибка считается не критичной.
     * Если все ошибки не критичные, то у пользователя будет возможность нажать "Save Anyway"
     * @return void
     * @internal param string $hint
     */
    final protected function riseSaveErorr($msg, $fieldKey = '', $critical = true)
    {
        $this->_saveErrors[] = [
            'msg' => $msg,
            'fieldKey' => $fieldKey,
            'critical' => $critical,
        ];
    }

    final protected function riseSaveErorrSubtable(
        $msg,
        string $subtableName,
        int $rowNum,
        string $fieldKey,
        $critical = true
    ) {
        // Если имя класс передано с помощью Fully Qualified Class Name, то приведём к короткому виду
        $subtableName = Misc::getClass($subtableName);
        $fieldKey = "{$subtableName}Subtable[$rowNum][$fieldKey]";
        $this->riseSaveErorr($msg, $fieldKey, $critical);
    }

    /**
     * @param string $subtableClass имя класса подтаблицы без суффикса Subtable и без неймспейса текущего модуля
     * @return void
     */
    protected function addSubtable(string $subtableClass)
    {
        $this->subtables[$subtableClass] = [
            'class' => $subtableClass,
            'readonly' => false,
        ];
    }

    protected function addSubtableReadonly(string $subtableClass)
    {
        $this->subtables[$subtableClass] = [
            'class' => $subtableClass,
            'readonly' => true,
        ];
    }

    protected function hasSubtable(string $subtableClass): bool
    {
        return isset($this->subtables[$subtableClass]);
    }

    /**
     * @param string $subtableClass имя класса подтаблицы
     * @return Subtable
     */
    protected function getSubtable(string $subtableClass)
    {
        if (!isset($this->subtables[$subtableClass]['instance'])) {
            $this->subtables[$subtableClass]['instance'] = new $subtableClass($this->_obj);
        }

        return $this->subtables[$subtableClass]['instance'];
    }

    /**
     * Метод для переопределения в классах потомках
     * должен вернуть какое-то дополнительное SQL условие на основе которого в режиме List и в режимах View/Edit
     * будет ограничен доступ к объекту
     * @return string
     */
    protected function getAccessSqlWhere(): string
    {
        return '';
    }

    /**
     * Метод для переопределения в классах потоках - здесь проверяются права доступа к объекту в режимах View/Edit/Log/Delete
     * @return bool
     */
    protected function hasAccessRightToObj(ObjAbstract $obj)
    {
        $accessSqlWhere = $this->getAccessSqlWhere();
        if ($accessSqlWhere) {
            // Есть какое-то дополнительное ограничивающее SQL условие.
            // Проверим, удовлетворяет ли текущий объект этому условию?
            $id = $obj->getId();
            $dbTable = $this->getDbTable();
            return (bool)Db::getOne("SELECT ID FROM $dbTable WHERE ID = $id AND ($accessSqlWhere)");
        }

        // Если никаких дополнительных органичений в классе потомке не накладывается,
        // то по умолчанию возвращаем логику описанную в Obj
        return $obj->accessRead();
    }

    protected function initFieldsFromSubtables()
    {
        foreach ($this->subtables as ['class' => $subtableClass]) {
            $subtable = $this->getSubtable($subtableClass);
            // Добавим новые поля
            // Эти поля объявлены внутри подтаблиц с переменных $this->groupconcatFieldKeys и $this->_cacheFieldsKeys
            $subtableFields = $subtable->getFieldsForMasterModuleList();
            if ($subtableFields) {
                $this->addFields($subtableFields);
            }
        }
    }

    /**
     * Метод для переопределения в классах потомках, если нужно добавить какие-то поля в режиме List
     */
    protected function initList()
    {
    }

    /**
     * В режиме List иногда удобно превратить обычное поле типа Money в поле типа MoneyWithCurrency
     * чтобы внизу total считался с учётом валюты
     */
    protected function addListFieldMoneyWithCurrency(string $moneyFieldKey, string $currencyFieldKey = 'CurrencyISO')
    {
        /** @var FieldMoney $moneyField */
        $moneyField = $this->getField($moneyFieldKey);
        $this->addField(
            FieldMoneyWithCurrency::createFromMoney($moneyField, $currencyFieldKey)
        );
    }

    /**
     * Убираем у всех полей MODE_EDIT. Это актуально для ajax поле, если у текущего пользователя нет прав на редактирование в этом
     * модуле или в отдельном объекте, то они не будут рендриться как ajax-editable
     */
    protected function disableAllFieldsEdit()
    {
        /** @var AjaxChangeField $ajaxChageFieldAction */
        $ajaxChageFieldAction = $this->createViewActionInstance(Misc::getClass(AjaxChangeField::class));
        foreach ($this->getFieldset() as $field) {
            // Для ajax полей особый подход к отключению редактирования: нужно отдельно вызвать функцию,
            // которая может быть переопределена
            if ($this->allowEdit
                && $field->ajax
                && User::hasWriteAccess()
                && $ajaxChageFieldAction->hasAccessAjaxChangeField($field->key)) {
                // Это ajax поле можно редактировать, не смотря на то, что в целом для текущего пользователя
                // все поля должны быть отключены от редактирования
                continue;
            }
            $field->setModes($field->getModes() & ~F::MODE_EDITSUBMIT);
        }
    }

    /**
     * Регистрирует дополнительные поля для таблицы списка, такие как CheckBox, DEL ...
     * @return void
     */
    protected function initTableFieldsAdditional()
    {
        if ($this->massactions) {
            $this->addCheckboxCol();
        }

        if ($this->folders) {
            $this->getFieldsetFirst()->addField($this->fieldFolder());
            $this->addFields(
                $this->fieldFolderHasFiles(),
                $this->fieldFolderFilesList(),
                $this->fieldFolderFilesCount(),
                $this->fieldFolderSize()
            );
        }

        if (User::hasWriteAccess()) {

            $module = $this->getModule();

            if ($this->hasField('Color')) {
                $colorField = $this->getField('Color');
                $colorField->setParam(
                    'source',
                    Link::create($module)->action(AjaxChangeField::class)->getHref() . '&id={id}',
                );
                $this->getFieldsetFirst()->addField($colorField);
            }
        }

        $this->initTableFieldAction();
    }

    protected function initTableFieldAction()
    {
        if (!$this->allowDelete && !$this->allowEdit) {
            return;
        }

        if (!User::hasWriteAccess()) {
            return;
        }

        $module = $this->getModule();

        $params = [
            'nameTable' => '',
            'tableOrderBy' => '',
            'tableAlign' => 'center',
        ];
        //Ширина колонки Del зависит от наличия иконки удаления
        if ($this->allowDelete && $this->allowEdit) {
            $params['tableWidthHtml'] = 60;
        } else {
            $params['tableWidthHtml'] = 20;
        }
        $params['getHtmlFunction'] = function ($value, array $data) use ($module) {

            if (!isset($data['ID'])) {
                return '';
            }

            $outHtml = '';

            if ($this->allowEdit) {
                $outHtml .= Link::create($module, Ico::edit())
                    ->edit($data['ID'])
                    ->params(['fromList' => 1] + $this->getModuleSessionUrlParams())
                    ->getHtml();
            }

            if ($this->allowDelete) {
                $outHtml .= Link::create($module, Ico::delete())
                    ->params(['delid' => $data['ID'], 'fromList' => 1] + $this->getModuleSessionUrlParams())
                    ->ajax()
                    ->cssClass("del-link")
                    ->title(Config::$lang === 'ru' ? 'Удалить' : 'Delete')
                    ->getHtml();
            }

            return $outHtml;
        };

        $this->getFieldsetLast()->addField(F::createHtml('Actions', $params));
    }

    protected function tableLoadData()
    {
        if (isset($_GET['excelIds']) && $this->isStateListExcel()) {

            $ids = Misc::idsFromComaSeparatedString($_GET['excelIds']);

        } else {

            //Сделаем некую оптимизацию: сначала вынем все ID записей, которые должны в итоге попасть в таблицу
            //После этого достанем все поля, указав в условии WHERE ID IN (...)
            //Это значительно ускорит запрос, т.к. сортировка сначала LIMIT будут производиться на лёгком наборе ID
            //При выборке остальных колонок объём данных будет уже меньше за счёт ограниченного набора WHERE ID IN (...)
            $queryIds = $this->getDataQueryIds();

            // Получим все ID из базы
            $ids = Db::getCol($queryIds);
            $this->_queryTime += Db::getLastQueryTime();

        }

        if (!$ids) {
            // Не найдены id для загрузки - пользователь указал какие-то очень сильные фильтры
            return;
        }


        // Посчитаем общее количество строк, если это не режим экспорта в Excel
        if (!$this->isStateListExcel()) {
            $queryCount = $this->getDataQueryCount();

            $foundRows = Db::getOne($queryCount);
            $this->_queryTime += Db::getLastQueryTime();

            $this->setTableTotalRecordsFound($foundRows);
        }

        // Если это не режим экспорта в Excel, запишем ID всех отображенных пользователю строк в базу
        // Они понадобятся для навигации по стрелочкам в режимах View/Edit/Log
        if (!$this->isStateListExcel()) {
            $this->createModuleSession($ids, $this->getTablePage());
        }

        // Теперь сделаем второй запрос, в котором WHERE ID IN (...) - результат запишем в таблицу $this->tableData
        $query = $this->getDataQuery($ids);

        $this->tableData = Db::getAll($query);
        $this->_queryTime += Db::getLastQueryTime();

        // Загрузим специальный массив с закешированным состоянием непустых папок
        if ($this->folders) {
            FilesFolder::loadFoldersStatus($this->folderModule, $ids);
        }
    }

    /**
     * Формирует SQL запрос для выборки ID записей подходящих по условиям WHERE и пажинации LIMIT
     * @return string
     */
    protected function getDataQueryIds(): string
    {
        $queryIds = "SELECT ID";

        // Проверим поле по которому идёт сортировка. Если оно вычисляемое, его тоже придётся добавить в этот запрос
        $queryIds = $this->getDataQueryWithSortFieldInSelect($queryIds);
        // Добавялем секцию FROM $this->getDbTable()
        $queryIds = $this->getDataQueryWithFrom($queryIds);
        // Добавляем секцию WHERE которая состит из фильтров и прав доступа $this->getAccessSqlWhere()
        $queryIds = $this->getDataQueryWithWhere($queryIds);
        // Добавляем секцию ORDER BY
        $queryIds = $this->getDataQueryWithSortFieldOrderBy($queryIds);
        // Добавляем секцию LIMIT с учётом паджинации
        $queryIds = $this->getDataQueryWithLimits($queryIds, $this->isStateListExcel());

        return $queryIds;
    }

    /**
     * Формирует SQL запрос для выборки реальных данных на основе отображаемых у пользователя колонок
     * и выбирает только указнные $ids (эти ids были определены ранее)
     * @return string
     */
    protected function getDataQuery(array $ids): string
    {
        $query = "SELECT ";
        // В секрции SELECT набор полей определяется реально отображаемыми пользователем полями
        $selectFieldKeys = $this->getFieldsetShowed()->getKeys();
        $query .= $this->getFieldset()->getSqlSelect($selectFieldKeys);
        // Добавялем секцию FROM
        $query = $this->getDataQueryWithFrom($query);
        // В условии WHERE вместо всех сложных фильтров указываем полученный массив $ids
        $query .= Db::parse(" WHERE ID IN (@a)", $ids);
        // Добавляем секцию ORDER BY
        $query = $this->getDataQueryWithSortFieldOrderBy($query);
        // LIMIT не добавляем, т.к. у нас уже есть финсированный набор $ids подходящий для текущей страницы
        return $query;
    }

    /**
     * Формирует запрос SELECT count(*) FROM ... WHERE ... чтобы посчитать общее число строк для работы пажинации
     * @return string
     */
    protected function getDataQueryCount(): string
    {
        $queryCount = "SELECT count(*)";
        $queryCount = $this->getDataQueryWithFrom($queryCount);
        $queryCount = $this->getDataQueryWithWhere($queryCount);
        return $queryCount;
    }

    private function getDataQueryWithFrom(string $query): string
    {
        return $query . " FROM " . Db::quote($this->getDbTable());
    }

    private function getDataQueryWithWhere(string $query): string
    {
        $query = $this->getDataQueryWithFiltersWhere($query);

        // Добавляем к общему запросу на получение данных для режима list
        // дополниетельное условие ограничивающее по правам доступа
        $accessSqlWhere = $this->getAccessSqlWhere();
        if ($accessSqlWhere) {
            $query .= " AND ($accessSqlWhere)";
        }

        return $query;
    }

    final protected function isStateListExcel()
    {
        return $this->_state === self::STATE_LIST_EXCEL;
    }

    public function getHtmlTableForAjax()
    {
        $outHtml = parent::getHtmlTableForAjax();
        if ($this->hasFilters()) {
            $outHtml .= $this->getFiltersHiddenInput();
        }

        $outHtml .= $this->getMassactionsHtml();

        return $outHtml;
    }

    protected function getMassactionsHtml(): string
    {
        if ($this->massactions && $this->getTableDataCount()) {
            return MassactionUtils::getHtmlSelectMassactions($this->massactions);
        }

        return '';
    }

    protected function hasWriteAccessAjaxChangeFieldSubtable(FieldAbstract $field): bool
    {
        return $this->allowEdit
            && User::hasWriteAccess()
            && ($this->_obj->getId() === 0 || $this->hasWriteAccessRightToObj($this->_obj))
            && $field->hasMode(F::MODE_SUBMIT);
    }

    /**
     * Метод для переопределения в классах потоках - здесь проверяются права доступа к объекту в режиме Log
     * @return bool
     */
    protected function hasLogAccessRightToObj(ObjAbstract $obj)
    {
        return true;
    }

    /**
     * Метод для переопределения в классах потоках - здесь проверяются права доступа к объекту в режиме Edit
     * @return bool
     */
    protected function hasWriteAccessRightToObj(ObjAbstract $obj)
    {
        return $obj->accessWrite();
    }

    protected function hasWriteAccessRightToFolder(ObjAbstract $obj)
    {
        return User::hasWriteAccess() && $this->hasWriteAccessRightToObj($obj);
    }

    /**
     * @return bool
     */
    protected function hasDeleteAccessRightToObj(ObjAbstract $obj)
    {
        return $obj->accessDelete();
    }

    protected function getFindidUrlParams()
    {
        return [$this->getTableName() . '_findid' => $this->_obj->getId()];
    }

    /**
     * Иногда пользователь попадает на экран просмотра (View) какого-то модуля из другого модуля при этом в строке url есть
     * специальный параметр для посдветки какой-то записи в подтаблицы. Например, мы были на экране счёта VInvoice и
     * кликнули по оплате VPayment -> попадаем на экран VPayment View и при этом в подтаблице с инвойсами подсвечивается
     * строка с тем счётом VInvoice из которого мы пришли. Всё работает автоматически, т.к. в url есть параметр
     * VInvoices_findid=123456.
     *
     * Однако, если я хочу отредактировать эту оплату, жму на Edit, параметр VInvoices_findid теряется и нужная строка
     * в режиме редактирования уже не подсвечена. Чтобы передать этот параметр при клике по кнопке Edit (а также Log и Save)
     * нужно найти все эти параметры в текущем запросе и добавить в ссылки
     *
     * Данный метод ищет в $_GET различные findid и возвращает их в массиве
     */
    protected function getSubtablesFindidUrlParams()
    {
        $out = [];
        foreach ($_GET as $key => $value) {
            if (str_ends_with($key, '_findid')) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    public static function proceedDownloadExampleFile(): void
    {
        $file = $_GET['file'];
        $examplesDir = LIGHT_DIR_PROJECT . '/Module/' . Request::getModule() . '/Examples';

        $realpath = realpath($examplesDir . '/' . $file);
        if (!$realpath) {
            Response::redirect(Request::getModule());
            return;
        }

        if (!str_starts_with($realpath, $examplesDir)) {
            Response::redirect(Request::getModule());
            return;
        }

        if (!is_file($realpath)) {
            Response::redirect(Request::getModule());
            return;
        }

        Response::streamFileFromFilesystem($realpath);
    }
}
