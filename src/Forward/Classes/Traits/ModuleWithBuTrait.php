<?php
namespace Forward\Classes\Traits;

use Forward\Classes\BuAccess;
use Light\Db;
use Light\ObjAbstract;

trait ModuleWithBuTrait
{
    public function getFiltersSqlWhere()
    {
        return Db::maybeImplodeSqlWheres('AND', parent::getFiltersSqlWhere(), BuAccess::getSqlWhereBu());
    }

    public function hasAccessRightToObj(ObjAbstract $obj)
    {
        return BuAccess::hasAccess($obj['BUIDs']);
    }
}