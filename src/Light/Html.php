<?php

namespace Light;

class Html
{
    /**
     * Rewritten by Nathan Codding - Feb 6, 2001.
     * - Goes through the given string, and replaces xxxx://yyyy with an HTML <a> tag linking
     *         to that URL
     * - Goes through the given string, and replaces www.xxxx.yyyy[zzzz] with an HTML <a> tag linking
     *         to http://www.xxxx.yyyy[/zzzz]
     * - Goes through the given string, and replaces xxxx@yyyy with an HTML mailto: tag linking
     *                to that email address
     * - Only matches these 2 patterns either after a space, or at the beginning of a line
     *
     * Notes: the email one might get annoying - it's easy to make it more restrictive, though.. maybe
     * have it require something like xxxx@yyyy.zzzz or such. We'll see.
     * @param $text
     * @return mixed|string
     */
    public static function clickable($text)
    {

        // pad it with a space so we can match things at the start of the 1st line.
        $ret = ' ' . $text;

        // matches an "xxxx://yyyy" URL at the start of a line, or after a space.
        // xxxx can only be alpha characters.
        // yyyy is anything up to the first space, newline, comma, double quote or <
        /** @noinspection NotOptimalRegularExpressionsInspection */
        $ret = preg_replace(
            "#(^|[\n ])([\w]+?://[^ \"\n\r\t<]*)#is",
            "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>",
            $ret
        );

        // matches a "www|ftp.xxxx.yyyy[/zzzz]" kinda lazy URL thing
        // Must contain at least 2 dots. xxxx contains either alphanum, or "-"
        // zzzz is optional.. will contain everything up to the first space, newline,
        // comma, double quote or <.
        /** @noinspection NotOptimalRegularExpressionsInspection */
        $ret =
            preg_replace(
                "#(^|[\n ])((www|ftp)\.[^ \"\t\n\r<]*)#is",
                "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>",
                (string) $ret
            );

        // matches an email@domain type address at the start of a line, or after a space.
        // Note: Only the followed chars are valid; alphanums, "-", "_" and or ".".
        $ret = preg_replace(
            "#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i",
            "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>",
            (string) $ret
        );

        // Remove our padding..
        $ret = substr((string) $ret, 1);

        return $ret;
    }

    public static function getArrayInColumns($dataArr, $columns, $minRowsPerCol = 4, $cssClass = '')
    {
        $countRows = count($dataArr);
        $rowsPerCol = (int)($countRows / $columns);
        if ($countRows % $columns !== 0) {
            $rowsPerCol += 1;
        }

        if ($rowsPerCol < $minRowsPerCol) {
            $rowsPerCol = $minRowsPerCol;
        }

        if ($cssClass != '') {
            $cssClass = ' class="' . $cssClass . '"';
        }
        $outHtml = '<table' . $cssClass . '><tr>';
        $max = count($dataArr);
        for ($i = 0; $i < $max; $i += $rowsPerCol) {
            $colData = array_slice($dataArr, $i, $rowsPerCol);
            $outHtml .= '<td>' . implode('<br>', $colData) . '</td>';
        }
        $outHtml .= '</tr></table>';

        return $outHtml;
    }

    /**
     * Возвращает html код ссылки подчёркнутой пунктиром при клике на которую отображается или скрывается дополнительный текст
     * @param string $linkHtml текст ссылки
     * @param string $hiddenHtml содержимое отобржаемого/скрываемого блока
     * @return string
     */
    public static function showHide($linkHtml, $hiddenHtml)
    {
        if (str_contains($hiddenHtml, 'truncated-full')) {
            // Если переданный текст уже содержит html код для скрытия-отображения, то второй раз не оборачиваем (актуально для вывода логов)
            return $hiddenHtml;
        }
        $outHtml = '<a class="inner showhide" href="#">' . $linkHtml . '</a>'
            . '<div class="truncated-full display-none">' . $hiddenHtml . '</div>';

        return $outHtml;
    }


    public static function getTruncatedText($text, $maxlength = 30)
    {
        if (str_contains((string) $text, 'truncated-full')) {
            // Если переданный текст уже содержит html код для скрытия-отображения, то второй раз не оборачиваем (актуально для вывода логов)
            return $text;
        }
        if (mb_strlen(strip_tags((string) $text), 'UTF-8') > $maxlength) {
            $showLabel = Config::$lang === 'ru' ? 'Показать' : 'Show';
            $text = '<a class="inner showhide" href="#">' . $showLabel . '</a>'
                . '<div class="truncated-full display-none">' . $text . '</div>';
        }
        return $text;
    }

    public static function getPartlyTruncatedText($text, $lettersShown = 200)
    {
        if (str_contains((string) $text, 'truncated-full')) {
            // Если переданный текст уже содержит html код для скрытия-отображения, то второй раз не оборачиваем (актуально для вывода логов)
            return $text;
        }

        $shownText = mb_substr((string) $text, 0, $lettersShown, 'UTF-8');
        $lastOpenTagsPosition = strrpos($shownText, '<');
        $lastClosedTagsPosition = strrpos($shownText, '>');

        if ($lastOpenTagsPosition !== false &&
            $lastClosedTagsPosition !== false &&
            $lastOpenTagsPosition > $lastClosedTagsPosition
        ) {
            $shownText = substr($shownText, 0, $lastOpenTagsPosition);
        }

        if (mb_strlen(strip_tags((string) $text), 'UTF-8') > $lettersShown * 2) {
            $text = '<span class="truncated-part">' . $shownText . ' ...</span>'
                . '<div class="truncated-full display-none">' . $text . '</div>'
                . ' <a class="inner showhide" href="#">Show</a>';
        }

        return $text;
    }

    public static function getTruncatedList(array $lines, string $label, int $maxElements = 4): string
    {
        $outHtml = implode('<br>', $lines);
        if (str_contains($outHtml, 'truncated-full')) {
            // Если переданный текст уже содержит html код для скрытия-отображения, то второй раз не оборачиваем (актуально для вывода логов)
            return $outHtml;
        }

        $count = count($lines);
        if ($count > $maxElements) {
            $outHtml = '<a class="inner showhide" href="#">'
                . $label . ':&nbsp;<strong>' . $count . '</strong>'
                . '</a>'
                . '<div class="truncated-full display-none">' . $outHtml . '</div>';
        }

        return $outHtml;
    }

    public static function progress()
    {
        return '<div class="progress visibility-hidden"></div>';
    }

}
