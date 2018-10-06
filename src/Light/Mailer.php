<?php

namespace Light;


use Symfony\Component\Filesystem\Filesystem;

class Mailer
{


    private $from;
    private $to;
    private $cc;
    private $bcc;
    private $replyTo;
    private $subject;
    private $body;
    private $attachements = [];
    private $contentType = 'text/html';
    private $wrapBody = false;
    private $linksAbsolute = false;

    /** @var int используется только при отправке почты из очереди в Autolaunch Light/Email */
    private $createdBy;
    /** @var string используется только при отправке почты из очереди в Autolaunch Light/Email */
    private $createdDateTime;
    /** @var int используется только при отправке почты из очереди в Autolaunch Light/Email */
    private $retryCount;

    /**
     * Mailer constructor.
     * @param string | array $to
     * @param string $subject
     * @param string $body
     */
    public function __construct($to, string $subject, string $body = '')
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * @param string | array $to
     * @param string $subject
     * @param string $body
     * @return Mailer
     */
    public static function create($to, string $subject, string $body = '')
    {
        return new Mailer($to, $subject, $body);
    }

    public function setFromQueue(int $createdBy, string $createdDateTime, int $retryCount)
    {
        $this->createdBy = $createdBy;
        $this->createdDateTime = $createdDateTime;
        $this->retryCount = $retryCount;
    }

