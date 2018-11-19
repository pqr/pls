<?php

namespace Light\SubtableBase;

use Light\ApplicationAbstract;
use Light\Css;
use Light\Db;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Img;
use Light\Js;
use Light\ObjAbstract;
use Light\Request;
use Light\Response;
use Light\SimpleObj;
use Light\Template;
use Light\Traits\FieldsetAwareTrait;
use Light\Traits\TableWithFoldersTrait;

abstract class Subtable2
{
    use FieldsetAwareTrait, TableWithFoldersTrait;

    const STATE_VIEW = 'view';
    const STATE_EDIT = 'edit';
    const STATE_SUBMIT = 'submit';

    protected $_tableMsgIfEmpty;

    /** @var ObjAbstract */
    protected $_obj;

    /** @var string имя css-класса, которое будет добавлено к div контейнеру содержащему подтаблицу */
    protected $_cssClass;

    /** @var ObjAbstract */
    protected $_masterObj;

    /**
     * Название подтаблицы (для подзаголовков и пр.)
     * @var string
     */
    protected $_title = '';

    /**
     * Ассоциативный массив с данными по всем строкам в формате ID => [данные строки]
     * Ключи $this->_data - это ID элементов в базе
     * @var array
     */
    protected $_data;

    /**
     * Дополнительные данные, которые ещё не существую в базе, которые пользователь класса хочет добавить динамически
     * @var array
     */
    protected $_additionalData;

    /**
     * @var array поля из списка $_groupconcatFieldKeys будут добавлены в мастер-модуль для режима List, причём
     * их значения будут вычисляться на лету с помощью GROUP_CONCAT подзапроса
     */
    protected $_groupconcatFieldKeys = [];
    /**
     * @var array поля из списка $_cacheFieldsKeys будут добавлены в мастер-модуль для режима List, их значения будут
     * браться из базы из полей с такими же ключами с префиксом Cache.
     * При сохранении подтаблицы эти поля в мастер-объекте в базе будут автоматически обновлены
     */
    protected $_cacheFieldsKeys = [];

    /**
     * @var bool если флаг установлен в true, то будут кешироваться только уникальные значения, например, номера контейнеров в счетах
     */
    protected $_cacheOnlyUniqueValues = false;
    /**
     * @var string префикс, который добавлять в параметр name у поля при выводе в режиме таблицы в мастер-модуле
     * Этот же префикс используется для полей из списка $_groupconcatFieldKeys
     */
    protected $_cacheFieldsPrefix = '';

    /**
     * Системные поля, которые должны быть в начале таблицы, не выводятся в Excel
     * @var Fieldset|\Light\Field\FieldAbstract[]
     */
    private $fieldsetFirst;

    //Сортировка по умолчанию
    protected $_orderBy = 'ID';

    //Настройки режима редактирования (Subtable::STATE_EDIT)
    protected $allowAdd = true;
    protected $allowDelete = true;
    protected $textAddLink = 'Add Line';

    /**
     * Обычно при двойном клике по строке мы проваливаемся куда-то внутрь объекта. Возможно, в этом объекте есть подтаблица
     * со связанными объектами их которых мы могли провалиться. Здесь можно указать имя этой подтаблицы и тогда ко ссылкам
     * (в том числе при двойном клике) будет добавлен параметр <имяподтаблицы>_findid=<$this->_masterObj->getId()>
     */
    protected $_tableLinkFindidSubtableName;

    public function __construct(ObjAbstract $masterObj)
    {
        //Инициализируем внутреннюю переменную _obj хитрым способом -  возможно, на основе файла в этой же папке
        $this->initObj();

        $this->_masterObj = $masterObj;

        $masterModule = $this->getMasterModule();
        if ($masterModule) {
            $this->_obj->setMasterModuleForDbLog($masterModule);
        }

    }

    /**
     * @return Fieldset|F[]
     */
    public function getFieldsetFirst()
    {
        if (!$this->fieldsetFirst) {
            // Лениво инициализируем при первом обращении
            $this->fieldsetFirst = new Fieldset();
        }
        return $this->fieldsetFirst;
    }

