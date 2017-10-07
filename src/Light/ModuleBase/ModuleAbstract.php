<?php

namespace Light\ModuleBase;

use Light\ApplicationAbstract;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Field\FieldAbstract;
use Light\Fieldset;
use Light\FilesFolder;
use Light\Img;
use Light\Intro\IntroManager;
use Light\Js;
use Light\Link;
use Light\Metric;
use Light\ModuleRegister;
use Light\Msg;
use Light\ObjAbstract;
use Light\PrintForm;
use Light\Request;
use Light\Response;
use Light\Session;
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

abstract class ModuleAbstract extends Table
{
    use TablePaginatable, TableFilterable, TableSortable, TableLayoutable, TableExcelExportable,
        TableWithFoldersTrait, ModuleSessionTrait;

    const STATE_LIST = 'list';
    const STATE_LIST_EXCEL = 'excel';
    const STATE_ADDNEW = 'addnew';
    const STATE_VIEW = 'view';
    const STATE_EDIT = 'edit';
    const STATE_LOG = 'log';
    const STATE_DELETE = 'delete';
    const STATE_SUBMIT = 'submit';
    const STATE_INSERT = 'insert';
    const STATE_UPDATE = 'update';
    const STATE_LIST_FUNC = 'list_func';
    const STATE_VIEW_ELFINDER = 'view_elfinder';
    const STATE_VIEW_FUNC = 'view_func';
    const STATE_VIEW_CHANGE_FIELD = 'view_change_field';
    const STATE_MULTIPLEUPDATESUBMIT = 'multiupdate';
    const STATE_SUBTABLE_RELOAD = 'subtable_reload';
    const STATE_SUBTABLE_EXCEL = 'subtable_excel';
    const STATE_SUBTABLE_FUNC = 'subtable_func';

    //Общие настройки для всех режимов
    protected $_allowAdd = true;
    protected $_allowEdit = true;
    protected $_allowLog = true;
    protected $_allowDelete = true;

    //Использовать ли выпадающий список с выбором действия после сохранения?
    protected $continmethod = false;

    //Настройки сообщений на экран
    protected $_textObjectName = '';

    //Внутренние переменные состояния
    protected $_saveErrors = []; //Массив имя поля => сообщение об ошибке
    protected $_justInserted = false;
    protected $_state;
    /**
     * Наборы полей, которые нужно сохранять при копировании
     * Можно определить несколько режимов копирования, задвая различные наборы полей
     * ['default' => ['fieldKeys' => ['fieldKey1', 'fieldKey2'], 'subtables' => ['subtableName1', 'subtableName2']], ...]
     * @var array
     */
    protected $_copyModes = [];

    protected $_submenuItemsTplData = [];
    protected $printForms = [];

    private $_initedSubtables = [];

    /**
     * @var array(SubtableName)
     * Массив с именами подтаблиц. Автоматизация следующая:
     * - будут подключены файлы Subtable/<модуль>/<имя подтаблицы>.php
     * - в режиме List будет вызван метод подтаблицы initFieldsFromSubtables
     * - в функциях proceedSubmit, proceedDelete, getTplData будут созданы экземпляры этих классов и запущены соотвесвующие функции
     */
    private $_subtables = [];

    private $_queryTime = 0;

    protected $massactions = [];

    public function __construct()
    {
        parent::__construct(Request::getModule());
    }

    public function getTableExcelUrl()
    {
        return '?a=' . Request::getModule() . '&xls=1';
    }

    /**
     * Срабатывает после отправки формы Mltiple Update через AJAX
     * Возвращает AJAX сообщение об удачном multiple update, после закрытия котрого страница обновляется
     * Метод вызывается напрямую, т.к. ulr формы Multiple Update содержит параметр p=MultipleUpdateSubmit
     */
    public function proceedMultipleUpdateSubmit()
    {
        $this->_state = self::STATE_MULTIPLEUPDATESUBMIT;

        $msgBuff = new Msg();
        $updated = 0;
        $nothingChanged = 0;

        if ($this->_allowEdit && User::hasWriteAccess()) {

            $dataFromPost = $this->multipleUpdateGetDataFromPost($msgBuff);
            if ($dataFromPost) {
                $ids = MassactionAbstract::getCheckedIdsFromPost();
                $updatedObjects = [];

                //Поочерёдно обновим каждый id
                foreach ($ids as $id) {

                    //Проверим, что id числовой и больше 0
                    $idInt = (int)$id;
                    if ($idInt <= 0) {
                        continue;
                    }

                    //Проверим существование объекта в базе, попробовав его загрузить
                    $obj = clone $this->_obj;
                    $obj->setId($idInt);
                    if (!$obj->loadData()) {
                        $msgBuff->addError('Object with ID = ' . $idInt . ' not found');
                        continue;
                    }

                    //Проверим, что у пользователя есть права доступа к этому объекту
                    if (!$this->hasAccessRightToObj($obj) || !$this->hasWriteAccessRightToObj($obj)) {
                        $msgBuff->addError('No access to edit object with ID = ' . $idInt);
                        continue;
                    }

                    if ($this->proceedUpdateObjFromMultipleUpdate($obj, $dataFromPost, $msgBuff)) {
                        $updated++;
                        $updatedObjects[] = $obj;
                    } else {
                        $nothingChanged++;
                    }

                }

                $this->afterMultipleUpdateSubmit($updatedObjects, $dataFromPost, $msgBuff);

            }
        }

        $msg = 'Updated: ' . $updated;
        //Стандартное сообщение о числе обновлённых записей (зелёное или серое)
        if ($updated > 0) {
            $msgBuff->addSuccess($msg);
        } else {
            $msgBuff->addInfo($msg);
        }

        //Стандратное сообщение о количесве ошибок
        if ($nothingChanged > 0) {
            $msgBuff->addInfo('Nothing changed: ' . $nothingChanged);
        }

        MassactionAbstract::sendSuccessDialog($msgBuff->getHtml());
    }

    protected function multipleUpdateGetDataFromPost(Msg $msgBuff)
    {
        //Отфильтруем fieldset только теми полями, которые обозначены как доступные для Multiple Update
        $muFieldset = $this->getMultipleUpdateFieldset();

        //Дополнительно, отфильтруем только теми полями, которые были на форме отмечены как редактируемые
        //На форме были скрытые поля со статусами 1 или 0 - все они оформлены виде массива multipleupdate
        $allFieldKeys = $_POST['multipleupdate'];
        $changebaleFieldKeys = array_keys(
            array_filter(
                $allFieldKeys,
                function ($value) {
                    return $value === 'showed';
                }
            )
        );

        if (!$changebaleFieldKeys) {
            //Ни одно поле в диалоговом окне не было отмечено для изменения
            return false;
        }

        $muFieldset = $muFieldset->filterByKeys($changebaleFieldKeys);

        //Соберём массив с данными по полям
        $requestData = [];
        foreach ($changebaleFieldKeys as $fieldKey) {
            if (isset($_POST[$fieldKey])) {
                $requestData[$fieldKey] = $_POST[$fieldKey];
            }
        }

        //Обработаем данные с помощью функций определённый в объектах полей
        return $muFieldset->getDataFromRequest($requestData);
    }

    /**
     * Метод для переопределения в классах потомках
     * @param array $updatedObjects - массив из объектов
     * @param array $dataFromPost - массив данных из диалогового окна MultipleUpdate
     * @param Msg $msgBuff
     */
    protected function afterMultipleUpdateSubmit(array $updatedObjects, array $dataFromPost, Msg $msgBuff)
    {
    }

    /**
     * Выполняет обновление еденичной записи из режима MultipleUpdate
     * Функция удобна для переопределения в классах потмоках
     * @param ObjAbstract $obj
     * @param array $dataFromPost
     * @param Msg $msgBuff
     * @return bool
     */
    protected function proceedUpdateObjFromMultipleUpdate(ObjAbstract $obj, array $dataFromPost, Msg $msgBuff)
    {
        $oldData = $this->getOldDataBeforeSubmitOrDelete($obj);
        $affectedRows = $obj->updateDb($dataFromPost, 'Multiple Update');
        $this->afterUpdate($obj, $dataFromPost, $affectedRows, $oldData, $msgBuff, true);
        $this->afterSubmit($obj, $dataFromPost, $affectedRows, $oldData, $msgBuff, true);
        return $affectedRows;
    }

    /**
     * Основный метод proceed осуществляет только функции роутинга - проверяет входящие параметры строки запроса и
     * вызывает соответсвующий метод proceedList, proceedView, proceed...
     * Никаких проверок прав доступа или существования объекта в глобальном методе proceed не происходит - это всё должны
     * делать конкретные методы proceed
     */
    public function __invoke()
    {
        if (isset($_GET['addnew'])) {
            $this->proceedAddNew();
        } elseif (isset($_GET['id'])) {
            $this->proceedView();
        } elseif (isset($_GET['editid'])) {
            $this->proceedEdit();
        } elseif (isset($_GET['logid'])) {
            $this->proceedLog();
        } elseif (isset($_GET['submitid'])) {
            $this->proceedSubmit();
        } elseif (isset($_GET['delid'])) {
            $this->proceedDelete();
        } else {
            $this->proceedList();
        }
    }

    protected function proceedAddNew()
    {
        $this->_state = self::STATE_ADDNEW;

        //Проверим права на запись в этот модуль
        if (!$this->_allowAdd || !User::hasWriteAccess()) {
            //При обычном запросе сделаем редирект на режим просмотра с flash сообщенеим
            Session::flashError('No acces to add new');
            $this->redirectToList();
        }

        $this->initCssJs();

        //Если в GET переданы параметры copymode и copyfrom - инициализируем текущий объект
        if (isset($_GET['copymode'], $_GET['copyfrom'])) {
            $this->initCopyModes();
            $this->initDataForCopy($_GET['copymode'], $_GET['copyfrom']);
        }

        $moduleName = ModuleRegister::getModuleName(Request::getModule());
        $headText = Link::module($moduleName, $this->getModuleSessionUrlParams());
        $headText .= ' - <strong>Add New</strong>';
        Response::setPageHeadHtml($headText);
        $this->initAddNewJsData();

        $formActionUrlParams = ['submitid' => 0] + $this->getModuleSessionUrlParams();
        $tdata = [
            'formActionUrl' => Link::url($formActionUrlParams),
            'cancelUrl' => Link::url($this->getModuleSessionUrlParams()),
            'content' => $this->getViewEditContentHtml(),
            'formactions' => $this->getFormactionsHtml(),
            'fields' => $this->getFieldsForFormJs()
        ];

        Template::includeTpl('module/addnew.tpl.php', $tdata);
    }

    protected function redirectToList()
    {
        Response::redirect(Request::getModule(), $this->getModuleSessionUrlParams());
    }

