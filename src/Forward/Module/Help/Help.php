<?php

namespace Forward\Module\Help;


class Help
{
    public function __invoke()
    {
        include __DIR__ . '/help_include.php';
    }
}
