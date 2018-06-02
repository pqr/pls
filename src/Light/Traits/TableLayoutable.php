<?php

namespace Light\Traits;

use Light\Config;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Response;
use Light\Table\TableUserParamsRepository;
use Light\Template;
use Light\User;

trait TableLayoutable
{
    protected $tableLayouts = [];
    protected $allowTableLayouts = true;

    private static $isLayoutDailogPrinted = false;

    abstract public function setTableFieldsShow($fieldsKeysString);


    /** @return Fieldset|F[] */
    abstract public function getFieldset();

    /** @return Fieldset|F[] */
    abstract public function getFieldsetShowed();

    abstract public function getTableName();

    protected function initLayoutable()
    {
        if (!$this->allowTableLayouts) {
            return;
        }

        /** @var TableUserParamsRepository $tableUserParams */
        // Отдельный объект, которых заведует сохранением настроек в базу для данного пользователя
        $tableUserParams = $this->tableUserParams;

        $requestKey = $this->getTableName() . '_fieldsShow';

        if (isset($_REQUEST[$requestKey])) {

            // От браузера пришли какие-то новые настройки для таблицы - используем их!
            $newFieldsShowFromRequest = $_REQUEST[$requestKey];
            // Применим пришедшие настроки к текущей таблице
            $this->setTableFieldsShow($newFieldsShowFromRequest);
            // Также сохраним эти настройки в настройки пользователя
            $tableUserParams->saveFieldsShow($newFieldsShowFromRequest);
            return;

        }

        $userParamsFieldsShow = $tableUserParams->loadFieldsShow();
        if ($userParamsFieldsShow) {

            // У пользователя есть какие-то собственные настройки колонок в таблице, применим их
            $this->setTableFieldsShow($userParamsFieldsShow);
            return;

        }

        // У пользователя нет собственных настроек колонок в таблице, попробуем применить Default раскладку
        if (isset($this->tableLayouts['Default'])) {
            // Для данной таблицы задана некая Default раскладка, именно её и используем
            $this->setTableFieldsShow($this->tableLayouts['Default']);
            return;
        }

        // Для данной таблицы нет даже Default раскладки, отображаем все поля подряд
        $this->setFieldsShowAll();

    }

    public function isLayoutEnabled()
    {
        return $this->allowTableLayouts;
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
        if (isset($this->tableLayouts[$layoutName])) {
            $fieldsKeysString = $this->tableLayouts[$layoutName];
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
        if ($this->allowTableLayouts) {
            $linkText = Config::$lang === 'ru' ? 'Настройка колонок' : 'Table layout settings';
            return '<span><a href="#" class="table-layoutsettings"><i class="fa fa-cog icon-tablelayoutsettings"></i> ' . $linkText . '</a></span>';
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
        foreach ($this->tableLayouts as $name => $layoutFields) {
            $layouts[] = ['name' => $name, 'fieldKeys' => explode('|', $layoutFields)];
        }

        $userLayouts = self::getHtmlTableUserLayouts($this->getTableNameForSettings());

        Response::json([
            'allFields' => $allFields,
            'shownFieldKeys' => $shownFieldKeys,
            'layouts' => $layouts,
            'bodyHtml' => Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/table/layoutdialog.tpl.php',
                ['userLayouts' => $userLayouts])
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
