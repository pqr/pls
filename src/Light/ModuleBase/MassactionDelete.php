<?php


namespace Light\ModuleBase;


use Light\Config;
use Light\Db;
use Light\Msg;
use Light\ObjAbstract;
use Light\User;

trait MassactionDelete
{
    protected function addMassactionDelete()
    {
        if ($this->allowDelete && User::hasWriteAccess()) {
            $this->addMassaction('delete', 'Delete', 'bad');
        }
    }

    protected function massactionDelete(array $ids)
    {
        if (!$this->allowDelete || !User::hasDeleteAccess()) {
            MassactionUtils::sendErrorDialog('No access to delete');
            return;
        }

        $msgBuff = new Msg();
        $deleted = 0;

        $objBase = $this->getObj();
        Db::begin();
        foreach ($ids as $id) {

            // Проверим существование объекта в базе, попробовав его загрузить
            /** @var ObjAbstract $obj */
            $obj = clone $objBase;
            $obj->setId($id);
            if (!$obj->loadData()) {
                $msgBuff->addError("Object with ID = $id not found");
                continue;
            }

            // Проверим, что у пользователя есть права доступа к этому объекту
            if (!$this->hasAccessRightToObj($obj)
                || !$this->hasDeleteAccessRightToObj($obj)
            ) {
                $msgBuff->addError("No access to delete object with ID = $id");
                continue;
            }

            $oldData = $this->getOldDataBeforeSubmitOrDelete($obj);

            $this->beforeDelete($obj, $msgBuff);
            $this->proceedDeleteObj($obj, 'Multiple Delete');
            $this->proceedDeleteSubtables($obj);
            $this->afterDelete($obj, $oldData, $msgBuff);
            $deleted++;
        }
        Db::commit();

        // Стандартное сообщение о числе удалённых записей (зелёное или серое)
        $msg = Config::$lang === 'ru' ? 'Удалено' : 'Deleted';
        $msg .= ": $deleted";
        $msgBuff->addSuccessOrInfo($msg, $deleted);

        MassactionUtils::sendSuccessDialog($msgBuff->getHtml());
    }
}
