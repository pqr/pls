<?php

namespace Light\ModuleBase;

interface MassactionInterface
{
    public function __invoke(array $ids);
}
