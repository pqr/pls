<?php

namespace Light\Field;

use Light\Config;
use Light\Db;
use Light\Html;
use Light\User;

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
 * @method $this setViewMaxWidth(bool $value)
 * @method $this setEditId(string $value)
 * @method $this setEditName(string $value)
 * @method $this setEditWidth(string $miniSmallMediumDefaultFull)
 * @method $this setEditAlign(string $leftCenterRight)
 * @method $this setEditCssClass($stringOrCallable)
 * @method $this setEditTitle($stringOrCallable)
 * @method $this setEditTagParams($stringOrCallable)
 * @method $this setEditMandatory($booleanOrStringOrCallable)
 * @method $this setEditAccessFunction($callable)
 * @method $this setAccessFunction($callable)
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
 * @method $this setChangeAll(bool $changeAll)
 * @method $this setAjax(bool $ajax)
 * @method $this setNullable(bool $nullable)
 * @method $this setFilterNot(bool $filterNot)
 * @method $this setFilterDoNotReset(bool $filterDoNotReset)
 * @method $this setFilterFieldKeys(string|array $filterFieldKey)
 *
 * @method $this setAcReadonly(bool $value)
 * @method $this setAcSource(string $value)
 * @method $this setAcSourceJsFunction(string $value)
 * @method $this setAcDelimChar(string $value)
 * @method $this setAcWidth(int $value)
 * @method $this setAcResultFormatterJsFunction(string $value)
 * @method $this setAcOnSelectJsFunction(string $value)
 * @method $this setAcFormatValueOnSaveFunction(string $value)
 * @method $this setValidateFunction(callable $function)
 *
 * @method string getTitle()
 * @method int getModes()
 * @method string getDefault()
 * @method int getTableWidthExcel()
 * @method int getTableWidthHtml()
 * @method bool getTotal()
 * @method bool getCopy()
 * @method string getTableOrderBy()
 * @method string getTableOrderByForeignTable()
 * @method string getTableOrderByLikeFilter()
 * @method string getEditId()
 * @method string getEditName()
 * @method string getEditWidth()
 * @method string getEditAlign()
 * @method bool|string|callable getEditMandatory()
 * @method callable getEditAccessFunction()
 * @method callable getAccessFunction()
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
 * @method bool getFilterNot()
 * @method bool getFilterDoNotReset()
 * @method bool getAjax()
 * @method bool getNullable()
 * @method callable getValidateFunction()
 *
 * @method static FieldHtml createHtml(string $fieldKey, array|string $params = [])
 * @method static FieldCheckbox createCheckbox(string $fieldKey, array|string $params = [])
 * @method static FieldCheckboxSet createCheckboxSet(string $fieldKey, array|string $params = [])
 * @method static FieldString createString(string $fieldKey, array|string $params = [])
 * @method static FieldText createText(string $fieldKey, array|string $params = [])
 * @method static FieldDate createDate(string $fieldKey, array|string $params = [])
 * @method static FieldDateTime createDateTime(string $fieldKey, array|string $params = [])
 * @method static FieldColor createColor(string $fieldKey, array|string $params = [])
 * @method static FieldWeek createWeek(string $fieldKey, array|string $params = [])
 * @method static FieldInt createInt(string $fieldKey, array|string $params = [])
 * @method static FieldFloat createFloat(string $fieldKey, array|string $params = [])
 * @method static FieldMoney createMoney(string $fieldKey, array|string $params = [])
 * @method static FieldMoneyWithCurrency createMoneyWithCurrency(string $fieldKey, array|string $params = [])
 * @method static FieldSelect createSelect(string $fieldKey, array|string $params = [])
 * @method static FieldSelectChild createSelectChild(string $fieldKey, array|string $params = [])
 * @method static FieldSelect2Multiple createSelect2Multiple(string $fieldKey, array|string $params = [])
 * @method static FieldSelect2ChildMultiple createSelect2ChildMultiple(string $fieldKey, array|string $params = [])
 * @method static FieldYesNo createYesNo(string $fieldKey, array|string $params = [])
 * @method static FieldMonth createMonth(string $fieldKey, array|string $params = [])
 */
