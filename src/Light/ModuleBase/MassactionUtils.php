<?php


namespace Light\ModuleBase;


use Light\Config;
use Light\Response;
use Light\Field\FieldAbstract as F;

class MassactionUtils
{
    public static function getHtmlHiddenCheckedIds(array $ids)
    {
        return '<input type="hidden" name="CheckedIds" value="' . implode(',', $ids) . '">';
    }

    public static function getHtmlSelectMassactions(array $massactions)
    {
        $textWithSelected = Config::$lang === 'ru' ? 'С выделенными' : 'With selected';
        $textSelectAll = Config::$lang === 'ru' ? 'Выделить все' : 'Select All';
        $textSelectNone = Config::$lang === 'ru' ? 'Снять выделение' : 'Select None';
        return '<div class="listmassaction">'
            . ' <div class="listmassaction__selectlinks">'
            . '<a href="#" class="listmassaction__selectall">' . $textSelectAll . '</a>'
            . ' / '
            . '<a href="#" class="listmassaction__selectnone">' . $textSelectNone . '</a>'
            . '</div>'
            . '<div class="listmassaction__content">'
            . '<label for="massaction-select">' . $textWithSelected . '<span></span>:</label>&nbsp;'
            . F::createSelect('massaction-select', [
                'options' => $massactions,
                'editWidth' => 'medium'
            ])->getHtmlInput('')
            . '</div>'
            . '</div>';
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
            Response::sendHeaderJsEval('Light.table.get(Light.getModule()).reload();');
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

    /**
     * @param string|array $errors
     */
    public static function sendWarningDialog($errors, $ids)
    {
        if (is_array($errors)) {
            $bodyHtml = self::getHtmlErrorsList($errors);
        } else {
            $bodyHtml = $errors;
        }
        $bodyHtml .= '<form action="' . $_SERVER['REQUEST_URI'] . '&warning=1#ajax" method="post">'
            . self::getHtmlHiddenCheckedIds($ids)
            . '</form>';
        Response::showDialog($bodyHtml, 'Продолжить', null, 'Close');
    }
}