    protected function initCssJs()
    {
        //Проверим, есть ли файлы для этого модуля в зависимости от текущего режима
        $module = Request::getModule();
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
     * @desc инициализирует $this->_obj переменные данными из файла id=$copyfrom
     * @param string $copymode
     * @param int $copyfrom
     * @return void
     */
    protected function initDataForCopy($copymode, $copyfrom)
    {
        //Проверим, есть ли набор полей определённых для текущего $copymode
        if (isset($this->_copyModes[$copymode])) {
            //Возьмём данные из объекта источника
            $sourceObj = clone $this->_obj;
            if ($sourceObj->setId($copyfrom)->loadData()) {
                //Перенесём нужные поля в $this->_obj
                $copyFieldKeys = $this->_copyModes[$copymode]['fieldKeys'];
                if ($copyFieldKeys !== null) {
                    if (is_array($copyFieldKeys)) {
                        $fieldKeys = $copyFieldKeys;
                    } else {
                        //если $copyFieldKeys это строка, значит там строка 'all' - копируем все поля!
                        $fieldKeys = $this->_obj->getFieldset()->getKeys();
                    }
                    foreach ($fieldKeys as $fieldKey) {
                        if (isset($sourceObj[$fieldKey])) {
                            //Поле существует в источнике
                            //Инициализируем данные значением из источника
                            $this->_obj[$fieldKey] = $sourceObj[$fieldKey];
                        }
                    }
                }

                $copySubtables = $this->_copyModes[$copymode]['subtables'];
                if ($copySubtables !== null) {
                    if (is_array($copySubtables)) {
                        $subtables = $copySubtables;
                    } else {
                        //Нужно копировать все подтаблицы! Кроме тех, которые readonly
                        $subtables = [];
                        foreach ($this->_subtables as $subtableName => $subtableParams) {
                            if (!$subtableParams['readonly']) {
                                $subtables[] = $subtableName;
                            }
                        }
                    }

                    //Теперь приступим к копированию
                    foreach ($subtables as $subtableName) {
                        //Получим объект, который нужно заполнить данными из копируемого источника
                        $subtableObj = $this->getSubtableObject($this->_obj, $subtableName);
                        //Источник - это на самом деле подтаблица того же класса, но с другим masterId
                        $sourceSubtableObj = clone $subtableObj;
                        //Заменим мастер-объект в подтаблице
                        $sourceSubtableObj->setMasterObj($sourceObj);
                        //Теперь загрузим данные в подтаблицу источник благодаря подменённому мастер объекту
                        $sourceSubtableObj->loadData();
                        //Копируем данные в подтаблицу текущего объекта
                        //Метод $sourceSubtableObj->getData() возвращает массив где ключи - это ID в базе
                        //чтобы добавить данные в $subtableObj нам не нужны ключи в виде ID, нам нужны ключи от 0 до N
                        //для этого применим array_values
                        $sourceData = array_values($sourceSubtableObj->getData());
                        $subtableObj->setAdditionalData($sourceData);
                    }
                }
            }
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
        //Проверим, есть ли шаблон для отображения объектов этого модуля?
        $module = Request::getModule();
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
        //Если есть поле Color и режим View - добавим ему параметр source
        if ($this->_state === self::STATE_VIEW && $this->hasField('Color')) {
            $this->getField('Color')->setParam(
                'source',
                '?a=' . Request::getModule() . '&id=' . $this->_obj->getId() . '&func=changeField'
            );
        }

        $fieldMode = $this->_state === self::STATE_VIEW ? F::MODE_VIEW : F::MODE_EDIT;
        $filteredFieldset = $this->getFieldset()->filterByMode(F::MODE_VIEW | F::MODE_EDIT);

        $data = $this->_obj->getData();
        if ($this->_state === self::STATE_ADDNEW) {
            //В режиме Add New изначальные данные для формы могли быть заданы в url
            //Однако, проблема с чекбоксами - если их нет в $_GET, то они станут якобы сняты
            //А на самом деле может быть они должны получить свои параметры из default!
            $fieldsetWithoutCheckboxes = new Fieldset();
            foreach ($this->getFieldset() as $field) {
                $type = $field->getType();
                if ($type !== F::TYPE_CHECKBOX && $type !== F::TYPE_CHECKBOX_SET) {
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
        foreach ($this->_subtables as $subtableName => $subtableParam) {
            $tdataSubtables[$subtableName] = [
                'obj' => $this->getSubtableObject($this->_obj, $subtableName),
                'readonly' => $subtableParam['readonly']
            ];
        }

        return $tdataSubtables;
    }

    private function getFormactionsHtml()
    {
        // Кнопку "Save" специально делаем button type="button"
        // чтобы форма не отрпавлялась случайно при нажатии enter на лубом из полей
        $contentHtml = '<div class="formactions_fixed">'
            . '<div class="progress visibility-hidden"><div class="determinate"></div></div>'
            . '<button type="button" class="waves-effect waves-light btn btn-save bgcolor-btn-primary" title="Ctrl + S">'
            . 'Save ' . $this->_textObjectName
            . '</button>'
            . '<a href="javascript: history.back()" class="btn-flat waves-effect cancel-link">Cancel</a>';

        if ($this->continmethod) {

            $continmetods = [
                'list' => 'Back to list',
                'view' => 'View this',
                'edit' => 'Edit this'
            ];

            if ($this->_allowAdd) {
                $continmetods['addnew'] = 'Add new';
            }

            $continmethod = User::getParam(Request::getModule() . '_continmethod') ?? 'view';

            $contentHtml .= F::createSelect('_continmethod', [
                'editWidth' => 'auto',
                'empty' => false,
                'options' => $continmetods
            ])->getHtmlInput($continmethod);

        }


        $contentHtml .= '</div>';

        return $contentHtml;
    }

    /**
     * Метод вызывается если в $_GET запросе есть параметр id
     * Далее в этом методе попробуем разобраться что именно хочет сделать пользователь
     */
    protected function proceedView()
    {
        // Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
        $this->loadObjAndCheck($_GET['id']);

        // Проверим дополнительные GET параметры относящиеся к режиму просмотра
        if (isset($_GET['subtable'])) {
            // Методы работы с subtable
            $this->proceedViewSubtable();
        } elseif (isset($_GET['elfinder'])) {
            // Отображаем окно с папкой (elfinder)
            $this->proceedViewElfinder();
        } elseif (isset($_GET['func'])) {
            // Попытка вызвать пользовательскую функцию из режима просмотра - это может быть ajax запрос или печать
            // документа или вывод в Excel особой формы
            $this->proceedViewFunc();
        } else {
            $this->proceedViewPage();
        }
    }

    /**
     * Загружает данные в объект $this->_obj, проверяет права доступа к нему у текущего пользователя
     * @param int $id
     */
    protected function loadObjAndCheck($id)
    {
        //Проверим, что id числовой и больше 0
        $id = (int)$id;
        if ($id <= 0) {
            //Если id был не числовой (приведён к 0) или отрицательный - перенаправляем в режим List
            $this->loadObjAndCheckErrorReuslt(400, 'Invalid object id');
        }

        //Проверим существование объекта в базе, попробовав его загрузить
        $this->_obj->setId($id);
        if (!$this->_obj->loadData()) {
            $this->loadObjAndCheckErrorReuslt(404, 'Object ' . $id . ' not found');
        }

        //Проверим, что у пользователя есть права доступа к этому объекту
        if (!$this->hasAccessRightToObj($this->_obj)) {
            $this->loadObjAndCheckErrorReuslt(403, 'No access to object');
        }
    }

    private function loadObjAndCheckErrorReuslt($status, $text)
    {
        Session::flashError($text);
        if (Request::isAjax()) {
            Session::$showAsToast = true;
            Response::abort($status);
        } else {
            $this->redirectToList();
        }
    }

    private function proceedViewSubtable()
    {
        $subtableName = $_GET['subtable'];
        //проверим, что такая подтаблица сщуетсвует
        if (!isset($this->_subtables[$subtableName])) {
            Session::flashError('Unknown subtable ' . $subtableName);
            $this->redirectToView();
        }

        /** @var $subtableObj \Light\SubtableBase\Subtable */
        $subtableObj = $this->getSubtableObject($this->_obj, $subtableName);

        // С подтаблицей можно осуществлять несколько действий
        if (isset($_REQUEST['fieldKey'])) {
            //Изменение какого-то поля через ajax
            if (!$this->_allowEdit || !User::hasWriteAccess() || !$this->hasWriteAccessRightToObj($this->_obj)) {
                Response::abort(403, 'Edit not allowed');
            }

            $subtableObj->proceedChangeField();
        } elseif (Request::isAjax()) {
            //Перезагрузка через ajax при изменении Table Layout
            $subtableObj->proceedAjaxReload();
        } elseif (Request::isXls()) {
            //Отправка Excel файла
            $subtableObj->proceedExcel();
        } else {
            //Не удалось определить действие с подтаблицей, сообщим об этом пользователю
            Session::flashError('Bad subtable request');
            $this->redirectToView();
        }
    }

    private function proceedViewElfinder()
    {
        $this->_state = self::STATE_VIEW_ELFINDER;

        if (!$this->_folders) {
            Response::abort(403, 'Module without folders');
            return;
        }

        // На всякий случай создадим папку, если её ещё нет на сервере
        $this->createFolder($this->_obj->getData());
        $module = Request::getModule();
        $id = $this->_obj->getId();

        // Чтобы правильно работала функция basename с файлами на русском языке
        // Перед этим нужно установить локаль в операционную систему:
        // https://askubuntu.com/questions/76013/how-do-i-add-locale-to-ubuntu-server
        // locale -a
        // sudo locale-gen ru_RU
        // sudo locale-gen ru_RU.UTF-8
        // sudo update-locale
        // sudo service php7.1-fpm restart

        if ($_GET['elfinder'] === 'dialog') {
            // Это первичный запрос на рендер каркаса диалога (пока без содержимого с файлами)
            Template::includeTpl(LIGHT_DIR_LIGHT . '/templates/module/elfinderdialog.tpl.php', [
                'module' => $module,
                'id' => $id,
                'title' => 'Файлы: ' . $this->_textObjectName . ' ' . strip_tags($this->_obj->getDescriptionHtml())
            ]);
            ApplicationAbstract::terminate();
            return;
        }

        if ($_GET['elfinder'] === 'api') {
            // Пришел API запрос от окна с elfiner (это может быть API запрос на отображение списка файлов
            // или upload нового файла
            $relativePath = $this->getFolderRelativePath($this->_obj->getData());
            $opts = [
                'locale' => "ru_RU.utf8",
                'roots' => [
                    [
                        'driver' => 'LocalFileSystem',
                        'encoding' => 'UTF-8',
                        'locale' => "ru_RU.utf8",
                        'mimeDetect' => 'internal',
                        // если не установить internal, то будет использована функция finfo_file, которая при обнаружении xls файла сбрасывает локаль в default заничеие ('C') и дальше все файлы с русскими буквами не смогут быть обработаны с помощью basename
                        // driver for accessing file system (REQUIRED)
                        'path' => FilesFolder::getAbsolutePath($relativePath),
                        'URL' => "?a=$module&id=$id&elfinder=download&file=",
                        // URL to files (REQUIRED)
                        'accessControl' => function ($attr, $path, $data, $volume) {
                            if (strpos(basename($path), '.') === 0) {
                                // Для путей начинающихся с .., если запрошен атрибут read или write - сразу вернём false
                                if ($attr === 'read' || $attr === 'write') {
                                    return false;
                                }
                            }

                            // Если elfinder хочет проверить атрибут на запись, делегируем эту проверку в ModuleAbstract
                            if ($attr === 'write') {
                                return $this->hasWriteAccessRightToFolder($this->_obj);
                            }

                            // Возвращаем null - elfinder должен решить сам, что делать
                            return null;

                        }
                    ]
                ],
                'bind' => [
                    'mkdir mkfile rename duplicate upload rm paste' => function ($cmd, $result, $args, $elfinder) {

                        /* @var \elFinder $elfinder */

                        if (!empty($result['added'])) {
                            foreach ($result['added'] as $file) {
                                $mode = isset($file['mime']) && $file['mime'] === 'directory' ? 0777 : 0666;

                                @chmod($elfinder->realpath($file['hash']), $mode);
                            }
                        }

                        if (!empty($result['changed'])) {
                            foreach ($result['changed'] as $file) {
                                $mode = isset($file['mime']) && $file['mime'] === 'directory' ? 0777 : 0666;
                                @chmod($elfinder->realpath($file['hash']), $mode);
                            }
                        }
                    },
                ],
            ];

            // run elFinder
            $connector = new \elFinderConnector(new \elFinder($opts));
            $connector->run();
            ApplicationAbstract::terminate();
            return;
        }

        if ($_GET['elfinder'] === 'download' && isset($_GET['file'])) {

            // Проверим, чтобы в имени скачиваемого файла file не было ..
            $test = '/' . str_replace('\\', '/', $_GET['file']) . '/';
            if (strpos($test, '/../') !== false) {
                throw new \InvalidArgumentException("file contains ..: " . $_GET['file']);
            }

            $relativePath = $this->getFolderRelativePath($this->_obj->getData());
            FilesFolder::streamFile($relativePath . '/' . $_GET['file']);
            ApplicationAbstract::terminate();
            return;
        }
    }

    private function abortAndRedirectToView($code, $errMsg)
    {
        //При обычном запросе сделаем редирект на режим просмотра с flash сообщенеим
        Session::flashError($errMsg);
        $this->redirectToView();

    }

    protected function redirectToView()
    {
        $urlParams = ['id' => $this->_obj->getId()] + $this->getModuleSessionUrlParams();
        Response::redirect(Request::getModule(), $urlParams);
    }

    private function proceedViewFunc()
    {
        $this->_state = self::STATE_VIEW_FUNC;

        $func = ucfirst($_GET['func']);
        $postOrGet = Request::isPost() ? 'Post' : 'Get';

        $method = "view{$postOrGet}{$func}";
        if (!method_exists($this, $method)) {
            Session::flashError('Unknown method ' . $method);
            $this->redirectToView();
            $this->abortAndRedirectToView(405, 'Unknown method ' . $method);
        }

        //Метод найден, вызовем его!
        $this->{$method}();

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
     * Метод запускается если у печатной формы определён какой-то fieldset и, соответсвенно, при клике на ссылку в меню
     * отправляется ajax GET запрос, чтобы вывести форму
     */
    private function viewGetPrint()
    {
        $printForm = $this->beforePrint();


        $module = Request::getModule();
        $id = $this->_obj->getId();
        $form = $printForm->getKey();
        $formAction = "?a={$module}&id=$id&func=Print&form=$form";

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

        Response::showDialog($html, 'Print');
    }

    /**
     * Метод вызывается при Post запросе, который может прийти от ссылки из submenu, а может прийти из диалога
     * с дополнительными параметрами для печати (см. как рендерится этот диалог в ModuleAbstract::viewGetPrint())
     */
    private function viewPostPrint()
    {
        $printForm = $this->beforePrint();

        // Запустим процесс описанный в классе PrintForm (или в его наследнике)
        // Для этого нам нужно передать в метод proceedPrint путь к папке текущего объекта
        $filesFolderRelativePath = $this->getFolderRelativePath($this->_obj->getData());
        $printForm->proceedPrint($this->_obj, $filesFolderRelativePath);

        ApplicationAbstract::terminate();
    }


    protected function proceedViewPage()
    {
        $this->_state = self::STATE_VIEW;

        if (!$this->_allowEdit || !User::hasWriteAccess() || !$this->hasWriteAccessRightToObj($this->_obj)) {
            $this->disableAllFieldsEdit();
        }

        //Наконец, это обычный режим view
        $this->initCssJs();
        $this->setPageHeadForObj();
        $this->initViewJsData();

        if ($this->_folders) {
            $this->createFolder($this->_obj->getData());
        }

        $this->addModuleSessionNextPrevLinks($this->_obj, 'id');
        $this->initViewSubmenu();
        $this->initViewIntro();
        $this->initPrintForms();

        $tdata = [
            'id' => $this->_obj->getId(),
            'tabsmenuItems' => $this->getViewEditLogTabsmenuItems(),
            'submenuItems' => $this->getSubmenuItemsTplData(),
            'content' => $this->getViewEditContentHtml()
        ];

        Template::includeTpl('module/view.tpl.php', $tdata);
    }

    protected function setPageHeadForObj($stateText = '')
    {
        $moduleName = ModuleRegister::getModuleName(Request::getModule());
        $headText = Link::module($moduleName, $this->getFindidUrlParams() + $this->getModuleSessionUrlParams());
        $headText .= ' - ';
        if ($stateText) {
            $headText .= $stateText . ' ';
        }
        $headText .= '<strong>' . $this->_obj->getDescriptionHtml() . '</strong>';
        $headText .= ' (ID: ' . $this->_obj->getId() . ')';
        Response::setPageHeadHtml($headText);
        Response::setPageTitle($headText);
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
                'moduleSessionParams' => http_build_query($this->getModuleSessionUrlParams())
            ]
        );
    }

    /**
     * Метод для переопределения в классах потоках. Здесь нужно последовательно вызывать метод
     * $this->addSubmenuItemViewFunc() или $this->addSubmenuItemViewFuncAjax() или $this->addSubmenuItemUrl()
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

    protected function getViewEditLogTabsmenuItems()
    {
        $out = [];

        $id = $this->_obj->getId();
        $msUrlParams = $this->getModuleSessionUrlParams();
        $subtablesFindidUrlParams = $this->getSubtablesFindidUrlParams();

        $out[] = [
            'text' => 'List',
            'url' => Link::url($this->getFindidUrlParams() + $msUrlParams),
            'active' => false
        ];

        $out[] = [
            'text' => 'View',
            'url' => Link::url(['id' => $id] + $msUrlParams + $subtablesFindidUrlParams),
            'active' => $this->_state === self::STATE_VIEW
        ];

        if ($this->_allowEdit && User::hasWriteAccess() && $this->hasWriteAccessRightToObj($this->_obj)) {
            $out[] = [
                'text' => 'Edit',
                'url' => Link::url(['editid' => $id] + $msUrlParams + $subtablesFindidUrlParams),
                'active' => $this->_state === self::STATE_EDIT
            ];
        }

        if ($this->_allowLog && User::hasLogInPlaceAccess()) {
            $out[] = [
                'text' => 'Log ',
                'url' => Link::url(['logid' => $id] + $msUrlParams + $subtablesFindidUrlParams),
                'active' => $this->_state === self::STATE_LOG
            ];
        }

        if ($this->_allowDelete && User::hasWriteAccess() && $this->hasDeleteAccessRightToObj($this->_obj)) {
            $out[] = [
                'text' => 'Delete',
                'url' => Link::url(['delid' => $id] + $msUrlParams) . '#ajax',
                'linkCssClass' => 'del-link'
            ];
        }

        return $out;
    }

    protected function getSubmenuItemsTplData()
    {
        $out = [];
        if ($this->_folders) {
            $out[] = [
                'html' => $this->fieldFolder('Folder')->getHtml('', $this->_obj->getData())
            ];

            /*
            $folderRelativePath = $this->getFolderRelativePath($this->_obj->getData());
            if ($folderRelativePath) {
                $isNotEmpty = FilesFolder::isNotEmpty($folderRelativePath);
                $moduleName = $this->getFolderBaseDir();
                $recordId = $this->_obj[$this->_folderNameFieldKey];
                if ($isNotEmpty) {
                    Db::insertRow(['ModuleName' => $moduleName, 'RecordID' => $recordId], 'cache_notemptyfilesfolder',
                        true);
                } else {
                    Db::query("DELETE FROM cache_notemptyfilesfolder WHERE ModuleName = @s AND RecordID = @i",
                        $moduleName, $recordId);
                }
                $out[] = [
                    'html' => FilesFolder::getLink($folderRelativePath,
                        FilesFolder::getImgHtml($isNotEmpty) . ' Folder')
                ];
            }
            */
        }

        // Добавим ссылки на различные режимы копирования
        if ($this->isStateView()) {

            //Добавим ссылки на копирование
            $this->initCopyModes();
            if ($this->_copyModes) {
                $urlCopy = '?a=' . Request::getModule() . '&addnew=1&copyfrom=' . $this->_obj->getId() . '&copymode=';
                foreach ($this->_copyModes as $name => $_) {

                    $out[] = [
                        'text' => Img::iconCopy() . ' ' . $name,
                        'url' => $urlCopy . rawurlencode($name)
                    ];
                }
            }

            // Добавим ссылки на печать
            if ($this->printForms) {

                // Начало ссылки на печать
                $printFormUrlStart = '?a=' . Request::getModule() . '&id=' . $this->_obj->getId() . '&func=Print&form=';

                /**
                 * @var string $printFormName
                 * @var PrintForm $printForm
                 */
                foreach ($this->printForms as $printFormName => $printForm) {
                    if ($printForm->testForObj($this->_obj)) {
                        $out[] = [
                            'text' => ($printForm->getFormat() == PrintForm::FORMAT_EXCEL ? Img::iconExcel() : Img::iconPrint()) . ' ' . $printForm->getTitle(),
                            'url' => $printFormUrlStart . $printFormName . ($printForm->getFieldset()->count() ? '#ajax' : '#post'),
                            // Делаем ссылку с открытием нового окна только в том случае, если эта форма настроена
                            // на вывод HTML в браузер
                            'blank' => $printForm->isLinkBlank()
                        ];
                    }
                }
            }
        }

        $out = array_merge($out, $this->_submenuItemsTplData);

        return $out;
    }

    final protected function isStateView()
    {
        return $this->_state == self::STATE_VIEW;
    }

    protected function proceedEdit()
    {
        if (isset($_GET['fromList'])) {
            Metric::record('EditFromList', 0, Request::getModule());
        }

        //Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
        $this->loadObjAndCheck($_GET['editid']);

        $this->_state = self::STATE_EDIT;

        //Проверим права на запись текущего объекта
        if (!$this->_allowEdit || !User::hasWriteAccess() || !$this->hasWriteAccessRightToObj($this->_obj)) {
            //При обычном запросе сделаем редирект на режим просмотра с flash сообщенеим
            Session::flashError('No acces to edit');
            $this->redirectToView();
        }

        $this->initCssJs();
        $this->setPageHeadForObj('Edit');
        $this->initEditJsData();
        $this->addModuleSessionNextPrevLinks($this->_obj, 'editid');
        $this->initEditSubmenu();

        $formActionUrlParams = ['submitid' => $this->_obj->getId()] + $this->getModuleSessionUrlParams() + $this->getSubtablesFindidUrlParams();
        $tdata = [
            'tabsmenuItems' => $this->getViewEditLogTabsmenuItems(),
            'submenuItems' => $this->getSubmenuItemsTplData(),
            'formActionUrl' => Link::url($formActionUrlParams),
            'content' => $this->getViewEditContentHtml(),
            'formactions' => $this->getFormactionsHtml(),
            'fields' => $this->getFieldsForFormJs()
        ];

        Template::includeTpl('module/edit.tpl.php', $tdata);
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
                'moduleSessionParams' => $this->getModuleSessionUrlParams()
            ]
        );
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

    protected function proceedLog()
    {
        $this->_state = self::STATE_LOG;

        //Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
        $this->loadObjAndCheck($_GET['logid']);

        if (!User::hasLogInPlaceAccess()) {
            Session::flashError('No access to log');
            $this->redirectToView();
        }

        //В момент создания объект CrudModuleLog загрузит данные из базы и инициализирует фильты
        $logTable = new ModuleAbstractLog($this->_obj->getId());

        if (Request::isXls()) {

            $logTable->sendExcel();

        } elseif (Request::isAjax()) {

            print $logTable->getHtmlTableForAjax();

        } else {

            $this->initCssJs();
            $this->setPageHeadForObj('Log');
            Response::addJsData(['state' => 'log', 'id' => $this->_obj->getId()]);
            $this->addModuleSessionNextPrevLinks($this->_obj, 'logid');

            $tdata = [
                'table' => $logTable->getHtmlTable(),
                'tabsmenuItems' => $this->getViewEditLogTabsmenuItems(),
                'filters' => $logTable->getFiltersHtml()
            ];

            Template::includeTpl('module/log.tpl.php', $tdata);
        }
    }

    protected function proceedSubmit()
    {
        $this->_state = self::STATE_SUBMIT;

        $id = (int)$_GET['submitid'];
        if ($id < 0 || ($_GET['submitid'] !== '' && !is_numeric($_GET['submitid']))) {
            //Если id был не числовой (приведён к 0) или отрицательный - перенаправляем в режим List
            $this->riseSaveErorr('Invalid object id');
            $this->abortInsertUpdate(400);
        }

        //Проверим, есть ли права на запись в этот модуль?
        if (!$this->_allowEdit || !User::hasWriteAccess()) {
            $this->riseSaveErorr('No access to edit');
            $this->abortInsertUpdate(403);
        }

        //Проверим можно ли вообще добавлять новые элементы, если это режим добавления?
        if ($id === 0) {
            if (!$this->_allowAdd) {
                $this->riseSaveErorr('Add new records not allowed');
                $this->abortInsertUpdate(403);
            }
        } else {
            //Это режим редактирования - проверим, существует ли этот объект?
            //Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
            $this->loadObjAndCheck($id);

            //Проверим права доступа к редактированию именно этого объекта
            if (!$this->hasWriteAccessRightToObj($this->_obj)) {
                $this->riseSaveErorr('No access to edit');
                $this->abortInsertUpdate(403);
            }
        }

        $timer = new Timer();

        $dataFromPost = $this->getDataFromPost();
        $this->validateUnique($dataFromPost);
        $this->validateMandatory($dataFromPost);
        $this->validateMandatorySubtables();
        $this->afterValidation($dataFromPost, $this->_saveErrors);

        if ($this->hasCriticalErrors()) {
            $this->abortInsertUpdate(400);
        }

        //Были ли другие ошибки? Если были, нажал ли пользователь кнопку Save Anyway?
        if (!isset($_GET['saveanyway']) && count($this->_saveErrors) > 0) {
            $this->abortInsertUpdate(400);
        }

        $oldData = $this->getOldDataBeforeSubmitOrDelete($this->_obj);

        Db::begin();

        $msgBuff = new Msg();
        $affectedRows = $this->proceedInsertUpdateObj($dataFromPost, $msgBuff);

        if ($this->_folders) {
            $this->createFolder($this->_obj->getData());
        }


        if ($this->_justInserted) {
            $this->afterInsert($this->_obj, $dataFromPost, $msgBuff);
        } else {
            $this->afterUpdate($this->_obj, $dataFromPost, $affectedRows, $oldData, $msgBuff, false);
        }

        $this->afterSubmit($this->_obj, $dataFromPost, $affectedRows, $oldData, $msgBuff, false);

        Db::commit();


        $msg = '<strong>' . $this->_obj->getDescriptionHtml() . '</strong> saved';
        if ($timer->get() > 3) {
            $msg .= ' (' . $timer->getHtml() . ')';
        }

        $msgBuff->addSuccess($msg);
        $msgBuff->putIntoSessionFlash();

        $this->redirectAfterSubmit();
    }


    protected function redirectAfterSubmit()
    {
        $module = Request::getModule();

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
     * Старые данные сохраняются во временную переменную, чтобы потом быть передаными в методы afterUpdate() и afterSubmit()
     * Иногда, старые данные нужно расширить, например, данными из подтаблиц - тогда этот метод можно переопределить в
     * классах потомках
     * @param ObjAbstract $obj
     * @return array
     */
    protected function getOldDataBeforeSubmitOrDelete(ObjAbstract $obj)
    {
        $oldData = $obj->getData();
        foreach ($this->_subtables as $subtableName => $subtableParams) {
            if (!$subtableParams['readonly']) {
                $subtableObj = $this->getSubtableObject($obj, $subtableName);
                if (is_subclass_of($subtableObj, 'Light\SubtableBase\Subtable')) {
                    $subtableObj->loadData();
                    $oldData['_subtables'][$subtableName] = $subtableObj->getData();
                }
            }
        }
        return $oldData;
    }

    private function abortInsertUpdate($code)
    {
        Response::abort($code, json_encode($this->_saveErrors));
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
            $synArr = explode("\n", $dataFromPost['Synonyms']);
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

        $query = "SELECT ID
                  FROM " . $this->getDbTable() . "
                  WHERE " . $nameFieldKey . " = '" . Db::esc($name) . "'
                      AND ID != " . (int)$this->_obj->getId();

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
        $msg = $this->_textObjectName
            . ' <strong>' . htmlspecialchars($name) . '</strong> already exists '
            . Link::moduleViewBlank('view...', $existId);
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
                  WHERE CONCAT(Synonyms, '\n') LIKE '%" . Db::esc($name) . "\n%'
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
        $errors = $this->getFieldset()->validateMandatory($dataFromPost);
        foreach ($errors as $error) {
            $this->riseSaveErorr($error['msg'], $error['fieldKey']);
        }

    }

    protected function validateMandatorySubtables()
    {
        // Далее проверим заполненность полей внутри подтаблиц
        if (!$this->_subtables) {
            return;
        }

        // Перебираем все подтаблицы
        foreach ($this->_subtables as $subtableName => $subtableParams) {

            if ($subtableParams['readonly']) {
                continue;
            }

            $subtableObj = $this->getSubtableObject($this->_obj, $subtableName);
            foreach ($subtableObj->getNotDeletedDataFromReqeust() as ['fieldsData' => $rowData, 'rowNum' => $rowNum]) {
                $errors = $subtableObj->getFieldset()->validateMandatory($rowData);
                if (!$errors) {
                    continue;
                }

                $lineObj = clone $subtableObj->getObj();
                foreach ($errors as $error) {
                    $fieldKey = 'StatusSubtable[' . $rowNum . '][' . $error['fieldKey'] . ']';
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
     * @param array $dataFromPost
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

    protected function proceedInsertUpdateObj(array $dataFromPost, Msg $msgBuff)
    {
        if ($this->_obj->getId() === 0) {
            $this->proceedInsertObj($dataFromPost);
            $this->_justInserted = true;
            $affectedRows = 1;
        } else {
            $affectedRows = $this->proceedUpdateObj($dataFromPost);
        }

        $affectedRows += $this->proceedSubmitSubtables($msgBuff);

        return $affectedRows;
    }

    /**
     * Если нужно добавть какую-то дополнительную логику сохранения, следует переопределять метод afterSubmit(array $dataFromPost)
     * В нём можно проверить флаг $this->_justInserted, чтобы определить вставку или обнвление
     * @param array $dataFromPost
     */
    private function proceedInsertObj(array $dataFromPost)
    {
        $this->_state = self::STATE_INSERT;

        $dataFromPost = $this->_obj->getOnlySubmitFieldsData($dataFromPost);
        $this->_obj->insertDb($dataFromPost);
    }

    /**
     * Если нужно добавть какую-то дополнительную логику сохранения, следует переопределять метод afterSubmit(array $dataFromPost)
     * В нём можно проверить флаг $this->_justInserted, чтобы определить вставку или обнвление
     * @param array $dataFromPost
     * @return int $affectedRows
     */
    private function proceedUpdateObj(array $dataFromPost)
    {
        $this->_state = self::STATE_UPDATE;
        $dataFromPost = $this->_obj->getOnlySubmitFieldsData($dataFromPost);
        return $this->_obj->updateDb($dataFromPost);
    }

    protected function proceedSubmitSubtables(Msg $msgBuff)
    {
        $affectedRows = 0;
        if ($this->_subtables) {
            //Перебираем все подтаблицы и вызываем метод getDataFromRequest у каждой из них
            foreach ($this->_subtables as $subtableName => $subtableParams) {
                if (!$subtableParams['readonly']) {
                    $subtableObj = $this->getSubtableObject($this->_obj, $subtableName);
                    $affectedRows += $subtableObj->proceedSubmit($msgBuff);
                }
            }
        }

        return $affectedRows;
    }

    /**
     * Вызывается после создания новой записи через режим Add New
     * @param ObjAbstract $obj
     * @param array $dataFromPost
     * @param Msg $msgBuff
     */
    protected function afterInsert(ObjAbstract $obj, array $dataFromPost, Msg $msgBuff)
    {
    }

    /**
     * Вызывается после обновления записи через режим Edit или MultipleUpdate
     * @param ObjAbstract $obj
     * @param array $dataFromPost
     * @param int $affectedRows
     * @param array $oldData
     * @param Msg $msgBuff
     * @param bool $fromMultipleUpdate
     */
    protected function afterUpdate(
        ObjAbstract $obj,
        array $dataFromPost,
        $affectedRows,
        array $oldData,
        Msg $msgBuff,
        $fromMultipleUpdate
    ) {
    }

    /**
     * Вызывается после создания ноыой записи из Add New и при редактировании записи через Edit или Multiple Update
     * (иными словами самый универсальный метод - вызывается всегда! Если нужно сделать какое-то обновление кеширующих
     * данных во всех случаях, этот метод подходит лучше всего)
     * @param ObjAbstract $obj
     * @param array $dataFromPost
     * @param int $affectedRows
     * @param array $oldData
     * @param Msg $msgBuff
     * @param bool $fromMultipleUpdate
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
     * @param ObjAbstract $obj
     * @param string $subtableName имя класса подтаблицы без суффикса Subtable и без неймспейса текущего модуля
     * @return \Light\SubtableBase\Subtable
     */
    protected function getSubtableObject(ObjAbstract $obj, $subtableName)
    {
        if (!isset($this->_initedSubtables[$obj->getId() . '-' . $subtableName])) {
            $module = Request::getModule();
            $class = PROJECT_NAMESPACE . "\\Module\\$module\\Subtables\\$subtableName";
            $this->_initedSubtables[$obj->getId() . '-' . $subtableName] = new $class($obj);
        }

        return $this->_initedSubtables[$obj->getId() . '-' . $subtableName];
    }

    /**
     * Реальное удаление объекта из базы
     * Если реальное удаление невозможно, т.к. пользователь не выбрал какие-то поля на форме подтверждения
     * то следует вернуть http ответ 400 c текстом сообщения, которое будет выведено в alert()
     */
    protected function proceedDelete()
    {
        $this->_state = self::STATE_DELETE;

        //Попробоуем загрузить объект и одновременно проверим права доступа к нему, если что-то не так - будет редирект
        $this->loadObjAndCheck($_GET['delid']);

        if (!$this->_allowDelete || !User::hasWriteAccess() || !$this->hasDeleteAccessRightToObj($this->_obj)) {
            Response::abort(403, 'Delete not allowed');
        }

        if (Request::isPost()) {

            // Запрос пришел из диалогового окна, нужно проверить, приавильно ли было заполнено диалоговое окно?
            // Если внутри checkDeleteConfirmResult выяснится, что пользователь что-то не заполнил - этот метод
            // сам отправит пользователю новый диалог или другое сообщение
            if (!$this->checkDeleteConfirmResult()) {
                return;
            }

            // Непосредственное удаление
            $timer = new Timer();

            $oldData = $this->getOldDataBeforeSubmitOrDelete($this->_obj);

            Db::begin();

            $msgBuff = new Msg();

            $this->beforeDelete($this->_obj, $msgBuff);
            $this->proceedDeleteObj($this->_obj);
            $this->proceedDeleteSubtables($this->_obj);
            $this->afterDelete($this->_obj, $oldData, $msgBuff);

            Db::commit();

            $msg = '<strong>' . $this->_obj->getDescriptionHtml() . '</strong> deleted';
            if ($timer->get() > 3) {
                $msg .= ' (' . $timer->getHtml() . ')';
            }

            $msgBuff->addSuccess($msg);
            $msgBuff->putIntoSessionFlash();

            //Возвращаем специальный header, который позволяет выполнить js на стороне клиента
            if (isset($_GET['fromList'])) {
                //Light.tables.get(Light.getModule()).reload();
                Response::sendHeaderJsEval('Light.tables.get(Light.getModule()).reload()');
                ApplicationAbstract::terminate();
            } else {
                $this->redirectToList();
            }

        } else {

            //Флага подтверждения не было, выведем форму подтверждения в диалоговом окне
            $this->sendDeleteConfirmDialog();

        }
    }

    /**
     * Метод отправляет диалог на подтверждение удаления.
     * Этот метод можно переопределить в классе потомке, если нужен какой-то особый диалог
     */
    protected function sendDeleteConfirmDialog()
    {
        $module = Request::getModule();
        $id = $this->_obj->getId();
        $formAction = "?a=$module&delid=$id";
        if (isset($_GET['fromList'])) {
            $formAction .= '&fromList=1';
        }
        $bodyHtml = '<form action="' . $formAction . '#ajax" method="post">'
            . $this->getDeleteConfirmationDialogHtml()
            . '</form>';

        Response::showDialog($bodyHtml, 'Delete');
    }

    /**
     * Этот метод удобно переопределять в классах потоках, если нужны дополнительные дейтсвия по удалению чего-либо
     * @param ObjAbstract $obj
     * @param string $dbLogMsg
     */
    protected function proceedDeleteObj(ObjAbstract $obj, $dbLogMsg = '')
    {
        if ($this->_folders) {
            $this->deleteFolder($obj->getData());
        }
        $obj->deleteDb($dbLogMsg);
    }

    /**
     * @param ObjAbstract $obj
     */
    private function proceedDeleteSubtables(ObjAbstract $obj)
    {
        foreach ($this->_subtables as $subtableName => $subtableParams) {
            if (!$subtableParams['readonly']) {
                $subtableObj = $this->getSubtableObject($obj, $subtableName);
                $subtableObj->loadData();
                $subtableObj->deleteAll();
            }
        }
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Метод для переопределения в классах потомках - должен вернуть ассоциативный массив из
     * 'имя класса объекта' => 'текст для вывода в диалог'
     *
     * Например, так:
     * return [
     *     ShipmentObj::class => 'Shipments'
     * ]
     *
     * @return array
     */
    protected function whileDeleteGetLinkedClasses()
    {
        return [];
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Возвращает имя поля (внешний ключ) в других таблицах, которые ссылаются на удаляемую запись.
     * Например, если мы удаляем клиента (Customer), то этот метод вернёт 'CustomerID'
     *
     * @param string $className для какого внешнего класса пытаемся сформировать внешний ключ?
     * @return string
     */
    protected function whileDeleteGetForeignFieldKey(string $className)
    {
        return Request::getModule() . 'ID';
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Метод возвращает массив ID записей указанного класса связанных с удаляемоей записью.
     * Например, если удаляем клеиента, то этот метод будет последовательно вызыван с именами классов
     * ShipmentObj::class, CInvoice::class, CPayment::class и т.п.
     * @param string $className
     * @param int $oldId
     * @return array
     */
    protected function whileDeleteGetLinkedIds($className, $oldId)
    {
        /** @var ObjAbstract $obj */
        //Создадим объект требуемого класса, чтобы узнать таблицу в базе к которой он привязан
        $obj = new $className;
        $dbTable = $obj->getDbTable();
        return Db::getCol(
            "SELECT ID
             FROM $dbTable
             WHERE {$this->whileDeleteGetForeignFieldKey($className)} = @i"
            , $oldId
        );
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Возвращает HTML строку с указанием числа связанных объектов каком-то другом модуле.
     * В этом сообщении автоматически будет указана ссылка на внешний модуль (на режим list с фильтрами)
     *
     * @param int $oldId
     * @param string $className
     * @param string $description
     * @param int $countLinkedIds
     * @return string
     */
    protected function whileDeleteGetErrorMessageHtmlAboutLinkedRecords(
        $oldId,
        $className,
        $description,
        $countLinkedIds
    ) {
        $outHtml = $description . ': <strong>' . $countLinkedIds . '</strong> ';
        $link = $this->whileDeleteGetErrorMessageLinkViewMore($oldId, $className);
        if ($link) {
            $outHtml .= ' <small>' . $link . '</small>';
        }


        return $outHtml;
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Возвращает HTML строку со ссылкой на просмотр связанных записей в соотвествующем модуле
     * Этот метод можно переопределить в классах потомках, если для формирования ссылки нужно указать какие-то
     * хитрые фильтры
     *
     * @param int $oldId
     * @param string $className
     * @return string|bool
     */
    protected function whileDeleteGetErrorMessageLinkViewMore($oldId, $className)
    {
        $externalModule = explode('\\', $className)[2];
        if (User::hasReadAccess($externalModule)) {
            return Link::moduleList('посмотреть...', [$this->whileDeleteGetForeignFieldKey($className) => $oldId], null,
                $externalModule, null, null,
                'blank');
        }

        return false;
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Возвращает HTML код для вывода элемента выбора новой записи вместо удаляемой. Обычно это выпадающий список
     *
     * @param int $oldId
     * @return string
     */
    protected function whileDeleteGetHtmlInputForReplacement($oldId)
    {
        $options = "SELECT ID, Name FROM {$this->getDbTable()} WHERE ID != " . (int)$oldId . " ORDER BY Name";
        return F::createSelect('NewValueForReplacement', [
            'options' => $options
        ])->getHtmlInput('');
    }

    protected function getDeleteConfirmationDialogHtml()
    {
        $oldId = $this->_obj->getId();
        //Выведем диалоговое окно на подтверждение удаления отправителя
        $linkedMessagess = [];
        foreach ($this->whileDeleteGetLinkedClasses() as $className => $description) {
            $linkedIds = $this->whileDeleteGetLinkedIds($className, $oldId);
            $countLinkedIds = count($linkedIds);
            if ($countLinkedIds > 0) {
                $linkedMessagess[] = $this->whileDeleteGetErrorMessageHtmlAboutLinkedRecords($oldId, $className,
                    $description, $countLinkedIds);
            }
        }

        if ($linkedMessagess) {
            $outHtml = 'Удаляемая запись связана с другими записями в базе:<br>';
            $outHtml .= '<ul><li>' . implode('</li><li>', $linkedMessagess) . '</li></ul>';

            //Далее нужно вывести выпадающий список для выбора элемента на замену
            $outHtml .= 'Выберите новый элемен на замену удаляемому:<br>';
            $outHtml .= $this->whileDeleteGetHtmlInputForReplacement($oldId);
            return $outHtml;
        }

        return 'Удалить <strong>' . $this->_obj->getDescriptionHtml() . '</strong>?';
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Метод для переопределения в классах потоках - здесь можно проверить какие-то поля пришедшие с формы подтверждения
     * и вернуть новый диалог, если что-то не устраивает
     */
    protected function checkDeleteConfirmResult(): bool
    {
        //Сразу определим вспомагательные значения
        $dbLogMsg = 'Удаление ' . $this->_obj->getDescriptionHtml();
        $oldId = $this->_obj->getId();
        $updateMessagess = [];

        // Для начала проверим, есть ли связанные записи?
        foreach ($this->whileDeleteGetLinkedClasses() as $className => $description) {
            $linkedIds = $this->whileDeleteGetLinkedIds($className, $oldId);
            $countLinkedIds = count($linkedIds);
            if ($countLinkedIds > 0) {
                //Нашли какие-то связанные записи!
                //Проверим, а был ли передан ID нового элемента на замену?
                $postKey = 'NewValueForReplacement';
                $newId = isset($_POST[$postKey]) ? (int)$_POST[$postKey] : 0;
                if (!$newId) {
                    //Сразу возвращаем диалоговое окно с ошибкой
                    Response::showDialog('<span class="bad">При удалении нужно выбрать элемент на замену удаляемому</span>');
                    return false;
                }

                //Производим непосредственное обновление связанных записей в базе!
                $updated = 0;
                foreach ($linkedIds as $linkedId) {
                    $affectedRows = $this->whileDeleteUpdateLinkedRecord($linkedId, $className, $newId, $dbLogMsg);
                    if ($affectedRows) {
                        $updated++;
                    }
                }
                if ($updated) {
                    //Если что-то было обновлено (а должно было, ибо $linkedIds не пустой массив), то выведем
                    //сообщение с количеством обновлённых
                    $updateMessagess[] = $description . ': <strong>' . $updated . '</strong>';
                }
            }
        }

        if ($updateMessagess) {
            //В процессе удаления были произведены какие-то обновления в других таблицах, выведем сообщение об этом
            $msg = 'Было обновлено:<br>';
            $msg .= '<ul><li>' . implode('</li><li>', $updateMessagess) . '</li></ul>';
            Session::flashSuccess($msg);
        }

        return true;
    }

    /**
     * Вспомагательный метод для процесса удаления и проверки, нет ли связанных объектов
     *
     * Обновляет одну указанную запись в связанной таблице. Метод может быть переопределён, если нужно как-то хитро
     * обновлять
     *
     * @param int $linkedId
     * @param string $className
     * @param int $newId
     * @param string $dbLogMsg
     * @return int
     */
    protected function whileDeleteUpdateLinkedRecord($linkedId, $className, $newId, $dbLogMsg)
    {
        /** @var ObjAbstract $obj */
        $obj = new $className;
        $obj->setId($linkedId);
        $affectedRows = $obj->updateDb([$this->whileDeleteGetForeignFieldKey($className) => $newId], $dbLogMsg);
        return $affectedRows;
    }

    protected function beforeDelete(ObjAbstract $obj, Msg $msgBuff)
    {
    }

    /**
     * Метод для переопределения в классах потомках
     * Вызывается после удаления текущего объекта и всех подтаблиц
     * Данные текущего объекта при этом продолжают быть доступными в $this->_obj,
     * В дополнение к этому метод получает в качестве единственного параметра массив $oldData - это результат работы
     * метода $this->getOldDateBeforeSubmitOrDelete(), который можно переопределить в классах потмоках, чтобы насытить его
     * дополнительными данными (например, данными подтаблиц)
     * @param ObjAbstract $obj
     * @param array $oldData
     * @param Msg $msgBuff
     */
    protected function afterDelete(ObjAbstract $obj, array $oldData, Msg $msgBuff)
    {
    }

    protected function proceedList()
    {
        if (isset($_GET['loadTableLayoutSettings'])) {
            $this->initFieldsFromSubtables();
            $this->initLayoutable();
            $this->initList();
            $this->sendAjaxTableLayoutSettings();
        }

        $this->proceedFilters();
        if (isset($_POST['filterValues']) && !Request::isAjax()) {
            // Пы попали на эту страницу каким-то POST запросом устанавилвающим значения фильтров
            // Чтобы при нажатии кнопки "Назад" в браузере не было смущающего сообщения, сделаем
            // редирект с помощью GET (фильтры уже сохранены в настройках пользователя благодаря $this->proceedFilters();
            // и они не потеряются)
            Response::redirectUrl($_SERVER['REQUEST_URI']);
        }

        //Правильно определим переменную _state
        if ($this->_tableExcelEnabled && Request::isXls()) {
            $this->_state = self::STATE_LIST_EXCEL;
        } else {
            $this->_state = self::STATE_LIST;
        }

        //Параметр из класса Table: имя модуля на который автоматически делать ссылки в таблице
        $this->_tableLinkModule = $this->_tableLinkModule ?: Request::getModule();

        $this->initCssJs();

        //Регистрируем массовые действия - метод initMassactionOptions должен быть переопределён в классе потомке
        $this->initMassactions();
        //Добавляем опции печати
        $this->initPrintForms();
        if ($this->printForms) {
            // Если в классе потомке были добавлены какие-то опции печати, то добавим massaction действие
            // Это массовое действие соответсвенно вызовет метод massactionPrint, в котором будет выведен диалог
            // для выбора формы печати подходящей всем выбранным объектам
            $this->addMassaction('Print', 'Печать (выбрать форму...)', '', 'Печать');
        }

        //Если у пользователя нет прав на редактирование в этом модуле - нужно закрыть для редактирования все поля
        //Это особо актуально для полей с ajax редактированием, чтобы они не рендрились в таблицу как редактируемые
        if (!$this->_allowEdit || !User::hasWriteAccess()) {
            $this->disableAllFieldsEdit();
        }
        //Регистрирует поля типа Folder, Checkbox, Del, ...
        $this->initTableFieldsAdditional();
        //Добавим поля, которые объявлены как кеширующие в подтаблицах
        $this->initFieldsFromSubtables();

        $this->initSortable();
        $this->initLayoutable();

        if ($this->_state != self::STATE_LIST_EXCEL) {
            //Инициализируем пажинацию, только если это не экспорт в Excel
            $this->initPaginatable();
        }

        //Установим номер страницы в зависимости от findid и moduleSession
        $findid = $this->getTableFindIdFromRequest();
        $newPage = $this->getTablePageFromModuleSession($findid);
        if ($newPage !== null) {
            $this->setTablePage($newPage);
        }

        //Метод initList() переопределяется в классх потоках - там добавляются поля необходимые только для режима List
        $this->initList();

        $this->tableLoadData();

        if ($this->isStateListExcel()) {

            //Запрос через кнопку Export to Excel
            $this->sendExcel();

        } elseif (Request::isAjax()) {

            print $this->getHtmlTableForAjax();

        } else {

            $moduleName = ModuleRegister::getModuleName(Request::getModule());
            Response::setPageHeadHtml('<strong>' . $moduleName . '</strong>');
            Response::setPageTitle($moduleName);
            $this->initListJsData();
            $this->initListIntro();

            //Это обычный запрос на рендринг таблицы в режиме List
            $tdata = [
                'table' => $this->getHtmlTable(),
                'buttons' => $this->getListButtons(),
                'filters' => $this->getFiltersHtml()
            ];

            Template::includeTpl('module/list.tpl.php', $tdata);
        }
    }

    protected function initListJsData()
    {
        Response::addJsData(['state' => 'list']);
    }

    protected function initListIntro()
    {
        IntroManager::addIntroWithHash('.filters-history', 'Смотрите и выбирайте историю Ваших поисков',
            'intro-filters-history');
        IntroManager::addIntroWithHash('.filters-save',
            'Сохраните часто используемые фильтры, чтобы получить возможность к ним быстро вернуться',
            'intro-filters-save');
        IntroManager::addIntroWithHash(
            '.filters-settings',
            '<div style="padding-bottom: 10px;">Настройте фильтры под себя, меняйте местами и скрывайте не используемые фильтры</div>
                    <img src="Light/i/intro-filters.gif" style="width: 370px;" />',
            'intro-filters-settings'
        );
    }

    protected function addIntro($selector, $text)
    {
        IntroManager::addIntro($selector, $text, $this->_state);
    }

    protected function getListButtons()
    {
        $out = [];
        if ($this->_allowAdd && User::hasWriteAccess()) {
            $out[] = [
                'text' => 'Add New',
                'url' => '?a=' . Request::getModule() . '&addnew=1',
            ];
        }

        return $out;
    }

    /**
     * Метод для переопределения в классах потомках
     * Здесь нужно последовательно вызывать $this->addCopyMode(), добавляя разные режимы копирования
     */
    protected function initCopyModes()
    {
    }

    /**
     * @param string $name
     * @param string|array $fieldsKeys
     * @param string|array $subtables
     */
    final protected function addCopyMode($name = 'Copy', $fieldsKeys = 'all', $subtables = 'all')
    {
        $this->_copyModes[$name] = ['fieldKeys' => $fieldsKeys, 'subtables' => $subtables];
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

    final protected function isMultipleupdateSubmitState()
    {
        return $this->_state == self::STATE_MULTIPLEUPDATESUBMIT;
    }

    protected function getFolderBaseDir()
    {
        return Request::getModule();
    }

    protected function getExcelFilename()
    {
        return ModuleRegister::getModuleName(Request::getModule());
    }

    protected function getExcelWorksheetName()
    {
        return ModuleRegister::getModuleName(Request::getModule());
    }

    /**
     * Метод для использования в классах потомках
     * Добавляет кнопку в подменю с тем расчётом, что при клике на это кнопку будет вызываться указанная viewFunc
     * в текущем модуле
     * @param string $text
     * @param string $imgHtml
     * @param string $viewFunc
     * @param array $urlParams
     * @param bool $blank
     * @param string $urlHash
     * @return void
     */
    final protected function addSubmenuItemViewFunc(
        $text,
        $imgHtml,
        $viewFunc,
        array $urlParams = [],
        $blank = false,
        $urlHash = ''
    ) {
        $urlParams['id'] = $this->_obj->getId();
        $urlParams['func'] = $viewFunc;
        $urlParams += $this->getModuleSessionUrlParams();
        $url = Link::url($urlParams);

        if ($urlHash) {
            //Если этот url нужгно открыть ajax запросом, добавим небольшую метку к url.
            //По этой метке будет навешен обработчик клика в JS initAjaxLinkListeners() в Light.js
            $url .= "#$urlHash";
        }

        $this->addSubmenuItemUrl($text, $imgHtml, $url, $blank);
    }

    final protected function addSubmenuItemViewFuncPost(
        $text,
        $imgHtml,
        $viewFunc,
        array $urlParams = []
    ) {
        $this->addSubmenuItemViewFunc($text, $imgHtml, $viewFunc, $urlParams, false, 'post');
    }

    final protected function addSubmenuItemViewFuncAjax(
        $text,
        $imgHtml,
        $viewFunc,
        array $urlParams = []
    ) {
        $this->addSubmenuItemViewFunc($text, $imgHtml, $viewFunc, $urlParams, false, 'ajax');
    }

    final protected function addSubmenuItemViewFuncAjaxPost(
        $text,
        $imgHtml,
        $viewFunc,
        array $urlParams = []
    ) {
        $this->addSubmenuItemViewFunc($text, $imgHtml, $viewFunc, $urlParams, false, 'ajax-post');
    }

    final protected function addSubmenuItemUrl($text, $imgHtml, $url, $blank = false, $ajax = false)
    {
        if ($imgHtml) {
            $text = $imgHtml . ' ' . $text;
        }

        $this->_submenuItemsTplData[] = [
            'text' => $text,
            'url' => $url,
            'blank' => $blank
        ];
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
            'critical' => $critical
        ];
    }

    protected function getTableReloadUrl()
    {
        return '?a=' . Request::getModule();
    }

    /**
     * @param string $subtableName имя класса подтаблицы без суффикса Subtable и без неймспейса текущего модуля
     * @return void
     */
    protected function registerSubtable($subtableName)
    {
        $this->_subtables[$subtableName] = [
            'readonly' => false
        ];
    }

    protected function registerSubtableReadonly($subtableName)
    {
        $this->_subtables[$subtableName] = [
            'readonly' => true
        ];
    }

    /**
     * Метод для переопределения в классах потоках - здесь проверяются права доступа к объекту в режимах View/Edit/Log/Delete
     * @param ObjAbstract $obj
     * @return bool
     */
    protected function hasAccessRightToObj(ObjAbstract $obj)
    {
        return true;
    }

    protected function initFieldsFromSubtables()
    {
        foreach ($this->_subtables as $subtableName => $subtableParams) {
            $subtableObj = $this->getSubtableObject($this->_obj, $subtableName);
            //Добавим новые поля
            //Эти поля объявлены внутри подтаблиц с переменных $this->_groupconcatFieldKeys и $this->_cacheFieldsKeys
            $subtableFields = $subtableObj->getFieldsForMasterModuleList();
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
     * Убираем у всех полей MODE_EDIT. Это актуально для ajax поле, если у текущего пользователя нет прав на редактирование в этом
     * модуле или в отдельном объекте, то они не будут рендриться как ajax-editable
     */
    protected function disableAllFieldsEdit()
    {
        foreach ($this->getFieldset() as $field) {
            $field->setModes($field->getModes() & ~F::MODE_EDIT);
        }
    }

    /**
     * Регистрирует дополнительные поля для таблицы списка, такие как CheckBox, DEL ...
     * @return void
     */
    protected function initTableFieldsAdditional()
    {
        //Общие параметры для всех полей типов
        $commonParams['tableWidthHtml'] = 20;
        $commonParams['tableOrderBy'] = '';
        $commonParams['tableAlign'] = 'center';

        if ($this->massactions) {
            $params = $commonParams;
            $params['nameTable'] = '<input type="checkbox" class="cb-all">';
            $params['getHtmlFunction'] = function ($value, array $data) {
                if (isset($data['ID'])) {
                    return '<input type="checkbox" class="cb" value="' . $data['ID'] . '">';
                }

                return '';
            };
            $this->getFieldsetFirst()->addField(F::create('CheckBox', F::TYPE_HTML, $params));
        }

        if ($this->_folders) {
            $this->getFieldsetFirst()->addField($this->fieldFolder());
            $this->addFields(
                $this->fieldFolderIsEmpty(),
                $this->fieldFolderFilesList(),
                $this->fieldFolderFilesCount(),
                $this->fieldFolderSize()
            );
        }

        if (User::hasWriteAccess()) {

            if ($this->hasField('Color')) {
                $colorField = $this->getField('Color');
                $colorField->setParam(
                    'source',
                    '?a=' . Request::getModule() . '&id={id}&func=changeField'
                );
                $this->getFieldsetFirst()->addField($colorField);
            }

            if ($this->_allowDelete || $this->_allowEdit) {
                //Ширина колонки Del зависит от наличия иконки удаления
                $params = $commonParams;
                if ($this->_allowDelete && $this->_allowEdit) {
                    $params['tableWidthHtml'] = 60;
                } else {
                    $params['tableWidthHtml'] = 20;
                }
                $params['nameTable'] = '';
                $params['getHtmlFunction'] = function ($value, array $data) {

                    $outHtml = '';
                    if (isset($data['ID'])) {
                        if ($this->_allowEdit) {
                            $outHtml .= Link::moduleEdit(
                                Img::iconEdit(),
                                $data['ID'],
                                $this->getModuleSessionUrlParams() + ['fromList' => 1]
                            );
                        }
                        if ($this->_allowDelete) {
                            $url = Link::url(['delid' => $data['ID'], 'fromList' => 1] + $this->getModuleSessionUrlParams()) . '#ajax';
                            $outHtml .= '<a href="' . $url . '" class="del-link" title="Delete">' . Img::iconDelete() . '</a>';
                        }
                    }

                    return $outHtml;
                };

                $this->getFieldsetLast()->addField(F::createHtml('Actions', $params));
            }
        }
    }

    /** @var  \mysqli_result */
    protected $_tableQueryRes;

    protected function tableLoadData()
    {
        //Cформируем условие сортировки.
        //Оно может состоять из сортировки указанной в trait TableSortable и из _tableDefaultOrderBy
        //Сначала соберём всё в массив, потом из него склеим строку
        $sqlOrderBy = $this->getSqlOrderBy();

        if (isset($_GET['excelIds']) && $this->isStateListExcel()) {

            $idsString = implode(',', array_map('intval', explode(',', $_GET['excelIds'])));

        } else {

            //Сделаем некую оптимизацию: сначала вынем все ID записей, которые должны в итоге попасть в таблицу
            //После этого достанем все поля, указав в условии WHERE ID IN (...)
            //Это значительно ускорит запрос, т.к. сортировка сначала LIMIT будут производиться на лёгком наборе ID
            //При выборке остальных колонок объём данных будет уже меньше за счёт ограниченного набора WHERE ID IN (...)

            $queryIds = "SELECT";

            //В режиме выгрузки таблицы в Excel нам не важно сколько строк в таблице всего
            if (!$this->isStateListExcel()) {
                $queryIds .= " SQL_CALC_FOUND_ROWS";
            }

            $queryIds .= " ID";

            //Проверим поле по которому идёт сортировка. Если оно вычисляемое, его тоже придётся добавить в этот запрос
            //Причём у этого вычисляемого поля могут быть зависимости, поэтому используем $this->getFieldset()->getSqlSelect()
            $sortField = $this->getSortField();
            if ($sortField && $sortField->getSqlSelect()) {
                $queryIds .= ', ' . $this->getFieldset()->getSqlSelect([$sortField->key]);
            }

            $queryIds .= " FROM " . Db::quote($this->getDbTable());

            $sqlWhere = $this->getFiltersSqlWhere();
            if ($sqlWhere) {
                $queryIds .= " WHERE " . $sqlWhere;
            }

            if ($sqlOrderBy) {
                $queryIds .= " ORDER BY " . $sqlOrderBy;
            }

            if ($this->isStateListExcel()) {
                $queryIds .= ' LIMIT ' . $this->getTableExcelMaxRows();
            } elseif ($this->_tablePaginationEnabled) {
                $page = $this->getTablePage();
                $rowsPerPage = $this->getTableRowsPerPage();
                $queryIds .= ' LIMIT ' . ($page - 1) * $rowsPerPage . ', ' . $rowsPerPage;
            }

            //Получим все ID из базы
            $timer = new Timer();
            $res = Db::query($queryIds);
            if ($res->num_rows) {
                $idsString = '0';
                while ($row = $res->fetch_row()) {
                    $idsString .= ',' . $row[0];
                }
            } else {
                $idsString = '';
            }
            $res->free();
            $this->_queryTime += $timer->get();


        }

        if ($idsString) {

            //Сразу посчитаем общее количество строк, если это не режим экспорта в Excel
            if (!$this->isStateListExcel()) {
                $foundRows = Db::getOne("SELECT FOUND_ROWS()");
                $this->setTableTotalRecordsFound($foundRows);
            }

            //Если это не режим экспорта в Excel, запишем ID всех отображенных пользователю строк в базу
            //Они понадобятся для навигации по стрелочкам в режимах View/Edit/Log
            if (!$this->isStateListExcel()) {
                $this->createModuleSession($idsString, $this->getTablePage());
            }

            //Теперь сделаем второй запрос, в котором WHERE ID IN (...) - результат запишем в таблицу $this->_tableData
            $query = "SELECT ";

            $selectFieldKeys = $this->getFieldsetShowed()->getKeys();
            $query .= $this->getFieldset()->getSqlSelect($selectFieldKeys);

            $query .= " FROM " . Db::quote($this->getDbTable());
            $query .= " WHERE ID IN (" . $idsString . ")";

            if ($sqlOrderBy) {
                $query .= " ORDER BY " . $sqlOrderBy;
            }

            $timer = new Timer();
            $this->_tableQueryRes = Db::query($query);
            /*
            $this->_tableData = new \SplFixedArray($res->num_rows);
            $rowIndex = 0;
            while ($row = $res->fetch_assoc()) {
                $this->_tableData[$rowIndex] = $row;
                $rowIndex++;
            }
            */

            //Загрузим специальный массив с закешированным состоянием непустых папок
            $this->loadCacheNotEmptyFolderIds($idsString);

            $this->_queryTime += $timer->get();

        }
    }

    public function getTableDataCount()
    {
        if (!$this->_tableQueryRes) {
            return count($this->_tableData);
        }
        return $this->_tableQueryRes->num_rows;
    }

    final protected function getTableRowData($rowNum)
    {
        if ($this->_tableQueryRes) {
            return $this->_tableQueryRes->fetch_assoc();
        }

        return parent::getTableRowData($rowNum);
    }

    public function getTableData()
    {
        $numRows = $this->getTableDataCount();
        if ($numRows == 0) {
            return [];
        }

        if ($this->_tableQueryRes) {
            $this->_tableData = $this->_tableQueryRes->fetch_all(MYSQLI_ASSOC);
            $this->_tableQueryRes->free();
            $this->_tableQueryRes = null;
        }

        return $this->_tableData;
    }

    final protected function isStateListExcel()
    {
        return $this->_state === self::STATE_LIST_EXCEL;
    }

    public function getHtmlTableForAjax($printHeadBlock = true, $printIfEmpty = false)
    {
        $outHtml = parent::getHtmlTableForAjax($printHeadBlock, $printIfEmpty);
        if ($this->hasFilters()) {
            $outHtml .= $this->getFiltersHiddenInput();
        }

        $outHtml .= $this->getHtmlMassactionsSelectBlock();

        return $outHtml;
    }

    protected function hasWriteAccessAjaxChnageField(FieldAbstract $field):bool
    {
        return $this->_allowEdit
            && User::hasWriteAccess()
            && $this->hasWriteAccessRightToObj($this->_obj)
            && $field->hasMode(F::MODE_SUBMIT);
    }

    /**
     * Метод вызывается через GET ajax запросы на url ?a=<module>&id=123&func=changeField
     * когда пользователь кликнул на ссылку .ajax-input - в этот момент нужно подгрузить с сервера HTML код поля ввода
     */
    private function viewGetChangeField()
    {
        $this->_state = self::STATE_VIEW_CHANGE_FIELD;
        if (!Request::isAjax()) {
            $this->redirectToView();
        }

        $fieldKey = $_REQUEST['fieldKey'];
        $field = $this->_obj->getFieldset()->getField($fieldKey);
        if (!$field) {
            Response::abort(400, "Field not found");
        }

        if (!$this->hasWriteAccessAjaxChnageField($field)) {
            Response::abort(403, 'Edit not allowed');
        }

        $value = $this->_obj[$fieldKey];
        $data = $this->_obj->getData();
        // Выводим в ajax ответ HTML код для поля ввода
        print $this->_obj->getField($fieldKey)->getHtmlInput($value, $data);
        ApplicationAbstract::terminate();
    }

    /**
     * Метод вызывается через POST ajax запросы на url ?a=<module>&id=123&func=changeField&fieldKey=...&newValue=...
     * когда пользователь отправляет на сервер значение какого-то ajax поля
     */
    private function viewPostChangeField()
    {
        $this->_state = self::STATE_VIEW_CHANGE_FIELD;
        if (!Request::isAjax()) {
            $this->redirectToView();
        }

        $fieldKey = $_REQUEST['fieldKey'];
        $field = $this->_obj->getFieldset()->getField($fieldKey);
        if (!$field) {
            Response::abort(400, "Field not found");
        }

        if (!$this->hasWriteAccessAjaxChnageField($field)) {
            Response::abort(403, 'Edit not allowed');
        }

        $value = $_REQUEST['newValue'];
        $data = [$fieldKey => $value];
        $normilizedValue = $field->getDataFromRequest($data);
        if ($normilizedValue === null) {
            Response::abort(400, "Invalid value $value");
        }

        $oldData = $this->getOldDataBeforeSubmitOrDelete($this->_obj);

        Db::begin();

        $this->_obj->updateDb([$fieldKey => $normilizedValue], 'Ajax');

        //Вызовем дополнительный обработчик, который, возможно, переопределён в классах потомках
        $this->afterChangeField($fieldKey, $oldData);

        Db::commit();

        print $this->_obj->getFieldHtml($fieldKey);
        ApplicationAbstract::terminate();
    }

    /**
     * @param string $fieldKey
     * @param array $oldData
     */
    protected function afterChangeField($fieldKey, array $oldData)
    {
    }

    /**
     * Метод для переопределения в классах потоках - здесь проверяются права доступа к объекту в режиме Edit
     * @param ObjAbstract $obj
     * @return bool
     */
    protected function hasWriteAccessRightToObj(ObjAbstract $obj)
    {
        return true;
    }

    protected function hasWriteAccessRightToFolder(ObjAbstract $obj)
    {
        return User::hasWriteAccess() && self::hasWriteAccessRightToObj($obj);
    }

    /**
     * @param ObjAbstract $obj
     * @return bool
     */
    protected function hasDeleteAccessRightToObj(ObjAbstract $obj)
    {
        return true;
    }

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
            if (substr($key, -7) === '_findid') {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * Метод для переопределения в классах потомках - здесь должен заполняться массив $this->massactions
     * Для более удобного заполнения массива $this->massactions можно использовать метод $this->addMassaction()
     */
    protected function initMassactions()
    {
    }

    /**
     * @param string $action уникальный ключ этого massaction
     * @param string $text текст отображаемый в выпадающем списке
     * @param string $cssClass класс которым подсветить элемент в выпадающем списке
     * @param string $optgroup
     * @param string|null $class
     * @param bool $blank открывать при сабмите в новом окне браузера
     */
    protected function addMassaction(
        string $action,
        string $text,
        string $cssClass = '',
        string $optgroup = '',
        string $class = null,
        bool $blank = false
    ) {
        //Если передан флаг blank, значит нужно откывать новое окно браузера при сабмите massaction
        //Это происходит в js окружении, поэтому нужно как-то передать информацию в js окружение, что данный massaction
        //следует открывать в новом окне. Используем для этого css class
        if ($blank) {
            $cssClass = trim($cssClass . ' massaction-target-blank');
        }

        $this->massactions[$action] = [
            'text' => $text,
            'cssClass' => $cssClass,
            'optgroup' => $optgroup,
            'class' => $class
        ];
    }

    final protected function addMassactionDelete()
    {
        if (User::hasWriteAccess() && $this->_allowDelete) {
            $this->addMassaction('delete', 'Delete', 'bad');
        }
    }

    final protected function addMassactionCollectPdfs()
    {
        if ($this->_folders) {
            $this->addMassaction('CollectPdfs', 'Собрать PDF файлы в папку');
        }
    }

    final protected function addMassactionMultipleUpdate()
    {
        if (User::hasWriteAccess() && $this->_allowEdit) {
            $this->addMassaction('MultipleUpdate', 'Multiple Update');
        }
    }

    /**
     * Метод вызывается напрямую из браузера через url `?a=${_lightModule}&p=massaction&action=${action}`;
     */
    public function proceedMassaction()
    {
        $this->initMassactions();
        $action = $_GET['action'];
        $ids = MassactionAbstract::getCheckedIdsFromPost();

        if ($action === 'Print') {
            // Особый massaction которого нет в массиве $this->massactions
            $this->massactionPrint($ids);
            return;
        }

        if (!isset($this->massactions[$action])) {
            throw new \InvalidArgumentException("Unknown massaction $action");
        }

        $class = $this->massactions[$action]['class'];
        if ($class) {
            // Если внутри Massaction определён какой-то калсс для запуска - инициализируем и запустим его!
            /** @var MassactionAbstract $massactionObj */
            $massactionObj = new $class;
            $massactionObj($ids);

        } else {

            // В противном случае попробуем запустить метод с определённым именем в текущем классе
            $handler = 'massaction' . ucfirst($action);
            if (!method_exists($this, $handler)) {
                $this->{$handler}($ids);
                throw new \BadMethodCallException("Unknown function $handler in class " . get_class($this));
            }

            $this->{$handler}($ids);

        }
    }

    protected function massactionDelete(array $ids)
    {
        if (!$this->_allowDelete || !User::hasWriteAccess()) {
            MassactionAbstract::sendErrorDialog('No access to delete');
        }
        $msgBuff = new Msg();
        $deleted = 0;


        Db::begin();
        foreach ($ids as $id) {

            //Проверим, что id числовой и больше 0
            $idInt = (int)$id;
            if ($idInt <= 0) {
                continue;
            }

            //Проверим существование объекта в базе, попробовав его загрузить
            $obj = clone $this->_obj;
            $obj->setId($idInt);
            if (!$obj->loadData()) {
                $msgBuff->addError("Object with ID = $idInt not found");
                continue;
            }

            //Проверим, что у пользователя есть права доступа к этому объекту
            if (!$this->hasAccessRightToObj($obj)
                || !$this->hasDeleteAccessRightToObj($obj)
            ) {
                $msgBuff->addError("No access to delete object with ID = $idInt");
                continue;
            }

            $oldData = $this->getOldDataBeforeSubmitOrDelete($obj);

            $this->beforeDelete($obj, $msgBuff);
            $this->proceedDeleteObj($obj, 'Multiple Delete');
            $this->proceedDeleteSubtables($obj);
            $this->afterDelete($obj, $oldData, $msgBuff);
            $deleted++;
        }
        Db::commit();

        //Стандартное сообщение о числе обновлённых записей (зелёное или серое)
        $msg = 'Deleted: ' . $deleted;
        if ($deleted > 0) {
            $msgBuff->addSuccess($msg);
        } else {
            $msgBuff->addInfo($msg);
        }

        MassactionAbstract::sendSuccessDialog($msgBuff->getHtml());
    }

    protected function massactionCollectPdfs(array $ids)
    {
        $getFolderRelativePath = function (array $data) {
            return $this->getFolderRelativePath($data);
        };
        MassactionCollectPdfs::proceedCollectPdfs($ids, $this->getDbTable(), $getFolderRelativePath);
    }

    protected function massactionMultipleUpdate(array $ids)
    {
        // Функция getHtmlMultipleUpdateForm может быть переопределена в классе потомке - именно она формирует HTML код формы
        $html = '<form action="?a=' . Request::getModule() . '&p=MultipleUpdateSubmit#ajax" method="post" class="multipleupdate">
                     <input type="hidden" name="CheckedIds" value="' . implode(',', $ids) . '">
                     ' . $this->getHtmlMultipleUpdateFormBody($ids) . '
                     </form>';
        Response::showDialog($html, 'Multiple Update (' . count($ids) . ')', null, null, null, null, '95%', 10);
    }

    /**
     * Метод для переопределения в классах потомках
     * Иногда к полям выводимым для редакитирования на форме Multiple Update нужно добавить какие-то особенные поля
     * Например, поля связанные с подтаблицами
     * @return Fieldset
     */
    protected function getMultipleUpdateFieldset()
    {
        return $this->getFieldset()->filterByMode(F::MODE_MULTIPLEUPDATE);
    }

    /**
     * Метод для переопределения в классах потомках, если нужно добавить какие-то переменные к шаблону
     * @return array
     */
    protected function getTplDataMultipleUpdateFormBody()
    {
        return $this->getMultipleUpdateFieldset()->getTplData([], F::MODE_MULTIPLEUPDATE);
    }

    /**
     * Для переопределения в классах потомках - возвращает HTML код формы БЕЗ ТЕГОВ form
     * @param array $ids
     * @return string HTML
     */
    protected function getHtmlMultipleUpdateFormBody(array $ids)
    {
        $tdata = $this->getTplDataMultipleUpdateFormBody();

        //Определим шаблон, который использовать для формы MultipleUpdate
        $module = Request::getModule();
        $templateFile = $module . 'MultipleUpdate.tpl.php';
        if (!Template::findfile($templateFile)) {
            $templateFile = LIGHT_DIR_LIGHT . '/templates/module/multipleupdate/auto.tpl.php';
        }

        return Template::parseTpl($templateFile, $tdata);
    }

    protected function massactionPrint(array $ids)
    {
        $this->initPrintForms();
        $objBase = $this->_obj;

        if (isset($_GET['form'])) {

            // Пользователь уже видел диалог с выбором печатной формы и кликнул по одной из ссылок!
            $form = $_GET['form'];
            if (!isset($this->printForms[$form])) {
                throw new \InvalidArgumentException("Неизвестная печатная форма $form");
            }

            // Запустим массовую печать (логика инкапсулирована в отдельном классе MassactionPrint)
            $printForm = $this->printForms[$form];
            $getFolderRelativePath = function (array $data) {
                return $this->getFolderRelativePath($data);
            };
            MassactionPrint::proceedMultiplePrint($ids, $printForm, $objBase, $getFolderRelativePath);
            return;

        }

        MassactionPrint::showSelectPrintFromDialog($ids, $objBase, $this->printForms);

    }

    protected function getHtmlMassactionsSelectBlock()
    {
        if ($this->massactions && $this->getTableDataCount()) {
            $html = '<div class="listmassaction">'
                . '<span class="selected-count display-none">Selected: <strong>0</strong></span>'
                . '<a href="#" class="inner table-select-all-link">Select all</a>'
                . '<a href="#" class="inner table-unselect-all-link">Unselect all</a>'
                . '<label for="massaction-select">With selected:</label>'
                . F::createSelect('massaction-select', ['options' => $this->massactions])->getHtmlInput('');
            if ($this->_tableTotalRowCalcFieldsKeys && is_array($this->_tableTotalRowCalcFieldsKeys)) {
                $html .= '<span class="selected-count-column" data-keys="' . implode("|",
                        $this->_tableTotalRowCalcFieldsKeys) . '"></span>';
            }
            $html .= '</div><div class="listmassactionIndicator"></div>';
            return $html;
        }

        return '';
    }
}
