<?php
namespace Forward\Module\CurrencyRateCitadele;

use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

class CurrencyRateCitadeleObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('currencyrate_citadele');

        $this->addFields(

            F::createString('Name')->setName('Currency')->setNameTable('Curr.')->setEditWidth('mini')->setEditMaxLength(3),
            F::createDate('ValidDate')->setName('Valid Date'),
            F::createFloat('BuyingForEUR')->setName('Покупка за EUR'),
            F::createFloat('SellingForEUR')->setName('Продажа за EUR'),
            F::createFloat('BuyingForUSD')->setName('Покупка за USD'),
            F::createFloat('SellingForUSD')->setName('Продажа за USD')

        );
    }

} 