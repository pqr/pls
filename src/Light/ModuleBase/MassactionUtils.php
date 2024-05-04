<?php


namespace Light\ModuleBase;


use Exception;
use Light\ApplicationAbstract;
use Light\Config;
use Light\Field\FieldAbstract as F;
use Light\Response;

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
                'editWidth' => 'medium',
            ])->getHtmlInput('')
            . '</div>'
            . '</div>';
    }

    /**
     * @param array|string $errors
     * @return string
     */
    public static function getHtmlErrorsList($errors): string
    {
        if (is_array($errors)) {
            if (count($errors) > 1) {
                return '<ol><li>' . implode('</li><li>', $errors) . '</li></ol>';
            }

            return reset($errors);
        }

        return (string)$errors;
    }

    public static function sendSuccessDialog(string $bodyHtml, $reloadTable = true): void
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
     * @param bool $isBlank
     * @throws Exception
     */
    public static function sendErrorDialog($errors, $isBlank = false): void
    {
        $bodyHtml = self::getHtmlErrorsList($errors);
        if (!$isBlank) {
            Response::showDialog($bodyHtml);
        } else {
            print $bodyHtml;
            ApplicationAbstract::terminate();
        }
    }

    /**
     * @param string|array $errors
     * @param $ids
     */
    public static function sendWarningDialog($errors, array $ids): void
    {
        $bodyHtml = self::getHtmlErrorsList($errors);
        $bodyHtml .= '<form action="' . $_SERVER['REQUEST_URI'] . '&warning=1#ajax" method="post">'
            . self::getHtmlHiddenCheckedIds($ids)
            . '</form>';
        Response::showDialog($bodyHtml, 'Продолжить');
    }
}
