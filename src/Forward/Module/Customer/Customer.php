<?php

namespace Forward\Module\Customer;

use Forward\Application;
use Forward\Classes\BuAccess;
use Forward\Classes\CustomerAccess;
use Forward\Field\CreditFields;
use Forward\Field\GeneralFields;
use Forward\Module\Customer\StandartRateDialog\StandartRateDialog;
use Forward\Module\Customer\StandartRateDialog\StandartRatesExcel;
use Forward\Module\Customer\Subtables\Agents;
use Forward\Module\Customer\Subtables\Consignee;
use Forward\Module\Customer\Subtables\Contacts;
use Forward\Module\Customer\Subtables\CustomerAccounts;
use Forward\Module\Customer\Subtables\Quotations;
use Forward\Module\Customer\Subtables\Responsibles;
use Forward\Module\Customer\Subtables\StandartRates;
use Light\Currency;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class Customer extends ModuleAbstract
{
    public $folders = true;
    protected $textSaveBtn = 'Customer';
    public $tableLayoutDefault = 'Name|Commodity|Address|CreditPeriod|CreditAmount|CreditCurrencyISO|Active';

    public function __construct()
    {
        parent::__construct();
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
    }

    #[\Override]
    protected function initSubtables()
    {
        $this->addSubtable(CustomerAccounts::class);
        $this->addSubtable(Responsibles::class);
        $this->addSubtable(Contacts::class);
        $this->addSubtable(Agents::class);
        $this->addSubtableReadonly(Quotations::class);
        $this->addSubtableReadonly(StandartRates::class);
        $this->addSubtable(Consignee::class);
    }

    #[\Override]
    protected function initList()
    {
        parent::initList();

        //Инициализируем дополнительные поля только для режима List
        $this->addFields(

            CreditFields::creditPeriod()
                ->setGroupconcat(true)
                ->setSqlSelect(
                    "SELECT GROUP_CONCAT(DISTINCT CreditPeriod ORDER BY CreditPeriod SEPARATOR '\\n')
                FROM customeraccount ca, customer_customeraccount cca
                WHERE ca.ID = cca.CustomerAccountID
                    AND cca.CustomerID = customer.ID"
                ),

            F::createSelect('CustomerAccount', [
                'name' => "Customer Accounts",
                'modes' => F::MODE_LIST,
                'options' => 'customeraccount',
                'sqlSelect' => "SELECT GROUP_CONCAT(cca.CustomerAccountID SEPARATOR '\\n') 
                                FROM customer_customeraccount cca 
                                WHERE cca.CustomerID = customer.ID",
                'groupconcat' => true
            ]),

            F::createSelect('ResponsibleSales', [
                'name' => "Responsible\nSales",
                'modes' => F::MODE_LIST,
                'options' => 'user',
                'sqlSelect' => "SELECT GROUP_CONCAT(cr.UserID SEPARATOR '\\n') 
                                FROM customer_responsible cr 
                                WHERE cr.CustomerID = customer.ID
                                    AND Role = '" . Application::ROLE_SALES . "'",
                'groupconcat' => true
            ]),

            F::createSelect('ResponsibleCustomerService', [
                'name' => "Responsible\nCustomer Service",
                'modes' => F::MODE_LIST,
                'options' => 'user',
                'sqlSelect' => "SELECT GROUP_CONCAT(cr.UserID SEPARATOR '\\n') 
                                FROM customer_responsible cr 
                                WHERE cr.CustomerID = customer.ID
                                    AND Role = '" . Application::ROLE_CS . "'",
                'groupconcat' => true
            ])
        );

    }

    #[\Override]
    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;

        $this->addFilterText('Name');

        $this->addFilters(

            F::createSelect('Responsible', [
                'options' => "SELECT ID, Name FROM user WHERE ID IN (SELECT DISTINCT UserID FROM customer_responsible) ORDER BY Name",
                'sqlWhere' => "EXISTS (SELECT * FROM customer_responsible cr WHERE cr.CustomerID = customer.ID AND cr.UserID = {value})"
            ]),
            GeneralFields::buIdsFilter(),
            CF::active()

        );
    }


    #[\Override]
    protected function getTplData()
    {
        $tdata = parent::getTplData();
        $tdata['activeTab'] = User::getParam('CustomerActiveTab');
        return $tdata;
    }

    protected function viewGetStandartRateDialog()
    {
        // Все ajax действия связанные с подтаблицей StandartRates перенаправляем в неё!
        /** @var CustomerObj $customerObj */
        $customerObj = $this->_obj;
        StandartRateDialog::proceed($customerObj);
    }

    protected function viewPostStandartRateDialog()
    {
        // Все ajax действия связанные с подтаблицей StandartRates перенаправляем в неё!
        /** @var CustomerObj $customerObj */
        $customerObj = $this->_obj;
        StandartRateDialog::proceed($customerObj);
    }

    #[\Override]
    protected function initCssJs()
    {
        parent::initCssJs();

        if ($this->isStateView() || $this->isStateEdit()) {
            //Добавим также информацию по курсам валют
            Currency::putJsRates();
        }
    }

    #[\Override]
    protected function getAccessSqlWhere(): string
    {
        return BuAccess::getSqlWhereBu() . " AND " . CustomerAccess::getSqlWhere("ID");
    }

    protected function viewGetStandartRatesExcel()
    {
        $rateIds = array_map('intval', explode(',', (string) $_GET['RateIDs']));
        $table = new StandartRatesExcel($this->_obj->getId(), $rateIds);
        $table->sendStandartRatesExcel($this->_obj['Name'], $this->_obj['CompanyID'], 'en');
    }
}
