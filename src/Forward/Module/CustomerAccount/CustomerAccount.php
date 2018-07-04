<?php
namespace Forward\Module\CustomerAccount;

use Forward\Classes\BuAccess;
use Forward\Classes\Constant;
use Forward\Classes\CustomerAccess;
use Forward\Field\GeneralFields;
use Light\Db;
use Light\Img;
use Light\ModuleBase\ModuleAbstract;
use Light\ObjAbstract;

class CustomerAccount extends ModuleAbstract
{
    public function __construct()
    {
        parent::__construct();

        $this->textSaveBtn = 'Customer Account';
        $this->tableLayouts['Default'] = 'Name|ContractNo|InvoiceAddress|Comments|Active';
        $this->registerSubtable('Customers');
    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;

        $this->addFilterName();
        $this->addFilter(GeneralFields::buIdsFilter());
    }

    protected function initViewSubmenu()
    {
        if (isset(Constant::$onecCompany[$this->_obj['CompanyID']])) {
            $id = $this->_obj->getId();
            $customerAccountName = $this->_obj['Name'];
            $ourCompanyName = $this->_obj->getFieldText('CompanyID');
            switch ($this->_obj['CompanyID']) {
                case Constant::OC_PLS_OOO:
                case Constant::OC_PLS12_OOO:
                    $mailto = 'accounting@proactive-ls.com';
                    break;

                case Constant::OC_PGL_OOO:
                case Constant::OC_PGL11_OOO:
                    $mailto = 'pgl-accounting@proactive-gl.ru';
                    break;

                default:
                    $mailto = '';
            }

            $subject = "Новый клиент $customerAccountName для $ourCompanyName, ID=$id";
            $body = "Внесите, пожалуйста, нового клиента $customerAccountName в 1С для нашей компании $ourCompanyName, ID=$id. Договор во вложении.";

            $url = "mailto:$mailto?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);
            $this->addSubmenuItemUrl('Email в бухгалтерию', Img::iconMailBlue(), $url);
        }
    }

    protected function getAccessSqlWhere(): string
    {
        return BuAccess::getSqlWhereBu() . " AND " . CustomerAccess::getSqlWhereCustomerAccount();
    }


}
