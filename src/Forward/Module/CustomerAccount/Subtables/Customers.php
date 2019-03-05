<?php
namespace Forward\Module\CustomerAccount\Subtables;

use Forward\Classes\SubtablesBase\Many2ManyAbstract;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class Customers extends Many2ManyAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('customer_customeraccount');
        $this->_masterText = 'Customer Account';

        $this->_slaveField = 'CustomerID';
        $this->_slaveTable = 'customer';
        $this->_slaveText = 'Customer';
        $this->_slaveModule = 'Customer';

        //Для определения по какому полю внутри массива data искать ID для данной строки
        $this->tableLinkIdFieldKey = 'CustomerID';
        //Список полей, на которых нужно сделать ссылки
        $this->tableLinkFieldKeys = ['CustomerID'];
        //Модуль на который нужно сделать ссылки на поле определённом в tableLinkFieldKeys и при двойном клике
        $this->tableLinkModule = 'Customer';

        $this->textAddLink = 'Add Customer';

        $this->textTableEmpty = 'No linked customers';
        $this->folders = true;
        $this->folderModule = 'Customer';
        $this->textSubtableTitle = 'Customers';

        $this->addFields(

            F::createSelect('CustomerID', [
                'name' => 'Customer',
                'tableWidthHtml' => 280,
                'options' => "SELECT ID, Name, Active FROM customer ORDER BY Name",
                'empty' => false
            ])

        );

        $this->setNameFieldKey('CustomerID'); // Для правильной записи логов, чтобы сработал метод getDescriptionHtml
    }

    protected function getFolderName(array $data)
    {
        if (isset($data['CustomerID'])) {
            return $data['CustomerID'];
        }
        return false;
    }
}
