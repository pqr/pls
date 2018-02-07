<?php

namespace Light;

class Mailer
{
    public static function wrapBody(string $rawBody): string
    {
        return Template::parseTpl(LIGHT_DIR_LIGHT . '/templates/emailhtml.tpl.php', ['body' => $rawBody]);
    }

    public static function sendMail(
        $from,
        $to,
        $cc,
        string $subject,
        string $body,
        array $attachements = null,
        Msg $msgBuff = null,
        $bcc = '',
        string $contentType = 'text/html',
        $replyTo = ''
    ) {
        if (is_array($to)) {
            $to = implode(', ', $to);
        }

        if (is_array($cc)) {
            $cc = implode(', ', $cc);
        }

        if (is_array($bcc)) {
            $bcc = implode(', ', $bcc);
        }

        if (is_array($replyTo)) {
            $replyTo = implode(', ', $replyTo);
        }

        $insertData = [
            'CreatedBy' => User::getId(),
            'Subject' => $subject,
            'From' => $from,
            'To' => $to,
            'Cc' => $cc,
            'Bcc' => $bcc,
            'Body' => $body,
            'ContentType' => $contentType,
            'ReplyTo' => $replyTo,
            'Attachements' => $attachements ? implode("\n", $attachements) : ''
        ];

        if ($attachements) {
            // Если есть какой-то список вложений, то создадим запись с флагом PID = -1,
            // чтобы отправка письма не началась раньше чем мы успеем скопировать файлы в нужную папку
            $insertData['PID'] = -1;
        }

        $id = Db::insertRow($insertData, 'queue_email');

        if ($attachements) {
            // Скопируем файлы во временную папку имя которой содержит ID записи в очереди
            $attachementsDir = self::getAttachementsDir($id);
            File::mkdir($attachementsDir);
            foreach ($attachements as $filepathAbsEnc) {
                $basename = pathinfo($filepathAbsEnc, PATHINFO_BASENAME);
                copy($filepathAbsEnc, $attachementsDir . '/' . $basename);
            }

            // После завершения копирования сниманием PID = -1
            Db::query("UPDATE queue_email SET PID = NULL WHERE ID = $id");
        }

        if ($msgBuff) {
            $msgBuff->addSuccess(self::getSuccessSentMessage($subject, $to, $cc, $attachements));
        }

        return $id;
    }

    private static function getSuccessSentMessage(
        string $subject,
        string $to,
        string $cc = null,
        array $attachements = null
    ) {
        $msg = 'На почту <strong>' . htmlspecialchars($to) . '</strong> будет отправлено сообщение <strong>' . htmlspecialchars($subject) . '</strong>';
        if ($cc) {
            if (is_array($cc)) {
                $cc = implode(', ', $cc);
            }
            $msg .= ' (Cc: ' . htmlspecialchars($cc) . ')';
        }
        if ($attachements) {
            $msg .= ', вложений: <span title="'
                . htmlspecialchars(implode(', ', $attachements))
                . '">' . count($attachements) . '</span>';
        }
        return $msg;
    }

    public static function getAttachementsDir(int $recordId): string
    {
        $subfolder = floor($recordId / 1000);
        return LIGHT_DIR_TEMP . "/QueueEmailAttachements/$subfolder/$recordId";
    }
} 
