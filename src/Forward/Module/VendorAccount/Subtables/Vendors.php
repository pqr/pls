<?php
namespace Forward\Module\VendorAccount\Subtables;

use Forward\Classes\SubtablesBase\Many2ManyAbstract;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class Vendors extends Many2ManyAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('vendor_vendoraccount');
        $this->_masterText = 'Vendor Account';

        $this->_slaveField = 'VendorID';
        $this->_slaveTable = 'vendor';
        $this->_slaveText = 'Vendor';
        $this->_slaveModule = 'Vendor';

        //Для определения по какому полю внутри массива data искать ID для данной строки
        $this->tableLinkIdFieldKey = 'VendorID';
        //Список полей, на которых нужно сделать ссылки
        $this->tableLinkFieldKeys = ['VendorID'];
        //Модуль на который нужно сделать ссылки на поле определённом в tableLinkFieldKeys и при двойном клике
        $this->tableLinkModule = 'Vendor';

        $this->textAddLink = 'Add Vendor';

        $this->textTableEmpty = 'No linked vendors';
        $this->folders = true;
        $this->folderBaseDir = 'Vendor';
        $this->textSubtableTitle = 'Vendors';

        $this->addFields(

            F::createSelect('VendorID', [
                'name' => 'Vendor',
                'tableWidthHtml' => 280,
                'options' => "SELECT ID, Name, Active FROM vendor ORDER BY Name",
                'empty' => false
            ])

        );

        $this->setNameFieldKey('VendorID'); // Для правильной записи логов, чтобы сработал метод getDescriptionHtml
    }

    protected function getFolderName(array $data)
    {
        if (isset($data['VendorID'])) {
            return $data['VendorID'];
        }
        return false;
    }
}

