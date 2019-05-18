<?php


namespace Forward\Autolaunch;


use Light\Config;
use Light\Mailer;
use Light\Module\Autolaunch\AutolaunchAbstract;

class DiskFreeSpace extends AutolaunchAbstract
{
    const WARN_GB = 1;
    const NOTIFY_EMAILS = 'petr.myazin@gmail.com, mike.zgodko@proactive-gl.ru';

    protected static $title = 'Проверяет свободное место на диске с программой и диске с папками.';
    protected static $description = 'Если свободного места меньше 1 Гб, то отправляются E-mail уведомления на ' . self::NOTIFY_EMAILS . '. Провека должна запускаться раз в день.';

    protected function run(array $params)
    {
        if (!Config::$filesfolderLocal) {
            $this->_msgBuffShort->addError('Config::$filesfolderLocal not set');
            return;
        }

        $this->check(Config::$filesfolderLocal, 'с папками');
        $this->check(LIGHT_DIR_ROOT, 'с программой');
    }

    private function check(string $dir, string $folderTitle)
    {
        $bytes = disk_free_space($dir);
        $gb = $bytes / 1024 / 1024 / 1024;
        $isEnough = $gb > self::WARN_GB;
        $this->_msgBuffShort->addSuccessOrInfo(
            "Свободное место на диске $folderTitle: " . number_format($gb, 1, '.', ' ') . " Гб",
            $isEnough
        );

        if (!$isEnough) {
            $subject = "Заканчивается свободное место на диске $folderTitle, осталось $gb Гб";
            $body = $subject . ", директория " . $dir;
            Mailer::create(self::NOTIFY_EMAILS, $subject, $body)
                ->asText()
                ->send($this->_msgBuff);
        }
    }
}
