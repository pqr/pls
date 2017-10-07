<?php
namespace Light;

class Timer
{
    private $_time = 0;

    //При создании объекта автоматически запускается таймер
    public function __construct()
    {
        $this->restart();
    }

    public function restart()
    {
        $this->_time = microtime(true);
    }

    public function get()
    {
        return microtime(true) - $this->_time;
    }

    public function getMs()
    {
        return $this->get() * 1000;
    }

    public function getHtml()
    {
        $time = $this->get();
        return '<span title="' . $time . '">' . round($time, 2) . ' sec</span>';
    }
}