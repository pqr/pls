<?php


namespace Light\ModuleBase\Massactions;


use Light\Config;
use Light\ErrorHandler;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Link;
use Light\Misc;
use Light\ModuleBase\MassactionAbstract;
use Light\ModuleBase\MassactionUtils;
use Light\Msg;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\Template;
use Light\User;

class MassUpdate extends MassactionAbstract
{
    /**
     * @var ObjAbstract
     */
    protected $objBase;

    public function __construct(ObjAbstract $objBase)
    {
        if (!$this->text) {
            $this->text = Config::$lang === 'ru' ? 'Массовое редактирование' : 'Multiple Update';
        }

        $this->objBase = $objBase;
    }

    public function getKey(): string
    {
        return Misc::getModule($this->objBase) . 'MassUpdate';
    }

    public function hasAccess(): bool
    {
        return User::hasWriteAccess(Misc::getModule($this->objBase));
    }

    public function __invoke(array $ids)
    {
        if (isset($_GET['submit']) && Request::isPost()) {
            $this->proceedSubmit($ids);
            return;
        }

        $this->showDialog($ids);
    }

    protected function showDialog(array $ids): void
    {
        $formAction = $this->getLink(['submit' => 1])->getHref();

        $select2ajaxUrl = $this->getSelect2ajaxUrl();

        $html = '<form action="' . $formAction . '" 
                    method="post" 
                    class="multipleupdate"
                    data-select2ajaxurl="' . $select2ajaxUrl . '">'
            . MassactionUtils::getHtmlHiddenCheckedIds($ids)
            . $this->getDialogBodyHtml($ids)
            . '</form>';

        $buttonOkText = $this->getButtonOkText($ids);
        Response::showDialog($html, $buttonOkText);
    }

    protected function getSelect2ajaxUrl(): string
    {
        return Link::create(Request::getModule())->getHref();
    }

    protected function getDialogBodyHtml(array $ids): string
    {
        $tdata = $this->getTplData($ids);
        $templateFile = $this->getTemplate();
        return Template::parseTpl($templateFile, $tdata);
    }

    protected function getButtonOkText(array $ids): string
    {
        return $this->text . ' (' . count($ids) . ')';
    }

    protected function getTemplate(): ?string
    {
        if (get_class($this) === MassUpdate::class) {

            // Если это стандартный класс MassUpdate из движка, то попробуем найти файл шаблона рядом с объектом
            // в папке Massactions
            $namespace = Misc::getNamespace($this->objBase);
            $objClass = Misc::getClass($this->objBase);
            if (substr($objClass, -3) === 'Obj') {
                $objClass = substr($objClass, 0, -3);
            }
            $filepath = LIGHT_DIR_ROOT . '/src/'
                . str_replace('\\', '/', $namespace)
                . '/Massactions/' . $objClass . 'MassUpdate.tpl.php';

        } else {

            // Этот класс является наследником от MassUpdate - будем искать файл непосредственно рядом с текущим классом
            $massactionClassWithNamespace = get_class($this);
            $filepath = LIGHT_DIR_ROOT . '/src/'
                . str_replace('\\', '/', $massactionClassWithNamespace)
                . '.tpl.php';

        }

        if (file_exists($filepath)) {
            // Специально созданный файл шаблона найден - используем именно его!
            return $filepath;
        }

        return LIGHT_DIR_LIGHT . '/templates/module/multipleupdate/auto.tpl.php';
    }

    protected function getTplData(array $ids): array
    {
        return $this->getFieldset($ids)->getTplData([], F::MODE_MULTIPLEUPDATE);
    }

    /**
     * Метод для переопределения в классах потомках
     * Иногда к полям выводимым для редакитирования на форме Multiple Update нужно добавить какие-то особенные поля
     * Например, поля связанные с подтаблицами
     * @param array $ids
     * @return Fieldset
     */
    protected function getFieldset(array $ids): Fieldset
    {
        return $this->objBase->getFieldset()->filterByMode(F::MODE_MULTIPLEUPDATE);
    }

