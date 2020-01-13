<?php


namespace Light;


class Translate
{
    const OPPOSITE_LETTERS = [
        ["q", "й"],
        ["w", "ц"],
        ["e", "у"],
        ["r", "к"],
        ["t", "е"],
        ["y", "н"],
        ["u", "г"],
        ["i", "ш"],
        ["o", "щ"],
        ["p", "з"],
        ["[", "х"],
        ["]", "ъ"],
        ["\\", "ё"],
        ["a", "ф"],
        ["s", "ы"],
        ["d", "в"],
        ["f", "а"],
        ["g", "п"],
        ["h", "р"],
        ["j", "о"],
        ["k", "л"],
        ["l", "д"],
        [";", "ж"],
        ["z", "я"],
        ["x", "ч"],
        ["c", "с"],
        ["v", "м"],
        ["b", "и"],
        ["n", "т"],
        ["m", "ь"],
        [",", "б"],
        [".", "ю"],
    ];

    private static $_cmu_dict = null;
    private static $_countries = null;


    /**
     * Перевод через Яндекс.Переводчик API
     * https://tech.yandex.ru/translate/doc/dg/reference/translate-docpage/
     * @param string $text
     * @param string $lang
     * @return null|string
     */
    public static function translateByYandex(string $text, string $lang = 'ru-en'): ?string
    {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query(['text' => $text]),
            ],
        ];

        $context = stream_context_create($opts);

        $url = 'https://translate.yandex.net/api/v1.5/tr.json/translate?' . http_build_query([
                'key' => Config::$yandexTranslateApiKey,
                'lang' => $lang,
            ]);

        $result = file_get_contents($url, false, $context);

        $result = json_decode($result, true);

        if (isset($result['text'])) {
            return reset($result['text']);
        }

        return null;
    }

    public static function oppositeString(string $str): string
    {
        if ($str === '') {
            return '';
        }

        static $oppositeLettersHash;
        if (!$oppositeLettersHash) {
            $enLetters = array_column(self::OPPOSITE_LETTERS, 0);
            $ruLetters = array_column(self::OPPOSITE_LETTERS, 1);
            // Создадим хэщ таблицу с буквами en->ru
            $oppositeLettersHash = array_combine($enLetters, $ruLetters);
            // Добавим к этой хэш таблице буквы ru->en
            $oppositeLettersHash += array_combine($ruLetters, $enLetters);
        }

        $out = '';
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $char = mb_substr($str, $i, 1);
            $out .= $oppositeLettersHash[$char] ?? $char;
        }

        return $out;
    }

    public static function smartPhoneticTranslate($text, $countryCheck = false)
    {
        require_once __DIR__ . '/External/CMUDict.class.php';
        if (self::$_cmu_dict === null) {
            self::$_cmu_dict = \CMUDict::get();
        }

        if ($countryCheck && self::$_countries === null) {
            $dataCountries = Db::getAll("SELECT Name, NameEn FROM list_country");
            self::$_countries['en'] = array_column($dataCountries, 'NameEn');
            self::$_countries['ru'] = array_column($dataCountries, 'Name');
        }

        $words = preg_split("/([^A-Za-z+])/", $text, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $translates = [];

        foreach ($words as $word) {
            $translate = $word;
            $charWord = preg_replace("/[^A-Za-z]/", '', $word);
            if (!empty($charWord)) {
                $countryReplaceFlag = 0;
                if ($countryCheck) {
                    // попробуем сначала перевести с помощью справочника стран
                    $translate = str_ireplace(self::$_countries['en'], self::$_countries['ru'], $charWord,
                        $countryReplaceFlag);
                }
                if (!$countryReplaceFlag) {
                    $translate = self::$_cmu_dict->getRusTranscription($charWord);
                    if (empty($translate)) {
                        $translate = self::latToRus($word);
                    }
                }
                $translate = mb_strtoupper($translate);
            }
            $translates[$word] = $translate;
        }

        $stringWords = [];

        foreach ($words as $word) {
            $charWord = preg_replace("/[^A-Za-z0-9?! ]/", '', $word);
            $stringWords[] = str_replace($charWord, $translates[$word], $word);
        }

        return implode("", $stringWords);
    }

    public static function currencyWordsRu(string $currencyIso, $amount = 0): string
    {
        $amount = (int)$amount;
        switch ($currencyIso) {
            case 'RUB':
                return self::morph($amount, 'рубль', 'рубля', 'рублей');
            case 'USD':
                return self::morph($amount, 'доллар США', 'доллара США', 'долларов США');
            case 'EUR':
                return 'евро';
        }

        return '';
    }

    public static function centWordsRu(string $currencyIso, $cents): string
    {
        $cents = (int)$cents;
        switch ($currencyIso) {
            case 'RUB':
                return self::morph($cents, 'копейка', 'копейки', 'копеек');
            case 'USD':
            case 'EUR':
                return self::morph($cents, 'цент', 'цента', 'центов');
        }

        return '';
    }

    /**
     * @desc Возвращает русское название месяца по его номеру
     * @param int $number номер месяца от 1 до 12
     * @param bool $genitive если установлен в true, то название месяца будет в родительном падеже
     * @return string
     */
    public static function monthRu($number, $genitive = false)
    {
        if ($number < 1 || $number > 12) {
            return '';
        }

        $index = $number - 1;

        $start = [
            "Январ",
            "Феврал",
            "Март",
            "Апрел",
            "Ма",
            "Июн",
            "Июл",
            "Август",
            "Сентябр",
            "Октябр",
            "Ноябр",
            "Декабр",
        ];
        $end = ["ь", "ь", "", "ь", "й", "ь", "ь", "", "ь", "ь", "ь", "ь"];
        $endGen = ["я", "я", "а", "я", "я", "я", "я", "а", "я", "я", "я", "я"];

        return $start[$index] . ($genitive ? $endGen[$index] : $end[$index]);
    }

    public static function monthYearRu(string $monthAsDate, $short = false)
    {
        $monthNo = (int)substr($monthAsDate, 5, 2);
        $monthText = Translate::monthRu($monthNo);
        if (!$monthText) {
            return $monthText;
        }

        // Если получили не пустой месяц, то добавим и год
        $year = substr($monthAsDate, 0, 4);

        if ($short) {
            $monthTextShort = mb_substr($monthText, 0, 3);
            $yearShort = substr($year, 2);
            return "$monthTextShort'$yearShort";
        }

        return "$monthText $year";
    }

    public static function rusToLat($string)
    {
        $rus = ['ё', 'ж', 'ц', 'ч', 'ш', 'щ', 'ю', 'я', 'Ё', 'Ж', 'Ц', 'Ч', 'Ш', 'Щ', 'Ю', 'Я'];
        $lat = ['yo', 'zh', 'tc', 'ch', 'sh', 'sh', 'yu', 'ya', 'YO', 'ZH', 'TC', 'CH', 'SH', 'SH', 'YU', 'YA'];
        $string = str_replace($rus, $lat, $string);
        $string = strtr($string,
            "АБВГДЕЗИЙКЛМНОПРСТУФХЪЫЬЭабвгдезийклмнопрстуфхъыьэ",
            "ABVGDEZIJKLMNOPRSTUFH_I_Eabvgdezijklmnoprstufh_i_e");
        return ($string);
    }

    public static function latToRus($string)
    {
        $cyr = [
            "К",
            "В",
            "в",
            "Щ",
            "Ш",
            "Ч",
            "К",
            "Ю",
            "Я",
            "Ж",
            "А",
            "Б",
            "В",
            "Г",
            "Д",
            "Е",
            "Ё",
            "З",
            "И",
            "Й",
            "К",
            "Л",
            "М",
            "Н",
            "О",
            "П",
            "Р",
            "С",
            "Т",
            "У",
            "Ф",
            "Х",
            "Ь",
            "Ы",
            "Ъ",
            "Э",
            "Є",
            "Ї",
            "І",
            "щ",
            "ш",
            "ч",
            "к",
            "ю",
            "я",
            "ж",
            "а",
            "б",
            "в",
            "г",
            "д",
            "е",
            "ё",
            "з",
            "и",
            "й",
            "к",
            "л",
            "м",
            "н",
            "о",
            "п",
            "р",
            "с",
            "т",
            "у",
            "ф",
            "х",
            "ь",
            "ы",
            "ъ",
            "э",
            "є",
            "ї",
            "і",
        ];
        $lat = [
            "Q",
            "W",
            "w",
            "Shch",
            "Sh",
            "Ch",
            "C",
            "Yu",
            "Ya",
            "J",
            "A",
            "B",
            "V",
            "G",
            "D",
            "E",
            "E",
            "Z",
            "I",
            "y",
            "K",
            "L",
            "M",
            "N",
            "O",
            "P",
            "R",
            "S",
            "T",
            "U",
            "F",
            "H",
            "",
            "Y",
            "",
            "E",
            "E",
            "Yi",
            "I",
            "shch",
            "sh",
            "ch",
            "c",
            "Yu",
            "Ya",
            "j",
            "a",
            "b",
            "v",
            "g",
            "d",
            "e",
            "e",
            "z",
            "i",
            "y",
            "k",
            "l",
            "m",
            "n",
            "o",
            "p",
            "r",
            "s",
            "t",
            "u",
            "f",
            "h",
            "",
            "y",
            "",
            "e",
            "e",
            "yi",
            "i",
        ];
        $string = str_replace($lat, $cyr, $string);
        $string = str_replace("_", " ", $string);
        return ($string);
    }

    /**
     * Склоняем словоформу
     * @param $n
     * @param $f1
     * @param $f2
     * @param $f5
     * @return mixed
     */
    public static function morph($n, $f1, $f2, $f5)
    {
        $n = abs((int)$n) % 100;
        if ($n > 10 && $n < 20) {
            return $f5;
        }
        $n %= 10;
        if ($n > 1 && $n < 5) {
            return $f2;
        }
        if ($n == 1) {
            return $f1;
        }
        return $f5;
    }

    /**
     * Возвращает сумму прописью
     * @param $num
     * @param null $currencyIso
     * @return string
     * @uses morph(...)
     * @author runcore
     */
    public static function amountWordsRu($num, $currencyIso = null)
    {
        $nul = 'ноль';
        $ten = [
            ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
            ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
        ];
        $a20 = [
            'десять',
            'одиннадцать',
            'двенадцать',
            'тринадцать',
            'четырнадцать',
            'пятнадцать',
            'шестнадцать',
            'семнадцать',
            'восемнадцать',
            'девятнадцать',
        ];
        $tens = [
            2 => 'двадцать',
            'тридцать',
            'сорок',
            'пятьдесят',
            'шестьдесят',
            'семьдесят',
            'восемьдесят',
            'девяносто',
        ];
        $hundred = [
            '',
            'сто',
            'двести',
            'триста',
            'четыреста',
            'пятьсот',
            'шестьсот',
            'семьсот',
            'восемьсот',
            'девятьсот',
        ];
        $unit = [
            ['тысяча', 'тысячи', 'тысяч', 1],
            ['миллион', 'миллиона', 'миллионов', 0],
            ['миллиард', 'милиарда', 'миллиардов', 0],
        ];

        list($rub, $kop) = explode('.', sprintf("%015.2f", (float)$num));
        $out = [];
        if ((int)$rub > 0) {
            foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
                if (!(int)$v) {
                    continue;
                }
                $uk = 2 - $uk; // unit key
                $gender = isset($unit[$uk]) ? $unit[$uk][3] : 0;
                list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                // mega-logic
                $out[] = $hundred[$i1]; # 1xx-9xx
                if ($i2 > 1) {
                    $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3];
                } # 20-99
                else {
                    $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];
                } # 10-19 | 1-9
                // units without rub & kop
                if ($uk >= 0) {
                    $out[] = Translate::morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
                }
            } //foreach
        } else {
            $out[] = $nul;
        }

        $currencyIso = (string)$currencyIso;
        $currencyWordRu = Translate::currencyWordsRu($currencyIso, $rub);
        if ($currencyWordRu) {
            $out[] = $currencyWordRu . ' ' . $kop . ' ' . Translate::centWordsRu($currencyIso, $kop);
        } else {
            $out[] = "$kop $currencyIso";
        }

        return trim(preg_replace('/ {2,}/', ' ', implode(' ', $out)));
    }
}
