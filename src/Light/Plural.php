<?php

namespace Light;

class Plural
{
    const MALE = 1;
    const FEMALE = 2;
    const NEUTRAL = 3;
    const EN = 0;

    protected static $_digits = [
        'ru' =>
            [
                self::MALE => ['ноль', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
                self::FEMALE => ['ноль', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
                self::NEUTRAL => ['ноль', 'одно', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять']
            ],
        'en' =>
            [
                self::EN => ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine']
            ]
    ];

    protected static $_ths = [
        'en' => [
            0 => ['', '', ''],
            1 => ['thousand', 'thousands', 'thousands'],
            2 => ['million', 'millions', 'millions'],
            3 => ['billion', 'billions', 'billions'],
            4 => ['trillion', 'trillions', 'trillions']
        ],
        'ru' => [
            0 => ['', '', ''],
            1 => ['тысяча', 'тысячи', 'тысяч'],
            2 => ['миллион', 'миллиона', 'миллионов'],
            3 => ['миллиард', 'миллиарда', 'миллиардов'],
            4 => ['триллион', 'триллиона', 'триллионов']
        ]
    ];

    protected static $_ths_g = [
        self::NEUTRAL,
        self::FEMALE,
        self::MALE,
        self::MALE,
        self::MALE,
        self::MALE
    ]; // hack 4 thsds

    protected static $_teens =
        [
            'ru' =>
                [
                    0 => 'десять',
                    1 => 'одиннадцать',
                    2 => 'двенадцать',
                    3 => 'тринадцать',
                    4 => 'четырнадцать',
                    5 => 'пятнадцать',
                    6 => 'шестнадцать',
                    7 => 'семнадцать',
                    8 => 'восемнадцать',
                    9 => 'девятнадцать'
                ],
            'en' =>
                [
                    0 => 'ten',
                    1 => 'eleven',
                    2 => 'twelve',
                    3 => 'thirteen',
                    4 => 'fouteen',
                    5 => 'fifteen',
                    6 => 'sixteen',
                    7 => 'seventeen',
                    8 => 'eighteen',
                    9 => 'nineteen'
                ]
        ];

    protected static $_tens =
        [
            'ru' => [
                2 => 'двадцать',
                3 => 'тридцать',
                4 => 'сорок',
                5 => 'пятьдесят',
                6 => 'шестьдесят',
                7 => 'семьдесят',
                8 => 'восемьдесят',
                9 => 'девяносто'
            ],
            'en' => [
                2 => 'twenty',
                3 => 'thirty',
                4 => 'fourty',
                5 => 'fifty',
                6 => 'sixty',
                7 => 'seventy',
                8 => 'eighty',
                9 => 'ninety'
            ]
        ];

    protected static $_hundredsRu = [
        1 => 'сто',
        2 => 'двести',
        3 => 'триста',
        4 => 'четыреста',
        5 => 'пятьсот',
        6 => 'шестьсот',
        7 => 'семьсот',
        8 => 'восемьсот',
        9 => 'девятьсот'
    ];

    protected static function _ending($value, array $endings = [])
    {
        if ($value < 2) {
            $result = $endings[0];
        } elseif ($value < 5) {
            $result = $endings[1];
        } else {
            $result = $endings[2];
        }

        return $result;
    }

    protected static function _triade($value, $triadeNum, $lang, $mode = self::MALE, array $endings = [])
    {
        $result = '';
        if ($value == 0) {
            return $result;
        }
        $triade = str_split(str_pad($value, 3, '0', STR_PAD_LEFT));
        if ($triade[0] != 0) {
            if ($lang === 'ru') {
                $result .= (self::$_hundredsRu[$triade[0]] . ' ');
            } else {
                $result .= (self::$_digits[$lang][$mode][$triade[0]] . ' hunderd ');
            }
        }
        if ($triade[1] == 1) {
            $result .= self::$_teens[$lang][$triade[2]];
        } elseif ($triade[1] != 0) {
            $result .= (self::$_tens[$lang][$triade[1]] . (($lang === 'en') ? '' : ' '));
        }
        if (($triade[2] != 0) && ($triade[1] != 1)) {
            if ($lang === 'en') {
                if ($triadeNum == 1 && $triade[0] && !$triade[1] ) {
                    $result .= ' and ';
                }
                if ($triade[1] > 1) {
                    $result .= '-';
                }
            }
            $result .= (self::$_digits[$lang][$mode][$triade[2]] . ' ');
        }
        if ($value != 0) {
            $ends = ($triade[1] == 1 ? '1' : '') . $triade[2];
            $result .= self::_ending($ends, $endings) . ' ';
        }
        return $result;
    }

    public static function asString($value, $lang, $mode = self::MALE, array $endings = [])
    {
        if ($lang === 'en') {
            $mode = self::EN;
        }
        if (!$endings) {
            $endings = ['', '', ''];
        }
        $result = '';
        $value = abs((int)$value);
        $steps = (int)ceil(strlen($value) / 3);
        $sv = str_pad((int)$value, $steps * 3, '0', STR_PAD_LEFT);
        for ($i = 0; $i < $steps; $i++) {
            $triade = substr($sv, $i * 3, 3);
            $iter = $steps - $i;
            $ends = $iter != 1 ? self::$_ths[$lang][$iter - 1] : $endings;
            if ($lang === 'ru') {
                $gender = $iter != 1 ? self::$_ths_g[$iter - 1] : $mode;
            } else {
                $gender = $mode;
            }
            $result .= self::_triade($triade, $steps - $i, $lang, $gender, $ends);
        }
        return $result;
    }

}