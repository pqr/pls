<?php

namespace Light\Autolaunch;


use Light\Config;
use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Light\Msg;
use Light\User;

class Migrate extends AutolaunchAbstract
{
    private const RESULT_ERROR = 2;
    private const RESULT_SKIPPED = 0;
    private const RESULT_SUSCCESS = 1;

    protected function run(array $params)
    {
        Config::$sqlErrorThrowException = true;

        $results = [
            self::RESULT_ERROR => 0,
            self::RESULT_SKIPPED => 0,
            self::RESULT_SUSCCESS => 0,
        ];
        // Читаем все файлы из папки sql
        $dir = LIGHT_DIR_ROOT . '/sql';
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                $result = $this->migrageSqlFile($dir, $file);
                $results[$result]++;
            }
        }

        $this->_msgBuff->addEmptyLine();

        $msg = "Success: " . $results[self::RESULT_SUSCCESS];
        $this->_msgBuff->addSuccessOrInfo($msg, $results[self::RESULT_SUSCCESS]);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $results[self::RESULT_SUSCCESS]);

        if ($results[self::RESULT_SKIPPED]) {
            $msg = "Skipped: " . $results[self::RESULT_SKIPPED];
            $this->_msgBuff->addSkipp($msg);
            $this->_msgBuffShort->addSkipp($msg);
        }
        if ($results[self::RESULT_ERROR]) {
            $msg = "Errors: " . $results[self::RESULT_ERROR];
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);
        }
    }

    private function migrageSqlFile(string $dir, string $file)
    {
        if (strpos($file, '.') === 0) {
            return self::RESULT_SKIPPED;
        }

        // Имя файла должно начинаться с даты
        if (!preg_match('/^\d{4}_\d{2}_\d{2}/', $file)) {
            return self::RESULT_SKIPPED;
        }

        // Проверим, есть ли в таблице migration?
        $existId = Db::getOne("SELECT ID FROM migration WHERE Name = @s", $file);
        if ($existId) {
            return self::RESULT_SKIPPED;
        }

        $content = file_get_contents("$dir/$file");
        if (!$content) {
            return self::RESULT_SKIPPED;
        }

        $this->_msgBuff->addEmptyLine();
        $this->_msgBuff->addInfo('<strong>' . htmlspecialchars($file) . '</strong>');

        $result = 0;
        $msgBuff = new Msg();
        $queries = array_filter(array_map('trim', explode(';', $content)));
        foreach ($queries as $query) {
            try {
                if (strpos($query, '-- AUTOLAUNCH') === 0) {

                    $autolaunchCliName = trim(str_replace('-- AUTOLAUNCH', '', $query));
                    $out = shell_exec('php ' . LIGHT_DIR_PUBLIC . '/index.php ' . $autolaunchCliName);
                    $msgBuff->addInfo("<strong>Autolaunch: " . htmlspecialchars($autolaunchCliName) . "</strong>");
                    $msgBuff->addInfo($out);

                } else {
                    Db::query($query);
                    $msgBuff->addSuccess($query);
                    $msgBuff->addSuccess("Affected rows: " . Db::getAffectedRows());
                }
                $result = self::RESULT_SUSCCESS;
            } catch (\Throwable $e) {
                $msgBuff->addError($e->getMessage());
                $result = self::RESULT_ERROR;
            }
        }

        Db::insertRow([
            'CreatedBy' => User::getId(),
            'Name' => $file,
            'Report' => $msgBuff->getHtml(),
        ], 'migration');

        $this->_msgBuff->addMessagess($msgBuff->getAll());
        return $result;
    }

}