    protected function initObj()
    {
        //Если объект не задан явно, попробуем найти соответсвующий класс рядом с текущим файлом модуля
        $objClass = get_class($this) . 'Obj';
        $filepath = LIGHT_DIR_ROOT . '/src/' . str_replace('\\', '/', $objClass) . '.php';
        if (file_exists($filepath)) {
            //Файл найден, создаём объект именно этого класса
            $this->_obj = new $objClass();
        } else {
            //Отдельного файла для класса найдено не было, создадим на основе обычного Obj
            $this->_obj = new SimpleObj();
            $this->_obj->setDbTable(strtolower($this->getTableName()));
        }
    }

    /**
     * Вычисляет модуль к которому относится эта подтаблица на основе неймспейсов
     */
    protected function getMasterModule()
    {
        //Массив неймспейсов будет выглять так: <ProjectNamespace>\Module\<ModuleNamespace>\Subtables\ClassName
        $namespaces = explode('\\', get_class($this));
        //Имя модуля таким образом находится в элементе с индексом 2
        if (isset($namespaces[2])) {
            return $namespaces[2];
        }

        return '';
    }

    /**
     * Метод пригождается при копировании в ModuleAbstract
     * @param ObjAbstract $masterObj
     */
    public function setMasterObj(ObjAbstract $masterObj)
    {
        $this->_masterObj = $masterObj;
    }

    /** @var string кеширующая переменная для метода getTableName */
    private $_subtableName;

    public function getTableName()
    {
        if ($this->_subtableName === null) {
            $parts = explode('\\', get_class($this));
            $this->_subtableName = end($parts);
        }
        return $this->_subtableName;
    }

    public function setTableName($name)
    {
        $this->_subtableName = $name;
    }

    /**
     * Добавляет к изначальным данным некоторые дополнительные, которые будут выведены на экран
     * @param array $data
     * @return void
     */
    public function setAdditionalData($data)
    {
        $this->_additionalData = $data;
    }

    /**
     * Возвращает поля объявленные в этой подтаблице как кеширующие
     * К ключам полей добавляется префикс Cache<имя класса подтаблицы без слова Subtable>
     * К именам полей добавляет префикс $this->_cacheFieldsPrefix
     * @return array
     */
    public function getFieldsForMasterModuleList()
    {
        $fieldsForMasterModuleList = [];

        //Существует два массива с ключами полей, которые нужно добавить в мастер-модуль
        $arrays = ['Groupconcat' => $this->_groupconcatFieldKeys, 'Cache' => $this->_cacheFieldsKeys];
        foreach ($arrays as $type => $fieldKeys) {
            foreach ($fieldKeys as $fieldKey) {
                if ($this->hasField($fieldKey)) {

                    $field = $this->getField($fieldKey);
                    //Новый ключ будет начинаться либо с Cache, либо с Groupconcat (переменная $fieldsType)
                    $newKey = $type . $this->getTableName() . $field->key;

                    /** @var $newField \Light\Field\FieldAbstract */
                    $newField = $field->getClone($newKey);

                    //Ко всем кеширующим полям в отображаемые имена добавим префикс, чтобы пользователю на экране было понятно, что эти данные из подтбалицы
                    $newField->addNamePrefix($this->_cacheFieldsPrefix);
                    //Кеширующие поля выводятся только в режиме таблицы
                    $newField->setModes(F::MODE_LIST);
                    //Обязательно нужно наложить параметр groupconcat
                    $newField->groupconcat = true;
                    $newField->groupconcatTruncate = true;
                    //Обязательно изменим ключ по которому будет сортировка в SQL запросе
                    //TODO: нужен новый параметр groupConcatOrderBy - если он определён, то использовать его
                    $newField->tableOrderBy = $newKey;

                    //Если кеширующее поле === null, то выведем красный прочерк
                    /** @var $originalGetHtmlFunction string */
                    $originalGetHtmlFunction = $newField->getHtmlFunction;
                    $newField->setParam(
                        'getHtmlFunction',
                        function ($value, $data, $outHtml, $forTable) use ($originalGetHtmlFunction, $newField) {
                            if ($value === null) {
                                $outHtml = '&mdash;';
                            } elseif ($originalGetHtmlFunction && is_callable($originalGetHtmlFunction)) {
                                $outHtml = $originalGetHtmlFunction($value, $data, $outHtml, $forTable, $newField);
                            }

                            return $outHtml;

                        }
                    );

                    /** @var $originalViewCssClass string */
                    $originalViewCssClass = $newField->viewCssClass;
                    $newField->setParam(
                        'viewCssClass',
                        function ($value, $data, $forTable) use ($originalViewCssClass) {
                            if ($value === null) {
                                return 'bad';
                            } elseif ($originalViewCssClass && is_callable($originalViewCssClass)) {
                                return $originalViewCssClass($value, $data, $forTable);
                            }

                            return $originalViewCssClass;
                        }
                    );

                    //Для Groupconcat полей нужно обязательно добавить sqlSelect параметр!
                    if ($type === 'Groupconcat') {
                        $sqlSelect = "SELECT GROUP_CONCAT(";
                        if ($this->_cacheOnlyUniqueValues) {
                            $sqlSelect .= "DISTINCT ";
                        }
                        $sqlSelect .= $fieldKey . " ORDER BY ID SEPARATOR '\\n')";
                        $sqlSelect .= " FROM " . $this->getDbTable();
                        $sqlSelect .= " WHERE " . $this->getMasterFieldKey() . "
                                              = " . $this->_masterObj->getDbTable() . ".ID";

                        $newField->setParam('sqlSelect', $sqlSelect);
                    }

                    $fieldsForMasterModuleList[] = $newField;
                }
            }
        }

        return $fieldsForMasterModuleList;
    }

