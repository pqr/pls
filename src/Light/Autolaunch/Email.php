<?php

namespace Light\Autolaunch;

use Light\Config;
use Light\Db;
use Light\DbLog;
use Light\File;
use Light\Html;
use Light\Mailer;
use Light\Module\Autolaunch\AutolaunchAbstract;
use function Light\reportException;
use Light\Timer;

class Email extends AutolaunchAbstract
{
    protected static $_title = 'System: Email Queue';
    protected static $_description = 'Отправляет письма добавленные в очередь в таблицу queue_email. Этот скрипт нужно настроить на автоматический запуск каждую минуту.';

    /**
     * @var int число писем, которые можно отпарвить за один запуск этого Autolaunch
     */
    protected $maxLoops = 50;

    protected function run(array $params)
    {
        $pid = uniqid('', false);

        $totalSent = 0;
        $errors = 0;

        $count = $this->maxLoops;
        while ($count--) {

            $record = $this->pickOneRecord($pid);
            if (!$record) {
                // Следующей записи для обработки не нашлось, прерываем цикл
                break;
            }

            $sent = $this->proceedOneRecord($record);
            if ($sent) {
                $totalSent += $sent;
            } else {
                $errors++;
            }
            // Сбросим накопившийся буфер логов, чтобы не накопилось слишком много
            DbLog::flush();

        }

        // Если какие-то записи в очереди не удалось обработать и они там так и остались
        // то надо снять с них блокирующее поле PID
        Db::query("UPDATE queue_email SET PID = NULL WHERE PID = @s", $pid);

        if ($totalSent === 0 && $errors === 0) {

            $msg = 'Очередь пуста';
            $this->_msgBuff->addSkipp($msg);
            $this->_msgBuffShort->addSkipp($msg);

        } else {

            $this->_msgBuff->addEmptyLine();
            $msg = 'Отправлено писем: ' . $totalSent;
            $this->_msgBuff->addSuccessOrInfo($msg, $totalSent);
            $this->_msgBuffShort->addSuccessOrInfo($msg, $totalSent);

            if ($errors) {
                $msg = 'Ошибок: ' . $errors;
                $this->_msgBuff->addError($msg);
                $this->_msgBuffShort->addError($msg);
            }
        }
    }

    private function pickOneRecord($pid)
    {
        // Для одной записи из таблицы установим флаг блокировки (PID)
        $updateQuery = "UPDATE queue_email SET PID = @s WHERE RetryCount < 3 AND PID IS NULL ORDER BY ID LIMIT 1";
        $res = Db::query($updateQuery, $pid);
        if (!$res || !Db::getAffectedRows()) {
            // Ничего не обновилось, значит нет больше записей доступных для обработки
            return null;
        }

        // Попробуем выбрать из базы только что заблокированную с помощью PID запись
        // Эту операцию нужно сделать атомарно, поэтому сначала ставим PID, а только потом выбираем из очереди ID
        $selectQuery = "SELECT * FROM queue_email WHERE PID = @s ORDER BY ID LIMIT 1";
        return Db::getRow($selectQuery, $pid);
    }

    private function getMailer()
    {
        static $mailer;

        if (!$mailer) {

            $transport = new \Swift_SmtpTransport(Config::$swiftmailerHost, Config::$swiftmailerPort,
                Config::$swiftmailerSecuriy);

            if (Config::$swiftmailerUser) {
                $transport->setUsername(Config::$swiftmailerUser);
            }

            if (Config::$swiftmailerPassword) {
                $transport->setPassword(Config::$swiftmailerPassword);
            }

            $mailer = new \Swift_Mailer($transport);

        }

        return $mailer;
    }

    private static function emailsStringToArray(string $emails): array
    {
        return array_values(array_unique(array_filter(array_map('strtolower',
            array_map('trim', explode(',', $emails))))));
    }

