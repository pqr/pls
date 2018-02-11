<?php

namespace Light;

use Light\Field\FieldDate;
use Rollbar\Payload\Level;
use Rollbar\Rollbar;

class Db
{
    private static $_queriesCount = 0;
    private static $_queriesTime = 0;
    private static $_queriesLog = [];

    /** @var \mysqli */
    public static $mysqli;

    public static function connect()
    {
        self::$mysqli = new \mysqli(Config::$mysqlHost, Config::$mysqlUser, Config::$mysqlPassword, Config::$mysqlDb,
            Config::$mysqlPort);
        if (\mysqli_connect_errno()) {
            die(\mysqli_connect_errno() . ' ' . \mysqli_connect_error());
        }

        self::$_queriesCount = 0;
        self::$_queriesTime = 0;
        self::$_queriesLog = [];

        self::$mysqli->set_charset('utf8');
        self::$mysqli->set_opt(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
        self::$mysqli->query("SET SESSION SQL_MODE = ''");
    }

    /**
     * низкоуровневая функция, вызывает оригинальную mysqli::query
     * @param string $query
     * @param array $params
     * @return mixed|\mysqli_result
     * @throws \Exception
     */
    public static function query($query, ...$params)
    {
        self::$_queriesCount++;
        $startTime = microtime(true);

        if (count($params) > 0 /*func_num_args() > 1*/) {
            //Если в метод было передано более одного параметра, значит был передан запрос с плейсхолдерами
            //и ещё много перменных для подстановки в эти плейсхолдеры
            $query = self::parse($query, ...$params);
        }

        $res = self::$mysqli->query($query);

        if ($res === false) {

            //Завершим работу скрипта с информацией об ошибке на экран пользователя
            self::riseError($query, self::$mysqli->error);

        }

        $queryTime = microtime(true) - $startTime;

        if (Config::$debug) {
            self::$_queriesLog[] = [$queryTime, $query];
        }

        //Если установлен специальный cookie, то запишем логи об этом sql запросе
        //Запишем логи об ошибке в SQL запросе в CSV файл
        if (isset($_COOKIE['sqllog']) && $_COOKIE['sqllog']) {
            $header = [
                'DateTime',
                'UserID',
                'User Name',
                'Module',
                'Query String',
                'Query',
                'Time'
            ];

            $data = [
                date('Y-m-d H:i:s'),
                User::getId(),
                User::getName(),
                Request::getModule(),
                $_SERVER['QUERY_STRING'] ?? '',
                $query,
                str_replace('.', ',', $queryTime)
            ];
            CsvLog::write('sqllog', $header, $data);
        }

        self::$_queriesTime += $queryTime;
        return $res;
    }

    public static function parse($rawQuery, ...$params)
    {
        $outQuery = '';

        /** @noinspection NotOptimalRegularExpressionsInspection */
        $array = preg_split(
            '/(@[nsifuapl12]{1,2}_[a-zA-Z0-9_]*|@[nsifuapl12]{1,2})/u',
            $rawQuery,
            null,
            PREG_SPLIT_DELIM_CAPTURE
        );
        //$anum = count($args);
        $anum = count($params);
        $pnum = floor(count($array) / 2);
        if ($pnum != $anum) {
            self::riseError($rawQuery, "Number of args ($anum) doesn't match number of placeholders ($pnum)");
            return false;
        }

        $paramNum = 0;
        foreach ($array as $i => $part) {
            if (($i % 2) == 0) {
                $outQuery .= $part;
                continue;
            }

            //$value = array_shift($args);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $value = $params[$paramNum];
            $paramNum++;

            $placeholderType = explode('_', $part)[0];
            switch ($placeholderType) {
                case '@n':
                    $part = self::quote($value);
                    break;
                case '@s':
                    $part = "'" . self::$mysqli->real_escape_string($value) . "'";
                    break;
                case '@i':
                    $part = (int)$value;
                    break;
                case '@f':
                    $part = (double)$value;
                    break;
                case '@a':
                    $part = self::createIn($value);
                    break;
                case '@u':
                    $part = self::createSet($value);
                    break;
                case '@l':
                    $part = "'%" . self::$mysqli->real_escape_string($value) . "%'";
                    break;
                case '@l1':
                    $part = "'%" . self::$mysqli->real_escape_string($value) . "'";
                    break;
                case '@l2':
                    $part = "'" . self::$mysqli->real_escape_string($value) . "%'";
                    break;
                case '@p':
                    $part = $value;
                    break;
            }
            $outQuery .= $part;
        }
        return $outQuery;
    }

    /**
     * Завершает работу скрипта с информацией об ошибке на экран пользователя
     * @param $query
     * @param $errMsg
     * @throws \Exception
     */
    private static function riseError($query, $errMsg)
    {
        //
        require_once __DIR__ . "/External/SqlFormatter.php";
        $queryFormated = \SqlFormatter::format($query, false);
        $msg = 'SQL error: ' . $queryFormated . "\n\n" . $errMsg;


        if (Config::$sqlErrorThrowException) {

            throw new \Exception($msg);

        } elseif (Config::$rollbar) {

            //Создаём Exception и отправяем его в логи rollbar - таким образом в логах будет backtrace!
            Rollbar::log(Level::ERROR, new \Exception($msg));

        } else {

            //Запишем логи об ошибке в SQL запросе в CSV файл
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
            CsvLog::write('sqlerror', $header, $data);

        }

        $msgHtml = 'SQL error:' . \SqlFormatter::format($query) . htmlspecialchars($errMsg);
        Response::sendErrorMsg($msgHtml);
    }

    public static function getInsertId()
    {
        return self::$mysqli->insert_id;
    }

    public static function getAffectedRows()
    {
        return self::$mysqli->affected_rows;
    }

    public static function getAll($query, ...$params): array
    {
        $res = self::query($query, ...$params);
        if (!$res) {
            return [];
        }

        $a = $res->fetch_all(MYSQLI_ASSOC);
        if (!is_array($a)) {
            $a = [];
        }
        $res->free();

        return $a;
    }

    public static function getAssoc($query, ...$params): array
    {
        $res = self::query($query, ...$params);
        if (!$res) {
            return [];
        }

        if ($res->field_count === 2) {

            $all = $res->fetch_all(MYSQLI_NUM);
            $a = array_combine(array_column($all, 0), array_column($all, 1));

        } else {

            $all = $res->fetch_all(MYSQLI_ASSOC);
            $keyName = mysqli_fetch_field($res)->name;
            $keys = array_column($all, $keyName);
            foreach ($all as &$row) {
                unset($row[$keyName]);
            }
            $a = array_combine($keys, $all);

        }

        $res->free();

        return $a;
    }

    public static function getCol($query, ...$params): array
    {
        $res = self::query($query, ...$params);
        if (!$res) {
            return [];
        }

        $a = array_column($res->fetch_all(MYSQLI_NUM), 0);
        $res->free();

        return $a;
    }

    /**
     * Возвращает ассоциативный массив, ключи - имена полей из базы
     * @param $query
     * @param array ...$params
     * @return array
     * @throws \Exception
     */
    public static function getRow($query, ...$params): array
    {
        $a = [];
        $res = self::query($query, ...$params);
        if ($res) {
            if ($row = $res->fetch_assoc()) {
                $a = $row;
            }

            $res->free();
        }

        return $a;
    }

    /**
     * Возвращает массив с индексами 0, 1, 2...
     * @param $query
     * @param array ...$params
     * @return array
     * @throws \Exception
     */
    public static function getRowValues($query, ...$params): array
    {
        $a = [];
        $res = self::query($query, ...$params);
        if ($res) {
            if ($row = $res->fetch_row()) {
                $a = $row;
            }

            $res->free();
        }

        return $a;
    }

    public static function getOne($query, ...$params)
    {
        $a = null;
        $res = self::query($query, ...$params);
        if ($res) {
            if ($row = $res->fetch_row()) {
                $a = $row[0];
            }

            $res->free();
        }

        return $a;
    }

    public static function getCount($table, $where = false): int
    {
        $query = "SELECT count(*) FROM " . self::quote($table);
        if ($where) {
            $query .= " WHERE " . $where;
        }
        return (int)self::getOne($query);
    }

    /**
     * Вспомагательный метод, который возвращает уникалные значения определённого поля из указанной таблицы
     * разделённые запятыми. Эти значения затем удобно подставить в какой-нибудь IN(...) запрос.
     * Если у казанной таблице нет значений, тогда этом метод вернёт строку '0', которую также
     * можно безопасно подставить в IN(...)
     * @param string $fieldKey
     * @param string $dbTable
     * @param string $sqlWhere
     * @return string
     */
    public static function getUniqueValuesComa(string $fieldKey, string $dbTable, string $sqlWhere = '')
    {
        $query = "SELECT GROUP_CONCAT(" . Db::quote($fieldKey) . ") FROM " . Db::quote($dbTable);
        if ($sqlWhere) {
            $query .= " WHERE $sqlWhere";
        }
        $valuesComa = Db::getOne($query);
        if (!$valuesComa) {
            $valuesComa = '0';
        }
        return $valuesComa;
    }

    /**
     * Добавляет экранирующие кавычки к имени таблицы.
     * Это нужно сделать аккуратно, т.к. имя таблицы может содержать имя базы через точку
     * @param string $table
     * @return string
     */
    public static function quote(string $table): string
    {
        return '`' . str_replace(".", "`.`", $table) . '`';
    }

    public static function esc($value): string
    {
        return self::$mysqli->real_escape_string($value);
    }

    public static function insertRow(array $fieldValues, string $table, bool $useReplace = false)
    {
        $fieldKeys = implode(',', array_map(__CLASS__ . '::quote', array_keys($fieldValues)));

        $query = ($useReplace ? 'REPLACE' : 'INSERT')
            . " INTO " . self::quote($table)
            . " (" . $fieldKeys . ")"
            . " VALUES (" . self::createIn($fieldValues) . ")";

        self::query($query);
        return self::getInsertId();
    }

    /**
     * Вставляет несколько строк одним запросом, возвращает число вставленных строк
     * @param array $rows массив строк. Важно: каждая строка должна содержать одинаковый набор полей
     * @param string $table
     * @param bool $useReplace
     * @return int
     * @throws \Exception
     */
    public static function insertRows(array $rows, $table, $useReplace = false)
    {
        if (!$rows) {
            return 0;
        }

        $firstRow = reset($rows);
        $fieldKeys = implode(',', array_map(__CLASS__ . '::quote', array_keys($firstRow)));

        $query = ($useReplace ? 'REPLACE' : 'INSERT')
            . " INTO " . self::quote($table)
            . " (" . $fieldKeys . ")"
            . " VALUES "
            . implode(',', array_map(function ($fieldValues) {
                return "(" . self::createIn($fieldValues) . ")";
            }, $rows));

        self::query($query);
        return self::getAffectedRows();
    }

    public static function replaceRow(array $fieldValues, $table)
    {
        return self::insertRow($fieldValues, $table, true);
    }

    /**
     * @param  array $fieldValues
     * @param  string $table
     * @param  $id
     * @return int affected rows
     * @throws \Exception
     */
    public static function updateRow(array $fieldValues, string $table, $id)
    {
        if (!$fieldValues) {
            return 0;
        }

        $query = "UPDATE " . self::quote($table)
            . " SET " . self::createSet($fieldValues)
            . " WHERE ID = '" . self::$mysqli->real_escape_string($id) . "'";

        self::query($query);

        return self::getAffectedRows();
    }

    public static function deleteRow(string $tableName, int $id, string $masterModule = '', int $masterId = 0)
    {
        static $traceId;

        if (!$traceId) {
            // Зарегистрируем запись в таблице deleted_trace, чтобы получить уникальный autoncrement
            // далее полученный TraceID будем добавлять ко всем удаляемым записям
            $traceId = Db::insertRow(['CreatedBy' => User::getId()], 'deleted_trace');
        }

        //Сформируем запрос на выборку данных удаляемой записи(ей)
        $selectQuery = "SELECT * FROM " . self::quote($tableName) . " WHERE ID = $id";
        $data = self::getAll($selectQuery);
        if ($data) {
            $tableId = DbLog::getTableId($tableName);
            $moduleId = DbLog::getModuleId($masterModule);
            //Если были найдены какие-то данные в базе, сериализуем их!
            foreach ($data as $row) {
                // Сохраняем метаинформацию в таблицу deleted
                $logId = self::insertRow([
                    'CreatedBy' => User::getId(),
                    'ModuleID' => $moduleId,
                    'MasterID' => $masterId,
                    'TableID' => $tableId,
                    'RecordID' => $id,
                    'TraceID' => $traceId,
                ], 'deleted');

                // Сохраняем JSON данные в отдельную таблицу deleted_text
                $json = json_encode($row, JSON_UNESCAPED_UNICODE);
                self::insertRow([
                    'deletedID' => $logId,
                    'JSON' => $json
                ], 'deleted_text');
            }
        }

        //Физически удаляем требуюемую запись
        $delQuery = "DELETE FROM " . self::quote($tableName) . " WHERE ID = $id";
        return self::query($delQuery);
    }

    public static function insertUnique($value, string $tableName)
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $query = "SELECT ID
                  FROM " . self::quote($tableName) . "
                  WHERE Name = '" . self::$mysqli->real_escape_string($value) . "'";
        if ($id = self::getOne($query)) {
            return $id;
        }

        $insertData = ['Name' => $value];
        if (User::getId()) {
            $insertData['CreatedBy'] = User::getId();
        }

        // Значения в базе нет, вставим
        return self::insertRow($insertData, $tableName);
    }