    private $_masterFieldKey;

    /**
     * @return string внешний ключ связывающий с мастер-объектом
     */
    protected function getMasterFieldKey()
    {
        if ($this->_masterFieldKey === null) {
            //<projectNamespace>\Module\<moduleNamespace>\Sutables\<subtableClass>
            $class = get_class($this);
            $class = explode('\\', $class);
            $masterModule = $class[2];
            $this->_masterFieldKey = $masterModule . 'ID';
        }

        return $this->_masterFieldKey;
    }

    protected function getMasterFieldValue()
    {
        return $this->_masterObj->getId();
    }

    /**
     * @desc возвращает запрос к базе на выборку данных
     * @return string $query
     */
    protected function getDataQuery()
    {
        $query = "SELECT " . $this->getFieldset()->getSqlSelect() . "
                  FROM " . Db::quote($this->getDbTable()) . "
                  WHERE ";

        if ($this->_masterObj->getId()) {
            $query .= Db::quote($this->getMasterFieldKey()) . " = '" . Db::esc($this->getMasterFieldValue()) . "'";
        } else {
            $query .= "0";
        }

        if ($this->_orderBy !== '') {
            $query .= " ORDER BY " . $this->_orderBy;
        }

        return $query;
    }

    public function loadData()
    {
        $this->_data = [];

        $query = $this->getDataQuery();
        $data = Db::getAll($query);
        foreach ($data as $row) {
            $id = $row['ID'];
            $this->_data[$id] = $row;
        }
    }

    public function getDataCount()
    {
        if ($this->_data === null) {
            $this->loadData();
        }
        $count = count($this->_data);
        if (is_array($this->_additionalData)) {
            $count += count($this->_additionalData);
        }
        return $count;
    }

    public function setData(array $data)
    {
        $this->_data = $data;
    }

    public function getData()
    {
        return $this->_data;
    }

    private function getFieldEditName($fieldKey, $rowNum)
    {
        return $this->getTableName() . 'Subtable2[' . $rowNum . '][' . $fieldKey . ']';
    }

    private $_blockTemplate;

    protected function getBlockTemplate()
    {
        if ($this->_blockTemplate === null) {
            $subtableClass = get_class($this);
            $filepath = str_replace('\\', '/', $subtableClass) . '.tpl.php';
            if (file_exists(LIGHT_DIR_ROOT . "/src/$filepath")) {
                $this->_blockTemplate = $filepath;
            } else {
                throw new \Exception('No template file for subtable ' . $subtableClass);
            }
        }

        return $this->_blockTemplate;
    }

