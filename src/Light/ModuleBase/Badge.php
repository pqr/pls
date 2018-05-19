<?php


namespace Light\ModuleBase;


class Badge
{
    const SUCCESS = 'success';
    const WARNING = 'warning';
    const ERROR = 'error';
    const INFO = 'info';

    /**
     * @var string
     */
    public $counter;

    /**
     * @var string SUCCESS | WARNING | ERROR | INFO
     */
    public $type;

    /**
     * @var string
     */
    public $title;

    /**
     * @var array
     */
    public $listFilters;

    public function __construct(string $counter, string $type, string $title, array $listFilters = [])
    {
        $this->counter = $counter;
        $this->type = $type;
        $this->title = $title;
        $this->listFilters = $listFilters;
    }
}
