<?php
namespace Light\Traits;

use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Request;
use Light\Response;
use Light\Template;
use Light\User;

trait TableLayoutable
{
    protected $_tableLayouts = [];
    protected $_tableLayoutsEnabled = true;

    private static $isLayoutDailogPrinted = false;

    abstract public function setTableFieldsShow($fieldsKeysString);

    abstract protected function setTableUserParam($name, $value);

    abstract protected function getTableUserParam($name);

    /** @return Fieldset|F[] */
    abstract public function getFieldset();

    /** @return Fieldset|F[] */
    abstract public function getFieldsetShowed();

    abstract public function getTableName();

    protected function initLayoutable()
    {
        if ($this->_tableLayoutsEnabled) {

            //Проверим, возможно вместе с запросом отобразить страницу пришел новый Layout в параметре fields
            //Затем пробуем установить поля из настроек пользователя из базы
            //Затем Default Layout
            //Если и его нет, тогда отображаем все поля таблицы
            if (isset($_REQUEST['fieldsShow'])) {

                $this->setTableFieldsShow($_REQUEST['fieldsShow']);
                $this->setTableUserParam('FieldsShow', $_REQUEST['fieldsShow']);

            } elseif ($this->getTableUserParam('FieldsShow') !== null) {

                $this->setTableFieldsShow($this->getTableUserParam('FieldsShow'));

            } elseif (!$this->setLayout('Default')) {

                $this->setFieldsShowAll();

            }

            //Добавим вёрстку диалога в html код старницы
            //Инициализируем диалог
            if (!self::$isLayoutDailogPrinted && !Request::isAjax() && !Request::isXls()) {
                Response::addHtml(Template::parseTpl(LIGHT_DIR_LIGHT .'/templates/table/layoutdialog.tpl.php'));
                self::$isLayoutDailogPrinted = true;
            }
        }
    }

    public function isLayoutEnabled()
    {
        return $this->_tableLayoutsEnabled;
    }

    protected function setLayout($layoutName)
    {
        if ($fieldsKeysString = $this->getLayoutFieldsKeys($layoutName)) {
            $this->setTableFieldsShow($fieldsKeysString);
            return true;
        }

        return false;
    }

    /**
     * Переключает текущие настройки FieldsShow в отображение ВСЕХ полей
     */
    protected function setFieldsShowAll()
    {
        $this->setTableFieldsShow(null);
    }

    private function getLayoutFieldsKeys($layoutName)
    {
        //Проверим, возможно такой layout есть среди стандартных в массиве $this->_layouts?
        if (isset($this->_tableLayouts[$layoutName])) {
            $fieldsKeysString = $this->_tableLayouts[$layoutName];
        } else {
            //Попробуем загрузить из user_tablelayout
            $query = "SELECT FieldsShow
                      FROM user_tablelayout
                      WHERE CreatedBy = " . User::getId() . "
                          AND LayoutName = '" . Db::esc($layoutName) . "'
                          AND TableName = '" . Db::esc($this->getTableNameForSettings()) . "'";
            $fieldsKeysString = Db::getOne($query);
        }

        return $fieldsKeysString;
    }

    protected function getHtmlTableHeadLayoutSettingsLink()
    {
        if ($this->isLayoutEnabled()) {
            return '<span><a href="#" class="table-layoutsettings"><i class="fa fa-cog icon-tablelayoutsettings"></i> Table layout settings</a></span>';
        }

        return '';
    }

    protected function showTableLayoutSettingsDialog()
    {
        $fieldset = $this->getFieldset();
        $shownFieldKeys = $this->getTableFieldsShow() ?? $fieldset->getKeys();

        $allFields = [];
        $fieldNames = [];
        foreach ($fieldset->getFieldsArr() as $field) {
            if ($field->modes & F::MODE_LIST) {
                $name = $field->getNameTableSettings();
                $allFields[] = [
                    'key' => $field->key,
                    'name' => $name,
                    'title' => $field->title
                ];
                $fieldNames[] = $name;
            }
        }

        // Сортируем массив по полю name
        array_multisort($fieldNames, $allFields);

        $layouts = [];
        foreach ($this->_tableLayouts as $name => $layoutFields) {
            $layouts[] = ['name' => $name, 'fieldKeys' => explode('|', $layoutFields)];
        }

        Response::json([
            'allFields' => $allFields,
            'shownFieldKeys' => $shownFieldKeys,
            'layouts' => $layouts,
            'userLayoutsHtml' => self::getHtmlTableUserLayouts($this->getTableNameForSettings())
        ]);
    }

    public static function getHtmlTableUserLayouts(string $tableName)
    {
        $layouts = Db::getAll(
            "SELECT ID, LayoutName, FieldsShow
             FROM user_tablelayout
             WHERE TableName = '" . Db::esc($tableName) . "'
                 AND CreatedBy = " . User::getId()
        );

        $outHtml = '';

        if ($layouts) {
            $outHtml .= '<table>';
            foreach ($layouts as $l) {
                $link = '<a href="#' . htmlspecialchars($l['FieldsShow']) . '">' . htmlspecialchars($l['LayoutName']) . '</a>';
                $delLink = '<i class="fa fa-trash icon-delete" data-delid="' . $l['ID'] . '"></i>';
                $outHtml .= "<tr><td>$link</td><td>$delLink</td></tr>";
            }
            $outHtml .= '</table>';
        }

        return $outHtml;
    }
}
