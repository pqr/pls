<?php

namespace Forward\Module\CustomerAccount;

use Forward\Application;
use Forward\Classes\BuAccess;
use Forward\Classes\Constant;
use Forward\Classes\CustomerAccess;
use Forward\Field\GeneralFields;
use Forward\Module\CustomerAccount\Subtables\Customers;
use Light\Ico;
use Light\Link;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class CustomerAccount extends ModuleAbstract
{
    protected $textSaveBtn = 'Customer Account';
    public $tableLayoutDefault = 'Name|CreditPeriod|ContractNo|InvoiceAddress|Comments|Active';

    public function __construct()
    {
        parent::__construct();

        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
    }

    #[\Override]
    protected function initSubtables()
    {
        $this->addSubtable(Customers::class);
    }

    #[\Override]
    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;

        $this->addFilterText('Name');
        $this->addFilter(GeneralFields::buIdsFilter());
    }

    #[\Override]
    protected function initViewSubmenu()
    {
        if (isset(Constant::$onecCompany[$this->_obj['CompanyID']])) {
            $id = $this->_obj->getId();
            $customerAccountName = $this->_obj['Name'];
            $ourCompanyName = $this->_obj->getFieldText('CompanyID');
            $mailto = match ($this->_obj['CompanyID']) {
                Constant::OC_PLS_OOO, Constant::OC_PLS12_OOO => 'accounting@proactive-ls.com',
                Constant::OC_PGL_OOO, Constant::OC_PGL11_OOO => 'pgl-accounting@proactive-gl.ru',
                default => '',
            };

            $subject = "Новый клиент $customerAccountName для $ourCompanyName, ID=$id";
            $body = "Внесите, пожалуйста, нового клиента $customerAccountName в 1С для нашей компании $ourCompanyName, ID=$id. Договор во вложении.";

            $url = "mailto:$mailto?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);
            $this->addSubmenuLink(
                Link::create(null, Ico::mailBlue() . ' Email в бухгалтерию')
                    ->externalUrl($url)
            );
        }
    }

    #[\Override]
    protected function getAccessSqlWhere(): string
    {
        return BuAccess::getSqlWhereBu() . " AND " . CustomerAccess::getSqlWhereCustomerAccount();
    }
}