abstract class FieldAbstract implements \JsonSerializable
{
    public const MODE_LIST = 0b000001;
    public const MODE_VIEW = 0b000010;
    public const MODE_EDIT = 0b000100;
    public const MODE_LOG = 0b001000;
    public const MODE_MULTIPLEUPDATE = 0b010000;
    public const MODE_SUBMIT = 0b100000;

    public const MODE_READONLY = 0b000011;
    public const MODE_EDITSUBMIT = 0b100100; // MODE_EDIT + MODE_SUBMIT
    public const MODE_ALL = 0b111111;

    public const TABLEALIGN_LEFT = 'left';
    public const TABLEALIGN_CENTER = 'center';
    public const TABLEALIGN_RIGHT = 'left';

    public const TYPE_HTML = 'Html';

    public const TYPE_CHECKBOX = 'Checkbox';
    public const TYPE_CHECKBOX_SET = 'CheckboxSet';

    public const TYPE_STRING = 'String';

    public const TYPE_TEXT = 'Text';

    public const TYPE_DATE = 'Date';
    public const TYPE_DATE_TIME = 'DateTime';
    public const TYPE_COLOR = 'Color';
    public const TYPE_WEEK = 'Week';

    public const TYPE_INT = 'Int';
    public const TYPE_FLOAT = 'Float';
    public const TYPE_MONEY = 'Money';
    public const TYPE_MONEY_WITH_CURRENCY = 'MoneyWithCurrency';

    public const TYPE_SELECT = 'Select';
    public const TYPE_SELECT_CHILD = 'SelectChild';
    public const TYPE_SELECT2_MULTIPLE = 'Select2Multiple';
    public const TYPE_YES_NO = 'YesNo';
    public const TYPE_MONTH = 'Month';

    public const PARAM_AC_READONLY = 'acReadonly';
    public const PARAM_AC_SOURCE = 'acSource';
    public const PARAM_AC_SOURCEJSFUNCTION = 'acSourceJsFunction';
    public const PARAM_AC_DELIMCHAR = 'acDelimChar';
    public const PARAM_AC_WIDTH = 'acWidth';
    public const PARAM_AC_RESULTFORMATTERJSFUNCTION = 'acResultFormatterJsFunction';
    public const PARAM_AC_ONSELECTJSFUNCTION = 'acOnSelectJsFunction';
    public const PARAM_AC_FORMATVALUEONSAVEFUNCTION = 'acFormatValueOnSaveFunction';

    public const PARAM_TEXTTRUNCATE = 'textTruncate';
    public const TEXTTRUNCATE_FULL = 'full';
    public const TEXTTRUNCATE_PART = 'part';
    public const TEXTTRUNCATE_LIST = 'list';

    public const FILTER_EQUAL = '=';
    public const FILTER_LIKE = 'LIKE';

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
    protected $modes = self::MODE_ALL;

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
     * @var string Можно указать внешнюю таблицу в которой будет использовано поле Name для сортировки
     * Например, для поля CustomerAccountID можно указать следующую настройку:
     * 'tableOrderByForeignTable' => 'customeraccount'
     * В SQL запрос в этом случае попадёт:
     * ORDER BY (SELECT Name FROM customeraccount WHERE ID = CustomerAccountID)
     */
    public $tableOrderByForeignTable;

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
     * Ограничить ли ширину текста с помощью css класса overflow150 overflow300?
     * Если да, то в title автоматически будет добавлено полное значение текущей ячейки {value}
     * @var bool
     */
    public $viewMaxWidth;

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
     * @var string|callable всплывающая подсказка title, которая будет добавлена внутрь тега input
     */
    public $editTitle;

    /**
     * @var string|callable
     */
    public $editTagParams;

