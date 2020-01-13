<?php


namespace Light;


interface FiltersBuilderInterface
{
    public static function init(Filters $filters);

    public static function getLayout(): array;
}
