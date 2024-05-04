<?php
namespace Light;

class ValueObject implements \JsonSerializable, \Stringable
{
    public function __construct(private $value)
    {
    }

    public function __invoke()
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }

    public function jsonSerialize()
    {
        return $this->value;
    }


}