    public static function getQueriesCount()
    {
        return self::$_queriesCount;
    }

    public static function getQueriesLog()
    {
        return self::$_queriesLog;
    }

    public static function getQueriesTime()
    {
        return self::$_queriesTime;
    }

    /**
     * возваращает строку для sql запроса where в виде "($SQLVarName LIKE '1234' OR $SQLVarName LIKE '3456')" с номерами,
     * либо возвращает строку "1", что так же подходит для sql условия where
     * $NormalStrLen можно указать = -1, тогда в SQL запросе вместо LIKE будет =
     * @param string $filterComaSeparated
     * @param string $sqlVarName
     * @param int $normalStrLen
     * @param bool $fullLike
     * @param bool $isIntField если true, будет применяться intval вместо $this->_app->db->esc
     * @param string $default
     * @return string
     */
    public static function getSqlWhereSeparatedByComa(
        $filterComaSeparated,
        $sqlVarName,
        $normalStrLen = 0,
        $fullLike = false,
        $isIntField = false,
        $default = '1'
    ) {
        $query = $default; //Условие для WHERE принимаем за 1. Будет изменено

        $filterComaSeparated = str_replace(['"', "\r", "\n", ";"], ['', '', ',', ','], $filterComaSeparated);
        $filterComaSeparatedArrRaw = explode(',', $filterComaSeparated);

        // в фильтре по номеру нужна возможность указать интервал номеров через тире (например: 850001-850020)
        $filterComaSeparatedArr = [];
        foreach ($filterComaSeparatedArrRaw as $elem) {

            $elem = trim($elem);
            $filterComaSeparatedArr[] = $elem;

            if (strpos($elem, '-') !== false) {
                list($from, $to) = explode('-', $elem, 2);
                if (is_numeric($from) && is_numeric($to)) {

                    $fromInt = (int)$from;
                    $toInt = (int)$to;
                    $diff = $toInt - $fromInt;
                    if ($diff >= 0 && $diff <= 1000) {
                        $minStrLen = strlen($from);
                        foreach (range($fromInt, $toInt) as $number) {
                            // если число с ведущими нулями (напр, 060040-060046)
                            $filterComaSeparatedArr[] = $minStrLen > strlen($number)
                                ? str_pad($number, $minStrLen, "0", STR_PAD_LEFT)
                                : $number;
                        }
                    }

                }

            }
        }

        if (count($filterComaSeparatedArr)) {
            //Подготавливаем два массива
            $valuesEqual = [];
            $valuesLike = [];
            foreach ($filterComaSeparatedArr as $cv) {
                $cv = trim($cv);
                if ($cv !== '') {
                    if ($isIntField && !is_numeric($cv)) {
                        continue;
                    }

                    if ($normalStrLen === -1 || strlen($cv) === $normalStrLen) {
                        $valuesEqual[] = $cv;
                    } else {
                        $valuesLike[] = $cv;
                    }

                    //Ещё вариант - удаляем пробелы
                    if (strpos($cv, ' ') !== false) {
                        $cv = str_replace(' ', '', $cv);
                        if ($normalStrLen === -1 || strlen($cv) === $normalStrLen) {
                            $valuesEqual[] = $cv;
                        } else {
                            $valuesLike[] = $cv;
                        }
                    }
                }
            }

            //Сформируем элементы запроса, которые будут разделены через OR
            $queryArr = [];

            //Добавим элементы запроса из эквивалентных значений
            if (count($valuesEqual) === 1) {
                //Найден всего один элемент
                $queryArr[] = $sqlVarName . " = '" . self::$mysqli->real_escape_string($valuesEqual[0]) . "'";
            } elseif (count($valuesEqual) > 1) {
                //Найдено множество элементов
                //Внесём в запрос в виде IN
                $queryArr[] = $sqlVarName . " IN (" . self::createIn($valuesEqual) . ")";
            }

            //Добавим запрос элементы типа LIKE
            if (count($valuesLike)) {
                foreach ($valuesLike as $val) {
                    if ($fullLike) {
                        $queryArr[] = $sqlVarName . " LIKE '%" . self::$mysqli->real_escape_string($val) . "%'";
                    } else {
                        $queryArr[] = $sqlVarName . " LIKE '" . self::$mysqli->real_escape_string($val) . "%'";
                    }
                }
            }

            if (count($queryArr)) {
                $query = "(" . implode(' OR ', $queryArr) . ")";
            }

        }
        return $query;
    }

