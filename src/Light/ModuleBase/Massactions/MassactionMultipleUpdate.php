<?php


namespace Light\ModuleBase\Massactions;


use Light\Config;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Link;
use Light\ModuleBase\MassactionUtils;
use Light\Msg;
use Light\ObjAbstract;
use Light\Response;
use Light\Template;
use Light\User;

/**
 * Один из стандартных Massaction реализован в виде Trait к ModuleAbstract
 * Trait MassactionMultipleUpdate
 * @package Light\ModuleBase
 */
trait MassactionMultipleUpdate
{
    protected function addMassactionMultipleUpdate()
    {
        if ($this->allowEdit && User::hasWriteAccess()) {
            $text = Config::$lang === 'ru' ? 'Массовое редактирование' : 'Multiple Update';
            $this->addMassaction('MultipleUpdate', $text);
        }
    }

    protected function massactionMultipleUpdate(array $ids)
    {
        $module = $this->getModule();

        $formAction = Link::create($module)
            ->proceed('MultipleUpdateSubmit')
            ->ajax()
            ->getHref();

        $select2ajaxUrl = Link::create($module)->getHref();

        $html = '<form action="' . $formAction . '" 
                    method="post" 
                    class="multipleupdate"
                    data-select2ajaxurl="' . $select2ajaxUrl . '">'
            . MassactionUtils::getHtmlHiddenCheckedIds($ids)
            . $this->muBodyHtml($ids)
            . '</form>';

        $buttonOkText = Config::$lang === 'ru' ? 'Массовое редактирование' : 'Multiple Update';
        $buttonOkText .= ' (' . count($ids) . ')';

        Response::showDialog($html, $buttonOkText, null, null, null, null, '95%', 10);
    }

    /**
     * Срабатывает после отправки формы Multiple Update через AJAX
     * Возвращает AJAX сообщение об удачном multiple update, после закрытия котрого страница обновляется
     * Метод вызывается напрямую, т.к. ulr формы Multiple Update содержит параметр p=MultipleUpdateSubmit
     */
    public function proceedMultipleUpdateSubmit()
    {
        if (!$this->allowEdit || !User::hasWriteAccess()) {
            MassactionUtils::sendErrorDialog('No access to edit');
            return;
        }

        $this->_state = self::STATE_MULTIPLEUPDATESUBMIT;

        $msgBuff = new Msg();
        $updated = 0;
        $nothingChanged = 0;

        $dataFromPost = $this->muDataFromPost($msgBuff);
        if ($dataFromPost) {
            $ids = $this->getMassactionIdsFromPost();
            $updatedObjects = [];

            /** @var ObjAbstract $objBase */
            $objBase = $this->getObj();
            //Поочерёдно обновим каждый id
            foreach ($ids as $id) {

                //Проверим существование объекта в базе, попробовав его загрузить
                $obj = clone $objBase;
                $obj->setId($id);
                if (!$obj->loadData()) {
                    $msgBuff->addError("Object with ID = $id not found");
                    continue;
                }

                // Проверим, что у пользователя есть права на запись к этому объекту
                // (права на чтение мы уже проверили в методе $this->getMassactionIdsFromPost();
                if (!$this->hasWriteAccessRightToObj($obj)) {
                    $msgBuff->addError("No access to edit object with ID = $id");
                    continue;
                }

                // На всякий случай запишем в $this->_obj ссылку на текущий объект,
                // чтобы в переопределённых методах в классах потомках не возникло случайных ошибок
                $this->_obj = $obj;
                if ($this->muUpdateObj($obj, $dataFromPost, $msgBuff)) {
                    $updated++;
                    $updatedObjects[] = $obj;
                } else {
                    $nothingChanged++;
                }

            }

            $this->muAfterSubmit($updatedObjects, $dataFromPost, $msgBuff);

        }

        $msg = Config::$lang === 'ru' ? 'Обновлено' : 'Updated';
        $msg .= ': ' . $updated;
        $msgBuff->addSuccessOrInfo($msg, $updated);

        //Стандратное сообщение о количесве ошибок
        if ($nothingChanged > 0) {
            $msg = Config::$lang === 'ru' ? 'Без изменений' : 'Nothing changed';
            $msg .= ': ' . $nothingChanged;
            $msgBuff->addInfo($msg);
        }

        MassactionUtils::sendSuccessDialog($msgBuff->getHtml());
    }

