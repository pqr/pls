<?php


namespace Light\Exceptions;

class ForceSendResponse extends \Exception
{

    public function __construct(private $resultFromModule)
    {
        parent::__construct();
    }

    public function getResultFromModule()
    {
        return $this->resultFromModule;
    }
}
