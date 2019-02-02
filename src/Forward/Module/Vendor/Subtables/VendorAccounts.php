<?php
namespace Forward\Module\Vendor\Subtables;

use Forward\Classes\SubtablesBase\Many2ManyAbstract;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class VendorAccounts extends Many2ManyAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('vendor_vendoraccount');
        $this->_masterText = 'Vendor';

        $this->_slaveField = 'VendorAccountID';
        $this->_slaveTable = 'vendoraccount';
        $this->_slaveText = 'Vendor Account';
        $this->_slaveModule = 'VendorAccount';

        //Для определения по какому полю внутри массива data искать ID для данной строки
        $this->tableLinkIdFieldKey = 'VendorAccountID';
        //Список полей, на которых нужно сделать ссылки
        $this->tableLinkFieldKeys = ['VendorAccountID'];
        //Модуль на который нужно сделать ссылки на поле определённом в tableLinkFieldKeys и при двойном клике
        $this->tableLinkModule = 'VendorAccount';

        $this->textAddLink = 'Add Vendor Account';

        $this->textTableEmpty = 'No linked vendor accounts';
        $this->textSubtableTitle = 'Vendor Accounts';

        $this->addFields(

            F::createSelect('VendorAccountID', [
                'name' => 'Vendor Account',
                'tableWidthHtml' => 280,
                'options' => "SELECT ID, Name, Active FROM vendoraccount ORDER BY Name",
                'empty' => false
            ])

        );

        $this->setNameFieldKey('VendorAccountID'); //Для правильной записи логов, чтобы сработал метод getDescriptionHtml
    }
}
