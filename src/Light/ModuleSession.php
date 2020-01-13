<?php


namespace Light;


class ModuleSession
{
    /**
     * Возвращает параметры для url содержащие msid
     * @return array
     */
    public static function getModuleSessionUrlParams(): array
    {
        $msid = Request::getQueryParam('msid');
        if ($msid) {
            return ['msid' => $msid];
        }

        return [];
    }
}