    protected function proceedSubmit(array $ids): void
    {
        $msgBuff = new Msg();
        $updated = 0;
        $nothingChanged = 0;

        $dataFromPost = $this->getDataFromPost($ids, $msgBuff);
        if ($dataFromPost) {
            // Поочерёдно обновим каждый id
            foreach ($ids as $id) {
                try {
                    $affectedRows = $this->submitOneId($id, $dataFromPost, $msgBuff);
                    if ($affectedRows) {
                        $updated += $affectedRows;
                    } else {
                        $nothingChanged++;
                    }
                } catch (\Exception $e) {
                    $msgBuff->addError($e->getMessage());
                    ErrorHandler::reportException($e);
                    $nothingChanged++;
                }
            }
        }

        $this->afterMassUpdate($ids, $updated, $nothingChanged, $msgBuff);

        $msg = Config::$lang === 'ru' ? 'Обновлено' : 'Updated';
        $msg .= ': ' . $updated;
        $msgBuff->addSuccessOrInfo($msg, $updated);

        // Стандратное сообщение о количесве ошибок
        if ($nothingChanged > 0) {
            $msg = Config::$lang === 'ru' ? 'Без изменений' : 'Nothing changed';
            $msg .= ': ' . $nothingChanged;
            $msgBuff->addInfo($msg);
        }

        $this->showDialogSuccess($msgBuff);
    }

    /**
     * @param $id
     * @param array $dataFromPost
     * @param Msg $msgBuff
     * @return int affectedRows
     * @throws \Exception
     */
    protected function submitOneId($id, array $dataFromPost, Msg $msgBuff): int
    {
        // Проверим существование объекта в базе, попробовав его загрузить
        $obj = clone $this->objBase;
        $obj->setId($id);
        if (!$obj->loadData()) {
            $msg = Config::$lang === 'ru' ? "Не найден объект с ID = $id" : "Object with ID = $id not found";
            $msgBuff->addError($msg);
            return 0;
        }

        if (!$obj->accessWrite()) {
            $link = $obj->getLinkView()->blank();
            $msg = Config::$lang === 'ru' ? "Нет доступа на редактирование $link" : "No access to edit $link";
            $msgBuff->addError($msg);
            return 0;
        }

        return $this->updateObj($obj, $dataFromPost, $msgBuff);
    }

    /**
     * Выполняет обновление еденичной записи
     * Функция удобна для переопределения в классах потмоках
     * @param ObjAbstract $obj
     * @param array $dataFromPost
     * @param Msg $msgBuff
     * @return int affectedRows
     * @throws \Exception
     */
    protected function updateObj(ObjAbstract $obj, array $dataFromPost, Msg $msgBuff): int
    {
        $fieldsData = $obj->filterDataByModeSubmit($dataFromPost);
        $fieldsData = $obj->filterDataByEditAccessFunction($fieldsData);
        if (!$fieldsData) {
            return 0;
        }

        $dbLogMsg = $this->text;
        $affectedRows = $obj->updateDb($fieldsData, $dbLogMsg);

        $obj->afterMultipleUpdate($msgBuff);
        return $affectedRows;
    }

    protected function getDataFromPost(array $ids, Msg $msgBuff): array
    {
        // Дополнительно, отфильтруем только теми полями, которые были на форме отмечены как редактируемые
        // На форме были скрытые поля со статусами 1 или 0 - все они оформлены виде массива multipleupdate
        $selectedFieldKeys = [];
        foreach ($_POST['multipleupdate'] as $fieldKey => $value) {
            if ($value === 'showed') {
                $selectedFieldKeys[] = $fieldKey;
            }
        }

        if (!$selectedFieldKeys) {
            // Ни одно поле в диалоговом окне не было отмечено для изменения
            return [];
        }

        $fieldset = $this->getFieldset($ids);

        // Оставим только те поля, которые указаны в $selectedFieldKeys
        $fieldset = $fieldset->filterByKeys($selectedFieldKeys);

        // Соберём массив с данными по полям
        $requestData = [];
        foreach ($selectedFieldKeys as $fieldKey) {
            if (isset($_POST[$fieldKey])) {
                $requestData[$fieldKey] = $_POST[$fieldKey];
            }
        }

        // Обработаем данные с помощью функций определённый в объектах полей
        return $fieldset->getDataFromRequest($requestData);
    }

    /**
     * Для переопределения в классах потомках
     * @param array $ids
     * @param int $updated
     * @param int $nothingChanged
     * @param Msg $msgBuff
     */
    protected function afterMassUpdate(array $ids, int $updated, int $nothingChanged, Msg $msgBuff)
    {

    }

}