    /**
     * Непосредсвенная отправка письма
     * @param array $record - данные выбранной строки из таблицы queue_email
     * @return int
     */
    protected function proceedOneRecord(array $record)
    {
        $timer = new Timer();

        // Заготовим переменную для возможного сообщения об ошибке в перехваченном Exception
        $emsg = null;

        // Массив прикреплённых файлов для логов
        $attachedFiles = [];

        try {

            $message = new \Swift_Message($record['Subject'], $record['Body'], $record['ContentType']);

            $to = self::emailsStringToArray($record['To']);

            $message->setFrom($record['From'])
                ->setTo($to);

            if ($record['Cc']) {
                $cc = self::emailsStringToArray($record['Cc']);
                // Уберём из массив Cc те адреса, которые уже встречались в To (чтобы пользователю не приходило по два письма)
                $cc = array_diff($cc, $to);
                if ($cc) {
                    $message->setCc($cc);
                }
            }

            if ($record['Bcc']) {
                $bcc = self::emailsStringToArray($record['Bcc']);
                if ($bcc) {
                    $message->setBcc($bcc);
                }
            }

            if ($record['ReplyTo']) {
                $message->setReplyTo(self::emailsStringToArray($record['ReplyTo']));
            }

            // Проверим, нужно ли прикладывать какие-то файлы к письму?
            $attachementsDir = Mailer::getAttachementsDir($record['ID']);
            if (is_dir($attachementsDir)) {
                foreach (scandir($attachementsDir) as $filenameEnc) {
                    if ($filenameEnc[0] === '.') {
                        continue;
                    }

                    $message->attach(\Swift_Attachment::fromPath($attachementsDir . '/' . $filenameEnc));

                    $attachedFiles[] = File::decode($filenameEnc);
                }
            }

            $mailer = $this->getMailer();
            $sent = $mailer->send($message);

        } catch (\Exception $e) {

            $sent = 0;
            $emsg = $e->getMessage();
            if (PHP_OS === 'WINNT') {
                $emsg = mb_convert_encoding($e->getMessage(), 'UTF-8', 'cp1251');
            }
        }

        if ($sent) {

            $this->_msgBuff->addInfo('From: ' . $record['From']);
            $this->_msgBuff->addInfo('To: ' . $record['To']);
            if ($record['Cc']) {
                $this->_msgBuff->addInfo('Cc: ' . $record['Cc']);
            }
            if ($record['Bcc']) {
                $this->_msgBuff->addInfo('Bcc: ' . $record['Bcc']);
            }
            if ($record['ReplyTo']) {
                $this->_msgBuff->addInfo('Reply To: ' . $record['ReplyTo']);
            }
            $this->_msgBuff->addInfo('Subject: ' . $record['Subject']);
            if ($attachedFiles) {
                $this->_msgBuff->addInfo('Attachements: ' . implode(", ", $attachedFiles));
            }
            $this->_msgBuff->addHtml('Body: ' . Html::getTruncatedText($record['Body'], 0));

            // Если отправить удалось, то перемещаем запись из queue_email в emaillog
            $this->moveToLog($record);

        } else {

            $msg = 'Не удалось отправить E-mail.';
            if ($emsg) {
                // Есть какое-то сообщение из Exception
                $msg .= ' ' . $emsg;
            }
            $this->_msgBuff->addError($msg);

            reportException(new \Exception($msg));

            // Если отправить не удалось, просто увеличим счётчик RetryCount
            Db::query("UPDATE queue_email SET RetryCount = RetryCount + 1 WHERE ID = @i", $record['ID']);

        }

        $this->_msgBuff->addHtml('<p class="note">Время выполнения: ' . $timer->getHtml() . '</p>');
        $this->_msgBuff->addEmptyLine();

        // Перед отправкой следующего письма сделаем задержку в 5 секунд
        sleep(5);

        return $sent;
    }

    private function moveToLog(array $record)
    {
        $recordId = $record['ID'];
        Db::query("DELETE FROM queue_email WHERE ID = @i", $recordId);
        unset($record['ID']);
        unset($record['PID']);
        $record['RetryCount']++;
        $logId = Db::insertRow($record, 'emaillog');

        // Переместим все файлы вложений в отдельную папку log
        $attachementsDir = Mailer::getAttachementsDir($recordId);
        if (is_dir($attachementsDir)) {
            // Сначала подготовим папку назначения
            $destFolder = LIGHT_DIR_LOGS . "/QueueEmailAttachementsLog/" . floor($logId / 1000);
            File::mkdir($destFolder);

            rename($attachementsDir, "$destFolder/$logId");
        }
    }

}