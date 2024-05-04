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

    #[\Override]
    public function __toString(): string
    {
        return (string)$this->value;
    }

    #[\Override]
    public function jsonSerialize()
    {
        return $this->value;
    }


}