    /**
     * @var bool|string|callable используется в ModuleAbstract::validateMandatory()
     * Можно задать в виде функции function($value, array $dataFromRequest, ObjAbstract $currentObjData, FieldAbstract $field): boolean
     */
    public $editMandatory = false;

    /**
     * @var callable function(array $data): bool
     *
     * По умолчанию функция не определена, т.е. она не вызывается, а доступ на чтение поля определется
     * исключительно полем modes. Но поле modes задаётся вне зависимости от данных текущего редактируемого объекта.
     * Чтобы ограничить права доступа на основе данных текущего объекта, необходимо задать accessFunction.
     *
     * Работает следующим образом:
     * Если поле modes содерит F::MODE_VIEW (в режиме View) или F::MODE_LIST (в режиме List) и accessFunction
     *  функция определена, то она вызывается
     * с полным набором данных текущего объекта, чтобы определить, есть ли у текущего пользователя права просмотра
     *
     * В режиме List, есть прав на просмотр поля нет, то значение поля ($value) присваивается равным null, чтобы
     * в таблице ячейка была пустой.
     * В режиме View, если используется вывод шаблона через t::c('FieldKey'), поле вообще не отображается на экране.
     */
    public $accessFunction;

    /**
     * @var callable function(array $data): bool
     *
     * По умолчанию функция не определена, т.е. она не вызывается, а доступ на редактирование поля определется
     * исключительно полем modes. Но поле modes задаётся вне зависимости от данных текущего редактируемого объекта.
     * Чтобы ограничить права доступа на основе данных текущего объекта, необходимо задать editAccessFunction.
     *
     * Работает следующим образом:
     * Если поле modes содерит F::MODE_EDIT | F::MODE_SUBMIT и editAccessFunction функция определена, то она вызывается
     * с полным набором данных текущего объекта, чтобы определить, есть ли у текущего пользователя права редактирования
     */
    public $editAccessFunction;

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
     * Имеет ли поле возможность ajax редактирования
     * @var callable($value, array $data, $forTable)
     */
    public $groupconcatIsSpecialAjaxibleFunction;

    /**
     * Специальная функция ajaх редактирования
     * @var callable($value, array $data, $forTable)
     */
    public $groupconcatSpecialAjaxSubmitFunction;

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
     * @var callable($value, array $data, $outHtml, $field, $idPrefix)
     */
    public $getHtmlInputFunction;

    /**
     * @var callable ($request, $value) внутри обязательно проверять if ($value !== null)
     */
    public $getDataFromRequestFunction;

    /**
     * @var callable (array $dataFromRequest, F $field) - возвращает null, если всё впорядке, либо string сообщение об ошибке
     */
    public $validateFunction;

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
     * Шаблон SQL фрагмента WHERE, плейсхолдер {value} "table.MyField LIKE '{value}%'"
     * или работает как метод $sqlWhere($value, array $filtersValues, $sql, $field)
     * @var string|callable
     */
    public $sqlWhere;

    /**
     * @var callable($value) предварительное форматирование перед  allowSqlWhereValue и getSqlWhereInner
     */
    public $sqlWhereFormatFunction;

    /**
     * Имя поля, которое подставлять в стандартное sqlWhere условие {fieldKey} = '{value}'
     * По умолчанию будет использовано значение $this->key
     * @var string|array
     */
    public $filterFieldKeys;

    /**
     * Как формировать условие поиска? Чёткое равенство или LIKE '%{value}%'?
     * @var string
     */
    public $filterOperator = self::FILTER_EQUAL;

    /**
     * Нужно ли воспринимать данное поле как фильтр с возможностью ввода нескольких значений через запятую или перевод строки?
     * @var bool
     */
    public $filterMany = false;

    /**
     * Если это filterMany и задана некая длина filterManyLength, то при совпадении длины поискового слова
     * будет использован оператор раветснва
     * Пример с номером контейнера, для котого задана длина filterManyLength=11
     * Если ищем по полному номеру контейнера, то в итоговом sql будет сравнение ContainerName='MSKU1234567'
     * В противном случае сработает поиск по подстроке ContainerName LIKE '%1234%'
     *
     * Если длина вообще не задана, то будет использован filterOperator
     * @var null|int
     */
    public $filterManyLength;

