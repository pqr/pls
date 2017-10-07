<?php
/**
 * Created by PhpStorm.
 * User: petr
 * Date: 17.08.17
 * Time: 15:06
 */

namespace Light\ModuleBase;


use Light\ApplicationAbstract;
use Light\Field\FieldAbstract as F;
use Light\Img;
use Light\ObjAbstract;
use Light\PrintForm;
use Light\Request;
use Light\Response;
use Light\Template;

class MassactionPrint
{
    /**
     * Выводи диалоговое окно где пользователь должен выбрать одну из доступных печатных форм
     * Алогритм проходит по всем отмеченным объектам и находит такое подмножество форм, которое удовлетворяет всем
     * @param array $ids
     * @param ObjAbstract $objBase
     * @param array $printForms
     */
    public static function showSelectPrintFromDialog(array $ids, ObjAbstract $objBase, array $printForms)
    {
        // Загрузим все объекты по выделенным галочками и на их основе определим набор печатных форм, которые удовлетворяют
        // всем объектам
        // Скопируем массив всех печатных форм, чтобы поочерёдно удалять печатные формы, которые не подходят
        foreach ($ids as $id) {
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
                if (!$printForm->testForObj($obj)) {
                    // Текущая печатная форма не подходит данному объету - убираем её из массива
                    unset($printForms[$key]);
                }
            }

            if (!$printForms) {
                // Если в процессе проверки все печатные формы были удалены из массива - прерываем цикл проверки
                break;
            }
        }

        if (!$printForms) {
            MassactionAbstract::sendErrorDialog('Нет печатной формы подходящей для всех выделенных строк');
            return;
        }

        // Выведем диалог с выбором печатной формы
        // Вместо ссылок создаём маленькие формы, чтобы передать параметр CheckedIds методом POST
        $html = '';
        $postDataAttr = ' data-post="' . htmlspecialchars(json_encode(['CheckedIds' => implode(',', $ids)])) . '"';
        foreach ($printForms as $key => $printForm) {

            $module = Request::getModule();
            $form = $printForm->getKey();

            $hash = $printForm->getFieldset()->count() ? '#ajax-post' : '#post';
            $url = "?a=$module&p=massaction&action=Print&form={$form}{$hash}";
            $target = $printForm->isLinkBlank() ? ' target="blank"' : '';
            $icon = $printForm->getFormat() == PrintForm::FORMAT_EXCEL
                ? Img::iconExcel()
                : Img::iconPrint();
            $title = $printForm->getTitle();

            $html .= '<p>'
                . '<a href="' . $url . '" ' . $target . $postDataAttr . ' class="btn-flat">'
                . $icon . '&nbsp;' . $title
                . '</a>'
                . '</p>';

            /*
            $ajax = $printForm->getFieldset()->count() ? '#ajax' : '';
            $formAction = "?a=$module&p=massaction&action=Print&form={$form}{$ajax}";

            $target = $printForm->isLinkBlank() ? ' target="blank"' : '';
            $icon = $printForm->getFormat() == PrintForm::FORMAT_EXCEL
                ? Img::iconExcel()
                : Img::iconPrint();

            $html .= '<p><form action="' . $formAction . '" method="post"' . $target . '>'
                . Template::csrfHidden()
                . '<input type="hidden" name="CheckedIds" value="' . implode(',', $ids) . '">'
                . '<button type="submit" class="massactionprintdialog__button btn-flat">' . $icon . '&nbsp;' . $printForm->getTitle() . '</button>'
                . '</form></p>';
            */

        }

        Response::showDialog($html);
    }

    /**
     * Метод вызывается после того, как пользователь нажал на конкретную кнопку печатной формы в диалоге выбора печатных
     * форм. Внутри этого метода мы либо запускаем массовую печать отмеченных галочками обёектов,
     * либо выводим дополнительный диалог с настройками формы (если у печатной формы определён fieldset)
     *
     * @param array $ids
     * @param PrintForm $printForm
     * @param ObjAbstract $objBase
     * @param callable $getFolderRelativePath
     * @throws \Exception
     */
    public static function proceedMultiplePrint(
        array $ids,
        PrintForm $printForm,
        ObjAbstract $objBase,
        callable $getFolderRelativePath
    )
    {
        /** @var PrintForm $printForm */
        $objectsAndPaths = [];

        foreach ($ids as $id) {
            $obj = clone $objBase;
            $obj->setId($id);
            // Попробуем загрузить объект и ещё раз на всякий случай проверим, подходит ли он для данной формы?
            // (за то время, пока пользователь выбирал фомру в диалоге, сами объекты могли измениться)
            if ($obj->loadData() && $printForm->testForObj($obj)) {
                $filesFolderRelativePath = $getFolderRelativePath($obj->getData());
                $objectsAndPaths[] = [$obj, $filesFolderRelativePath];
            }
        }

        if (!$objectsAndPaths) {
            // Все переданные в ids объекты либо уже не существуют, либо уже не подходят для печати в выбанной печатной форме
            MassactionAbstract::sendErrorDialog('Выбранные строки либо удалены, либо не подходят для печатной формы ' . $printForm->getTitle());
            return;
        }

        if ($printForm->getFieldset()->count() && !isset($_GET['fromDialog'])) {
            // Для данной формы задан какой-то fieldset, значит нужно вывести дополнительный диалог
            self::showFieldsetDialog($ids, $objectsAndPaths, $printForm);
            return;
        }

        // Похоже у печатной формы не было никакого fieldset, значит запускаем генерацию pdf/doc или html файлов
        if (!Request::isPost()) {
            throw new \Exception('HTTP method for print forms should be POST (' . $printForm->getKey() . ')');
        }
        $printForm->proceedPrintMultiple($objectsAndPaths);
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
     * @param array $objectsAndPaths
     * @param PrintForm $printForm
     */
    private static function showFieldsetDialog(array $ids, array $objectsAndPaths, PrintForm $printForm)
    {
        $module = Request::getModule();
        $form = $printForm->getKey();
        $formAction = "?a={$module}&p=massaction&action=Print&form=$form&fromDialog=1";

        $target = $printForm->isLinkBlank() ? ' target="blank"' : '';
        $html = '<form action="' . $formAction . '" method="post"' . $target . '>'
            . '<input type="hidden" name="CheckedIds" value="' . implode(",", $ids) . '">'
            . Template::csrfHidden();

        $data = $printForm->getDataForAjaxDialogMultiple($ids, $objectsAndPaths);
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
}