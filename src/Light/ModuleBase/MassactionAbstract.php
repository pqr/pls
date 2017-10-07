<?php
/**
 * Created by PhpStorm.
 * User: petr
 * Date: 17.08.17
 * Time: 13:17
 */

namespace Light\ModuleBase;


use Light\Response;

abstract class MassactionAbstract
{
    /**
     * @param array $ids
     * @return mixed
     * @internal param string $action иногда один класс отвечает сразу за несколько massaction, этот параметр помогает
     */
    abstract public function __invoke(array $ids);

    public static function getHtmlHiddenCheckedIds(array $ids)
    {
        return '<input type="hidden" name="CheckedIds" value="' . implode(',', $ids) . '">';
    }

    public static function getCheckedIdsFromPost(): array
    {
        if (!isset($_POST['CheckedIds'])) {
            return [];
        }

        $ids = explode(',', $_POST['CheckedIds']);
        // Преобразуем все элементы массива $_REQUEST['cb'] в числа
        $ids = array_map('intval', $ids);
        // Оставим только уникальные ID
        $ids = array_unique($ids);
        // Удалим нулевой элемент, если он есть
        $ids = array_filter($ids);

        return $ids;
    }

    public static function getHtmlErrorsList(array $errors): string
    {
        if (count($errors) > 1) {
            return '<ol><li>' . implode('</li><li>', $errors) . '</li></ol>';
        } else {
            return $errors[0];
        }
    }

    public static function sendSuccessDialog(string $bodyHtml, $reloadTable = true)
    {
        //Обновим таблицу, отправив специальный заголовок, который на стороне клиента вызовет перезагрузку
        //Перезагрузка таблицы будет происходить в фоновом режиме, пока пользователь читает диалоговое окно о результатах
        if ($reloadTable) {
            Response::sendHeaderJsEval('Light.tables.get(Light.getModule()).reload();');
        }
        Response::showDialog($bodyHtml);
    }

    /**
     * @param string|array $errors
     */
    public static function sendErrorDialog($errors)
    {
        if (is_array($errors)) {
            $bodyHtml = self::getHtmlErrorsList($errors);
        } else {
            $bodyHtml = $errors;
        }
        Response::showDialog($bodyHtml, null, null, 'Close');
    }
}