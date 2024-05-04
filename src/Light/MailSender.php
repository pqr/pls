<?php


namespace Light;


use Light\Module\EmailLogModule\EmailLogModule;

class MailSender
{
    /**
     * Читает указанную запись из очереди queue_email и реально отправляет по указанному адресу
     *
     * @return int кол-во отправленных сообщений
     */
    public static function sendFromQueue(int $queueId, Msg $msgBuff): int
    {
        try {
            $emailDataFromQueue = Db::getRow("SELECT * FROM queue_email WHERE ID = $queueId");
            if (!$emailDataFromQueue) {
                $msgBuff->addError("E-mail queue ID = $queueId not found");
                return 0;
            }
        } catch (\Exception $e) {
            \Light\ErrorHandler::reportException($e);
            $msgBuff->addError("Failed to load E-mail queue ID = $queueId");
            return 0;
        }

        $reportBuff = new Msg();
        $sent = 0;
        $isSuccess = false;
        try {

            if ($emailDataFromQueue['EmailAccountID']) {
                $swiftmailer = self::createSwiftmailerFromEmailAccount($emailDataFromQueue['EmailAccountID']);
            } else {
                $swiftmailer = self::createDefaultSwitfmailer();
            }

            /** @var \Swift_SmtpTransport $transport */
            $transport = $swiftmailer->getTransport();

            if (!empty($emailDataFromQueue['CustomFrom'])) {
                // Для данного письма значение From установлено вне рамок EmailAccountID (какое-то особенное)
                // именно его и писпользуем (но smtp сервер может не пропустить - разрешается ли данному приложению
                // подменять from настраивается с помощью Config::$emailAllowCustomFrom = false;)
                $from = $emailDataFromQueue['CustomFrom'];
            } else {
                $from = (string)$transport->getUsername();
            }

            $message = self::getSwiftMessage($emailDataFromQueue, $from);
            $failedRecipients = [];
            if (Config::$debug) {
                $sent = 1;
            } else {
                $sent = $swiftmailer->send($message, $failedRecipients);
            }
            if ($failedRecipients) {
                $msg = Config::$lang === 'ru' ? 'Не удалось отправить' : 'Failed recipients';
                $reportBuff->addError("$msg: " . htmlspecialchars(implode(', ', $failedRecipients)));
            }

            if ($sent) {

                $reportBuff->addSuccess(self::getSuccessMsg($emailDataFromQueue));
                $isSuccess = true;

            } else {

                throw new \Exception(self::getErrorMsg($emailDataFromQueue));

            }

        } catch (\Throwable $e) {

            \Light\ErrorHandler::reportException($e);
            $reportBuff->addError($e->getMessage());

        }

        $reportHtml = $reportBuff->getHtml();
        if ($isSuccess) {
            self::moveFromQueueToLog($emailDataFromQueue, $reportHtml, $from ?? '');
        } else {
            self::incRetryCount($queueId, $reportHtml);
        }

        $msgBuff->merge($reportBuff);

        return $sent;
    }

    private static function createSwiftmailerFromEmailAccount(int $emailAccountId): \Swift_Mailer
    {
        $emailAccount = Db::getRow("SELECT * FROM emailaccount WHERE ID = $emailAccountId");
        if (!$emailAccount) {
            throw new \InvalidArgumentException("Unknown E-mail account with ID = $emailAccountId");
        }

        $transport = new \Swift_SmtpTransport($emailAccount['Host'], $emailAccount['Port'],
            $emailAccount['Encryption']);

        if ($emailAccount['Username'] !== '') {
            $transport->setUsername($emailAccount['Username']);
        }
        if ($emailAccount['Password'] !== '') {
            $transport->setPassword($emailAccount['Password']);
        }

        return new \Swift_Mailer($transport);
    }

    private static function createDefaultSwitfmailer(): \Swift_Mailer
    {
        $transport = new \Swift_SmtpTransport(Config::$swiftmailerHost, Config::$swiftmailerPort,
            Config::$swiftmailerSecuriy);
        if (Config::$swiftmailerUser) {
            $transport->setUsername(Config::$swiftmailerUser);
        }
        if (Config::$swiftmailerPassword) {
            $transport->setPassword(Config::$swiftmailerPassword);
        }

        return new \Swift_Mailer($transport);
    }

