<?php

namespace Light;


use Light\Field\FieldMoneyWithCurrency;

class Misc
{
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
        // hot fix: ссылки, у которых теги начинаются с @, менять не будем
        $replacement = [
            ' src="' => ' src="' . $url,
            ' href="' => ' href="' . $url,
            ' url(' => ' url(' . $url,
            ' @src="' => ' src="',
            ' @href="' => ' href="',
            ' @url(' => ' url(',
        ];
        return str_replace(array_keys($replacement), array_values($replacement), $html);
    }

    /*
     * @deprecated use Translate::morph()
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

    public static function encodeMailtoLinkText($text)
    {
        if (str_contains($_SERVER['HTTP_USER_AGENT'], 'Windows NT 5.2')) {
            //Outlook 2003 не понимает utf8
            $text = mb_convert_encoding($text, "windows-1251", "UTF-8");
        }
        return str_replace('+', ' ', urlencode($text));
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
        [$width, $height] = getimagesize($file);
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
        return str_replace(['(', ')'], ['\\\\(', '\\\\)'], $string);
    }

    public static function getClass($classNameOrObj): string
    {
        if (is_string($classNameOrObj) && !str_contains($classNameOrObj, '\\')) {
            // Имя класса задано простой строкой без неймспейсов - тут уже ничего сделать нельзя
            return $classNameOrObj;
        }

        $ref = new \ReflectionClass($classNameOrObj);
        return $ref->isAnonymous() ? 'Anonymous' . $ref->getParentClass()->getShortName() : $ref->getShortName();
    }

    public static function getNamespace($classNameOrObj): string
    {
        $ref = new \ReflectionClass($classNameOrObj);
        return $ref->getNamespaceName();
    }

    public static function getModule($classNameOrObj): string
    {
        if (is_string($classNameOrObj)) {
            $className = $classNameOrObj;
        } elseif (is_object($classNameOrObj)) {
            $className = $classNameOrObj::class;
        } else {
            throw new \InvalidArgumentException('Misc::getModule invalid $classNameOrObj type (should be string or object)');
        }

        if (!str_contains($className, '\\')) {
            return $className;
        }

        // Имя модуля по соглашению идёт под индексом 2
        // например: Basis\Module\Shipment\SomeClass - имя модуля Shipment
        $parts = explode('\\', $className);
        return $parts[2] ?? $className;
    }

    public static function getFileByClass(string $className): string
    {
        return LIGHT_DIR_ROOT . '/src/' . str_replace('\\', '/', $className) . '.php';
    }

    public static function classExists(string $className): bool
    {
        $file = self::getFileByClass($className);
        if (!file_exists($file)) {
            return false;
        }

        return class_exists($className);
    }

    public static function getFirstClassExists(array $classNames): ?string
    {
        foreach ($classNames as $className) {
            if (self::classExists($className)) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Проверим, если пользователь ввёл что-то похожее на номер MAWB (а именно 11 цифр с неважно какими разделителями)
     * То этот номер MAWB нужно отформатировать по образцу XXX-XXXX XXXX
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

    public static function isArrayEmptyRecurcive($inputVariable): bool
    {
        if (!\is_array($inputVariable)) {
            // Значение не является массивом - в этом случае нет необходимости углубляться рекурсивно
            // просто проверяем текущее значение на пустоту
            return empty($inputVariable);
        }

        // Если массив пуст, то foreach цикл не запустится и ниже будет возвращено true
        foreach ($inputVariable as $value) {
            // Проверяем каждый элемент массива рекурсивно, т.к. элементами массива могут быть вложенные массивы
            $result = static::isArrayEmptyRecurcive($value);
            if ($result === false) {
                // Как только нашли не пустой элемент - выходим
                return false;
            }
        }

        return true;

    }

    public static function idsFromComaSeparatedString(string $comaSeparated, string $separator = ','): array
    {
        return array_values(array_filter(array_map('intval', explode($separator, $comaSeparated))));
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

    /**
     * @param array $amounts значения валюты и суммы в виде строки, например 'RUB 1000.00'
     * @return array сгруппированные значения CurrencyISO => TotalAmount
     */
    public static function calcAmountPerCurrency(array $amounts): array
    {
        $amountPerCurrency = [];
        foreach ($amounts as $amountWithCurrencyString) {
            [$currencyIso, $amount] = FieldMoneyWithCurrency::getAsTouple($amountWithCurrencyString);
            if ($amount && $currencyIso) {
                if (!isset($amountPerCurrency[$currencyIso])) {
                    $amountPerCurrency[$currencyIso] = 0;
                }
                $amountPerCurrency[$currencyIso] += round($amount, 2);
            }
        }

        ksort($amountPerCurrency);

        return $amountPerCurrency;
    }


    /**
     * Разделяет сумму $amount на несколько частей в пропорциях, которые переданы в массивве $proportions.
     * При этом сумма всех частей равна $amount, а погрешности округления равномерное распределены между всеми частями
     * @param float $amount
     * @return array
     */
    public static function splitMoneyInProroption($amount, array $proportions): array
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

    public static function getRecordsMap(array $records): array
    {
        $out = [];
        foreach ($records as $r) {
            $out[$r['Name']] = $r['ID'];
            if (!empty($r['Synonyms'])) {
                $synonyms = array_filter(array_map('trim', explode("\n", $r['Synonyms'])));
                foreach ($synonyms as $synonym) {
                    if (!isset($out[$synonym])) {
                        $out[$synonym] = $r['ID'];
                    }
                }
            }
        }

        return $out;
    }

    public static function json($data, $options = 0): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | $options);
    }

}
