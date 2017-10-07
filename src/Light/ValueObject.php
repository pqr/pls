<?php
namespace Light;

class ValueObject
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __invoke()
    {
        return $this->value;
    }

    public function __toString()
    {
        return (string)$this->value;
    }
}