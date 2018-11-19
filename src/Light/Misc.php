<?php

namespace Light;


class Misc
{
    const DAYS_CALENDAR = 1;
    const DAYS_WORKING = 2;

    /**
     * В PHP есть стандартный метод uniqid, однако если его вызывать много раз в рамках одного php скрипта, то он не
     * гарантирует уникальный результат http://stackoverflow.com/questions/4070110/how-unique-is-uniqid
     *
     * Этот метод возвращает действительно уникальное значение для данной страницы - используется время + глобальный счётчик вызовов
     *
     * @param string $prefix
     * @return string
     */
    public static function uniqid($prefix = 'uniqid')
    {
        static $counter = 0;
        $counter++;

        //Используем секунды + десятые доли секунды с двумя знаками после запятой
        $microtime = floor(microtime(true) * 10);

        return $prefix . $microtime . $counter;
    }

    /**
     * Возвращает сумму прописью
     * @author runcore
     * @uses morph(...)
     * @param $num
     * @param null $currencyIso
     * @return string
     */
    public static function num2str($num, $currencyIso = null)
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
            'девятнадцать'
        ];
        $tens = [
            2 => 'двадцать',
            'тридцать',
            'сорок',
            'пятьдесят',
            'шестьдесят',
            'семьдесят',
            'восемьдесят',
            'девяносто'
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
            'девятьсот'
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
                    $out[] = self::morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
                }
            } //foreach
        } else {
            $out[] = $nul;
        }

        if ($currencyIso && self::getCurrencyRus($currencyIso)) {
            $out[] = self::getCurrencyRus($currencyIso, (int)$rub, true);
            $out[] = $kop . ' ' . self::getCurrencyRus($currencyIso, (int)$kop, false);
        } else {
            $out[] = $kop;
            $out[] = $currencyIso;
        }
        return trim(preg_replace('/ {2,}/', ' ', implode(' ', $out)));
    }

    public static function getCurrencyRus($currencyIso, $int = 0, $integerPart = true)
    {
        $currency = [
            'RUB' => [
                ['копейка', 'копейки', 'копеек'],
                ['рубль', 'рубля', 'рублей']
            ],
            'USD' => [
                ['цент', 'цента', 'центов'],
                ['доллар США', 'доллара США', 'долларов США']
            ],
            'EUR' => [
                ['цент', 'цента', 'центов'],
                ['евро', 'евро', 'евро']
            ]
        ];
        if (!isset($currency[$currencyIso])) {
            return false;
        }
        if ($integerPart) {
            return self::getNumberEnd((int)$int, $currency[$currencyIso][1]);
        } else {
            return self::getNumberEnd((int)$int, $currency[$currencyIso][0]);
        }
    }

    /**
     * Склоняем словоформу
     * @param $n
     * @param $f1
     * @param $f2
     * @param $f5
     * @return mixed
     */
    private static function morph($n, $f1, $f2, $f5)
    {
        $n = abs((int)$n) % 100;
        if ($n > 10 && $n < 20) {
            return $f5;
        }
        $n = $n % 10;
        if ($n > 1 && $n < 5) {
            return $f2;
        }
        if ($n == 1) {
            return $f1;
        }
        return $f5;
    }

    /**
     * @desc Возвращает русское название месяца по его номеру
     * @param int $number номер месяца от 1 до 12
     * @param bool $genitive если установлен в true, то название месяца будет в родительном падеже
     * @return string
     */
    public static function getRusMonthName($number, $genitive = false)
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
            "Декабр"
        ];
        $end = ["ь", "ь", "", "ь", "й", "ь", "ь", "", "ь", "ь", "ь", "ь"];
        $endGen = ["я", "я", "а", "я", "я", "я", "я", "а", "я", "я", "я", "я"];

        return $start[$index] . ($genitive ? $endGen[$index] : $end[$index]);
    }

    public static function getRusMonthNameYear(string $monthAsDate, $short = false)
    {
        $monthNo = (int)substr($monthAsDate, 5, 2);
        $monthText = self::getRusMonthName($monthNo);
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

    /**
     * Проходит по массиву options и выставляе флаг Active в зависимости от результата применения функции $testFunc
     * @param array $options
     * @param callable $testFunc
     * @return array
     */
    public static function setOptionsActiveInactive(array $options, callable $testFunc)
    {
        array_walk(
            $options,
            function (&$option) use ($testFunc) {
                $option['Active'] = $testFunc($option);
            }
        );

        return $options;
    }

    /**
     * группирует элементы массивов по принципу каждый с каждым
     * @param array $arrays
     * @return array
     */
    public static function arrayMultiply(array $arrays)
    {
        /**
         * На входе имеем массив массивов (двухменрый массив неких элементов c1,c2,...,s2)
         * array( 'DEM' => array(c1, c2, c3),
         * 'DET' => array(d1, d2, d3),
         * 'STOR' => array(s1, s2)
         * )
         *
         * На выходе должны получить двухменрый массив организованный следующим образом:
         *
         * array( array( 'DEM' => c1, 'DET' => d1, 'STOR' => s1 ),
         * array( 'DEM' => c1, 'DET' => d1, 'STOR' => s2 ),
         * array( 'DEM' => c1, 'DET' => d2, 'STOR' => s1 ),
         * ...
         * array( 'DEM' => c3, 'DET' => d3, 'STOR' => s2 ),
         * )
         *
         * (в данном примере количество комбинаций 3 х 3 х 2 = 18)
         *
         *
         * Для понимания алгоритма, представим входные и выходные данные в виде таблиц:
         *
         * Входные данные:
         *
         * №  |    DEM     |   DET      |   STOR
         * ---|------------|------------|----------
         * 1  | c1, c2, c3 | d1, d2, d3 | s1, s2
         *
         *
         * Выходные выходные данные:
         *
         * №  |    DEM     |   DET      |   STOR
         * ---|------------|------------|---------
         * 1  |     c1     |     d1     |     s1
         * 2  |     c1     |     d1     |     s2
         * 3  |     c1     |     d2     |     s1
         * 4  |     c1     |     d2     |     s2
         * ...
         * 18 |     c3     |     d3     |     s3
         *
         * Выходные данные будут записаны в двухмерный массив $outArr
         * Первый индекс - порядковый номер строки в изображенном табличном представлении
         * Второй интекс - наименование столбца (DEM/DET/STOR/...)
         *
         * Алгоритм пореобразований:
         *
         * Проинициализируем $outArr входной таблицей, т.е. одной "строкой"
         * Это сделает $outArr трёхмерным массивом, т.к. ячейки "таблицы" сами по себе оказались массивами
         *
         * $outArr[0] = $arrays
         *
         * № |    DEM     |   DET      |   STOR
         * --|------------|------------|----------
         * 1 | c1, c2, c3 | d1, d2, d3 | s1, s2
         *
         * Суть алгоримпа сводится к манипулированию с $outArr.
         * Увеличивая количество строк в нём мы будем постепенно избавляться от массивов в ячейках,
         * заменяя их на единичные элементы.
         *
         * Глобальный цикл алгорима проходит по именам столбцов.
         * Рассмотрим подробно первый шаг глобального цикла: столбец DEM
         *
         * 1. Обозначим первую (и пока единственную) строку в $outArr "ТЕКУЩЕЙ"
         * запоминаем значение ячейки столбца DEM во временную переменную
         * $currentCell = [c1, c2, c3]
         *
         * 2. заменяем в значение этой ячейки на первый элемент $currentCell, т.е. на c1
         *
         * было:  c1, c2, c3 | d1, d2, d3 | s1, s2
         * стало:     c1     | d1, d2, d3 | s1, s2
         *
         * 3. копируем текущую строку (№ 1) в конец массива $outArr нужное число раз:
         * столько раз, сколько элементов осталось во временной переменной $currentCell
         * в данном примере ещё два раза
         * после каждого копирования кладём в ячейку столбца DEM следущиее значение из $currentCell
         * в итоге получим $outArr:
         *
         * DEM     |   DET      |   STOR
         * ------------------------|----------
         * c1     | d1, d2, d3 | s1, s2
         * c2     | d1, d2, d3 | s1, s2
         * c3     | d1, d2, d3 | s1, s2
         *
         * Шаги 1-3 нужно повторить для каждой строки исходного $outArr на момент начало работы выбранным столбцом.
         * Для столбца DEM исходный $outArr состоял всего из одной строки,
         * поэтому разбиение по столбцу DEM закончено одним проходом по пунктам 1-3
         *
         * Разбивая следующий столбец (DET), пункты 1-3 повторяются для каждой строки $outArr на начало работы с DET - их уже 3
         * Поскольку DET содержит в себе 3 элемента (d1, d2, d3),
         * на п.3 необходимо 2 операции копирования (для подстановки d2 и d3)
         * Итого, после разбивки DET получим 3(исходных) + 3*2(новых) = 9 строк
         *
         * STOR содержит всего 2 элемента, значит в п.3 неходимо всего по одному копированию на каждую исходную строку $outArr,
         * которых к моменту начала работы со STOR уже 9!
         * Итого 9(исходных) + 9(новых) = 18 строк в $outArr к концу алгоритма
         **/

        //Запомним ключи (имена) исходных массивов
        $names = array_keys($arrays);

        //Сначала инициализиурем выходной массив всей матрицей $arrays
        $outArr[0] = $arrays;

        //Глобальный цикл по именам 'DEM', 'DET', 'STOR'...
        $cntOutArr = count($outArr);
        foreach ($names as $name) {
            //Запоминаем исходное число строк в $outArr на момент начала работы с текущим столбцом
            for ($i = 0; $i < $cntOutArr; $i++) {
                //Запомним содержимое ячейки (содержимое является неразбитым массивом),
                //которое будет использовано для создания нужного числа доп. строк
                $currentCell = $outArr[$i][$name];
                //Нулевой элемент ячейки запишем в теущую строку
                $outArr[$i][$name] = $currentCell[0];
                //Остальные элементы ячейки будем добавлять к $outArr в конец
                $cntCell = count($currentCell);
                for ($j = 1; $j < $cntCell; $j++) {
                    //Копируем шаблон строки
                    $newrow = $outArr[$i];
                    //Заменяем один ячейку в текущем столбце новым значением
                    $newrow[$name] = $currentCell[$j];
                    //Добавляем в конец $outArr
                    $outArr[] = $newrow;
                }
            }
        }

        return $outArr;
    }

    /**
     * Разделяет сумму $amount на несколько частей в пропорциях, которые переданы в массивве $proportions.
     * При этом сумма всех частей равна $amount, а погрешности округления равномерное распределены между всеми частями
     * @param float $amount
     * @param array $proportions
     * @return array
     */
    public static function splitMoneyInProroption($amount, array $proportions)
    {
        //Сначала посчитаем сумму всех числев в массиве $prportions, чтобы знать 100%
        $totalProportion = array_sum($proportions);

        $roundedTotal = 0;
        $realTotal = 0;

        $outAmounts = [];
        foreach ($proportions as $p) {
            $outAmount = round($amount * $p / $totalProportion, 2);

            $roundedTotal += $outAmount;
            $realTotal += $amount * $p / $totalProportion;

            $deviation = round($realTotal - $roundedTotal, 2);
            //Если накопилась погрешность больше 1 цента, пора прибавлять!
            if ($deviation) {
                $outAmount += $deviation;
                $roundedTotal += $deviation;
            }

            $outAmounts[] = $outAmount;
        }

        return $outAmounts;
    }

    public static function getIsAreVerb($number)
    {
        if ($number > 1) {
            return 'are';
        }
        return 'is';
    }

    public static function getWasWereVerb($number)
    {
        if ($number > 1) {
            return 'were';
        }
        return 'was';
    }

    public static function getHasHaveVerb($number)
    {
        if ($number > 1) {
            return 'have';
        }
        return 'has';
    }

    public static function getSSuffix($number)
    {
        if ($number > 1) {
            return 's';
        }
        return '';
    }

    public static function exec($command)
    {
        //Перенаправляем stderr в stdout, чтобы увидеть ошибки, если они есть
        //Для вывода stderr можно было бы использовать proc_open вместо exec, но есть баг: https://bugs.php.net/bug.php?id=60181
        $command .= ' 2>&1';

        exec($command, $out);

        $outTxt = implode("\n", $out);
        if (PHP_OS == 'WINNT') {
            $outTxt = mb_convert_encoding($outTxt, 'UTF8', 'cp866');
        }

        return $outTxt;
    }

    public static function procOpen($cmd, Msg $msgBuff = null, $stdInContent = null, bool $stdOutReturn = false)
    {
        $cmdEnc = File::encode($cmd);
        $descriptorspec = [];

        if ($stdInContent) {
            $descriptorspec[0] = ['pipe', 'r']; //stdin
        }

        $descriptorspec[1] = ['pipe', 'w']; //stdout
        $descriptorspec[2] = ['pipe', 'w']; //stderr

        //bypass_shell (только для Windows): при установке в TRUE процесс будет запущен в обход оболочки cmd.exe
        //это нужно для обхода бага https://bugs.php.net/bug.php?id=60181 "proc_open fails to read quoted whitespaced directories in Windows"
        $process = proc_open($cmdEnc, $descriptorspec, $pipes/*, null, null, ['bypass_shell' => true]*/);

        if (!is_resource($process)) {
            if ($msgBuff) {
                $msgBuff->addError('Cant open process ' . $cmd);
            }
            return false;
        }

        if ($stdInContent) {
            // Пишем в stdin
            fwrite($pipes[0], $stdInContent);
            fclose($pipes[0]);
        }

        if ($stdOutReturn) {
            // Читаем из stdOut
            $stdOut = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }

        //Возможно ,были какие-то ошибки и мы можем прочитать их в stderr
        $errText = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $result = proc_close($process);

        if ($errText && $msgBuff) {
            $msgBuff->addWarning($cmdEnc);
            $msgBuff->addWarning(mb_convert_encoding($errText, 'UTF-8', 'cp866'));
        }

        if ($stdOutReturn) {
            return $stdOut;
        }

        return $result == 0;
    }

    public static function makeLinksAbsolute($html)
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        } else {
            $path = $_SERVER['PHP_SELF'];
        }
        $path = str_replace('index.php', '', $path);
        $path = ltrim('/', $path);
        $url = Config::$localUrl . "/$path";
        $replacement = [
            ' src="' => ' src="' . $url,
            ' href="' => ' href="' . $url,
            ' url(' => ' url(' . $url
        ];
        return str_replace(array_keys($replacement), array_values($replacement), $html);
    }

    /*
     * Метод склонения чисел
     * @param int $number
     * @param array $titles варианты окончаний [1, 2-4, >5], например: ['', 'а', 'ов']
     * @return string
     */
    public static function getNumberEnd($number, array $titles)
    {
        $number = abs($number);
        $cases = [2, 0, 1, 1, 1, 2];
        return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }

    /**
     * mb_ucfirst - преобразует первый символ в верхний регистр
     * @param string $str - строка
     * @param string $encoding - кодировка, по-умолчанию UTF-8
     * @return string
     */
    public static function mb_ucfirst($str, $encoding = 'UTF-8')
    {
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) .
            mb_substr($str, 1, mb_strlen($str), $encoding);
        return $str;
    }

    public static function getWeekStartDate($weekNum, $year)
    {
        $wTxt = $weekNum < 10 ? '0' . $weekNum : $weekNum;
        return date('Y-m-d', strtotime($year . 'W' . $wTxt . '1'));
    }

    public static function getWeekEndDate($weekNum, $year)
    {
        $wTxt = $weekNum < 10 ? '0' . $weekNum : $weekNum;
        return date('Y-m-d', strtotime($year . 'W' . $wTxt . '7'));
    }

    public static function addDays(string $dateFrom, int $addDays, int $calendarOrWorking): string
    {
        return $calendarOrWorking === self::DAYS_CALENDAR
            ? self::addDaysCalendar($dateFrom, $addDays)
            : self::addDaysWorking($dateFrom, $addDays);
    }

    public static function addDaysCalendar(string $dateFrom, int $addDays): string
    {
        return date('Y-m-d', strtotime("$dateFrom +$addDays days"));
    }

    public static function addDaysWorking(string $dateFrom, int $addWorkingDays): string
    {
        // будем прибавлять к стартовой дате рабочие дни
        $currentDate = new \DateTime($dateFrom);

        $addedDaysActual = 0;

        while ($addedDaysActual < $addWorkingDays) {
            // Двигаем счётчик на один день вперёд
            $currentDate->modify("+1 day");
            // Проверим, это рабочий день? (с учётом производственного календаря)
            if (self::isWorkingDay($currentDate->format("Y-m-d"))) {
                // Да, это рабочий день - увеличиваем счётчик реально использованных рабочих дней
                // чтобы рано или поздно выйти из while цикла
                $addedDaysActual++;
            }
        }

        return $currentDate->format("Y-m-d");
    }

    public static function isWorkingDay(string $date): bool
    {
        static $cacheCalendar = [];

        $year = substr($date, 0, 4);

        if (!isset($cacheCalendar[$year])) {
            // Закешируем список исключений на год указанный в $startDate
            $cacheCalendar[$year] = Db::getAssoc("SELECT Date, WorkingDay FROM production_calendar WHERE YEAR(Date) = @i",
                $year);
        }

        if (isset($cacheCalendar[$year][$date])) {
            // Если запрашиваемый день обнаружился в производственном календаре - вернём информацию оттуда,
            // рабочий он или нет
            return (bool)$cacheCalendar[$year][$date];
        }

        // Иначе используем стандартный подход
        // 'N' - ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0)
        // 1 (for Monday) through 7 (for Sunday)
        $weekDay = date('N', strtotime($date));
        // Вохвращаем true, если это день с понедельника по пятницу
        return $weekDay <= 5;
    }

    public static function minifyHtml($html)
    {
        //http://websistent.com/how-to-use-php-to-minify-html-output/
        $search = [
            '/\>[^\S ]+/s',
            '/[^\S ]+\</s',
            '/(\s)+/s',
        ];
        $replace = [
            '>',
            '<',
            '\\1',
        ];
        return preg_replace($search, $replace, $html);
    }

    public static function encodeMailtoLinkText($text)
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Windows NT 5.2') !== false) {
            //Outlook 2003 не понимает utf8
            $text = mb_convert_encoding($text, "windows-1251", "UTF-8");
        }
        return str_replace('+', ' ', urlencode($text));
    }

    public static function latToRus($string)
    {
        $cyr = array(
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
            "і"
        );
        $lat = array(
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
            "i"
        );
        $string = str_replace($lat, $cyr, $string);
        $string = str_replace("_", " ", $string);
        return ($string);
    }

    public static function rusToLat($string)
    {
        $rus = array('ё', 'ж', 'ц', 'ч', 'ш', 'щ', 'ю', 'я', 'Ё', 'Ж', 'Ц', 'Ч', 'Ш', 'Щ', 'Ю', 'Я');
        $lat = array('yo', 'zh', 'tc', 'ch', 'sh', 'sh', 'yu', 'ya', 'YO', 'ZH', 'TC', 'CH', 'SH', 'SH', 'YU', 'YA');
        $string = str_replace($rus, $lat, $string);
        $string = strtr($string,
            "АБВГДЕЗИЙКЛМНОПРСТУФХЪЫЬЭабвгдезийклмнопрстуфхъыьэ",
            "ABVGDEZIJKLMNOPRSTUFH_I_Eabvgdezijklmnoprstufh_i_e");
        return ($string);
    }

    private static $_cmu_dict = null;
    private static $_countries = null;

    public static function getSmartPhoneticTranslate($text, $countryCheck = false)
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
                        $translate = Misc::latToRus($word);
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

    /**
     * Масштабирует и обрезает картинку
     * @param $file
     * @param $image
     * @param $w
     * @param $h
     * @param bool $crop
     * @return resource
     */
    public static function resizeImage($file, $image, $w, $h, $crop = false)
    {
        list($width, $height) = getimagesize($file);
        $r = $width / $height;
        if ($crop) {
            $dst = imagecreatetruecolor($w, $h);
            $width_new = $height * $w / $h;
            $height_new = $width * $w / $h;
            if ($width_new > $width) {
                $h_point = (($height - $height_new) / 2);
                imagecopyresampled($dst, $image, 0, 0, 0, $h_point, $w, $h, $width, $height_new);
            } else {
                $w_point = (($width - $width_new) / 2);
                imagecopyresampled($dst, $image, 0, 0, $w_point, 0, $w, $h, $width_new, $height);
            }
        } else {
            if ($w / $h > $r) {
                $newwidth = $h * $r;
                $newheight = $h;
            } else {
                $newheight = $w / $r;
                $newwidth = $w;
            }
            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $image, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        }

        return $dst;
    }

    public static function escapeParentheses(string $string): string
    {
        return str_replace(')', '\\\\)', str_replace('(', '\\\\(', $string));
    }

    public static function getClass($classNameOrObj)
    {
        $ref = new \ReflectionClass($classNameOrObj);
        return $ref->isAnonymous() ? 'Anonymous' . $ref->getParentClass()->getShortName() : $ref->getShortName();
    }

    public static function getModule($classNameOrObj): string
    {
        // Сначала проверим, если на вход передан объект, то нужно получить полный путь к его классу
        $className = (is_object($classNameOrObj)) ? get_class($classNameOrObj) : $classNameOrObj;
        $parts = explode('\\', $className);
        // Имя модуля по соглашению идёт под индексом 2
        // например: Basis\Module\Shipment\SomeClass - имя модуля Shipment
        return $parts[2] ?? $className;
    }

    /**
     * Проверим, если пользователь ввёл что-то похожее на номер MAWB (а именно 11 цифр с неважно какими разделителями)
     * То этот номер MAWB нужно отформатировать по образцу XXX-XXXX XXXX
     * @param string $mawb
     * @return string
     */
    public static function normalizeMawb(string $mawb): string
    {
        $testMawb = str_replace(['-', ' '], '', trim($mawb));
        if (is_numeric($testMawb) && strlen($testMawb) === 11) {
            return substr($testMawb, 0, 3) . '-' . substr($testMawb, 3, 4) . ' ' . substr($testMawb, 7);
        }

        return $mawb;
    }

    public static function is_array_empty($inputVariable): bool
    {
        $result = true;
        if (\is_array($inputVariable) && \count($inputVariable) > 0) {
            foreach ($inputVariable as $value) {
                $result = $result && static::is_array_empty($value);
            }
        } else {
            $result = empty($inputVariable);
        }
        return $result;
    }

    public static function idsFromComaSeparatedString(string $comaSeparated): array
    {
        return array_values(array_filter(array_map('intval', explode(',', $comaSeparated))));
    }

    public static function filesize_formatted(int $size): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format(($size / (1024 ** $power)), 1, '.', ' ') . ' ' . $units[$power];
    }

    public static function validateEmails($string)
    {
        $regex = "/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/iu";
        $result = preg_split("/,|;/", preg_replace("/\s/", "", $string));
        foreach ($result as $value) {
            if (!preg_match($regex, $value)) {
                return false;
            }
        }
        return true;
    }

}