    private static function getSwiftMessage(array $emailDataFromQueue, string $from)
    {
        $toArr = Mailer::getEmailsAsArray($emailDataFromQueue['To']);
        $message = (new \Swift_Message($emailDataFromQueue['Subject'], $emailDataFromQueue['Body'],
            $emailDataFromQueue['ContentType']))
            ->setFrom($from)
            ->setTo($toArr);

        $ccArr = Mailer::getEmailsAsArray($emailDataFromQueue['Cc']);
        if ($ccArr) {
            $message->setCc($ccArr);
        }

        $bccArr = Mailer::getEmailsAsArray($emailDataFromQueue['Bcc']);
        if ($bccArr) {
            $message->setBcc($bccArr);
        }

        $replyToArr = Mailer::getEmailsAsArray($emailDataFromQueue['ReplyTo']);
        if ($replyToArr) {
            $message->setReplyTo($replyToArr);
        }

        $attachements = explode("\n", $emailDataFromQueue['Attachements']);
        foreach ($attachements as $attachement) {
            if ($attachement && file_exists($attachement)) {
                $message->attach(\Swift_Attachment::fromPath($attachement));
            }
        }

        return $message;
    }

    private static function moveFromQueueToLog(array $emailDataFromQueue, string $reportHtml, string $from): void
    {
        $record = $emailDataFromQueue;
        unset($record['ID'], $record['PID'], $record['Report'], $record['Body']);
        $record['SentDateTime'] = date('Y-m-d H:i:s');
        $record['From'] = $from;
        $record['RetryCount']++;
        $logId = Db::insertRow($record, 'log_email');

        Db::insertRow([
                'ID' => $logId,
                'Body' => $emailDataFromQueue['Body'],
                'Report' => $reportHtml,
            ]
            , 'log_email_text'
        );

        // Скопируем все файлы вложений в отдельную папку log
        self::copyAttachementsToLogDir($logId, $emailDataFromQueue['Attachements']);

        Db::query("DELETE FROM queue_email WHERE ID = @i", $emailDataFromQueue['ID']);
    }

    private static function incRetryCount(int $queueId, string $reportHtml): void
    {
        Db::query(
            "UPDATE queue_email 
            SET RetryCount = RetryCount + 1,
                Report = CONCAT(" . Db::escString($reportHtml) . ", '\\n\\n')
            WHERE ID = $queueId"
        );
    }

    public static function getSuccessMsgQueue(array $emailDataFromQueue): string
    {
        $msg = 'На почту <strong>'
            . htmlspecialchars($emailDataFromQueue['To'])
            . '</strong> будет отправлено сообщение <strong>'
            . htmlspecialchars($emailDataFromQueue['Subject'])
            . '</strong>';

        if ($emailDataFromQueue['Cc']) {
            $msg .= ' (Cc: ' . htmlspecialchars($emailDataFromQueue['Cc']) . ')';
        }

        $attachements = array_filter(explode("\n", $emailDataFromQueue['Attachements']));
        if ($attachements) {
            $title = htmlspecialchars(implode(', ', $attachements));
            $msg .= ', вложений: <span title="' . $title . '">' . count($attachements) . '</span>';
        }

        return $msg;
    }

    private static function getSuccessMsg(array $emailDataFromQueue): string
    {
        $msg = self::getSuccessMsgQueue($emailDataFromQueue);
        $msg = str_replace('будет', '', $msg);
        return $msg;
    }

    public static function getErrorMsg(array $emailDataFromQueue): string
    {
        $msg = 'Ошибка при отправке сообщения на почту <strong>'
            . htmlspecialchars($emailDataFromQueue['To'])
            . '</strong> <strong>'
            . htmlspecialchars($emailDataFromQueue['Subject'])
            . '</strong>';

        if ($emailDataFromQueue['Cc']) {
            $msg .= ' (Cc: ' . htmlspecialchars($emailDataFromQueue['Cc']) . ')';
        }

        if ($emailDataFromQueue['Attachements']) {
            $title = htmlspecialchars(implode(', ', $emailDataFromQueue['Attachements']));
            $msg .= ', вложений: <span title="' . $title . '">' . count($emailDataFromQueue['Attachements']) . '</span>';
        }

        return $msg;
    }

    private static function copyAttachementsToLogDir(int $logId, string $attachements): void
    {
        $attachementsArr = explode("\n", $attachements);
        if (!$attachementsArr) {
            return;
        }

        $logDirAbs = FilesFolder::getAbsoluteModule(EmailLogModule::class, $logId);
        $fs = File::fs();
        foreach ($attachementsArr as $attachement) {

            if ($attachement && file_exists($attachement)) {

                $target = $logDirAbs . '/' . pathinfo($attachement, PATHINFO_BASENAME);
                try {

                    $fs->copy($attachement, $target);

                } catch (\Throwable $e) {

                    \Light\ErrorHandler::reportException($e);

                }
            }

        }
    }
}