    public function from(string $from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @param string | array $to
     * @return $this
     */
    public function to($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * @param string | array $cc
     * @return $this
     */
    public function cc($cc)
    {
        $this->cc = $cc;
        return $this;
    }

    /**
     * @param string | array $bcc
     * @return $this
     */
    public function bcc($bcc)
    {
        $this->bcc = $bcc;
        return $this;
    }

    public function replyTo($replyTo)
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    public function subject(string $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function body(string $body)
    {
        $this->body = $body;
        return $this;
    }

    public function wrapBodyWithHtmlTemplate()
    {
        $this->wrapBody = true;
        return $this;
    }

    public function makeLinksAbsolute()
    {
        $this->linksAbsolute = true;
        return $this;
    }

    public function attachements(array $attachements)
    {
        $this->attachements = array_merge($this->attachements, $attachements);
        return $this;
    }

    public function asHtml()
    {
        $this->contentType = 'text/html';
        return $this;
    }

    public function asText()
    {
        $this->contentType = 'text/plain';
        return $this;
    }

    private function getFinalBody()
    {
        $body = $this->body;
        if ($this->linksAbsolute) {
            $body = Misc::makeLinksAbsolute($body);
        }
        if ($this->wrapBody) {
            $body = Template::parseTpl('emailhtml.tpl.php', ['body' => $body]);
        }
        return $body;
    }

    /**
     * Этот метод реально отправляет сообщение.
     * Вызывается из Autolaunch Light/Email, когда сообщения достаются из очереди queue_email
     * А также можно вызывать вручную, если нужно отправить E-mail здесь и сейчас (без добавления в очередь)
     *
     * @param Msg $msgBuff
     * @param \Swift_Mailer|null $swiftmailer
     * @return int число отправленных писем
     */
    public function sendImmideatelly(Msg $msgBuff, \Swift_Mailer $swiftmailer = null)
    {
        $sent = 0;
        try {

            if (!$swiftmailer) {
                // Если не передан какой-то особый Swift_Mailer, то используем настройки по умолчанию
                // прописанные в Config::$swiftmailer...
                $swiftmailer = $this->getDefaultSwitfmailer();
            }

            $message = $this->getSwiftMessage();
            $sent = $swiftmailer->send($message);
            if ($sent) {
                $msgBuff->addSuccess($this->getSuccessMsg());
                $this->writeLog();
            } else {
                $msgBuff->addError($this->getErrorMsg());
            }

        } catch (\Throwable $e) {

            $msgBuff->addError($this->getErrorMsg());
            $msgBuff->addError($e->getMessage());

        }

        return $sent;
    }

    public function send(Msg $msgBuff = null)
    {
        try {

            $finalBody = $this->getFinalBody();

            $insertData = [
                'CreatedBy' => User::getId(),
                'Subject' => $this->subject,
                'From' => $this->from ?? Config::$mailFrom,
                'To' => Mailer::getEmailsAsString($this->to),
                'Cc' => Mailer::getEmailsAsString($this->cc),
                'Bcc' => Mailer::getEmailsAsString($this->bcc),
                'Body' => $finalBody,
                'ContentType' => $this->contentType,
                'ReplyTo' => Mailer::getEmailsAsString($this->replyTo),
                'Attachements' => implode("\n", $this->attachements)
            ];

            Db::insertRow($insertData, 'queue_email');

            if ($msgBuff) {
                $msgBuff->addSuccess($this->getSuccessMsg());
            }

        } catch (\Throwable $e) {

            $msgBuff->addError($this->getErrorMsg());
            $msgBuff->addError($e->getMessage());

        }

    }

    /**
     * @param string | array $emails
     * @return string
     */
    public static function getEmailsAsString($emails): string
    {
        if (is_array($emails)) {
            $emails = implode(', ', $emails);
        }
        return (string)$emails;
    }

    public static function getEmailsAsArray($emails): array
    {
        if (!$emails) {
            return [];
        }
        if (!is_array($emails)) {
            $emails = preg_split("/,|;|\s/", $emails);
        }
        return array_values(array_unique(array_filter(array_map('trim', $emails))));
    }

    private function copyAttachements(string $targetDir)
    {
        $fs = new Filesystem();
        $fs->mkdir($targetDir);
        foreach ($this->attachements as $attachement) {
            $target = $targetDir . '/' . pathinfo($attachement, PATHINFO_BASENAME);
            $fs->copy($attachement, $target);
        }
    }

    private function getDefaultSwitfmailer(): \Swift_Mailer
    {
        static $defaultSwiftmailer;
        if (!$defaultSwiftmailer) {
            $defaultSwiftmailer = self::createSwiftmailer(
                Config::$swiftmailerHost,
                Config::$swiftmailerPort,
                Config::$swiftmailerSecuriy,
                Config::$swiftmailerUser,
                Config::$swiftmailerPassword
            );
        }

        return $defaultSwiftmailer;
    }

    private function getFinalCc()
    {
        $toArr = Mailer::getEmailsAsArray($this->to);
        $ccArr = Mailer::getEmailsAsArray($this->cc);
        // Уберём из массив Cc те адреса, которые уже встречались в To (чтобы пользователю не приходило по два письма)
        return array_diff($ccArr, $toArr);
    }

    private function getSwiftMessage()
    {
        $toArr = Mailer::getEmailsAsArray($this->to);
        $message = (new \Swift_Message($this->subject, $this->body, $this->contentType))
            ->setFrom($this->from ?: Config::$mailFrom)
            ->setTo($toArr);

        $ccArr = $this->getFinalCc();
        if ($ccArr) {
            $message->setCc($ccArr);
        }

        $bccArr = Mailer::getEmailsAsArray($this->bcc);
        if ($bccArr) {
            $message->setBcc($bccArr);
        }

        $replyToArr = Mailer::getEmailsAsArray($this->replyTo);
        if ($replyToArr) {
            $message->setReplyTo($replyToArr);
        }

        foreach ($this->attachements as $attachement) {
            if ($attachement && file_exists($attachement)) {
                $message->attach(\Swift_Attachment::fromPath($attachement));
            }
        }

        return $message;
    }

    public static function createSwiftmailer($host, $port, $security, $user, $pass): \Swift_Mailer
    {
        $transport = new \Swift_SmtpTransport($host, $port, $security);

        if ($user) {
            $transport->setUsername($user);
        }

        if ($pass) {
            $transport->setPassword($pass);
        }

        return new \Swift_Mailer($transport);
    }

    private function writeLog()
    {
        $record = [
            'CreatedBy' => $this->createdBy,
            'CreatedDateTime' => $this->createdDateTime,
            'SentDateTime' => date('Y-m-d H:i:s'),
            'From' => Mailer::getEmailsAsString($this->from),
            'To' => Mailer::getEmailsAsString($this->to),
            'Cc' => Mailer::getEmailsAsString($this->getFinalCc()),
            'Bcc' => Mailer::getEmailsAsString($this->bcc),
            'ReplyTo' => Mailer::getEmailsAsString($this->replyTo),
            'Subject' => $this->subject,
            'ContentType' => $this->contentType,
            'RetryCount' => $this->retryCount,
            'Attachements' => implode("\n", $this->attachements)
        ];
        $logId = Db::insertRow($record, 'log_email');

        Db::insertRow(['ID' => $logId, 'Body' => $this->getFinalBody()], 'log_email_text');

        // Скопируем все файлы вложений в отдельную папку log
        $attachementsLogDir = Config::$filesfolderLocal . "/LogEmailAttachements/" . floor($logId / 1000) . "/$logId";
        $this->copyAttachements($attachementsLogDir);
    }

    private function getSuccessMsg()
    {
        $to = Mailer::getEmailsAsString($this->to);
        $msg = 'На почту <strong>'
            . htmlspecialchars($to)
            . '</strong> будет отправлено сообщение <strong>'
            . htmlspecialchars($this->subject)
            . '</strong>';

        $ccArr = $this->getFinalCc();
        if ($ccArr) {
            $cc = Mailer::getEmailsAsString($ccArr);
            $msg .= ' (Cc: ' . htmlspecialchars($cc) . ')';
        }

        if ($this->attachements) {
            $title = htmlspecialchars(implode(', ', $this->attachements));
            $msg .= ', вложений: <span title="' . $title . '">' . count($this->attachements) . '</span>';
        }

        return $msg;
    }

    private function getErrorMsg()
    {
        $to = Mailer::getEmailsAsString($this->to);
        $msg = 'Ошибка при отправке сообщения на почту <strong>'
            . htmlspecialchars($to)
            . '</strong> <strong>'
            . htmlspecialchars($this->subject)
            . '</strong>';

        $ccArr = $this->getFinalCc();
        if ($ccArr) {
            $cc = Mailer::getEmailsAsString($ccArr);
            $msg .= ' (Cc: ' . htmlspecialchars($cc) . ')';
        }

        if ($this->attachements) {
            $title = htmlspecialchars(implode(', ', $this->attachements));
            $msg .= ', вложений: <span title="' . $title . '">' . count($this->attachements) . '</span>';
        }

        return $msg;
    }
} 
