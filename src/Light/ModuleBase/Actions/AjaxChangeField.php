<?php


namespace Light\ModuleBase\Actions;


use Light\Db;
use Light\Field\FieldColor;
use Light\Request;
use Light\User;

/**
 * От этого класса можно отнаследоваться и положить класс наследник в Module/<имя модуля>/Actions/AjaxChangeField.php
 */
class AjaxChangeField extends ActionAbstract
{
    public $onlyAjax = true;

    #[\Override]
    public function hasAccess(): bool
    {
        $fieldKey = $this->getFieldKey();
        if (!$fieldKey) {
            return false;
        }

        return $this->hasAccessAjaxChangeField($fieldKey);
    }

    /**
     * Всю логику переносим в специальный публичный метод hasAccessAjaxChangeField(string $fieldKey)
     * который явным образом принимает fieldKey, это позволяет:
     * 1. вызывать этот метод не имея конкретного request, а просто для проверки определённого поля fieldKey
     * 2. можно полностью переопределить логику проверики прав для конкретных полей в классе потомке
     * например, разрешить редактировать поле, даже если у пользователя нет доступа на редактирование всего объекта
     *
     * @return bool
     */
    public function hasAccessAjaxChangeField(string $fieldKey): bool
    {
        if (!User::hasWriteAccess()) {
            return false;
        }

        if (!$this->obj->accessWrite()) {
            return false;
        }

        $field = $this->obj->getField($fieldKey);
        if (!$field) {
            return false;
        }

        if (!$field->ajax && !($field instanceof FieldColor)) {
            return false;
        }

        if (!$this->obj->hasFieldModeSubmit($field->key)) {
            return false;
        }

        return true;
    }

    protected function getFieldKey(): string
    {
        return Request::getQueryParam('fieldKey');
    }

    #[\Override]
    public function __invoke()
    {
        if (Request::isPost()) {
            return $this->proceedSubmit();
        }

        return $this->showInput();
    }

    protected function showInput(): string
    {
        $fieldKey = $this->getFieldKey();
        $value = $this->obj[$fieldKey];
        $data = $this->obj->getData();
        // Выводим в ajax ответ HTML код для поля ввода
        return $this->obj->getField($fieldKey)->getHtmlInput($value, $data);
    }

    /**
     * Метод вызывается через POST ajax запросы на url ?a=<module>&id=123&func=changeField&fieldKey=...&newValue=...
     * когда пользователь отправляет на сервер значение какого-то ajax поля
     */
    protected function proceedSubmit(): string
    {
        $fieldKey = $this->getFieldKey();
        $value = $_REQUEST['newValue'];
        $data = [$fieldKey => $value];
        $field = $this->obj->getField($fieldKey);

        $normilizedValue = $field->getDataFromRequest($data);

        if ($normilizedValue === null && !$field->nullable) {
            $errMsg = "Invalid value $value";
        } else {
            $errMsg = $field->validate([$fieldKey => $normilizedValue], $this->obj->getData());
        }

        if ($errMsg !== null) {
            $this->msgBuff->addError($errMsg);
            return $this->obj->getFieldHtml($fieldKey);
        }

        Db::begin();

        $updateData = [$fieldKey => $normilizedValue];

        if ($field->groupconcatSpecialAjaxSubmitFunction && is_callable($field->groupconcatSpecialAjaxSubmitFunction)) {
            $specialAjaxSubmitFunction = $field->groupconcatSpecialAjaxSubmitFunction;
            $specialAjaxSubmitFunction($this->obj, $normilizedValue);
        } else {
            $this->obj->updateDb($updateData, 'Ajax');
        }

        // Вызовем дополнительный обработчик, который, возможно, переопределён в классах потомках
        $this->obj->afterSubmit($this->msgBuff);

        Db::commit();

        return $this->obj->getFieldHtml($fieldKey);
    }
}
