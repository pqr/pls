<?php

namespace Light;

use Light\Module\Core\Core;
use Light\Module\Reportmenu\Reportmenu;

class Link implements \Stringable
{
    /**
     * Обычно адрес ссылки формируется на основе $module и $params,
     * но если задать url напрямую, то он будет иметь приоритет.
     * Удобно для ссылок на внешние ресурсы.
     * @var string
     */
    public $externalUrl;

    public $innerHtml;
    public $module;
    public $title = '';
    public $isBlank = false;
    public $isAjax = false;
    public $isPost = false;
    public $dataPost = [];
    public $hash = '';
    public $isAbsolute = false;
    public $params = [];
    public $cssClass = [];
    public $attributes = [];
    public $filters = [];

    public function __construct(string $module, string $innerHtml = '')
    {
        $this->innerHtml = $innerHtml;

        // Иногда в качестве имени модуля может быть передан полный класс с неймспейсами
        // Например: Basis\Module\Shipment\Shipment
        $this->module = Misc::getModule($module);
    }

    public static function create(?string $module = '', ?string $innerHtml = '')
    {
        return new self((string)$module, (string)$innerHtml);
    }

    public static function userDialog(
        int $userId,
        string $innerHtml,
        string $mailSubject = '',
        string $mailBody = ''
    ): Link {
        $link = Link::create(Core::class, $innerHtml)
            ->proceedStatic('ShowUserDialog')
            ->params([
                'UserID' => $userId,
            ])
            ->ajax();

        if ($mailSubject) {
            $link->params(['MailSubject' => $mailSubject]);
        }
        if ($mailBody) {
            $link->params(['MailBody' => $mailBody]);
        }

        return $link;
    }

    public static function report(string $reportClass, string $innerHtml = '')
    {
        return Link::create(Reportmenu::class, $innerHtml)
            ->params(['class' => Misc::getClass($reportClass)]);
    }

    /**
     * Обычно адрес ссылки формируется на основе $module и $params,
     * но если задать url напрямую, то он будет иметь приоритет.
     * Удобно для ссылок на внешние ресурсы.
     * @return Link
     * @var string $externalUrl
     */
    public function externalUrl(string $externalUrl)
    {
        $this->externalUrl = $externalUrl;
        return $this;
    }

    public function innerHtml(string $innerHtml)
    {
        $this->innerHtml = $innerHtml;
        return $this;
    }

    public function title(string $title)
    {
        $this->title = $title;
        return $this;
    }

    public function blank(bool $isBlank = true)
    {
        $this->isBlank = $isBlank;
        return $this;
    }

    public function ajax(bool $isAjax = true)
    {
        $this->isAjax = $isAjax;
        return $this;
    }

    public function xls()
    {
        $this->params(['xls' => 1]);
        return $this;
    }

    public function post(bool $isPost = true)
    {
        $this->isPost = $isPost;
        return $this;
    }

    public function dataPost(array $dataPost)
    {
        $this->dataPost = array_merge($this->dataPost, $dataPost);
        return $this;
    }

    public function hash(string $hash)
    {
        $this->hash = $hash;
        return $this;
    }

    public function absolute(bool $isAbsolute = true)
    {
        $this->isAbsolute = $isAbsolute;
        return $this;
    }

