<?php

namespace Light;

class Msg implements \Countable
{
    private $messages = [];

    public function addPrefxToAll(string $prefix)
    {
        foreach ($this->messages as &$message) {
            $message['msg'] = $prefix . $message['msg'];
        }
        unset($message);
    }

    public function add($type, $msg)
    {
        $this->messages[] = ['type' => $type, 'msg' => $msg];
    }

    public function count()
    {
        return count($this->messages);
    }

    public function addMessagess(array $messagess)
    {
        $this->messages = array_merge($this->messages, $messagess);
    }

    public function addInfo($msg)
    {
        $this->add('info', $msg);
    }

    public function addSuccess($msg)
    {
        $this->add('success', $msg);
    }

    public function addError($msg)
    {
        $this->add('error', $msg);
    }

    public function addWarning($msg)
    {
        $this->add('warning', $msg);
    }

    public function addSkipp($msg)
    {
        $this->add('skipp', $msg);
    }

    public function addSuccessOrInfo($msg, $successCondition)
    {
        if ($successCondition) {
            $this->addSuccess($msg);
        } else {
            $this->addInfo($msg);
        }
    }

    public function addHtml($msg)
    {
        $this->add('html', $msg);
    }

    public function addEmptyLine()
    {
        $this->addInfo('&nbsp;');
    }

    public function getText()
    {
        $text = [];
        foreach ($this->messages as $msginfo) {
            $text[] = $msginfo['msg'];
        }

        $text = implode("\n", $text);
        $text = strip_tags($text);
        $text = htmlspecialchars_decode($text);

        return $text;
    }

    public function isEmpty()
    {
        return (bool)$this->messages;
    }

    public function getCount()
    {
        return count($this->messages);
    }

    public function getAll()
    {
        return $this->messages;
    }

    /**
     * Принимает массив из сообщений, каждое сообщение это массив из двух элементов: type и msg
     * @return string
     */
    public function getHtml()
    {
        $outHtml = '';
        foreach ($this->messages as $msginfo) {
            if ($msginfo['type'] === 'html') {
                $outHtml .= $msginfo['msg'];
            } else {
                $outHtml .= self::getMessageHtml($msginfo['type'], $msginfo['msg']);
            }
            $outHtml .= "\n";
        }

        return trim($outHtml);
    }

    public function getHtmlList()
    {
        $outHtml = '<ul class="msg">';
        foreach ($this->messages as $msginfo) {
            $outHtml .= self::getMessageHtmlList($msginfo['type'], $msginfo['msg']);
        }
        $outHtml .= '</ul>';

        return $outHtml;
    }

    public function getHtmlAlertList()
    {
        $outHtml = '';
        foreach ($this->messages as ['type' => $type, 'msg' => $msg]) {
            $outHtml .= self::getMessageAlertHtml($type, $msg);
        }
        return $outHtml;
    }

    /**
     * Сохраняет все сообщения в Session::flash
     */
    public function putIntoSessionFlash()
    {
        foreach($this->messages as ['type' => $type, 'msg' => $msg]) {
            Session::flashAdd($type, $msg);
        }
    }

    public static function getMessageHtml($type, $msg)
    {
        return '<p class="msg-' . $type . '">' . $msg . '</p>';
    }

    public static function getMessageHtmlList($type, $msg)
    {
        return '<li class="msg-' . $type . '">' . $msg . '</li>';
    }

    public static function getMessageAlertHtml($type, $msg)
    {
        return '<div class="alert alert-' . ($type === "error" ? "danger" : $type) . '">' . $msg . '</div>';
    }
}
