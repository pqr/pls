<?php
namespace Light;

class CsvLog
{
    public static $delim = ',';

    public static function write($dir, array $header, array $data)
    {
        //Определим директорию и создадим её, если её ещё нет
        $path = LIGHT_DIR_LOGS . "/$dir";
        File::mkdir($path);

        $isNewFile = false;
        $filepath = $path . '/' . date('Y-m') . '.csv';
        if (!is_file($filepath)) {
            //Создаём файл и добавляем заголовок
            $fh = fopen($filepath, 'a');
            if (!$fh) {
                return;
            }
            fwrite($fh, "sep=,\n");
            fputcsv($fh, $header, self::$delim, '"');
            $isNewFile = true;
        } else {
            $fh = fopen($filepath, 'a');
            if (!$fh) {
                return;
            }
        }

        //Записываем сами данные
        //Массив $data может быть одномерным - тогда это одна строка для записи
        //а может быть и двумерным - значит нужгно записать несколько строк
        if (is_array($data[0])) {
            //Двумерный массив, пройдём по нему, чтобы записать несколько строк
            foreach ($data as $line) {
                fputcsv($fh, $line, self::$delim, '"');
            }
        } else {
            //Одномерный массив
            fputcsv($fh, $data, self::$delim, '"');
        }

        fclose($fh);

        if ($isNewFile) {
            chmod($filepath, 0666);
        }
    }
}
