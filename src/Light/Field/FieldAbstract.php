<?php

namespace Light\Field;

use Light\Db;
use Light\Html;

/**
 * @method $this setName(string $value)
 * @method $this setNameDbLog(string $value)
 * @method $this setNameTable(string $value)
 * @method $this setNameTableSettings(string $value)
 * @method $this setTitle(string $value)
 * @method $this setModes(int $bitmap)
 * @method $this setDefault(string $value)
 * @method $this setUom(string $value)
 * @method $this setTotal(bool $total)
 * @method $this setCopy(bool $copy)
 * @method $this setTableWidthExcel(int $value)
 * @method $this setTableWidthHtml(int $value)
 * @method $this setTableOrderBy($sqlFieldKey)
 * @method $this setTableOrderByLikeFilter($comaSepFilterFieldKey)
 * @method $this setTableAlign(string $leftCenterRight)
 * @method $this setExcelStyle($arrayOrCallable)
 * @method $this setViewCssClass($stringOrCallable)
 * @method $this setViewTitle($stringOrCallable)
 * @method $this setViewTagParams($stringOrCallable)
 * @method $this setViewMaxWidth300(bool $value)
 * @method $this setEditId(string $value)
 * @method $this setEditName(string $value)
 * @method $this setEditWidth(string $miniSmallMediumDefaultFull)
 * @method $this setEditAlign(string $leftCenterRight)
 * @method $this setEditCssClass($stringOrCallable)
 * @method $this setEditTitle($stringOrCallable)
 * @method $this setEditTagParams($stringOrCallable)
 * @method $this setEditMandatory($value)
 * @method $this setPlaceholder(string $value)
 * @method $this setNobr(bool $value)
 * @method $this setClickable(bool $value)
 * @method $this setUpper(bool $value)
 * @method $this setGroupconcat(bool $value)
 * @method $this setGroupconcatRemoveEmpty(bool $value)
 * @method $this setGroupconcatTruncate(bool $value)
 * @method $this setGroupconcatTruncateMaxElements(int $value)
 * @method $this setCalcFunction(callable $function)
 * @method $this setGetHtmlFunction(callable $function)
 * @method $this setGetExcelFunction(callable $function)
 * @method $this setGetHtmlInputFunction(callable $function)
 * @method $this setGetDataFromRequestFunction(callable $function)
 * @method $this setSqlSelect($stringOrCallable)
 * @method $this setSqlSelectRequires($stringOrArray)
 * @method $this setSqlSelectMandatory(bool $value)
 * @method $this setSqlWhere($stringOrCallable)
 * @method $this setSqlWhereFormatFunction(callable $function)
 * @method $this setAjax(bool $ajax)
 * @method $this setChangeAll(bool $changeAll)
 *
 * @method $this setAcReadonly(bool $value)
 * @method $this setAcSource(string $value)
 * @method $this setAcSourceJsFunction(string $value)
 * @method $this setAcDelimChar(string $value)
 * @method $this setAcWidth(int $value)
 * @method $this setAcResultFormatterJsFunction(string $value)
 * @method $this setAcOnSelectJsFunction(string $value)
 * @method $this setAcFormatValueOnSaveFunction(string $value)
 *
 * @method string getTitle()
 * @method int getModes()
 * @method string getDefault()
 * @method int getTableWidthExcel()
 * @method int getTableWidthHtml()
 * @method bool getTotal()
 * @method bool getCopy()
 * @method string getTableOrderBy()
 * @method string getTableOrderByLikeFilter()
 * @method string getEditId()
 * @method string getEditName()
 * @method string getEditWidth()
 * @method string getEditAlign()
 * @method bool|string getEditMandatory()
 * @method string getPlaceholder()
 * @method bool getUpper()
 * @method callable getGetHtmlFunction()
 * @method callable getGeExcelFunction()
 * @method callable getGetHtmlInputFunction()
 * @method callable getGetDataFromRequestFunction()
 * @method bool getSqlSelectMandatory()
 * @method callable getSqlWhereFormatFunction()
 * @method string|array getSqlSelectRequires()
 * @method bool getChangeAll()
 * @method bool getAjax()
 *
 * @method static FieldHtml createHtml(string $fieldKey, array $params = [])
 * @method static FieldCheckbox createCheckbox(string $fieldKey, array $params = [])
 * @method static FieldCheckboxSet createCheckboxSet(string $fieldKey, array $params = [])
 * @method static FieldString createString(string $fieldKey, array $params = [])
 * @method static FieldText createText(string $fieldKey, array $params = [])
 * @method static FieldDate createDate(string $fieldKey, array $params = [])
 * @method static FieldDateTime createDateTime(string $fieldKey, array $params = [])
 * @method static FieldColor createColor(string $fieldKey, array $params = [])
 * @method static FieldWeek createWeek(string $fieldKey, array $params = [])
 * @method static FieldInt createInt(string $fieldKey, array $params = [])
 * @method static FieldFloat createFloat(string $fieldKey, array $params = [])
 * @method static FieldMoney createMoney(string $fieldKey, array $params = [])
 * @method static FieldMoneyWithCurrency createMoneyWithCurrency(string $fieldKey, array $params = [])
 * @method static FieldSelect createSelect(string $fieldKey, array $params = [])
 * @method static FieldSelectChild createSelectChild(string $fieldKey, array $params = [])
 * @method static FieldSelect2Multiple createSelect2Multiple(string $fieldKey, array $params = [])
 * @method static FieldSelect2ChildMultiple createSelect2ChildMultiple(string $fieldKey, array $params = [])
 * @method static FieldYesNo createYesNo(string $fieldKey, array $params = [])
 * @method static FieldMonth createMonth(string $fieldKey, array $params = [])
 */
