<?php


namespace Light\ModuleBase;


class Badge
{
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const INFO = 'info';

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

    /**
     * @var array
     */
    public $urlParams;

    public function __construct(string $counter, string $type, string $title, array $listFilters = [], array $urlParams = [])
    {
        $this->counter = $counter;
        $this->type = $type;
        $this->title = $title;
        $this->listFilters = $listFilters;
        $this->urlParams = $urlParams;
    }
}
