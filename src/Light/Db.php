<?php

namespace Light;

use Light\Field\FieldDate;

class Db
{
    private static $queriesCount = 0;
    private static $queriesTime = 0;
    private static $lastQueryTime = 0;
    private static $queriesLog = [];

    /** @var \mysqli */
    public static $mysqli;

    public static function connect(): void
    {
        try {
            self::$mysqli = new \mysqli(Config::$mysqlHost, Config::$mysqlUser, Config::$mysqlPassword,
                Config::$mysqlDb,
                Config::$mysqlPort);
            if (self::$mysqli->connect_error) {
                echo self::$mysqli->connect_error;
                exit();
            }
        } catch (\Throwable $e) {
            die($e->getMessage());
        }

        self::$queriesCount = 0;
        self::$queriesTime = 0;
        self::$lastQueryTime = 0;
        self::$queriesLog = [];

        self::$mysqli->set_charset('utf8');
        self::$mysqli->set_opt(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
        self::$mysqli->query("SET SESSION SQL_MODE = 'ALLOW_INVALID_DATES'");
    }

    /**
     * низкоуровневая функция, вызывает оригинальную mysqli::query
     * @param string $query
     * @param array $params
     * @return mixed|\mysqli_result
     * @throws \RuntimeException
     */
    public static function query($query, ...$params)
    {
        self::$queriesCount++;
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

        if (Config::$debug || (isset($_COOKIE['sqllog']) && $_COOKIE['sqllog'])) {
            self::$queriesLog[] = [$queryTime, $query];
        }

        self::$queriesTime += $queryTime;
        self::$lastQueryTime = $queryTime;

        return $res;
    }

    public static function parse($rawQuery, ...$params)
    {
        $outQuery = '';

        /** @noinspection NotOptimalRegularExpressionsInspection */
        $array = preg_split(
            '/(@[nsifuapl12]{1,2}_[a-zA-Z0-9_]*|@[nsifuapl12]{1,2})/u',
            (string) $rawQuery,
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
     * @throws \RuntimeException
     */
    private static function riseError($query, $errMsg): void
    {
        $msg = 'SQL error: ' . $query . "\n\n" . $errMsg;

        ErrorHandler::reportSqlError($query, $errMsg);

        if (Config::$sqlErrorThrowException) {
            throw new \RuntimeException($msg);
        }

        $msgHtml = 'SQL error: ' . $query . htmlspecialchars((string) $errMsg);
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

    /**
     * Возвращает данные в виде массивов с индексами (не ассоциативных с ключами)
     * @param $query
     * @return array
     * @throws \Exception
     */
    public static function getAllValues($query, mixed ...$params): array
    {
        $res = self::query($query, ...$params);
        if (!$res) {
            return [];
        }

        $a = $res->fetch_all(MYSQLI_NUM);
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

    public static function getCount($table, string $where = ''): int
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

    public static function getOnDuplicateQuery(array $onDuplicateUpdateFieldKeys): string
    {
        if (!$onDuplicateUpdateFieldKeys) {
            return '';
        }
        $parts = [];
        foreach ($onDuplicateUpdateFieldKeys as $fieldKey) {
            $parts[] = self::quote($fieldKey) . " = VALUES(" . self::quote($fieldKey) . ")";
        }
        return " ON DUPLICATE KEY UPDATE " . implode(', ', $parts);
    }

    public static function insertIgnoreRow(
        array $fieldValues,
        string $table
    ) {
        return self::insertRow($fieldValues, $table, false, true);
    }

    public static function insertRow(
        array $fieldValues,
        string $table,
        bool $replace = false,
        $ignore = false,
        array $onDuplicateUpdateFieldKeys = []
    ) {
        $fieldKeys = implode(',', array_map(self::class . '::quote', array_keys($fieldValues)));

        $query = ($replace ? 'REPLACE' : 'INSERT')
            . (!$replace && $ignore ? ' IGNORE' : '')
            . " INTO " . self::quote($table)
            . " ($fieldKeys)"
            . " VALUES (" . self::createIn($fieldValues) . ")";

        if (!$replace && $onDuplicateUpdateFieldKeys) {
            $query .= self::getOnDuplicateQuery($onDuplicateUpdateFieldKeys);
        }

        self::query($query);
        return self::getInsertId();
    }

    public static function insertIgnoreRows(array $rows, $table)
    {
        return self::insertRows($rows, $table, false, true);
    }

    /**
     * Вставляет несколько строк одним запросом, возвращает число вставленных строк
     * @param array $rows массив строк. Важно: каждая строка должна содержать одинаковый набор полей
     * @param string $table
     * @param bool $replace
     * @param bool $ignore
     * @return int
     * @throws \Exception
     */
    public static function insertRows(
        array $rows,
        $table,
        $replace = false,
        $ignore = false,
        array $onDuplicateUpdateFieldKeys = []
    ) {
        if (!$rows) {
            return 0;
        }

        $firstRow = reset($rows);
        $fieldKeys = implode(',', array_map(self::class . '::quote', array_keys($firstRow)));

        $query = ($replace ? 'REPLACE' : 'INSERT')
            . (!$replace && $ignore ? ' IGNORE' : '')
            . " INTO " . self::quote($table)
            . " (" . $fieldKeys . ")"
            . " VALUES "
            . implode(',', array_map(fn($fieldValues) => "(" . self::createIn($fieldValues) . ")", $rows));

        if (!$replace && $onDuplicateUpdateFieldKeys) {
            $query .= self::getOnDuplicateQuery($onDuplicateUpdateFieldKeys);
        }

        self::query($query);
        return self::getAffectedRows();
    }

    public static function replaceRow(array $fieldValues, $table)
    {
        return self::insertRow($fieldValues, $table, true);
    }

    /**
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

            $moduleId = 0;
            if ($masterModule) {
                // Преобразуем к простому текстовому виду на случай, если было передано имя модуля как FQN Class
                $masterModule = Misc::getModule($masterModule);
                $moduleId = DbLog::getModuleId($masterModule);
            }

            // Если были найдены какие-то данные в базе, сериализуем их!
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
                    'JSON' => $json,
                ], 'deleted_text');
            }
        }

        //Физически удаляем требуюемую запись
        $delQuery = "DELETE FROM " . self::quote($tableName) . " WHERE ID = $id";
        return self::query($delQuery);
    }

    public static function insertUnique($value, string $tableName)
    {
        $value = trim((string) $value);
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

    public static function getQueriesCount(): int
    {
        return self::$queriesCount;
    }

    public static function getQueriesLog(): array
    {
        return self::$queriesLog;
    }

    public static function getQueriesTime(): int
    {
        return self::$queriesTime;
    }

    public static function getLastQueryTime(): int
    {
        return self::$lastQueryTime;
    }

    public static function explodeByComa(string $filterComaSeparated): array
    {
        $filterComaSeparated = str_replace(['"', "\r", "\n", ";"], ['', '', ',', ','], $filterComaSeparated);
        return array_map('trim', explode(',', $filterComaSeparated));
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

        $filterComaSeparatedArrRaw = self::explodeByComa($filterComaSeparated);

        if (!$isIntField && count($filterComaSeparatedArrRaw) > 1) {
            // Если это не строго числовое поле и пользователь ввёл более одного значения разделённого через запятую
            // то возможно он хочет найти не только все эти значения по отдельности, но и документ в номере котого
            // присутсвует запятая. Например, поиск "123, 456" должен превратиться в поиск по трём вариантам:
            // "123" или "456" или "123, 456"
            // Для этого добавляем в массив оригинальное значение из поля ввода фильтров
            $filterComaSeparatedArrRaw[] = $filterComaSeparated;
        }

        // в фильтре по номеру нужна возможность указать интервал номеров через тире (например: 850001-850020)
        $filterComaSeparatedArr = [];
        foreach ($filterComaSeparatedArrRaw as $elem) {

            $filterComaSeparatedArr[] = $elem;

            if (str_contains((string) $elem, '-')) {
                [$from, $to] = explode('-', (string) $elem, 2);
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
                $cv = trim((string) $cv);
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
                    if (str_contains($cv, ' ')) {
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

    public static function begin(): void
    {
        self::query("BEGIN");
    }

    public static function commit(): void
    {
        self::query("COMMIT");
    }

    public static function rollback(): void
    {
        self::query("ROLLBACK");
    }

    public static function truncate($table): void
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
            if ($value === null) {
                $value = 'NULL';
            } elseif (!is_numeric($value) || str_starts_with($value, "0")) {
                // Если это НЕ число или это чисно, но начинаетс с 0 - тогда оборачиваем в кавычки
                $value = "'" . self::$mysqli->real_escape_string($value) . "'";
            }
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

    public static function getWhere(array $keyValues): string
    {
        $where = [];
        foreach ($keyValues as $fieldKey => $value) {
            $fieldSqlWhere = Db::quote($fieldKey);
            if (is_int($value) || is_float($value)) {
                $fieldSqlWhere .= ' = ' . $value;
            } elseif (is_array($value)) {
                $fieldSqlWhere .= Db::parse(' IN (@a)', $value);
            } elseif ($value === null) {
                $fieldSqlWhere .= " IS NULL";
            } else {
                $fieldSqlWhere .= " = " . Db::escString($value);
            }

            $where[] = $fieldSqlWhere;
        }

        return implode(' AND ', $where);
    }

}
