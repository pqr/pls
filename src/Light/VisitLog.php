<?php

namespace Light;


class VisitLog
{
    public static function write()
    {
        self::writeCsv();
        self::writeDb();
    }

    private static function writeCsv()
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

    private static function writeDb()
    {
        $insertData = [
            'CreatedBy' => User::getId(),
            'CreatedDate' => date('Y-m-d'),
            'ModuleID' => DbLog::getModuleId(Request::getModule()),
            'IsPOST' => Request::isPost(),
            'IsAjax' => Request::isAjax(),
        ];
        Db::insertRow($insertData, 'log_access');
    }
}