    public function getHtmlView()
    {
        Css::addLight('Subtable2.css');
        $this->loadData();

        $blockTemplate = $this->getBlockTemplate();
        $subtableName = $this->getTableName();

        $ajaxChangeFieldUrl = "?a=" . Request::getModule()
            . "&id=" . $this->_masterObj->getId()
            . "&subtable=" . htmlspecialchars($this->getTableName())
            . "&func=ChangeField&subid=";

        $outHtml = '<div data-ajaxchangefieldurl="' . $ajaxChangeFieldUrl . '">'
            . '<div class="subtable2-div" data-subtablename="' . htmlspecialchars($subtableName) . '">';
        if ($this->_data) {
            $rowNum = 0;
            $fieldset = $this->getFieldset();
            foreach ($this->_data as $blockData) {
                $tdata = $this->getTplData($fieldset, $blockData, F::MODE_VIEW, $rowNum);
                $tdata['_rowNum'] = $rowNum;
                $tdata['_state'] = 'view';
                $blockHtml = Template::parseTpl($blockTemplate, $tdata);
                $outHtml .= '<div class="subtable2-block">' . $blockHtml . '</div>';
                $rowNum++;
            }
        } else {
            $outHtml .= $this->_tableMsgIfEmpty;
        }

        $outHtml .= '</div></div>';
        return $outHtml;
    }

    public function getTplData(Fieldset $fieldset, array $blockData, $mode, $rowNum)
    {
        return $fieldset->getTplData($blockData, $mode);
    }

    public function getHtmlEdit()
    {
        Css::addLight('Subtable2.css');
        Js::addLight('Subtable2.js');
        $this->loadData();

        $allData = [];

        //Сформируем массив из ID строк, его нужно записать в input hidden name="<subtablename>[ids]"
        $ids = [];
        if (is_array($this->_data)) {
            $ids = array_keys($this->_data);
            $allData = array_values($this->_data);
        }
        //Если есть дополнительный массив с данными $this->_additionalData, нужно добавить соответсвующее кол-во нулей в $rowsIds
        if (is_array($this->_additionalData)) {
            $allData = array_merge($allData, $this->_additionalData);
            $countAdditional = count($this->_additionalData);
            for ($i = 0; $i < $countAdditional; $i++) {
                $ids[] = '0';
            }
        }

        //В режиме редактирования разложим default данные в отсутсвующие поля $data
        foreach ($allData as &$blockData) {
            $blockData = $this->getEditBlockDataWithDefaults($blockData);
        }
        //Уберём временную переменную - это переменная-ссылка, обязательно нужно убрать!
        unset($blockData);

        //Подготовим шаблон для добавления новых элементов
        if ($this->allowAdd) {
            $newBlockDefaultData = $this->getEditBlockDataWithDefaults([]);
            $newBlockHtmlTemplate = $this->getEditBlockHtml($newBlockDefaultData, '{NewBlockNum}');
        } else {
            $newBlockHtmlTemplate = '';
        }

        $fieldsJson = [];
        foreach ($this->getFieldset() as $field) {
            $fieldsJson[$field->key] = ['type' => $field->getType()];
        }
        $fieldsJson = json_encode($fieldsJson);

        $subtableName = $this->getTableName();
        $outHtml = '<div class="subtable2-div"'
            . ' data-fields="' . htmlspecialchars($fieldsJson) . '"'
            . ' data-subtablename="' . htmlspecialchars($subtableName) . '"'
            . ' data-blocktemplate="' . htmlspecialchars($newBlockHtmlTemplate) . '">';
        $rowNum = 0;
        foreach ($allData as $blockData) {
            $outHtml .= $this->getEditBlockHtml($blockData, $rowNum);
            $rowNum++;
        }

        $outHtml .= '<input type="hidden" name="' . $subtableName . 'Subtable2[ids]" id="' . $subtableName . '-subtable2-ids" value="' . implode(',',
                $ids) . '">';
        if ($this->allowDelete) {
            $outHtml .= '<input type="hidden" name="' . $subtableName . 'Subtable2[delrows]" id="' . $subtableName . '-subtable2-delrows">';
        }

        if ($this->allowAdd) {
            $outHtml .= '<p class="subtable2-add-p">'
                . '<a href="#" class="subtable2-add-link">' . Img::iconPlus() . ' ' . $this->textAddLink . '</a>'
                . '</p>';
        }
        $outHtml .= '</div>';

        return $outHtml;
    }

    /**
     * Возвращает массив данных основанный на $blockData, в котором все отсутсвующие поля заполнены default значениями
     * @param array $blockData
     * @return array
     */
    protected function getEditBlockDataWithDefaults(array $blockData)
    {
        foreach ($this->getFieldset() as $field) {
            if (!array_key_exists($field->key, $blockData)) {
                $blockData[$field->key] = $field->default;
            }
        }

        return $blockData;
    }

