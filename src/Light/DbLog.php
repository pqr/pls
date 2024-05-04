<?php

namespace Light;

class DbLog
{
    /** @var array */
    private static $_buffer;

    public static function write(
        $masterModule,
        $masterId,
        $tableName,
        $recordId,
        $fieldKey,
        $fieldDescription,
        array $textData
    ): void {
        // Если буфер ещё не инициалирован, значит это вообще первое обращение к DbLog::write()
        // Одновременно с этим инициализируем register_shutdown_function
        if (self::$_buffer === null) {
            self::$_buffer = [];
            register_shutdown_function('\\Light\\DbLog::flush');
        }

        //Накапливаем сообщения в буфере. Реальную запись в базу будем делать при завершении приложения в register_shutdown_function
        //Для этого в самом начале работы приложения был вызван метод register_shutdown_function('DbLog::flush');
        self::$_buffer[] = [
            'masterModule' => Misc::getModule($masterModule),
            'masterId' => $masterId,
            'tableName' => $tableName,
            'recordId' => $recordId,
            'fieldKey' => $fieldKey,
            'fieldDescription' => $fieldDescription,
            'textData' => $textData,
        ];
    }

    /**
     * Метод, который реально записывает накопившиеся логи в базу
     */
    public static function flush(): void
    {
        if (!self::$_buffer) {
            //Нечего записывать в базу
            return;
        }

        $timer = new Timer();

        Db::begin();

        $query = "INSERT INTO dblog (
                          CreatedBy
                        , ModuleID
                        , MasterID
                        , TableID
                        , RecordID
                        , FieldID)
                      VALUES (
                          " . User::getId() . "
                        , ?
                        , ?
                        , ?
                        , ?
                        , ?
                      )";
        $stmtInsert = Db::$mysqli->prepare($query);

        //Накопительный массив из ID вставленных в основнюу таблицу (dblog) строк
        $dblogIds = [];
        foreach (self::$_buffer as $index => $item) {

            $moduleId = $item['masterModule'] ? self::getModuleId($item['masterModule']) : 0;
            $tableId = $item['tableName'] ? self::getTableId($item['tableName']) : 0;
            $fieldId = $item['fieldKey'] && $tableId ? self::getFieldId(
                $tableId,
                $item['fieldKey'],
                $item['fieldDescription']
            ) : 0;

            $stmtInsert->bind_param('iiiii', $moduleId, $item['masterId'], $tableId, $item['recordId'], $fieldId);
            /** @noinspection DisconnectedForeachInstructionInspection */
            $stmtInsert->execute();
            $dblogIds[$index] = $stmtInsert->insert_id;

        }

        $stmtInsert->close();

        // После вставки основных данных и получения всех ID, можем вставить текстовые данные одним запросом
        $queryArr = [];

        foreach (self::$_buffer as $index => $item) {
            if (!empty($item['textData'])) {

                $dbLogId = $dblogIds[$index];
                $textData = $item['textData'];

                $text = isset($textData['Text']) ? Db::escString($textData['Text']) : 'NULL';
                $oldValue = isset($textData['OldValue']) ? Db::escString($textData['OldValue']) : 'NULL';
                $newValue = isset($textData['NewValue']) ? Db::escString($textData['NewValue']) : 'NULL';
                $oldValueFormated = isset($textData['OldValueFormated']) ? Db::escString($textData['OldValueFormated']) : 'NULL';
                $newValueFormated = isset($textData['NewValueFormated']) ? Db::escString($textData['NewValueFormated']) : 'NULL';
                $objectDescription = isset($textData['ObjectDescription']) ? Db::escString($textData['ObjectDescription']) : 'NULL';


                $queryArr[] = "($dbLogId, $text, $oldValue, $newValue, $oldValueFormated, $newValueFormated, $objectDescription)";

            }
        }

        if ($queryArr) {

            $query = "INSERT INTO dblog_text (
                          dblogID
                        , Text
                        , OldValue
                        , NewValue
                        , OldValueFormated
                        , NewValueFormated
                        , ObjectDescription
                      ) VALUES " . implode(', ', $queryArr);

            Db::query($query);
        }

        // Наконец, запомним ID всех событий записанных в dblog в специальную таблицу dblog_trace
        // чтобы позже можно было получить объединённую картину по всем сделанным за раз измененениям
        Db::insertRow([
            'CreatedBy' => User::getId(),
            'DbLogIDs' => implode(',', $dblogIds),
        ], 'dblog_trace');

        Db::commit();

        $worktimeMs = $timer->getMs();

        Metric::newrelicMetric('DbLog::flush', $worktimeMs);

        Metric::newrelicEvent('DbLog::flush', [
                'DbLogRecords' => count(self::$_buffer),
                'WorkTime' => $worktimeMs,
                'UserID' => User::getId(),
                'User' => User::getName(),
                'Module' => Request::getModule(),
            ]
        );

        // На всякий случай очистим буфер
        self::$_buffer = [];
    }

    public static function getModuleId(string $moduleName)
    {
        static $modules;
        if ($modules === null) {
            $modules = Db::getAssoc("SELECT LOWER(ModuleName), ID FROM dblog_module");
        }
        $moduleName = Misc::getModule($moduleName);
        $moduleNameLower = strtolower($moduleName);

        if (!isset($modules[$moduleNameLower])) {
            Db::query("INSERT INTO dblog_module (ModuleName) VALUES ('" . Db::esc($moduleName) . "')");
            $modules[$moduleNameLower] = Db::getInsertId();
        }

        return $modules[$moduleNameLower];
    }

    public static function getTableId(string $tableName)
    {
        static $tables;
        if ($tables === null) {
            $tables = Db::getAssoc("SELECT LOWER(TableName), ID FROM dblog_table");
        }

        $tableNameLower = strtolower($tableName);
        if (!isset($tables[$tableNameLower])) {
            Db::query("INSERT INTO dblog_table (TableName) VALUES (@s)", $tableNameLower);
            $tables[$tableNameLower] = Db::getInsertId();
        }

        return $tables[$tableNameLower];
    }

    public static function getFieldId(int $tableId, string $fieldKey, string $fieldDescription = null)
    {
        static $fieldsPerTable = [];
        if (!isset($fieldsPerTable[$tableId])) {
            $fieldsPerTable[$tableId] = Db::getAssoc(
                "SELECT FieldKey, ID, FieldDescription FROM dblog_field WHERE TableID = @i", $tableId
            );
        }

        if (!isset($fieldsPerTable[$tableId][$fieldKey])) {
            //Если поля ещё нет в справочнике - вставим его
            $fieldData = ['TableID' => $tableId, 'FieldKey' => $fieldKey];
            if ($fieldDescription) {
                $fieldData['FieldDescription'] = $fieldDescription;
            }

            $fieldsPerTable[$tableId][$fieldKey] = [
                'ID' => Db::insertRow($fieldData, 'dblog_field', true),
                'FieldDescription' => $fieldDescription,
            ];

        } else {
            //Поле уже есть в справочнике - проверим, совпадает ли Description?
            if ($fieldDescription && $fieldsPerTable[$tableId][$fieldKey]['FieldDescription'] !== $fieldDescription) {
                //Обновим в справочнике в базе данных
                Db::query(
                    "UPDATE dblog_field SET FieldDescription = @s WHERE ID = @i", $fieldDescription,
                    $fieldsPerTable[$tableId][$fieldKey]['ID']
                );
            }
        }

        return $fieldsPerTable[$tableId][$fieldKey]['ID'];
    }
}