abstract class FieldAbstract implements \JsonSerializable
{
    const MODE_LIST = 0b000001;
    const MODE_VIEW = 0b000010;
    const MODE_EDIT = 0b000100;
    const MODE_LOG = 0b001000;
    const MODE_MULTIPLEUPDATE = 0b010000;
    const MODE_SUBMIT = 0b100000;

    const MODE_READONLY = 0b000011;
    const MODE_EDITSUBMIT = 0b100100; // MODE_EDIT + MODE_SUBMIT
    const MODE_ALL = 0b111111;

    const TABLEALIGN_LEFT = 'left';
    const TABLEALIGN_CENTER = 'center';
    const TABLEALIGN_RIGHT = 'left';

    const TYPE_HTML = 'Html';

    const TYPE_CHECKBOX = 'Checkbox';
    const TYPE_CHECKBOX_SET = 'CheckboxSet';

    const TYPE_STRING = 'String';

    const TYPE_TEXT = 'Text';

    const TYPE_DATE = 'Date';
    const TYPE_DATE_TIME = 'DateTime';
    const TYPE_COLOR = 'Color';
    const TYPE_WEEK = 'Week';

    const TYPE_INT = 'Int';
    const TYPE_FLOAT = 'Float';
    const TYPE_MONEY = 'Money';
    const TYPE_MONEY_WITH_CURRENCY = 'MoneyWithCurrency';

    const TYPE_SELECT = 'Select';
    const TYPE_SELECT_CHILD = 'SelectChild';
    const TYPE_SELECT2_MULTIPLE = 'Select2Multiple';
    const TYPE_YES_NO = 'YesNo';
    const TYPE_MONTH = 'Month';

    const PARAM_AC_READONLY = 'acReadonly';
    const PARAM_AC_SOURCE = 'acSource';
    const PARAM_AC_SOURCEJSFUNCTION = 'acSourceJsFunction';
    const PARAM_AC_DELIMCHAR = 'acDelimChar';
    const PARAM_AC_WIDTH = 'acWidth';
    const PARAM_AC_RESULTFORMATTERJSFUNCTION = 'acResultFormatterJsFunction';
    const PARAM_AC_ONSELECTJSFUNCTION = 'acOnSelectJsFunction';
    const PARAM_AC_FORMATVALUEONSAVEFUNCTION = 'acFormatValueOnSaveFunction';

    const PARAM_TEXTTRUNCATE = 'textTruncate';
    const TEXTTRUNCATE_FULL = 'full';
    const TEXTTRUNCATE_PART = 'part';
    const TEXTTRUNCATE_LIST = 'list';
    const PARAM_VIEWMAXWIDTH300 = 'viewMaxWidth300';

    public $key;

    /**
     * @var string имя для отображения в заголовках таблицы и на формах (если не задано, используется key)
     */
    protected $name;

    /**
     * @var string имя для отображения в таблице логово изменений (если не задано, используется name)
     */
    protected $nameDbLog;

    /**
     * @var string имя для отображения в заголовках таблицы (если не задано, используется name)
     */
    protected $nameTable;

    /**
     * @var string имя для отображения в диалогоговом окне Table layout settings (если не задано, используется nameTable)
     */
    protected $nameTableSettings;

    /**
     * @var string Всплывающая подсказка при наведении указателя мыши на поле (параметр html тега: title="...")
     */
    public $title;

    /**
     * @var int Битовое поле с модификаторами MODE_* - режимы, в которых движок будет использовать это поле
     */
    public $modes = self::MODE_ALL;

    /**
     * Длеаем поле Public для прямого доступа из класса Template для увеличения производительности
     * @var string|number значение по умолчанию для форм в режиме Add New
     */
    public $default;

    /**
     * Длеаем поле Public для прямого доступа из класса Template для увеличения производительности
     * @var string единици измерения, в режиме таблицы будут добавлены в заголовок, в режиме view/edit после поля
     */
    public $uom;

    /**
     * @var bool нужно ли в таблице подсчитывать поэтому полю total в столбик?
     */
    public $total = false;

    /**
     * @var bool нужно ли копировать значение из предыдущей строки при добавлении новой строки в подтаблице (Subtable)?
     */
    public $copy = false;

    /**
     * @var int Ширина колонки при отображении поля в таблице в Excel режиме
     */
    public $tableWidthExcel;