    protected function getEditBlockHtml(array $blockData, $rowNum)
    {
        $blockTemplate = $this->getBlockTemplate();
        $subtableName = $this->getTableName();
        $newFieldset = new Fieldset();
        foreach ($this->getFieldset() as $field) {
            $fieldKey = $field->key;
            $newField = clone $field;
            $newField->setParams([
                'editName' => $this->getFieldEditName($fieldKey, $rowNum),
                'editId' => $subtableName . '-' . $rowNum . '-' . $fieldKey
            ]);
            $newFieldset->addField($newField);
        }

        $tdata = $this->getTplData($newFieldset, $blockData, F::MODE_EDIT, $rowNum);
        $tdata['_rowNum'] = $rowNum;
        $tdata['_state'] = 'edit';
        $innerHtml = Template::parseTpl($blockTemplate, $tdata);

        $blockHtml = '<div class="subtable2-block" rowNum="' . $rowNum . '" id="' . $subtableName . '-subtable-block-' . $rowNum . '">';
        $blockHtml .= '<div class="subtable2-del-div"><a href="#" class="subtable2-del-link">Delete</a></div>';
        $blockHtml .= $innerHtml;
        $blockHtml .= '</div>';

        return $blockHtml;
    }

    public function proceedSubmit()
    {
        $postKey = $this->getTableName() . 'Subtable2';

        if (!isset($_POST[$postKey])) {
            //Таблица вообще не пришла в POST запросе - нечего сохранять
            return 0;
        }

        /*
        Обновление элементов в базе основывается на двух скрытых полях:

        1. $_POST[$this->getTableName().'Subtable2']['ids'] - содержит ID всех элементов через запятую в порядке следования строк
        в HTML таблице. Если разделить эту строку через запятые, получим массив <строка в таблице> => <ID в базе>
        Новые строки, которые добавляются в HTML таблицу через JS будут отображены в конце с нулевым ID

        2. $_POST[$this->getTableName().'Subtable2']['delrows'] - сожержит номера строк через запятую, которые были удалены
        (если используется визуальное удаление строки на экране) или это непосредственно массив чекбоксов (если для
        удаления строк используются чекбоксы)

        */

        $this->loadData();

        $this->_obj->setMasterIdForDbLog($this->_masterObj->getId());

        //Удаляем некоторые строки из базы, если разрешено удаление
        if ($this->allowDelete) {
            foreach ($this->getRowsToBeDeleted() as $rowNum) {
                //Узнаем ID записи лежащей в данной строке
                $id = $this->getRowIdFromPost($rowNum);
                if ($id && isset($this->_data[$id])) {
                    //Если ID существует среди текущих данных, удалим объект
                    $this->proceedDeleteObj($id, $this->_data[$id]);
                    //Удалим непосредсвенно из массива Data
                    unset($this->_data[$id]);
                }
            }
        }

        //Пройдём в цикле по всем данным из запроса и сделаем вставки или обновления
        $affectedRows = 0;
        foreach ($this->getDataFromPost() as $rowData) {
            $id = (int)$rowData['ID'];
            $fieldsData = $rowData['fieldsData'];
            $rowNum = $rowData['rowNum'];

            // Оставим в fieldsData только те поля, которые имеют MODE_SUBMIT
            $fieldsData = $this->_obj->filterDataByModeSubmit($fieldsData);

            // Оставим только те поля, по которым проходит проверку editAccessFunction
            $fieldsData = $this->_obj->filterDataByEditAccessFunction($fieldsData);

            //Проверим $id и существование объекта в базе
            if ($id != 0) {
                $query = "SELECT ID FROM " . Db::quote($this->getDbTable()) . " WHERE ID = " . (int)$id;
                $id = Db::getOne($query);
            }

            $affectedRows += $this->proceedInsertUpdateObj($id, $fieldsData, $rowNum);
        }

        //Загружаем сохранённые данные
        $this->loadData();

        //Если определена мастер-таблица в которой нужно обновить некоторые кешируюище поля и определны эти поля - обновим кеш!
        if ($this->_cacheFieldsKeys) {
            $this->updateCache();
        }

        return $affectedRows;
    }

    //Кеширующая переменная
    private $_delRows;

