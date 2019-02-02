<?php

namespace Light;


use Symfony\Component\Filesystem\Filesystem;

class Mailer
{

    /** @var string */
    private $from;

    /** @var array|string */
    private $to;

    /** @var array|string */
    private $cc;

    /** @var array|string */
    private $bcc;

    /** @var string */
    private $replyTo;

    /** @var string */
    private $subject;

    /** @var string */
    private $body;

    /** @var array */
    private $attachements = [];

    /** @var string */
    private $contentType = 'text/html';
    private $wrapBody = false;
    private $linksAbsolute = false;

    /** @var int используется только при отправке почты из очереди в Autolaunch Light/Email */
    private $createdBy;
    /** @var string используется только при отправке почты из очереди в Autolaunch Light/Email */
    private $createdDateTime;
    /** @var int используется только при отправке почты из очереди в Autolaunch Light/Email */
    private $retryCount = 0;

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
        // Логи отпавки конкретно данного сообщения сначала будем записывать в чистый буфер $thisMsgBuff
        $thisMsgBuff = new Msg();
        $sent = 0;
        try {

            if (!$swiftmailer) {
                // Если не передан какой-то особый Swift_Mailer, то используем настройки по умолчанию
                // прописанные в Config::$swiftmailer...
                $swiftmailer = $this->getDefaultSwitfmailer();
            }

            /** @var \Swift_SmtpTransport $transport */
            $transport = $swiftmailer->getTransport();
            $correctFrom = (string)$transport->getUsername();

            $message = $this->getSwiftMessage($correctFrom);
            $failedRecipients = [];
            $sent = $swiftmailer->send($message, $failedRecipients);
            if ($failedRecipients) {
                $thisMsgBuff->addError('Failed recipients: ' . htmlspecialchars(implode(', ', $failedRecipients)));
            }
            if ($sent) {
                $thisMsgBuff->addSuccess($this->getSuccessMsg());
                $report = $thisMsgBuff->getHtml();
                $this->writeLog($report, $correctFrom);
            } else {
                $thisMsgBuff->addError($this->getErrorMsg());
            }

        } catch (\Throwable $e) {

            $thisMsgBuff->addError($this->getErrorMsg());
            $thisMsgBuff->addError($e->getMessage());

        }

        // Добавим логи отпавки конкретно данного сообщения к общему буферу сообщений
        $msgBuff->merge($thisMsgBuff);

        return $sent;
    }

    public function send(Msg $msgBuff = null)
    {
        try {

            $finalBody = $this->getFinalBody();

            $insertData = [
                'CreatedBy' => User::getId(),
                'Subject' => $this->subject,
                'From' => $this->from ?? '',
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
                $msgBuff->addSuccess($this->getSuccessMsgQueue());
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
        $emails = array_unique(array_filter(array_map('trim', $emails)));
        $emails = array_map(function ($email) {
            return str_replace(['<', '>'], '', trim($email));
        }, $emails);
        $emails = array_filter($emails, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
        return array_values(array_unique($emails));
    }

    private function copyAttachementsToLogDir(int $logId)
    {
        $logDirRelative = FilesFolder::getModuleRelative('LogEmailAttachements', $logId);
        FilesFolder::mkdir($logDirRelative);
        $logDirAbs = FilesFolder::getAbsolutePath($logDirRelative);

        $fs = new Filesystem();
        foreach ($this->attachements as $attachement) {
            if ($attachement && file_exists($attachement)) {
                $target = $logDirAbs . '/' . pathinfo($attachement, PATHINFO_BASENAME);
                try {
                    $fs->copy($attachement, $target);
                    @chmod($target, 0666);
                } catch (\Throwable $e) {
                    reportException($e);
                }
            }
        }
    }

    private function getDefaultSwitfmailer(): \Swift_Mailer
    {
        $swiftmailerUser = Config::$swiftmailerUser;
        $swiftmailerPassword = Config::$swiftmailerPassword;

        if ($this->from != Config::$swiftmailerUser) {
            // Если адрес отправителя ($from) не совпадает с пользователем заданным для отправки по умолчанию в конфиге
            // то нужно проверить, есть ли в базе пароль от почты указанного пользователя?
            $userEmailPassword = (string)Db::getOne("SELECT EmailPassword FROM user WHERE Email = @s", $this->from);
            if ($userEmailPassword !== '') {
                // Для данного пользователя определён его собственный пароль для отправки почты, используем его
                $swiftmailerUser = $this->from;
                $swiftmailerPassword = $userEmailPassword;
            }
        }

        return self::createSwiftmailer(
            Config::$swiftmailerHost,
            Config::$swiftmailerPort,
            Config::$swiftmailerSecuriy,
            $swiftmailerUser,
            $swiftmailerPassword
        );
    }

    private function getFinalCc()
    {
        $toArr = Mailer::getEmailsAsArray($this->to);
        $ccArr = Mailer::getEmailsAsArray($this->cc);
        // Уберём из массив Cc те адреса, которые уже встречались в To (чтобы пользователю не приходило по два письма)
        return array_diff($ccArr, $toArr);
    }

    private function getSwiftMessage(string $correctFrom)
    {
        $toArr = Mailer::getEmailsAsArray($this->to);
        $message = (new \Swift_Message($this->subject, $this->body, $this->contentType))
            ->setFrom($correctFrom)
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
        static $mailers = [];
        if (!isset($mailers[$user])) {

            $transport = new \Swift_SmtpTransport($host, $port, $security);

            if ($user) {
                $transport->setUsername($user);
            }

            if ($pass) {
                $transport->setPassword($pass);
            }

            $mailers[$user] = new \Swift_Mailer($transport);

        }

        return $mailers[$user];
    }

    private function writeLog(string $report, string $correctFrom)
    {
        $record = [
            'CreatedBy' => $this->createdBy,
            'CreatedDateTime' => $this->createdDateTime,
            'SentDateTime' => date('Y-m-d H:i:s'),
            'From' => $correctFrom,
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

        Db::insertRow([
                'ID' => $logId,
                'Body' => $this->getFinalBody(),
                'Report' => $report
            ]
            , 'log_email_text'
        );

        // Скопируем все файлы вложений в отдельную папку log
        $this->copyAttachementsToLogDir($logId);
    }

    private function getSuccessMsgQueue(): string
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

    private function getSuccessMsg(): string
    {
        $msg = $this->getSuccessMsgQueue();
        $msg = str_replace('будет', '', $msg);
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