    /**
     * Склеивает несколько строк из в один большой SQL WHERE блок через указанный разделитель ($separator)
     * Особенность в том, что перед склейкой этот метод убирает пустые элементы
     * Метод с переменным числом параметров
     * @param string $separator
     * @param string $sqlWhere1
     * @param string $sqlWhere2
     * @param null|string $_ [optional]
     * @return string
     */
    public static function maybeImplodeSqlWheres($separator, $sqlWhere1, $sqlWhere2, $_ = null)
    {
        $argv = func_get_args();
        //Удаляем первый - это separator
        array_shift($argv);

        $argv = array_map('trim', $argv);
        $argv = array_filter($argv);
        if (!$argv) {
            return '';
        }

        $separator = ' ' . trim($separator) . ' ';
        return '(' . implode($separator, $argv) . ')';
    }

    public static function getSqlWhereDates(string $sqlVarName, $dateFrom, $dateUntil, $asInterval = false)
    {
        $query = [];

        $dateFrom = FieldDate::parse($dateFrom);
        if (strlen($dateFrom) === 10) {
            $dateFrom .= ' 00:00';
        }
        $dateUntil = FieldDate::parse($dateUntil);
        if (strlen($dateUntil) === 10) {
            $dateUntil .= ' 23:59';
        }

        if ($asInterval) {
            if ($dateFrom !== '') {
                $query[] = $sqlVarName . "Until >= '" . self::$mysqli->real_escape_string($dateFrom) . "'";
            }
            if ($dateUntil !== '') {
                $query[] = $sqlVarName . "From <= '" . self::$mysqli->real_escape_string($dateUntil) . "'";
            }
        } else {
            if ($dateFrom !== '') {
                $query[] = $sqlVarName . " >= '" . self::$mysqli->real_escape_string($dateFrom) . "'";
            }
            if ($dateUntil !== '') {
                $query[] = $sqlVarName . " <= '" . self::$mysqli->real_escape_string($dateUntil) . "'";
            }
        }

        if (count($query)) {
            $query = '(' . implode(' AND ', $query) . ')';
        } else {
            $query = '1';
        }

        return $query;
    }

    public static function begin()
    {
        self::query("BEGIN");
    }

    public static function commit()
    {
        self::query("COMMIT");
    }

    public static function truncate($table)
    {
        self::query("TRUNCATE TABLE " . Db::quote($table));
    }

    public static function escString($value)
    {
        $value = (string)$value;
        return "'" . self::esc($value) . "'";
    }

    public static function createIn($data)
    {
        if (!is_array($data) || !$data) {
            return '0';
        }

        foreach ($data as &$value) {
            $value = "'" . self::$mysqli->real_escape_string($value) . "'";
        }

        return implode(',', $data);
    }

    private static function createSet($data)
    {
        if (!is_array($data) || !$data) {
            return 'NULL';
        }

        $query = [];
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $sqlValue = serialize($value);
            } elseif ($value === null) {
                $sqlValue = 'NULL';
            } else {
                $sqlValue = "'" . self::$mysqli->real_escape_string($value) . "'";
            }

            $query[] = self::quote($key) . '=' . $sqlValue;
        }
        return implode(',', $query);
    }

}
