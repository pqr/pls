<?php
/**
 * Created by PhpStorm.
 * User: petr
 * Date: 19.05.17
 * Time: 16:30
 */

namespace Light;


class VisitLog
{
    public static function write()
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

        CsvLog::write('visit', $header, $data);
    }
}