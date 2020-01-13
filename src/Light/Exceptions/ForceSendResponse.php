<?php


namespace Light\Exceptions;

class ForceSendResponse extends \Exception
{

    private $resultFromModule;

    public function __construct($resultFromModule)
    {
        parent::__construct();

        $this->resultFromModule = $resultFromModule;
    }

    public function getResultFromModule()
    {
        return $this->resultFromModule;
    }
}