    /**
     * @return array
     */
    protected function getRowsToBeDeleted()
    {
        if ($this->_delRows === null) {
            $this->_delRows = [];
            //сожержит номера строк через запятую, которые были удалены
            $postKey = $this->getTableName() . 'Subtable2';
            $subtableRequestData = $_POST[$postKey];
            if (isset($subtableRequestData['delrows']) && $subtableRequestData['delrows'] !== '') {
                $delRowsIds = explode(',', $subtableRequestData['delrows']);
                $this->_delRows = array_combine($delRowsIds, $delRowsIds);
            }
        }

        return $this->_delRows;
    }

    protected function isRowDeleted($rowNum)
    {
        $delRows = $this->getRowsToBeDeleted();
        return isset($delRows[$rowNum]);
    }

    //Кеширующая переменная
    private $_rowsIds;

    /**
     * Метод используется при сохранении данных (из proceedSubmit) - возвращает массив всех ID элементов записанных
     * в скрытом поле ids
     * @return array
     */
    final protected function getRowsIdsFromPost()
    {
        if ($this->_rowsIds === null) {
            /* специальное скрытое поле содержит ID всех элементов через запятую в порядке следования строк
            в HTML таблице. Если разделить эту строку через запятые, получим массив <строка в таблице> => <ID в базе>
            Новые строки, которые добавляются в HTML таблицу через JS будут отображены в конце с нулевым ID */
            $ids = $_POST[$this->getTableName() . 'Subtable2']['ids'];
            if ($ids !== '') {
                $this->_rowsIds = explode(',', $ids);
            } else {
                $this->_rowsIds = [];
            }
        }
        return $this->_rowsIds;
    }

    protected function getRowIdFromPost($rowNum)
    {
        $rowsIds = $this->getRowsIdsFromPost();
        return $rowsIds[$rowNum] ?? null;
    }

    protected function proceedDeleteObj($id, array $currentData)
    {
        $this->_obj->setId($id);
        $this->_obj->setMasterIdForDbLog($this->_masterObj->getId());
        $this->_obj->setData(
            $currentData
        ); //Заполням данные, чтобы при удалении правильно сработала функция _obj->getDescriptionHtml
        $this->_obj->deleteDb('from subtable');
    }

    public function deleteAll()
    {
        $this->loadData();
        foreach ($this->_data as $id => $row) {
            $this->proceedDeleteObj($id, $row);
        }
        $this->_data = [];
    }

    public function getDataFromPost()
    {
        $dataFromRequest = [];
        //Возвращает набор данных из $_REQUEST - может пригодится для внешних классов
        foreach ($this->getRowsIdsFromPost() as $rowNum => $id) {
            //Удалённые строки пропускаем
            if ($this->allowDelete && $this->isRowDeleted($rowNum)) {
                continue;
            }

            //rowNum не используется непосредсвенно в классе subtable, но может использоваться во внешнем коде
            //который вызвает этот публичный метод getDataFromRequest()
            //Например в модуле ModuleAbstract в методе checkCriticalErrors может производиться проверка подтаблицы
            $dataFromRequest[] = [
                'ID' => $id,
                'fieldsData' => $this->getRowDataFromPost($rowNum),
                'rowNum' => $rowNum
            ];
        }

        return $dataFromRequest;
    }

    public function getNotDeletedDataFromReqeust(array $dataFromRequest = null)
    {
        return [];
    }

    protected function proceedInsertUpdateObj($id, array $rowDataFromPost, $rowNum)
    {
        $rowDataFromPost[$this->getMasterFieldKey()] = $this->getMasterFieldValue();
        if ($id != 0) {
            //Объект уже существует в базе, требует обновления и, возможно, привязан к текущему MasterID
            $affectedRows = $this->proceedUpdateObj($id, $rowDataFromPost);
        } else {
            //Объект требует вставку в базу и привязки к MasterID
            $this->proceedInsertObj($rowDataFromPost);
            $affectedRows = 1;
        }

        return $affectedRows;
    }

    /**
     * @param int $id
     * @param array $rowDataFromPost
     * @return int $affectedRows
     */
    protected function proceedUpdateObj($id, array $rowDataFromPost)
    {
        $this->_obj->setId($id);
        return $this->_obj->updateDb($rowDataFromPost, 'Updated in subtable');
    }