    public function params(array $params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function proceed(string $funcName)
    {
        $this->params['p'] = $funcName;
        return $this;
    }

    public function proceedStatic(string $funcName)
    {
        $this->params['ps'] = $funcName;
        return $this;
    }

    public function massaction(string $massaction)
    {
        $this->params['p'] = 'massaction';
        $this->params['action'] = $massaction;
        return $this;
    }

    /**
     * Формирует query параметр для ссылки в формате Lines_findid=123
     *
     * @param string $tableName имя таблицы, которую нужно подсветить
     * @param int $findId ID строки, которую нужно подсветить
     * @return Link
     */
    public function findId($tableName, int $findId)
    {
        // В качестве имени таблицы можно передать имя класса Subtable - вырежем из него последнюю часть
        $tableName = Misc::getClass($tableName);
        $param = "{$tableName}_findid";
        $this->params[$param] = $findId;

        return $this;
    }

    /**
     * Формирует query параметр для ссылки в формате Lines_findid=ShipmentID|123+Amount|456
     * См. подробнее как это работает в Table::isFindId()
     *
     * @param string $tableName имя таблицы, которую нужно подсветить
     * @param array $keyVal здесь передаётся массив ключ-значение, которые потом будут преобразованы как в примере в ShipmentID|123+Amount|456
     * @return Link
     */
    public function findIdAssoc(string $tableName, array $keyVal)
    {
        // На всякий случай обернём в вызов Misc::getClass()
        // если сюда был передан полный класс с неймспейсами, то нам нужно взять только последнюю часть - название класса
        $tableName = Misc::getClass($tableName);
        $param = "{$tableName}_findid";
        $values = [];
        foreach ($keyVal as $key => $val) {
            $values[] = "$key|$val";
        }
        $this->params[$param] = implode('+', $values);

        return $this;
    }

    public function addNew($initialData = [])
    {
        $this->params['addnew'] = 1;
        if ($initialData) {
            $this->params['initialData'] = json_encode($initialData);
        }
        return $this;
    }

    public function view(int $id)
    {
        $this->params['id'] = $id;
        return $this;
    }

    public function edit(int $id)
    {
        $this->params['editid'] = $id;
        return $this;
    }

    public function log(int $id)
    {
        $this->params['logid'] = $id;
        return $this;
    }

    public function func(string $func)
    {
        $this->params['func'] = $func;
        return $this;
    }

    public function action(string $actionClass)
    {
        $this->params['action'] = Misc::getClass($actionClass);
        return $this;
    }

    public function viewDialog(int $id = null)
    {
        if ($id) {
            $this->params['id'] = $id;
        }
        $this->params['func'] = 'Dialog';
        $this->isAjax = true;
        return $this;
    }

    public function filePreview(int $id, string $file)
    {
        return $this
            ->view($id)
            ->func('FilePreview')
            ->params(['file' => $file])
            ->blank();
    }

    public function downloadExampleFile(string $file)
    {
        $this->proceedStatic('DownloadExampleFile')
            ->params(['file' => $file]);

        if ($this->innerHtml === '') {
            $this->innerHtml = $file;
        }

        return $this;
    }

    public function filters(array $filters)
    {
        $this->filters = array_merge($this->filters, $filters);
        return $this;
    }

    public function cssClass(string $cssClass)
    {
        $this->cssClass[] = $cssClass;
        return $this;
    }

    public function attrs(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    public function btnFlat()
    {
        $this->cssClass[] = "btn-flat";
        $this->cssClass[] = "waves-effect";
        return $this;
    }

    public function getHref(): string
    {
        if ($this->externalUrl) {

            /**
             * Обычно адрес ссылки формируется на основе $module и $params,
             * но если задать url напрямую, то он будет иметь приоритет.
             * Удобно для ссылок на внешние ресурсы.
             */
            $href = $this->externalUrl;

        } else {

            $href = $this->isAbsolute ? rtrim((string) Config::$localUrl, '/') . '/' : '';
            $href .= "?a=$this->module";
            if ($this->params) {
                $href .= '&' . http_build_query($this->params);
            }

            if ($this->filters && !$this->isPost) {
                $href .= '&filterValues=' . htmlspecialchars(json_encode($this->filters));
            }
        }

        if ($this->hash) {
            $href .= '#' . $this->hash;
        } elseif ($this->isAjax && $this->isPost) {
            $href .= "#ajax-post";
        } elseif ($this->isAjax) {
            $href .= "#ajax";
        } elseif ($this->isPost) {
            $href .= "#post";
        }

        return $href;
    }

    public function getHtml(): string
    {
        $href = $this->getHref();
        $attributesText = '';
        if ($this->attributes) {
            foreach ($this->attributes as $attrKey => $attrVal) {
                $attributesText .= ' ' . $attrKey . '="' . htmlspecialchars((string) $attrVal) . '"';
            }
        }

        if ($this->filters && $this->isPost) {
            $this->dataPost(['filterValues' => json_encode($this->filters)]);
        }

        if ($this->dataPost) {
            $attributesText .= ' data-post="' . htmlspecialchars(json_encode($this->dataPost)) . '"';
        }

        return '<a href="' . $href . '"'
            . ($this->title ? ' title="' . htmlspecialchars((string) $this->title) . '"' : '')
            . ($this->cssClass ? ' class="' . implode(' ', $this->cssClass) . '"' : '')
            . $attributesText
            . ($this->isBlank ? ' target="_blank"' : '')
            . '>'
            . $this->innerHtml
            . '</a>';
    }

    public function __toString(): string
    {
        return $this->innerHtml !== '' ? $this->getHtml() : $this->getHref();
    }

    /**
     * @param string|array|null $currentUrlParams уже существующие urlParams
     * @param string|array|null $additionalParams дополниьельные urlParams, которые нужно добавить у уже существующим
     * @return array
     * @deprecated
     * Добавляет к уже существующим urlParams дополнительные
     */
    public static function mergeUrlParams($currentUrlParams, $additionalParams)
    {
        //Параметры могли быть переданы не как удобный массив ключ=>значение, а как строка для http запроса: ключ1=значение1&ключ2=значение2
        //Для начала переведём в массив
        if (!is_array($currentUrlParams)) {
            parse_str((string) $currentUrlParams, $currentUrlParams);
        }

        if (!is_array($additionalParams)) {
            parse_str((string) $additionalParams, $additionalParams);
        }

        //Склеиваем два массива и возвращаем как массив
        return array_merge($currentUrlParams, $additionalParams);
    }

    /**
     * @deprecated
     */
    public static function moduleList(
        $innerHtml,
        $filterValues = null,
        $urlParams = null,
        $module = null,
        $title = null,
        $tagParams = null,
        $blank = false
    ) {
        if ($filterValues) {
            $filterValuesUrlParam = self::filterValuesUlrParam($filterValues);
            $urlParams ??= [];
            $urlParams = self::mergeUrlParams($urlParams, $filterValuesUrlParam);
        }

        return self::module($innerHtml, $urlParams, $module, $title, $tagParams, $blank);
    }

    /**
     * @deprecated
     */
    public static function moduleListBlank(
        $innerHtml,
        $filterValues = null,
        $urlParams = null,
        $module = null,
        $title = null,
        $tagParams = null
    ) {
        return self::moduleList($innerHtml, $filterValues, $urlParams, $module, $title, $tagParams, true);
    }

    /**
     * @deprecated
     */
    public static function moduleView(
        $innerHtml,
        $id,
        $urlParams = null,
        $module = null,
        $title = null,
        $tagParams = null,
        $blank = false,
        $dialog = false,
        $ajax = false
    ) {
        if ($urlParams) {
            $urlParams = self::mergeUrlParams(['id' => $id], $urlParams);
        } else {
            $urlParams = ['id' => $id];
        }

        if ($dialog) {
            $urlParams['func'] = 'dialog';
        }
        $ajax = $ajax || $dialog;

        return self::module($innerHtml, $urlParams, $module, $title, $tagParams, $blank, $ajax);
    }

    /**
     * @deprecated
     */
    public static function moduleViewBlank(
        $innerHtml,
        $id,
        $urlParams = null,
        $module = null,
        $title = null,
        $tagParams = null
    ) {
        return self::moduleView($innerHtml, $id, $urlParams, $module, $title, $tagParams, true);
    }

    /**
     * @deprecated
     */
    public static function module(
        $innerHtml,
        $urlParams = null,
        $module = null,
        $title = null,
        $tagParams = null,
        $blank = false,
        $ajax = false
    ) {
        $url = self::url($urlParams, $module);
        if ($ajax) {
            $url .= "#ajax";
        }

        if ($title) {
            $title = ' title="' . htmlspecialchars((string) $title) . '"';
        }

        if ($tagParams) {
            $tagParams = ' ' . trim((string) $tagParams);
        }

        if ($blank) {
            $blank = ' target="_blank"';
        }

        return '<a href="' . $url . '"' . $title . $tagParams . $blank . '>' . $innerHtml . '</a>';
    }

    /**
     * @deprecated
     */
    public static function url($urlParams = null, $module = null)
    {
        if ($module === null) {
            $module = Request::getModule();
        }

        if ($urlParams) {
            $urlParams = self::mergeUrlParams(['a' => $module], $urlParams);
        } else {
            $urlParams = ['a' => $module];
        }
        return '?' . http_build_query($urlParams);
    }

    /**
     * @deprecated
     */
    public static function filterValuesUlrParam(array $filterValues)
    {
        return ['filterValues' => json_encode($filterValues)];
    }
}
