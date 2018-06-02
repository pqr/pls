<?php

namespace Light;

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

    /**
     * Mailer constructor.
     * @param string | array $to
     * @param string $subject
     * @param string $body
     */
    public function __construct($to, string $subject, string $body = '')
    {
        $this->from = Config::$mailFrom;
        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
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

    public function replyTo(string $replyTo)
    {
        $this->replyTo = $replyTo;
        return $this;
    }

    public function subject(string $subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function body(string $body, bool $wrapWithHtmlTemplate = false)
    {
        if ($wrapWithHtmlTemplate) {
            $body = self::wrapBody($body);
        }
        $this->body = $body;
        return $this;
    }

    public function wrapBodyWithHtmlTemplate()
    {
        $this->body = self::wrapBody($this->body);;
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

    public function send(Msg $msgBuff = null)
    {
        Mailer::sendMail(
            $this->from,
            $this->to,
            $this->cc,
            $this->subject,
            $this->body,
            $this->attachements,
            $msgBuff,
            $this->bcc,
            $this->contentType,
            $this->replyTo
        );
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
