<?php
namespace Forward\Module\Customer\Subtables;

use Forward\Classes\SubtablesBase\Many2ManyAbstract;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class CustomerAccounts extends Many2ManyAbstract
{
    public function __construct(ObjAbstract $masterObj)
    {
        parent::__construct($masterObj);

        $this->setDbTable('customer_customeraccount');
        $this->_masterText = 'Customer';

        $this->_slaveField = 'CustomerAccountID';
        $this->_slaveTable = 'customeraccount';
        $this->_slaveText = 'Customer Account';
        $this->_slaveModule = 'CustomerAccount';

        //Для определения по какому полю внутри массива data искать ID для данной строки
        $this->_tableLinkIdFieldKey = 'CustomerAccountID';
        //Список полей, на которых нужно сделать ссылки
        $this->_tableLinkFieldKeys = ['CustomerAccountID'];
        //Модуль на который нужно сделать ссылки на поле определённом в _tableLinkFieldKeys и при двойном клике
        $this->_tableLinkModule = 'CustomerAccount';

        $this->_textAddLink = 'Add Customer Account';

        $this->_tableMsgIfEmpty = 'No linked customer accounts';
        $this->_title = 'Customer Accounts';

        $this->addFields(

            F::createSelect('CustomerAccountID', [
                'name' => 'Customer Account',
                'tableWidthHtml' => 280,
                'options' => "SELECT ID, Name, Active FROM customeraccount ORDER BY Name",
                'empty' => false
            ])

        );

        $this->setNameFieldKey('CustomerAccountID'); // Для правильной записи логов, чтобы сработал метод getDescriptionHtml
    }
}
