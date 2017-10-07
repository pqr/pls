<?php
namespace Forward\Autolaunch;

use Forward\Module\Customer\CustomerObj;
use Light\Db;
use Light\Module\Autolaunch\AutolaunchAbstract;

class UpdateCustomerOutstandingCache extends AutolaunchAbstract
{
    protected static $_title = 'Daily Update Customer Outstanding Cache';
    protected static $_description = 'customer_openinvoices, customer_openinvoicespercompany';

    public function run(array $params)
    {
        $customerObjBase = new CustomerObj();
        $customerIds = Db::getCol("SELECT ID FROM customer");
        foreach ($customerIds as $cid) {
            $customerObj = clone $customerObjBase;
            $customerObj->setId($cid);
            $customerObj->loadData();
            $customerObj->updateCacheOpenAndOverdueInvoicesAmount();
        }

        $this->_msgBuff->addSuccess('Customers updated: ' . count($customerIds));
    }
}