    /**
     * @param array $rowDataFromPost
     * @return int ID новой строки
     */
    protected function proceedInsertObj(array $rowDataFromPost)
    {
        return $this->_obj->insertDb($rowDataFromPost, 'in subtable');
    }

    protected function getRowDataFromPost($rowNum)
    {
        $postKey = $this->getTableName() . 'Subtable2';
        $rowDataFromPost = $_POST[$postKey][$rowNum] ?? [];
        return $this->_obj->getDataFromRequest($rowDataFromPost);
    }

    /**
     * Поля из массива $this->_cacheFieldsKeys - по ним собираются все данные и выполняет UPDATE запрос
     * на таблицу $this->_masterObj->getDbTable(), для каждого поля, добавляя префикс Cache<Имя класса без Subtable>
     * @return void
     */
    public function updateCache()
    {
        if ($this->_data === null) {
            $this->loadData();
        }

        //Соберём данные в ассоциативный массив fieldKey => <текстовые данные через перевод строки>
        $cacheData = [];
        //Для каждого поля пробегаемся по всем данным
        foreach ($this->_cacheFieldsKeys as $fieldKey) {

            //Имя поля в мастер-таблице имеет префикс 'Cache' . $this->getTableName()
            $cacheFieldKey = 'Cache' . $this->getTableName() . $fieldKey;

            if (count($this->_data)) {
                $values = [];
                foreach ($this->_data as $row) {
                    $values[] = $row[$fieldKey] ?? '';
                }

                //Если установлен специальный флаг, нужно записать только уникальные значения
                if ($this->_cacheOnlyUniqueValues) {
                    $values = array_unique($values);
                }

                //Объединяем все значения через перевод строки и записываем в выходной массив
                $cacheData[$cacheFieldKey] = implode("\n", $values);
            } else {
                $cacheData[$cacheFieldKey] = null;
            }
        }

        if (count($cacheData)) {
            $masterTable = $this->_masterObj->getDbTable();
            $masterId = $this->_masterObj->getId();
            //С помощью плейсхолдера @u формируем update запрос на основе массива $cacheData (имя поля => значение)
            $query = "UPDATE $masterTable SET @u WHERE ID = @i";
            Db::query($query, $cacheData, $masterId);
        }
    }

    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Изменение поля через ajax
     */
    public function proceedChangeField()
    {
        $fieldKey = $_REQUEST['fieldKey'];
        $field = $this->_obj->getFieldset()->getField($fieldKey);
        if (!$field) {
            Response::abort(400, 'Field ' . $fieldKey . ' not found');
        }

        $sudid = $_REQUEST['subid'];
        $this->_obj->setId($sudid);
        if (!$this->_obj->loadData()) {
            Response::abort(404, 'Subtable object not found (id = ' . $sudid . ')');
        }

        $data = $this->_obj->getData();
        if (Request::isGet()) {

            // Это GET запрос на получение поля ввода (input или textarea)
            $value = $this->_obj[$fieldKey];
            // Выводим в ajax ответ HTML код для поля ввода
            print $this->_obj->getField($fieldKey)->getHtmlInput($value, $data);
            ApplicationAbstract::terminate();

        } else {

            // Это POST запрос на изменение поля
            if (!$field->hasMode(F::MODE_SUBMIT)) {
                Response::abort(403, 'Field ' . $fieldKey . ' not editable');
            }

            $value = $_REQUEST['newValue'];
            $data = [$fieldKey => $value];
            $normilizedValue = $field->getDataFromRequest($data);
            if ($normilizedValue === null && !$field->nullable) {
                Response::abort(400, 'Invalid value ' . $value);
            }

            $this->_obj->setMasterIdForDbLog($this->_masterObj->getId());
            $this->_obj->updateDb([$fieldKey => $normilizedValue]);

            // Возвращаем новое HTML значение для вставки в вёрстку
            print $this->_obj->getFieldHtml($fieldKey);
            ApplicationAbstract::terminate();
        }
    }

    /**
     * @return Fieldset|F[]
     */
    public function getFieldset()
    {
        return $this->_obj->getFieldset();
    }

    public function getDbTable()
    {
        return $this->_obj->getDbTable();
    }

    public function setDbTable($dbTable)
    {
        $this->_obj->setDbTable($dbTable);
    }

    public function setNameFieldKey($nameFieldKey)
    {
        $this->_obj->setNameFieldKey($nameFieldKey);
    }
}
