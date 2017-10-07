<?php
namespace Forward\Module\DefaultAddons;

use Forward\Classes\Constant;
use Forward\Field\RateFields;
use Light\ApplicationAbstract;
use Light\Field\FieldAbstract as F;
use Light\Fieldset;
use Light\Response;
use Light\Session;
use Light\Template;

class DefaultAddons
{
    private function getFieldset()
    {
        return new Fieldset(
            RateFields::minAmount('AddonUSD')->setUom('USD'),
            RateFields::amountN('AddonUSD')->setUom('USD'),
            RateFields::amount45('AddonUSD')->setUom('USD'),
            RateFields::amount100('AddonUSD')->setUom('USD'),
            RateFields::amount200('AddonUSD')->setUom('USD'),
            RateFields::amount300('AddonUSD')->setUom('USD'),
            RateFields::amount500('AddonUSD')->setUom('USD'),
            RateFields::amount750('AddonUSD')->setUom('USD'),
            RateFields::amount1000('AddonUSD')->setUom('USD'),
            RateFields::amount2000('AddonUSD')->setUom('USD'),

            RateFields::minAmount('AddonEUR')->setUom('EUR'),
            RateFields::amountN('AddonEUR')->setUom('EUR'),
            RateFields::amount45('AddonEUR')->setUom('EUR'),
            RateFields::amount100('AddonEUR')->setUom('EUR'),
            RateFields::amount200('AddonEUR')->setUom('EUR'),
            RateFields::amount300('AddonEUR')->setUom('EUR'),
            RateFields::amount500('AddonEUR')->setUom('EUR'),
            RateFields::amount750('AddonEUR')->setUom('EUR'),
            RateFields::amount1000('AddonEUR')->setUom('EUR'),
            RateFields::amount2000('AddonEUR')->setUom('EUR')
        );
    }

    public function __invoke()
    {
        Response::setPageHeadHtml('Default Addons');
        Response::setPageTitle('Default Addons');

        $fs = $this->getFieldset();
        $data = Constant::getDefaultAddons();
        $tdata = $fs->getTplData($data, F::MODE_EDIT);
        Template::includeTpl('DefaultAddons.tpl.php', $tdata);
    }

    public function proceedSave()
    {
        $fs = $this->getFieldset();
        $addons = $fs->getDataFromRequest($_POST);
        ApplicationAbstract::setParam('DefaultAddons', json_encode($addons));
        Session::flashSuccess('Default Addons saved');
        Response::redirect('DefaultAddons');
    }
}