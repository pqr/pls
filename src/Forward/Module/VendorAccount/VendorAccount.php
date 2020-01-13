<?php

namespace Forward\Module\VendorAccount;

use Forward\Application;
use Forward\Classes\Constant;
use Forward\Module\VendorAccount\Subtables\Vendors;
use Light\Ico;
use Light\Link;
use Light\ModuleBase\ModuleAbstract;
use Light\User;

class VendorAccount extends ModuleAbstract
{
    protected $textSaveBtn = 'Vendor Account';
    public $tableDefaultOrderBy = 'Name ASC';
    public $tableLayoutDefault = 'Name|InvoiceAddress|Comments|Active';

    public function __construct()
    {
        parent::__construct();
        $this->allowExportToExcel = User::hasResponsibleRole(Application::ROLE_ALLOWEXCEL);
    }

    protected function initSubtables()
    {
        $this->addSubtable(Vendors::class);
    }

    protected function initFilters()
    {
        $this->allowFiltersLayoutChange = false;
        $this->addFilterText(['Name', 'OneCName']);
    }

    protected function initViewSubmenu()
    {
        if (isset(Constant::$onecCompany[$this->_obj['CompanyID']])) {
            $id = $this->_obj->getId();
            $vendorAccountName = $this->_obj['Name'];
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

            $subject = "Новый подрядчик $vendorAccountName для $ourCompanyName, ID=$id";
            $body = "Внесите, пожалуйста, нового клиента $vendorAccountName в 1С для нашей компании $ourCompanyName, ID=$id. Договор во вложении.";

            $url = "mailto:$mailto?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);
            $this->addSubmenuLink(
                Link::create(null, Ico::mailBlue() . ' Email в бухгалтерию')
                    ->externalUrl($url)
            );
        }
    }
}