    /**
     * Метод для переопределения в классах потомках
     * Иногда к полям выводимым для редакитирования на форме Multiple Update нужно добавить какие-то особенные поля
     * Например, поля связанные с подтаблицами
     * @return Fieldset
     */
    protected function muFieldset()
    {
        return $this->getFieldset()->filterByMode(F::MODE_MULTIPLEUPDATE);
    }

    /**
     * Метод для переопределения в классах потомках, если нужно добавить какие-то переменные к шаблону
     * @return array
     */
    protected function muTplData()
    {
        return $this->muFieldset()->getTplData([], F::MODE_MULTIPLEUPDATE);
    }

    /**
     * Для переопределения в классах потомках - возвращает HTML код формы БЕЗ ТЕГОВ form
     * @param array $ids
     * @return string HTML
     */
    protected function muBodyHtml(array $ids)
    {
        $tdata = $this->muTplData();

        //Определим шаблон, который использовать для формы MultipleUpdate
        $module = $this->getModule();
        $templateFile = $module . 'MultipleUpdate.tpl.php';
        if (!Template::findfile($templateFile)) {
            $templateFile = LIGHT_DIR_LIGHT . '/templates/module/multipleupdate/auto.tpl.php';
        }

        return Template::parseTpl($templateFile, $tdata);
    }

    protected function muDataFromPost(Msg $msgBuff): array
    {
        //Дополнительно, отфильтруем только теми полями, которые были на форме отмечены как редактируемые
        //На форме были скрытые поля со статусами 1 или 0 - все они оформлены виде массива multipleupdate
        $changeableFieldKeys = [];
        foreach ($_POST['multipleupdate'] as $fieldKey => $value) {
            if ($value === 'showed') {
                $changeableFieldKeys[] = $fieldKey;
            }
        }

        if (!$changeableFieldKeys) {
            // Ни одно поле в диалоговом окне не было отмечено для изменения
            return [];
        }

        /** @var Fieldset $fieldset */
        $muFieldset = $this->muFieldset();

        // Оставим только те поля, которые указаны в $changeableFieldKeys
        $muFieldset = $muFieldset->filterByKeys($changeableFieldKeys);

        // Соберём массив с данными по полям
        $requestData = [];
        foreach ($changeableFieldKeys as $fieldKey) {
            if (isset($_POST[$fieldKey])) {
                $requestData[$fieldKey] = $_POST[$fieldKey];
            }
        }

        //Обработаем данные с помощью функций определённый в объектах полей
        return $muFieldset->getDataFromRequest($requestData);
    }

    /**
     * Выполняет обновление еденичной записи из режима MultipleUpdate
     * Функция удобна для переопределения в классах потмоках
     * @param ObjAbstract $obj
     * @param array $dataFromPost
     * @param Msg $msgBuff
     * @return bool
     * @throws \Exception
     */
    protected function muUpdateObj(ObjAbstract $obj, array $dataFromPost, Msg $msgBuff)
    {
        $fieldsData = $obj->filterDataByModeSubmit($dataFromPost);

        $fieldsData = $obj->filterDataByEditAccessFunction($fieldsData);
        if (!$fieldsData) {
            return 0;
        }

        $oldData = $this->getOldDataBeforeSubmitOrDelete($obj);
        $dbLogMsg = Config::$lang === 'ru' ? 'Массовое редактирование' : 'Multiple Update';
        $affectedRows = $obj->updateDb($fieldsData, $dbLogMsg);
        $this->afterUpdate($obj, $dataFromPost, $affectedRows, $oldData, $msgBuff, true);
        $obj->afterSubmit($oldData, $msgBuff);
        $this->afterSubmit($obj, $dataFromPost, $affectedRows, $oldData, $msgBuff, true);
        return $affectedRows;
    }

    /**
     * Метод для переопределения в классах потомках
     * @param array $updatedObjects - массив из объектов
     * @param array $dataFromPost - массив данных из диалогового окна MultipleUpdate
     * @param Msg $msgBuff
     */
    protected function muAfterSubmit(array $updatedObjects, array $dataFromPost, Msg $msgBuff)
    {
    }
}
