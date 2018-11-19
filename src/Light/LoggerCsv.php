<?php

namespace Light;

class LoggerCsv
{
    public static $delim = ',';

    public static function logVisit()
    {
        $header = [
            'DateTime',
            'UserID',
            'UserName',
            'GETPOST',
            'IsAJAX',
            'Module',
            'HttpQuery',
            'FullTime',
            'DbTime',
            'Queries',
            'MemoryPeakUsage'
        ];

        $data = [
            date('Y-m-d H:i:s'),
            User::getId(),
            User::getName(),
            Request::getMehod(),
            Request::isAjax() ? 'ajax' : '',
            Request::getModule(),
            $_SERVER['QUERY_STRING'] ?? '',
            ApplicationAbstract::getWorkingTime(),
            Db::getQueriesTime(),
            Db::getQueriesCount(),
            memory_get_peak_usage(true)
        ];

        self::write('visit', $header, $data);
    }

    public static function logSqlQueries()
    {
        $header = [
            'DateTime',
            'UserID',
            'User Name',
            'Module',
            'Query String',
            'Query',
            'Time'
        ];

        foreach (Db::getQueriesLog() as [0 => $queryTime, 1 => $query]) {
            $data = [
                date('Y-m-d H:i:s'),
                User::getId(),
                User::getName(),
                Request::getModule(),
                $_SERVER['QUERY_STRING'] ?? '',
                $query,
                str_replace('.', ',', $queryTime)
            ];
            self::write('sqllog', $header, $data);
        }
    }

    public static function logSqlError(string $queryFormated, string $errMsg)
    {
        // Запишем логи об ошибке в SQL запросе в CSV файл
        $header = [
            'DateTime',
            'UserID',
            'User Name',
            'Module',
            'Query String',
            'Query',
            'Error',
        ];

        $data = [
            date('Y-m-d H:i:s'),
            User::getId(),
            User::getName(),
            Request::getModule(),
            $_SERVER['QUERY_STRING'] ?? '',
            $queryFormated,
            $errMsg
        ];
        self::write('sqlerror', $header, $data);
    }

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
