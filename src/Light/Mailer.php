<?php

namespace Light;


use Light\Module\EmailLogModule\EmailLogModule;
use Symfony\Component\Filesystem\Filesystem;

class Mailer
{

    /** @var int */
    private $emailAccountId;

    /**
     * Используется только если Config::$emailAllowCustomFrom === true
     * Позволяет подставть любой адрес отправителя в поле from (без привязки к emailAccountId)
     * @var string
     */
    private $customFrom;

    /** @var array|string */
    private $cc;

    /** @var array|string */
    private $bcc;

    /** @var string */
    private $replyTo;

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
     */
    public function __construct(private $to, private string $subject, private string $body = '')
    {
    }

    /**
     * @param string | array $to
     * @return Mailer
     */
    public static function create($to, string $subject, string $body = '')
    {
        return new Mailer($to, $subject, $body);
    }

    public function setFromQueue(int $createdBy, string $createdDateTime, int $retryCount): void
    {
        $this->createdBy = $createdBy;
        $this->createdDateTime = $createdDateTime;
        $this->retryCount = $retryCount;
    }

    public function from(string $from)
    {
        if ($from === Config::$swiftmailerUser) {

            // Отправляем от стандартного пользователя установленного в конфиге приложения
            // Значит указать на EmailAccountID нужно занулить
            $emailAccountId = null;

        } else {

            $emailAccountId = Db::getOne("SELECT ID FROM emailaccount WHERE Username = @s", $from);
            if (!$emailAccountId) {

                if (Config::$emailAllowCustomFrom) {
                    // Email Account не найден, но настройками приложения разрешено отправлять от имени любой почты
                    // запишем в специальное поле customFrom
                    $this->customFrom = $from;
                } else {
                    // Если не найден emailaccount и настройками приложения запрещено подставлять какой-попало from
                    // то не можем отправить такое письмо
                    throw new \InvalidArgumentException("Unknown E-mail account with Username = $from");
                }
            }

        }

        $this->emailAccountId = $emailAccountId;

        return $this;
    }

    public function emailAccountId(?int $emailAccountId)
    {
        $this->emailAccountId = $emailAccountId;
        return $this;
    }

    public function customFrom(string $customFrom)
    {
        $this->customFrom = $customFrom;
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
     * @return int число отправленных писем
     */
    public function sendNow(Msg $msgBuff): int
    {
        $dataForQueue = $this->getDataForQueue();
        // Срау установим PID, чтобы этот E-mail случайно не был подхвачен в Autolaunch
        $dataForQueue['PID'] = 'IMMEDIATE';
        $queueId = $this->writeToQueue($dataForQueue, $msgBuff);
        if (!$queueId) {
            // По каким-то причинам не удалось записать в очередь
            // (конкретная ошибка уже добавлена в $msgBuff внутри $this->writeToQueue($msgBuff);
            return 0;
        }

//        if (Config::$debug) {
//            $msgBuff->addSuccess('Debug Mailer::sendNow to ' . htmlspecialchars($this->to));
//            return 1;
//        }

        $sent = MailSender::sendFromQueue($queueId, $msgBuff);
        if (!$sent) {
            try {
                // Если отправка не удалась, значит что-то пошло не так. Очистим PID у этой записи в очереди
                Db::query("UPDATE queue_email SET PID = NULL WHERE ID = @i", $queueId);
            } catch (\Exception $e) {
                \Light\ErrorHandler::reportException($e);
            }
        }

        return $sent;
    }

    /**
     * Стандартный способ отправки - добавление в очередь queue_email
     * Из которой почта рассылается с помощью Autolaunch Light/Email раз в минуту
     * @param Msg|null $msgBuff
     * @return int возвращает ID записи в очереди queue_email
     */
    public function send(Msg $msgBuff = null): int
    {
        if (!$msgBuff) {
            // Создадим буфер загрушку
            $msgBuff = new Msg();
        }
        $dataForQueue = $this->getDataForQueue();
        $queueId = $this->writeToQueue($dataForQueue, $msgBuff);
        if (!$queueId) {
            // По каким-то причинам не удалось записать в очередь
            // (конкретная ошибка уже добавлена в $msgBuff внутри $this->writeToQueue($msgBuff);
            return 0;
        }

        // Вывдем сообщенеи об отложенной отправке: "На почту ... будет отправлено сообщение ... (Сс: ...)"
        $msgBuff->addSuccess(MailSender::getSuccessMsgQueue($dataForQueue));
        return $queueId;
    }

    private function getDataForQueue(): array
    {
        return [
            'CreatedBy' => User::getId(),
            'Subject' => $this->subject,
            'EmailAccountID' => $this->emailAccountId ?: null,
            'CustomFrom' => $this->customFrom ?: '',
            'To' => Mailer::getEmailsAsString($this->to),
            'Cc' => Mailer::getEmailsAsString($this->getFinalCc()),
            'Bcc' => Mailer::getEmailsAsString($this->bcc),
            'Body' => $this->getFinalBody(),
            'ContentType' => $this->contentType,
            'ReplyTo' => Mailer::getEmailsAsString($this->replyTo),
            'Attachements' => implode("\n", $this->attachements),
        ];
    }

    private function writeToQueue(array $dataForQueue, Msg $msgBuff): int
    {
        try {

            return Db::insertRow($dataForQueue, 'queue_email');

        } catch (\Exception $e) {
            \Light\ErrorHandler::reportException($e);
            $msgBuff->addError(MailSender::getErrorMsg($dataForQueue));
            $msgBuff->addError($e->getMessage());
            return 0;
        }
    }

    /**
     * @param string | array $emails
     * @return string
     */
    public static function getEmailsAsString($emails): string
    {
        if (is_array($emails)) {
            $emails = implode(', ', array_unique($emails));
        }
        return (string)$emails;
    }

    public static function getEmailsAsArray($emails): array
    {
        if (!$emails) {
            return [];
        }
        if (!is_array($emails)) {
            $emails = preg_split("/,|;|\s/", (string) $emails);
        }
        $emails = array_unique(array_filter(array_map('trim', $emails)));
        $emails = array_map(static fn($email) => str_replace(['<', '>'], '', trim($email)), $emails);
        $emails = array_filter($emails, static fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        return array_values(array_unique($emails));
    }

    private function getFinalCc()
    {
        $toArr = Mailer::getEmailsAsArray($this->to);
        $ccArr = Mailer::getEmailsAsArray($this->cc);
        // Уберём из массив Cc те адреса, которые уже встречались в To (чтобы пользователю не приходило по два письма)
        return array_diff($ccArr, $toArr);
    }
}
