<?php

namespace Light\Autolaunch;

use Light\Db;
use Light\DbLog;
use Light\Mailer;
use Light\Module\Autolaunch\AutolaunchAbstract;
use Light\Msg;
use Light\Timer;
use function Light\reportException;

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
        $alreadyDoneIds = [];

        $totalSent = 0;
        $errors = 0;

        $count = $this->maxLoops;
        while ($count--) {

            $record = $this->pickOneRecord($pid, $alreadyDoneIds);
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

            $alreadyDoneIds[] = $record['ID'];

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

    private function pickOneRecord($pid, $alreadyDoneIds)
    {
        // Для одной записи из таблицы установим флаг блокировки (PID)
        $updateQuery = "UPDATE queue_email SET PID = @s WHERE RetryCount < 1 AND PID IS NULL";
        if ($alreadyDoneIds) {
            //Если передан массив ID уже обработанных на предыдущих шагах записей, то добавим условие, чтобы не выбирать эти записи заново
            $updateQuery .= " AND ID NOT IN(" . implode(',', $alreadyDoneIds) . ")";
        }
        $updateQuery .= " ORDER BY ID LIMIT 1";
        $res = Db::query($updateQuery, $pid);
        if (!$res || !Db::getAffectedRows()) {
            // Ничего не обновилось, значит нет больше записей доступных для обработки
            return null;
        }

        // Попробуем выбрать из базы только что заблокированную с помощью PID запись
        // Эту операцию нужно сделать атомарно, поэтому сначала ставим PID, а только потом выбираем из очереди ID
        $selectQuery = "SELECT * FROM queue_email WHERE PID=@s";
        if ($alreadyDoneIds) {
            //Если передан массив ID уже обработанных на предыдущих шагах записей, то добавим условие, чтобы не выбирать эти записи заново
            $selectQuery .= " AND ID NOT IN(" . implode(',', $alreadyDoneIds) . ")";
        }
        $selectQuery .= " ORDER BY ID LIMIT 1";
        return Db::getRow($selectQuery, $pid);
    }

    /**
     * Непосредсвенная отправка письма
     * @param array $record - данные выбранной строки из таблицы queue_email
     * @return int
     */
    protected function proceedOneRecord(array $record)
    {
        $timer = new Timer();

        $mail = Mailer::create($record['To'], $record['Subject'], $record['Body'])
            ->from($record['From'])
            ->cc($record['Cc'])
            ->bcc($record['Bcc'])
            ->replyTo($record['ReplyTo'])
            ->attachements(explode("\n", $record['Attachements']));

        if ($record['ContentType'] == 'text/html') {
            $mail->asHtml();
        } else {
            $mail->asText();
        }

        $mail->setFromQueue($record['CreatedBy'], $record['CreatedDateTime'], $record['RetryCount'] + 1);

        $thisMsgBuff = new Msg();
        $sent = $mail->sendImmideatelly($thisMsgBuff);
        if ($sent) {
            $this->removeFromQueue($record['ID']);
        } else {
            // Если отправить не удалось, просто увеличим счётчик RetryCount
            $report = $thisMsgBuff->getHtml();
            $this->incRetryCount($record['ID'], $report);
            reportException(new \Exception($report));
        }

        $this->_msgBuff->merge($thisMsgBuff);

        $this->_msgBuff->addHtml('<p class="note">Время выполнения: ' . $timer->getHtml() . '</p>');
        $this->_msgBuff->addEmptyLine();

        // Перед отправкой следующего письма сделаем задержку в 2 секунды
        sleep(2);

        return $sent;
    }

    private function removeFromQueue(int $queueId)
    {
        Db::query("DELETE FROM queue_email WHERE ID = $queueId");
    }

    private function incRetryCount(int $queueId, string $report)
    {
        Db::query(
            "UPDATE queue_email 
            SET RetryCount = RetryCount + 1,
                Report = CONCAT(" . Db::escString($report) . ", '\\n\\n')
            WHERE ID = $queueId"
        );
    }

}