    /**
     * @var int Ширина колонки при отображении поля в таблице в Html режиме
     */
    public $tableWidthHtml;

    /**
     * @var string Имя поля в бд по которому сортировать (если не задано, используется key)
     */
    public $tableOrderBy;

    /**
     * @var string иногда в таблице хочется отфильтровать значения ровно в том порядке, как они указаны в множественном фильтре
     * Например фильтр по списку контейнеров.
     * Это можно сделать с помощью sql инструкции ORDER BY FIELD(ContainerName, <значения>, <из>, <фильтра>, ...)
     * https://stackoverflow.com/questions/396748/ordering-by-the-order-of-values-in-a-sql-in-clause
     *
     * В данном поле нужно указать ключ в фильтрах, который нужно проверить: если пользователь фильтрует по этому ключу
     * и внутри много значений, то применяем сортировку
     */
    public $tableOrderByLikeFilter;

    /**
     * Делаем поле public, чтобы иметь прямой доступ из классов Table и XlsWriter для увеличения производительности
     * @var string Выравнивание при выводе в таблицу left|center|right
     */
    public $tableAlign;

    /**
     * @var string|callable работает как метод $viewCssClass($value, array $data, $forTable, $field)
     */
    public $viewCssClass;

    /**
     * либо массив стилей, которые применить к этому полю (тогда стили применятся ко всей колонке)
     * либо здесь можно задать функцию 'excelStyle' => function($value, array $data) {...} - метод должен вернуть массив стилей для текущей ячейки
     * @var array|callable
     */
    public $excelStyle;

    /**
     * значение атрибута title для тега span вокруг заничения в режиме View
     * или работает как метод $viewTitle($value, array $data, $forTable)
     * @var string|callable
     */
    public $viewTitle;

    /**
     * атрибуты для тега span вокруг заничения в режиме View
     * или работает как метод $viewTagParams($value, array $data, $forTable)
     * @var string|callable
     */
    public $viewTagParams;


    /**
     * Ограничить ли ширину текста с помощью css класса overflow300?
     * Если да, то в title автоматически будет добавлено полное значение текущей ячейки {value}
     * @var bool
     */
    public $viewMaxWidth300 = false;

    /**
     * @var string атрибут id для html тега в режиме редактирования (если не задано, используется key)
     */
    public $editId;

    /**
     * @var string значение аттрибута name в теге input|select|textarea; нужно для subtable, где имена полей - массив
     * если не задано, используется key
     */
    public $editName;

    /**
     * @var string mini|small|medium|default|full - к полю будет добавлен css class input-*
     */
    public $editWidth;

    /**
     * @var string left|right - если значение right, то к полю будет добавлен css class input-right
     */
    public $editAlign;

    /**
     * @var string|callable
     */
    public $editCssClass;

    /**
     * @var string|callable всплывающая подсказка titale, которая будет добавлена внутрь тега input
     */
    public $editTitle;

    /**
     * @var string|callable
     */
    public $editTagParams;

    /**
     * @var bool|string используется в ModuleAbstract::validateMandatory()
     */
    public $editMandatory = false;

    /**
     * @var string используется в полях типа input и в react-select
     */
    public $placeholder;

    /**
     * @var bool
     */
    public $nobr = false;

    /**
     * @var bool
     */
    public $clickable = false;

    /**
     * @var bool
     */
    public $upper = false;

    /**
     * Специально делаем public, чтобы иметь быстрый доступ из внешних классов
     * Если стоит этот флаг, то значение поля разбивается на строки explode("\n", $value)
     * и затем к каждой строке применяется операция getHtmlOneLine() внутри которой также вызывается getHtmlFunction()
     * т.е. значение поля полученное из базы рассматиривается как массив значений разделённых переводом строки
     * @var bool
     */
    public $groupconcat = false;

    /**
     * @var bool
     */
    public $groupconcatRemoveEmpty = false;

    /**
     * Сворачивать ли длинные списки значений за ссылкой?
     * @var bool
     */
    public $groupconcatTruncate = true;

    /**
     * Количество элементов после которого список будет свёрнут за ссылкой
     * @var bool
     */
    public $groupconcatTruncateMaxElements = 4;

    /**
     * Оборачитвать ли каждую строку в <span class="nobr>...</span>?
     * Обычно используется nobr класс для каждой строки внутри groupconcat поля, чтобы при наличии нескольких groupconcat полей их значения были аккуратно выравнены напротив друг друга.
     * Однако, бывают поля в которых не нужно выравнивание, но нужен перенос по словам, например, список услуг из счёта - для таких полей будем ставить groupconcatNobr = false
     * @var bool
     */
    public $groupconcatNobr = true;

    /**
     * @var callable($value, array $data, $forTable)
     */
    public $calcFunction;

    /**
     * @var callable($value, array $data, $outHtml, $forTable, $field, $forLog)
     */
    public $getHtmlFunction;

    /**
     * @var callable($value, array $data, $out, $field)
     */
    public $getExcelFunction;