    /**
     * Ожидается, что значение числовое, в этом случае sql запрос будет поддерживать поиск по интервалу
     * номеров, например 1000-2000
     * @var bool
     */
    public $filterManyNumeric = false;

    /**
     * @var bool добавить ли к фильтру галочку "исключая"?
     */
    public $filterNot = false;

    /**
     * @var bool сбрасывать ли значение фильтра при нажатии кнопки RESET (в js коде)
     */
    public $filterDoNotReset = false;

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

    /**
     * @var string имя JavaScript функции и небольшой фрагмент javascript кода, который запустить при успешном обновлении
     * поля через ajax. Этот значение будет записано в тег ссылки как атрибут data-ajax-success-handler и запущено в AjaxFields.ts
     * Пример: 'ajaxSuccessHandler' => 'Basis.doSomething'
     * После успешного ajax обновления в браузере будет выполнен код `Basis.doSomething($link)`
     */
    public $ajaxSuccessHandler;

    public $nullable = false;

    public $initialParams = [];

    /** @var string $typeCache кеширующее поле для метода getType() */
    private $typeCache;

    final public function __construct(public $key, array $params = [])
    {
        if (isset($params['key'])) {
            unset($params['key']);
        }

        $this->initialParams = $params;
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

        if (str_starts_with($this->key, 'Name')) {
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

        $class = static::class;
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
        // сохраним также указанное пользователем значение в массиве initialParams
        $this->initialParams[$name] = $value;
        return $this;
    }

    /**
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
            $this->typeCache = substr(static::class, strlen('\\Light\\Field\\Field') - 1);
        }

        return $this->typeCache;
    }

    final public function hasMode(int $testMode)
    {
        return $this->modes & $testMode;
    }

    /**
     * Вызывает $this->accessFunction (если задана), чтобы проверить, есть ли у пользователя доступ на просмотр
     * этого поля на основе данных текущей записи (array $data)
     * @return bool
     */
    final public function checkAccessFunction(array $data)
    {
        $accessFunction = $this->accessFunction;
        if (\is_callable($accessFunction)) {
            // Для этого поля определена особая пользовательская фунция accessFunction - вызовем её
            return $accessFunction($data);
        }

        return true;
    }

    /**
     * Вызывает $this->editAccessFunction (если задана), чтобы проверить, есть ли у пользователя доступ на редактирование
     * этого поля на основе данных текущей записи (array $data)
     * @return bool
     */
    final public function checkEditAccessFunction(array $data)
    {
        $editAccessFunction = $this->editAccessFunction;
        if (\is_callable($editAccessFunction)) {
            // Для этого поля определена особая пользовательская фунция editAccessFunction - вызовем её
            return $editAccessFunction($data);
        }

        return true;
    }

    final public function addNamePrefix($prefix): void
    {
        if (!$prefix) {
            return;
        }

        //Используем временную анонимную функцию, чтобы добавить префиксы к четырём свойствам
        $add = function ($currentValue) use ($prefix) {
            //Если текущее значение поля не начинается с этого префикса, добавим префикс в начало
            if (!str_starts_with($currentValue, (string) $prefix)) {
                return $prefix . "\n" . $currentValue;
            }
            return $currentValue;
        };

        $this->name = $add($this->name);
        $this->nameDbLog = $add($this->getNameDbLog());
        $this->nameTable = $add($this->getNameTable());
        $this->nameTableSettings = $add($this->getNameTableSettings());

        // Отдельный разговор с title
        if ($this->title) {
            $this->title = $add($this->title);
        }
    }

    public function getHtml($value, array $data = [], $forTable = false, $forLog = false)
    {
        if (!$this->checkAccessFunction($data)) {
            return '';
        }

        if ($this->groupconcat && $value) {

            $lines = explode("\n", $value);
            // Если число линий больше 1 - будем рекурсивно обрабатывать каждую линию
            $groupconcatLinesCount = count($lines);

            // Пройдём по всем линия и обернём их в $this->getHtmlOneLine(), получив на выходе $outLines
            $groupconcatRemoveEmpty = $this->groupconcatRemoveEmpty;
            $groupconcatNobr = $this->groupconcatNobr;
            $outLines = [];
            foreach ($lines as $lineValue) {

                $html = $this->getHtmlOneLine($lineValue, $data, $forTable, $groupconcatLinesCount, $forLog);

                // Проверим, если есть специальный флаг пропускать пустые линии и эта линия действительно пустая - пропустим её!
                if ($groupconcatRemoveEmpty && trim(strip_tags($html)) === '') {
                    // Не записываем эту строку в $outLines
                    continue;
                }

                if ($html === '') {
                    $html = '&nbsp;'; // Принудительно вставим пробел, чтобы строка не "схлопнулась" - это важно для groupconcat
                }

                if ($groupconcatNobr) {
                    $html = '<span class="nobr">' . $html . '</span>';
                }

                $outLines[] = $html;
            }

            //Соберём отдельно обработанные строки обратно
            if ($this->groupconcatTruncate) {
                $outHtml = Html::getTruncatedList($outLines, $this->getName(), $this->groupconcatTruncateMaxElements);
            } else {
                $outHtml = implode('<br>', $outLines);
            }

            return (string)$outHtml;

        }

        return (string)$this->getHtmlOneLine($value, $data, $forTable, null, $forLog);
    }

    private function getHtmlOneLine($value, array $data, $forTable, $groupconcatLinesCount = null, $forLog = false)
    {
        // Сначала сформируем HTML представление этого значения стандартными средсвами класса
        $outHtml = $this->getHtmlInner($value, $data, $forTable);

        if ($this->textTruncate) {
            //Обрезаем текст, если его слишком много и указана специальная опция в настройках поля
            switch ($this->textTruncate) {
                case self::TEXTTRUNCATE_LIST:
                    $outHtml = Html::getTruncatedList(explode('<br>', $outHtml), $this->getName());
                    break;
                case self::TEXTTRUNCATE_FULL:
                    $outHtml = Html::getTruncatedText($outHtml);
                    break;
                case self::TEXTTRUNCATE_PART:
                    $outHtml = Html::getPartlyTruncatedText($outHtml);
                    break;
            }
        }

        // Затем, если передана специальная функцяи обёртка getHtmlFunction, передадим результат ей
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
        if ($value !== '' && (!$forTable || $groupconcatLinesCount > 1)) {
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
     * @param bool $forTable
     * @param string $outHtml
     * @return mixed
     */
    protected function ajaxify(mixed $value, array $data, $forTable, $outHtml)
    {
        if ($this->groupconcat) {
            if ($this->groupconcatIsSpecialAjaxibleFunction && is_callable($this->groupconcatIsSpecialAjaxibleFunction)) {
                $isSpecialAjaxibleFunction = $this->groupconcatIsSpecialAjaxibleFunction;
                $isAjaxible = $isSpecialAjaxibleFunction($value, $data, $forTable);
                if (!$isAjaxible) {
                    return $outHtml;
                }
            } else {
                return $outHtml;
            }
        }

        if (!isset($data['ID']) || !$data['ID']) {
            return $outHtml;
        }

        if (!$this->hasMode(self::MODE_EDIT)) {
            return $outHtml;
        }

        $ajaxSuccessHandler = $this->ajaxSuccessHandler ? ' data-ajax-success-handler="' . $this->ajaxSuccessHandler . '"' : '';

        return '<a href="#" class="ajax-input" data-id="' . $data['ID'] . '" data-fieldkey="' . htmlspecialchars($this->key) . '"' . $ajaxSuccessHandler . '>' . $outHtml . '</a>';
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
        if (!$this->checkAccessFunction($data)) {
            return '';
        }

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

    public function getCalculatedValue($value, array $data = [], $forTable = false)
    {
        if (!$this->calcFunction) {
            return $value;
        }

        $calcFunction = $this->calcFunction;

        if ($this->groupconcat && str_contains($value, "\n")) {
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
        if (!$this->checkEditAccessFunction($data)) {
            return '';
        }

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
            'editName' => $this->editName,
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

        // Сохраним введённое значение в базуе, если это поле поддерживает виджет Autocomplete
        $this->saveAutocompleteValue($value);

        if ($this->nullable) {
            $value = $this->convertToNullIfNeeded($value);
        }

        return $value;
    }

    protected function convertToNullIfNeeded($value)
    {
        if ($value === '' || (is_numeric($value) && $value == 0)) {
            return null;
        }

        return $value;
    }

    protected function getDataFromRequestInner(array $request)
    {
        if (array_key_exists($this->key, $request)) {

            $value = $request[$this->key];

            if ($this->upper && is_string($value)) {
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
    protected function getSqlWhereInner($value): string
    {
        if ($value === null) {
            return "{fieldKey} IS NULL";
        }

        if ($this->filterMany) {
            // Приведём параметры к классической функции Db::getSqlWhereSeparatedByComa();
            $normalStrLen = $this->filterOperator === self::FILTER_LIKE ? 0 : -1;
            $fullLike = $this->filterOperator === self::FILTER_LIKE;
            if ($this->filterManyNumeric) {
                $normalStrLen = -1; // ставим -1, чтобы включить строгое равенство
                $fullLike = false;
            }
            if ($this->filterManyLength) {
                $normalStrLen = $this->filterManyLength;
                $fullLike = true;
            }
            $isIntField = $this->filterManyNumeric;
            return Db::getSqlWhereSeparatedByComa($value, '{fieldKey}', $normalStrLen, $fullLike, $isIntField, '0');
        }

        if ($this->filterOperator === self::FILTER_LIKE) {
            return "{fieldKey} LIKE '%{value}%'";
        }

        return "{fieldKey} = '{value}'";
    }

    protected function getFilterFieldKeys(): array
    {
        if (is_array($this->filterFieldKeys)) {
            return $this->filterFieldKeys;
        }
        if (is_string($this->filterFieldKeys)) {
            return [$this->filterFieldKeys];
        }
        return [$this->key];
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

                $sqlWhere = str_replace('{value}', Db::esc($value), $sqlWhere);
                // В параметре filterFieldKeys пользователь мог задать несколько ключей по которым искать
                // нужно перебрать их все в цикле и сформировать OR запрос.
                $filterFieldKeys = $this->getFilterFieldKeys();
                $or = [];
                foreach ($filterFieldKeys as $filterFieldKey) {
                    $or[] = str_replace('{fieldKey}', Db::quote($filterFieldKey), $sqlWhere);
                }
                $or = array_unique($or);
                $sqlWhere = '((' . implode(') OR (', $or) . '))';

                // Проверим, возможно существует фильтр с профиксом 'Not' - это галачка, которая включает отрицание
                // текущего фильтра. Если такая галочка проставлена, то нам нужно сделать отрицание текущего фильтра
                $notFilterKey = 'Not' . $this->key;
                if ($this->filterNot && !empty($filtersValues[$notFilterKey])) {
                    $sqlWhere = "NOT ($sqlWhere)";
                }
            }
        }

        return $sqlWhere;
    }

    /**
     * @static
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
     * @return null|FieldAbstract
     */
    public static function __callStatic($method, array $args)
    {
        if (str_starts_with($method, 'create')) {
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

        $class = self::class;
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
            $this->nameDbLog = strip_tags($this->getName());
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

    public function getEvalueatedSqlSelect(string $table = null): string
    {
        $sqlSelect = $this->getSqlSelect();
        if (!$sqlSelect && $sqlSelect !== null) {
            // Задан какое-то пустое значение sqlSelect (пустая строка или false), но не null
            // это значит текущее поле не должно выбираться из базы
            return '';
        }

        if ($sqlSelect) {
            // У поля был какой-то особый способ загрузки из базы - используем его
            return '(' . $sqlSelect . ') AS ' . Db::quote($this->key);
        }

        // Если параметр совсем не инициализирован, по умолчанию считаем, что нужно просто вывести поле как $fieldKey
        // Альтернативный варинат: пользователь может указать sqlSelect = false, тогда поле не будет
        // включено в SQL запрос
        $sqlSelect = Db::quote($this->key);
        if ($table) {
            $sqlSelect = Db::quote($table) . '.' . $sqlSelect;
        }
        return $sqlSelect;
    }

    private $evaluatedViewCssClass = null;

    public function getViewCssClass($value, array $data = [], $forTable = false, $excludeViewMaxWidth = false)
    {
        if ($this->evaluatedViewCssClass !== null) {
            return $this->evaluatedViewCssClass;
        }

        $result = '';
        if ($this->nobr) {
            $result .= ' nobr';
        }

        if ($this->viewMaxWidth && !$excludeViewMaxWidth) {
            $result .= ' overflow' . $this->viewMaxWidth;
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
            // Чтобы больше не проверять nobr, viewMaxWidth и не склеивать по кусочкам.
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
        if ($viewTitleText === '' && $this->viewMaxWidth) {
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
            && !str_contains($this->acSource, ',')
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
            'Params' => $this->initialParams,
        ];
    }

    public static function empty($value): bool
    {
        return !$value;
    }

    public static function notEmpty($value): bool
    {
        return !static::empty($value);
    }

    public static function fisrtNotEmpty(...$values): ?string
    {
        foreach ($values as $value) {
            if (static::notEmpty($value)) {
                return $value;
            }
        }

        return null;
    }

    public static function modesReadonlyIfNoAccess(string $specialAccess, $defaultModes = self::MODE_ALL): int
    {
        return User::hasSpecialAccess($specialAccess) ? $defaultModes : self::MODE_READONLY;
    }

    public static function modesNoneIfNoAccess(string $specialAccess, $defaultModes = self::MODE_ALL): int
    {
        return User::hasSpecialAccess($specialAccess) ? $defaultModes : 0;
    }

    public function validate(array $dataFromRequest, array $currentObjData): ?string
    {
        $value = $dataFromRequest[$this->key] ?? '';
        $validateFunction = $this->validateFunction;
        if (is_callable($validateFunction)) {
            $errMsg = $validateFunction($value, $dataFromRequest, $currentObjData, $this);
            if ($errMsg !== null) {
                return $errMsg;
            }
        }

        if (!$this->editMandatory) {
            // Поле не является обязательным
            return null;
        }

        if (!static::empty($value)) {
            // Значение поля заполнено, нет смысла проверять дальше
            return null;
        }

        $editMandatory = $this->editMandatory;
        if (is_callable($editMandatory)) {
            // Пользователь мог задать функцию, которая должна вернуть boolean | string чтобы обозначить
            // является ли это поле в итоге обязательным?
            $editMandatory = $editMandatory($value, $dataFromRequest, $currentObjData, $this);
            if (!$editMandatory) {
                // Поле не является обязательным
                return null;
            }
        }

        // Стандартные сообщения для пользователя могут быть заданы внтури параметра editMandatory
        if (is_string($editMandatory)) {
            $msg = $editMandatory;
        } else {
            $defaultMsg = Config::$lang === 'ru' ? 'Заполните поле' : 'Fill';
            $msg = "$defaultMsg {$this->getName()}";
        }

        // Первую букву сообщения сделаем заглавной, чтобы придерживаться единого стиля
        $firstChar = mb_substr($msg, 0, 1);
        $firstChar = mb_strtoupper($firstChar);
        $msg = $firstChar . mb_substr($msg, 1);

        return $msg;
    }
}
