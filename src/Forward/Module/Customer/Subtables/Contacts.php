<?php
namespace Forward\Module\Customer\Subtables;

use Light\ObjAbstract;
use Light\SubtableBase\ContactsAbstract;

class Contacts extends ContactsAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('ccontact');
    }
}
