<?php

namespace Light\Autolaunch;


use Light\Config;
use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Light\Msg;
use Light\User;

class Migrate extends AutolaunchAbstract
{
    protected function run(array $params)
    {
        Config::$sqlErrorThrowException = true;

        $success = 0;
        $errors = 0;
        // Читаем все файлы из папки sql
        $dir = LIGHT_DIR_ROOT . '/sql';
        foreach (scandir($dir) as $file) {
            if ($file[0] === '.') {
                continue;
            }

            // Имя файла должно начинаться с даты
            if (!preg_match('/^\d{4}_\d{2}_\d{2}/', $file)) {
                $this->_msgBuff->addSkipp('Skipping ' . htmlspecialchars($file) . ' - not valid filename');
                continue;
            }

            // Проверим, есть ли в таблице migration?
            $existId = Db::getOne("SELECT ID FROM migration WHERE Name = @s", $file);
            if ($existId) {
                $this->_msgBuff->addSkipp('Skipping ' . htmlspecialchars($file) . ' - already migrated');
                continue;
            }

            $content = file_get_contents("$dir/$file");
            if (!$content) {
                $this->_msgBuff->addSkipp('Skipping ' . htmlspecialchars($file) . ' - empty content');
                continue;
            }

            $this->_msgBuff->addEmptyLine();
            $this->_msgBuff->addInfo('<strong>'.htmlspecialchars($file).'</strong>');

            $msgBuff = new Msg();
            $queries = array_filter(array_map('trim', explode(';', $content)));
            foreach($queries as $query) {
                try {
                    Db::query($query);
                    $msgBuff->addSuccess($query);
                    $msgBuff->addSuccess("Affected rows: " . Db::getAffectedRows());
                    $success++;
                } catch (\Throwable $e) {
                    $msgBuff->addError($e->getMessage());
                    $errors++;
                }
            }

            Db::insertRow([
                'CreatedBy' => User::getId(),
                'Name' => $file,
                'Report' => $msgBuff->getHtml()
            ], 'migration');

            $this->_msgBuff->addMessagess($msgBuff->getAll());

        }

        $this->_msgBuff->addEmptyLine();

        $msg = "Success: $success";
        $this->_msgBuff->addSuccessOrInfo($msg, $success);
        $this->_msgBuffShort->addSuccessOrInfo($msg, $success);

        if ($errors) {
            $msg = "Errors: $errors";
            $this->_msgBuff->addError($msg);
            $this->_msgBuffShort->addError($msg);
        }
    }

}
