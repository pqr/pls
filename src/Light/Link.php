<?php
namespace Light;

class Link
{
    /**
     * Добавляет к уже существующим urlParams дополнительные
     * @param string|array|null $currentUrlParams уже существующие urlParams
     * @param string|array|null $additionalParams дополниьельные urlParams, которые нужно добавить у уже существующим
     * @return array
     */
    public static function mergeUrlParams($currentUrlParams, $additionalParams)
    {
        //Параметры могли быть переданы не как удобный массив ключ=>значение, а как строка для http запроса: ключ1=значение1&ключ2=значение2
        //Для начала переведём в массив
        if (!is_array($currentUrlParams)) {
            parse_str($currentUrlParams, $currentUrlParams);
        }

        if (!is_array($additionalParams)) {
            parse_str($additionalParams, $additionalParams);
        }

        //Склеиваем два массива и возвращаем как массив
        return array_merge($currentUrlParams, $additionalParams);
    }

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
            $urlParams = $urlParams ?? [];
            $urlParams = self::mergeUrlParams($urlParams, ['filterValues' => json_encode($filterValues)]);
        }

        return self::module($innerHtml, $urlParams, $module, $title, $tagParams, $blank);
    }

    public static function moduleView(
        $innerHtml,
        $id,
        $urlParams = null,
        $module = null,
        $title = null,
        $tagParams = null,
        $blank = false
    ) {
        if ($urlParams) {
            $urlParams = self::mergeUrlParams(['id' => $id], $urlParams);
        } else {
            $urlParams = ['id' => $id];
        }
        return self::module($innerHtml, $urlParams, $module, $title, $tagParams, $blank);
    }

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

    public static function moduleEdit(
        $innerHtml,
        $id,
        $urlParams = null,
        $module = null,
        $title = null,
        $tagParams = null,
        $blank = false
    ) {
        if ($urlParams) {
            $urlParams = self::mergeUrlParams(['editid' => $id], $urlParams);
        } else {
            $urlParams = ['editid' => $id];
        }
        return self::module($innerHtml, $urlParams, $module, $title, $tagParams, $blank);
    }

    public static function module(
        $innerHtml,
        $urlParams = null,
        $module = null,
        $title = null,
        $tagParams = null,
        $blank = false
    ) {
        $url = self::url($urlParams, $module);

        if ($title) {
            $title = ' title="' . htmlspecialchars($title) . '"';
        }

        if ($tagParams) {
            $tagParams = ' ' . trim($tagParams);
        }

        if ($blank) {
            $blank = ' target="_blank"';
        }

        return '<a href="' . $url . '"' . $title . $tagParams . $blank . '>' . $innerHtml . '</a>';
    }

    public static function moduleBlank($innerHtml, $urlParams = null, $module = null, $title = null, $tagParams = null)
    {
        return self::module($innerHtml, $urlParams, $module, $title, $tagParams, true);
    }

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
}