    /**
     * @var callable($value, array $data, $outHtml, $field, $id)
     */
    public $getHtmlInputFunction;

    /**
     * @var callable ($request, $value) внутри обязательно проверять if ($value !== null)
     */
    public $getDataFromRequestFunction;

    /**
     * @var string|callable Если для изъятия из базы данных это поле нужно вычислить или следать подзапрос
     */
    public $sqlSelect;

    /**
     * @var array|string Массив с ключами полей от которых зависит данное поле в SELECT запросе
     */
    public $sqlSelectRequires;

    /**
     * @var bool если флаг установлен в true, это поле всегда будет выбираться в SELECT запросе
     */
    public $sqlSelectMandatory = false;

    /**
     * Шаблон SQL фрагмента WHERE, плейсхолдер {value} "mytalbe.MyField LIKE '{value}%'"
     * или работает как метод $sqlWhere($value, array $filtersValues, $sql, $field)
     * @var string|callable
     */
    public $sqlWhere;

    /**
     * @var callable($value) предварительное форматирование перед  allowSqlWhereValue и getSqlWhereInner
     */
    public $sqlWhereFormatFunction;

    /**
     * Автоматически обрезает длинный текст
     * Может принимать одно из четырёх значений:
     * - null
     * - FieldAbstract::TEXTTRUNCATE_FULL ('full') - скрывает весь текст за ссылкой, если он более 30 символов
     * - FieldAbstract::TEXTTRUNCATE_PART ('part') - отображает первые 200 символов текст, остальной скрывает за ссылкой
     * - FieldAbstract::TEXTTRUNCATE_LIST ('list') - скрывает весь текст за ссылкой, если в нём более 4-х строк
     * @var string
     */
    public $textTruncate;

    public $acReadonly;
    public $acSource;
    public $acSourceJsFunction;
    public $acDelimChar;
    public $acWidth;
    public $acResultFormatterJsFunction;
    public $acOnSelectJsFunction;
    public $acFormatValueOnSaveFunction;

    /**
     * @var bool для подтаблиц - отображать ли ссылку "изменить все" в режиме редактирования подтаблицы
     */
    public $changeAll = false;

    /**
     * @var bool позволить пользователю менять значение поля в режиме просмотра/таблицы через ajax функциональность
     */
    public $ajax = false;

    public $initialParams = [];

    /** @var string $typeCache кеширующее поле для метода getType() */
    private $typeCache;

    final public function __construct($key, array $params = [])
    {
        if (isset($params['key'])) {
            unset($params['key']);
        }

        $this->initialParams = $params;

        $this->key = $key;
        $this->initDefaultParams();

        // Внесём userDefinedParams во внутренние переменные класса
        foreach ($params as $paramName => $paramValue) {
            $this->{$paramName} = $paramValue;
        }
    }

    protected function initDefaultParams()
    {
        $this->name = $this->key;
        $this->editId = $this->key;
        $this->editName = $this->key;
        $this->tableOrderBy = $this->key;

        if (strpos($this->key, 'Name') === 0) {
            //Если поле начинается со слова Name - установим некую стандартную ширину для экспорта в Excel = 30
            $this->tableWidthExcel = 30;
        }
    }

    /**
     * Пользотватель может вызывать несуществующие методы set... и get..., например setDefault($newValue), getDefault()
     * В этом случае сработает магический метод __call, который должен полизвести соответсвующую операцию с указанным свойством
     *
     * @param string $method
     * @param array $args
     * @return $this|mixed|null
     */
    final public function __call($method, $args)
    {
        $setOrGet = substr($method, 0, 3);
        $paramName = lcfirst(substr($method, 3));
        if ($setOrGet === 'set') {

            $this->setParam($paramName, $args[0]);
            return $this;

        } elseif ($setOrGet === 'get') {

            //Метод  get для параметра, у которого явно не задан какой-то хитрый способ вычисления (с возможным
            //наследованием и переопределением) - просто возвращаем этот параметр
            return $this->{$paramName};

        }

        $class = get_class($this);
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR);

