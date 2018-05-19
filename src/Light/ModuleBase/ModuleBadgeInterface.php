<?php

namespace Light\ModuleBase;


interface ModuleBadgeInterface
{
    public function getBadges(): array;

    public function getMainmenuUrlParams(): array;
}