        return null;
    }

    final public function getKey()
    {
        return $this->key;
    }

    final public function setParam($name, $value)
    {
        // изменим непосредственное свойство в данном объекте
        $this->{$name} = $value;
        // cохраним также указанное пользователем значение в массиве initialParams
        $this->initialParams[$name] = $value;
        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    final public function setParams(array $params)
    {
        foreach ($params as $name => $value) {
            $this->setParam($name, $value);
        }
        return $this;
    }

    public function getType()
    {
        if ($this->typeCache === null) {
            $this->typeCache = substr(get_class($this), strlen('\\Light\\Field\\Field') - 1);
        }

        return $this->typeCache;
    }

    final public function hasMode($mode)
    {
        return $this->modes & $mode;
    }

    final public function addNamePrefix($prefix)
    {
        if (!$prefix) {
            return;
        }

        //Используем временную анонимную функцию, чтобы добавить префиксы к четырём свойствам
        $add = function ($currentValue) use ($prefix) {
            //Если текущее значение поля не начинается с этого префикса, добавим префикс в начало
            if (strpos($currentValue, $prefix) !== 0) {
                return $prefix . "\n" . $currentValue;
            }
            return $currentValue;
        };

        $this->name = $add($this->name);
        $this->nameDbLog = $add($this->getNameDbLog());
        $this->nameTable = $add($this->getNameTable());
        $this->nameTableSettings = $add($this->getNameTableSettings());

        //Отдельный разговор с title
        if ($this->title) {
            $this->title = $add($this->title);
        }
    }

    public function getHtml($value, array $data = [], $forTable = false, $forLog = false)
    {
        if ($this->groupconcat && $value) {

            $lines = explode("\n", $value);
            //Если число линий больше 1 - будем рекурсивно обрабатывать каждую линию
            $groupconcatLinesCount = count($lines);

            //Пройдём по всем линия и обернём их в $this->getHtmlOneLine(), получив на выходе $outLines
            $groupconcatRemoveEmpty = $this->groupconcatRemoveEmpty;
            $groupconcatNobr = $this->groupconcatNobr;
            $outLines = [];
            foreach ($lines as $lineValue) {

                $html = $this->getHtmlOneLine($lineValue, $data, $forTable, $groupconcatLinesCount, $forLog);

                // Проверим, если есть специальный флаг пропускать пустые линии и эта линия действительно пустая - пропустим её!
                if ($groupconcatRemoveEmpty && trim(strip_tags($html)) === '') {
                    //Не записываем эту строку в $outLines
                    continue;
                }

                if ($html === '') {
                    $html = '&nbsp;'; //Принудительно вставим пробел, чтобы строка не "схлопнулась" - это важно для groupconcat
                }

                if ($groupconcatNobr) {
                    $html = '<span class="nobr">' . $html . '</span>';
                }

                $outLines[] = $html;
            }

            //Соберём отдельно обработанные строки обратно
            $outHtml = implode('<br>', $outLines);
            if ($this->groupconcatTruncate) {
                $outHtml = Html::getTruncatedList($outHtml, $this->getName(), '<br>',
                    $this->groupconcatTruncateMaxElements);
            }

            return (string)$outHtml;

        }

        return (string)$this->getHtmlOneLine($value, $data, $forTable, null, $forLog);
    }

    private function getHtmlOneLine($value, array $data, $forTable, $groupconcatLinesCount = null, $forLog = false)
    {
        //Сначала сформируем HTML представление этого значения стандартными средсвами класса
        $outHtml = $this->getHtmlInner($value, $data, $forTable);

        if ($this->textTruncate) {
            //Обрезаем текст, если его слишком много и указана специальная опция в настройках поля
            switch ($this->textTruncate) {
                case self::TEXTTRUNCATE_LIST:
                    $outHtml = Html::getTruncatedList($outHtml, $this->getName(), '<br>');
                    break;
                case self::TEXTTRUNCATE_FULL:
                    $outHtml = Html::getTruncatedText($outHtml);
                    break;
                case self::TEXTTRUNCATE_PART:
                    $outHtml = Html::getPartlyTruncatedText($outHtml);
                    break;
            }
        }

        if (!$forTable && $outHtml && $this->uom) {
            $outHtml .= ' ' . $this->uom;
        }

        //Затем, если передана специальная функцяи обёртка getHtmlFunction, передадим результат ей
        if ($this->getHtmlFunction) {
            $function = $this->getHtmlFunction;
            $outHtml = $function(
                $value,
                $data,
                $outHtml,
                $forTable,
                $this,
                $forLog
            );
        }

        if ($this->clickable) {
            $outHtml = Html::clickable($outHtml);
        }

        //Узнаем, есть ли какой-то класс, которым нужно обернуть полученное значение, если не для таблицы
        //Либо для таблицы, но это поле groupconcat, т.е. каждая строка должна быть обёрнута отдельно
        if (!$forTable || $groupconcatLinesCount > 1) {
            $cssClass = $this->getViewCssClass($value, $data, $forTable);
            $title = $this->getViewTitle($value, $data, $forTable);
            $tagParams = $this->getViewTagParams($value, $data, $forTable);
            if ($cssClass || $tagParams || $title) {
                $cssClass = $cssClass ? ' class="' . $cssClass . '"' : '';
                $title = $title ? ' title="' . htmlspecialchars($title) . '"' : '';
                $tagParams = $tagParams ? ' ' . $tagParams : '';

                $outHtml = '<span' . $cssClass . $tagParams . $title . '>' . $outHtml . '</span>';
            }
        }

        if (!$forLog && $this->ajax) {
            $outHtml = $this->ajaxify($value, $data, $forTable, $outHtml);
        }

        return $outHtml;
    }

    abstract protected function getHtmlInner($value, array $data, $forTable);

    /**
     * Метод для переопределения в классах потоках - превращает поле в режимах list/view в редакитруемое через ajax
     * @param mixed $value
     * @param array $data
     * @param bool $forTable
     * @param string $outHtml
     * @return mixed
     */
    protected function ajaxify($value, array $data, $forTable, $outHtml)
    {
        if (!$this->hasMode(self::MODE_EDIT)) {
            return $outHtml;
        }

        if ($this->groupconcat) {
            return $outHtml;
        }

        if (!isset($data['ID']) || !$data['ID']) {
            return $outHtml;
        }

        return '<a href="#" class="ajax-input" data-id="' . $data['ID'] . '" data-fieldkey="' . htmlspecialchars($this->key) . '">' . $outHtml . '</a>';
    }

    public function getHtmlForLog($value, array $data)
    {
        return $this->getHtml($value, $data, false, true);
    }

    public function getText($value, array $data = [], $forTable = false)
    {
        $ajax = $this->ajax;
        $this->ajax = false;
        $out = htmlspecialchars_decode(strip_tags($this->getHtml($value, $data, $forTable)));
        $this->ajax = $ajax;
        return $out;
    }

    public function getExcel($value, array $data = [])
    {
        if ($value !== null) {
            //Правильное оформление перевода строк
            $value = strip_tags(str_replace(["\r", "<br>", "<p>"], ['', "\n", "\n"], $value));
        }

        //Проверим, возможно значение $value состоит из нескольких, которые необходимо разбить через перевод строки
        if ($this->groupconcat && $value) {
            $lines = explode("\n", $value);

            $groupconcatLinesCount = count($lines);
            $groupconcatRemoveEmpty = $this->groupconcatRemoveEmpty;
            $outLines = [];
            foreach ($lines as $lineValue) {
                $lineValue = $this->getExcelOneLine(trim($lineValue), $data, $groupconcatLinesCount);
                if ($groupconcatRemoveEmpty && trim($lineValue) === '') {
                    continue;
                }

                $outLines[] = $lineValue;
            }
            return implode("\n", $outLines);
        }

        return $this->getExcelOneLine($value, $data);
    }

    private function getExcelOneLine($value, array $data, int $groupconcatLinesCount = null)
    {
        $out = $this->getExcelInner($value, $data, $groupconcatLinesCount);

        if ($this->getExcelFunction) {
            $function = $this->getExcelFunction;
            $out = $function($value, $data, $out, $this);
        }

        return $out;
    }

    protected function getExcelInner($value, array $data, int $groupconcatLinesCount = null)
    {
        return $value;
    }

    final public function getCalculatedValue($value, array $data = [], $forTable = false)
    {
        if (!$this->calcFunction) {
            return $value;
        }

        $calcFunction = $this->calcFunction;

        if ($this->groupconcat && strpos($value, "\n") !== false) {
            $lines = explode("\n", $value);
            //Если число линий больше 1 - будем рекурсивно обрабатывать каждую линию
            if (count($lines) > 1) {
                //Обработаем каждую строку через метод $this->getCalculatedValueOneLine() и соберём все строки в текст
                //через перевод строки
                foreach ($lines as &$lineValue) {
                    $lineValue = $calcFunction($lineValue, $data, $forTable);
                }
                return implode("\n", $lines);
            }
        }

        return $calcFunction($value, $data, $forTable);

    }

    public function getHtmlInput($value, array $data = [], $idPrefix = null)
    {
        $outHtml = $this->getHtmlInputInner($value, $data, $idPrefix);

        $function = $this->getHtmlInputFunction;
        if ($function) {
            $outHtml = $function($value, $data, $outHtml, $this, $idPrefix);
        }

        return $outHtml;
    }

    protected function getHtmlInputTplData($value, array $data, $idPrefix)
    {
        $cssClass = trim($this->getEditCssClass($value, $data));
        $title = trim($this->getEditTitle($value, $data));
        $tagParams = trim($this->getEditTagParams($value, $data));

        return [
            'cssClass' => $cssClass ? ' class="' . $cssClass . '"' : '',
            'title' => $title ? ' title="' . htmlspecialchars($title) . '"' : '',
            'tagParams' => $tagParams ? ' ' . $tagParams : '',
            'key' => $this->key,
            'value' => $value,
            'editId' => $idPrefix . $this->editId,
            'type' => $this->getType(),
            'editName' => $this->editName
        ];
    }

    abstract protected function getHtmlInputInner($value, array $data, $idPrefix);

    public function getDataFromRequest(array $request)
    {
        $value = $this->getDataFromRequestInner($request);

        $function = $this->getDataFromRequestFunction;
        if ($function) {
            $value = $function($value, $request);
        }

        //Сохраним введённое значение в базуе, если это поле поддерживает виджет Autocomplete
        $this->saveAutocompleteValue($value);

        return $value;
    }

    protected function getDataFromRequestInner(array $request)
    {
        if (array_key_exists($this->key, $request)) {

            $value = $request[$this->key];

            if ($this->upper) {
                $value = mb_strtoupper($value, 'UTF-8');
            }

            return $value;
        }

        return null;
    }

    /**
     * Возвращает клон объекта-поля.
     * Опционально можно заменить ключ
     * @param string $newKey
     * @param array $newParams
     * @return $this
     */
    public function getClone($newKey = null, array $newParams = [])
    {
        if ($newKey === null) {
            $newKey = $this->key;
        }

        return self::create($newKey, $this->getType(), $newParams + $this->initialParams);
    }

    /**
     * Определяет по значению, пришедшему с формы - был использован фильтр или нет. В частности, переопределяется для полей типа select и int
     * @param $value
     * @return bool
     */
    protected function allowSqlWhereValue($value)
    {
        return trim($value) !== '';
    }

    /**
     * Возвращает SQL-условие для обработки фильтра
     * @param string $value
     * @return string
     */
    protected function getSqlWhereInner($value)
    {
        return "{fieldKey} = '{value}'";
    }

    final public function getEvaluatedSqlWhere($value, array $filtersValues = [])
    {
        $sqlWhere = '';

        $function = $this->sqlWhereFormatFunction;
        if ($function) {
            $value = $function($value);
        }

        //Считаться ли с этим значением?
        if ($this->allowSqlWhereValue($value)) {

            //Используем стандартное SQL-условие для данного типа поля
            $sqlWhere = $this->getSqlWhereInner($value);

            //Попробуем использовать sqlWhere из параметров заданных пользователем
            if ($this->sqlWhere !== null) {
                if (is_callable($this->sqlWhere)) {
                    $sqlWhereFunction = $this->sqlWhere;
                    $sqlWhere = $sqlWhereFunction($value, $filtersValues, $sqlWhere, $this);
                } else {
                    $sqlWhere = $this->sqlWhere;
                }
            }

            //Если хоть какой-то запрос получился, проведём над ним последние операции
            if ($sqlWhere) {
                $sqlWhere = str_replace(['{value}', '{fieldKey}'], [Db::esc($value), Db::quote($this->key)], $sqlWhere);
                $sqlWhere = '(' . $sqlWhere . ')';
            }
        }

        return $sqlWhere;
    }

    /**
     * @static
     * @param string $fieldKey
     * @param string $type
     * @param array $params
     * @return FieldAbstract
     */
    final public static function create(string $fieldKey, string $type, array $params = [])
    {
        $class = '\\Light\\Field\\Field' . $type;
        return new $class($fieldKey, $params);
    }

    /**
     * Метод для быстрого создания полей разного типа вместо универсального create
     * @param string $method
     * @param array $args
     * @return null|FieldAbstract
     */
    public static function __callStatic($method, array $args)
    {
        if (strpos($method, 'create') === 0) {
            //Это один из методов создания поля определённого типа!
            //Проверим, что существует обязательный первый параметр (который должен быть $fieldKey) в массиве $args
            if (!isset($args[0])) {
                throw new \InvalidArgumentException('Field Key is mandatory as first parameter');
            }

            $type = substr($method, 6);
            $fieldKey = $args[0];
            if (isset($args[1])) {
                if (is_string($args[1])) {
                    //В качестве второго параметра методу F::create(..., ...) может быть передан не массив, а просто строка
                    //в этом случае принимаем эту строку как имя поля
                    $params = ['name' => $args[1]];
                } else {
                    $params = $args[1];
                }
            } else {
                $params = [];
            }
            return self::create($fieldKey, $type, $params);
        }

        $class = __CLASS__;
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR);

        return null;
    }

    public function getName()
    {
        if ($this->name === null) {
            return $this->key;
        }

        return $this->name;
    }

    public function getNameDbLog()
    {
        if ($this->nameDbLog === null) {
            $this->nameDbLog = $this->getName();
        }

        return $this->nameDbLog;
    }

    public function getNameTable()
    {
        if ($this->nameTable === null) {
            $this->nameTable = $this->getName();
            $uom = $this->uom;
            if ($uom) {
                $this->nameTable .= "\n(" . $uom . ')';
            }
        }

        return $this->nameTable;
    }

    public function getNameTableSettings()
    {
        if ($this->nameTableSettings !== null) {
            return $this->nameTableSettings;
        }

        $name = $this->getNameTable();
        return strip_tags(str_replace(['<br>', "\n"], ' ', $name));
    }

    public function getSqlSelect()
    {
        $sqlSelect = $this->sqlSelect;
        if ($sqlSelect && is_callable($sqlSelect)) {
            return $sqlSelect($this);
        }

        return $sqlSelect;
    }

    private $evaluatedViewCssClass = null;

    public function getViewCssClass($value, array $data = [], $forTable = false, $excludeOverflow300 = false)
    {
        if ($this->evaluatedViewCssClass !== null) {
            return $this->evaluatedViewCssClass;
        }

        $result = '';
        if ($this->nobr) {
            $result .= ' nobr';
        }

        if ($this->viewMaxWidth300 && !$excludeOverflow300) {
            $result .= ' overflow300';
        }

        $viewCssClass = $this->viewCssClass;
        if ($viewCssClass && is_callable($viewCssClass)) {
            $callResult = trim($viewCssClass($value, $data, $forTable, $this));
            if ($callResult) {
                $result .= " $callResult";
            }
        } else {
            $viewCssClass = trim($viewCssClass);
            if ($viewCssClass) {
                $result .= ' ' . $viewCssClass;
            }
            // В случае когда viewCssClass не является определённой пользователем функцией
            // можем закешировать результат для данного поля раз и навсегда!
            // Чтобы больше не проверять nobr, viewMaxWidth300 и не склеивать по кусочкам.
            $this->evaluatedViewCssClass = trim($result);
        }

        return $result;
    }

    public function getViewTitle($value, array $data = [], $forTable = false)
    {
        $viewTitle = $this->viewTitle;
        if ($viewTitle && is_callable($viewTitle)) {
            $viewTitleText = (string)$viewTitle($value, $data, $forTable, $this);
        } else {
            $viewTitleText = (string)$viewTitle;
        }
        if ($viewTitleText === '' && $this->viewMaxWidth300) {
            $viewTitleText = $value;
        }

        return $viewTitleText;
    }

    public function getViewTagParams($value, array $data = [], $forTable = false)
    {
        $viewTagParams = $this->viewTagParams;
        if ($viewTagParams && is_callable($viewTagParams)) {
            return $viewTagParams($value, $data, $forTable, $this);
        }

        return $viewTagParams;
    }

    protected function getEditCssClass($value, array $data)
    {
        $editCssClass = $this->editCssClass;

        if ($editCssClass && is_callable($editCssClass)) {
            $editCssClass = $editCssClass($value, $data, $this);
        }

        if ($this->editWidth) {
            $editCssClass .= ' input-' . $this->editWidth;
        }

        if ($this->editAlign === 'right') {
            $editCssClass .= ' input-right';
        }

        return $editCssClass;
    }

    protected function getEditTitle($value, array $data)
    {
        $editTitle = $this->editTitle;
        if ($editTitle && is_callable($editTitle)) {
            return $editTitle($value, $data, $this);
        }

        return $editTitle;
    }

    protected function getEditTagParams($value, array $data)
    {
        $editTagParams = $this->editTagParams;
        if ($editTagParams && is_callable($editTagParams)) {
            $editTagParams = $editTagParams($value, $data, $this);
        }

        if ($this->acSource || $this->acSourceJsFunction) {
            // У этого поля заданые параметры для Autocomplete
            $ac = [
                'readonly' => $this->acReadonly,
                'source' => $this->acSource,
                'sourceJsFunction' => $this->acSourceJsFunction,
                'delimChar' => $this->acDelimChar,
                'width' => $this->acWidth,
                'resultFormatterJsFunction' => $this->acResultFormatterJsFunction,
                'onSelectJsFunction' => $this->acOnSelectJsFunction,
            ];
            $editTagParams .= ' data-ac="' . htmlspecialchars(json_encode($ac)) . '"';
        }

        return $editTagParams;
    }

    protected function saveAutocompleteValue($value)
    {
        if (!is_string($value)) {
            return;
        }

        // Сохраняем новое значение из поля Autocomplete в базу, только если нет флага readonly
        // и формат источника это одна таблица из базы (не начинается со знака ? и не содержит запятых внутри)
        if ($value !== ''
            && $this->acSource
            && !$this->acReadonly
            && $this->acSource[0] !== '?'
            && strpos($this->acSource, ',') === false
        ) {

            $formatFunction = $this->acFormatValueOnSaveFunction;

            // Возможно, в $source присутствовал знак |, который разделял имя таблицы и режим поиска like, например
            // list_table|full или list_table|startwith
            $table = explode('|', $this->acSource)[0];
            if ($this->acDelimChar) {
                // В строке может быть несоклько занчений разделённых DelimChar
                $values = explode($this->acDelimChar, $value);
            } else {
                // Если эе в строке было только одно значение - то приведём его к массиву из одного элемента для общности
                $values = [$value];
            }

            foreach ($values as $singleValue) {

                // Удаляем двойные пробелы и пробелы по краям
                $singleValue = trim(str_replace('  ', ' ', $singleValue));
                if ($singleValue === '') {
                    // Пустые значения не сохраняем в базу
                    continue;
                }

                if (is_callable($formatFunction)) {
                    // Задана пользовательская функция форматирования значения - применим её перед сохранением в справочник
                    $singleValue = (string)$formatFunction($singleValue);
                }

                if ($singleValue !== '') {
                    // Сохраняем в справочник только если после пользовательского форматирования вернулось не пустое значение
                    Db::insertUnique($singleValue, $table);
                }
            }

        }
    }

    public function isAutocomplete()
    {
        return $this->acSource || $this->acSourceJsFunction;
    }

    public function getExcelStyle($value, array $data = [], $forTable = false)
    {
        $excelStyle = $this->excelStyle;
        if (is_callable($excelStyle)) {
            return $excelStyle($value, $data, $forTable, $this);
        }

        return $excelStyle;
    }

    public static function parseFromDb($dbValue)
    {
        return $dbValue;
    }

    public function jsonSerialize()
    {
        return [
            'FieldKey' => $this->key,
            'Type' => $this->getType(),
            'Params' => $this->initialParams
        ];
    }
}
