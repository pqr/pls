<?php

namespace Light\Module\CurrencyRate;

use Light\Currency;
use Light\Field\CommonFields as CF;
use Light\Field\FieldAbstract as F;
use Light\ObjAbstract;

/**
 * @property \Light\Fieldset|\Light\Field\FieldAbstract[] $_fieldset
 */
class CurrencyRateObj extends ObjAbstract
{
    public function __construct()
    {
        $this->setDbTable('currencyrate');

        $this->addFields(

            CF::currency('', ['sqlSelectMandatory' => true]),

            F::createDate('ValidDate', [
                'name' => 'Date',
                'default' => date('Y-m-d'),
                'sqlSelectMandatory' => true,
                'editMandatory' => true,
            ]),

            F::createFloat('RateToRUB', [
                'name' => 'Rate to RUB',
                'editMandatory' => true,
                'precision' => 4,
            ])

        );
    }

    #[\Override]
    public function getDescriptionHtml()
    {
        return $this->_data['CurrencyISO'] . ' on ' . $this->getFieldHtml('ValidDate');
    }

    #[\Override]
    public function insertDb(array $fieldsData, $dbLogMsg = '')
    {
        $id = parent::insertDb($fieldsData, $dbLogMsg);
        Currency::updateCurrencyRateTodayAfterSend();
        return $id;
    }

    #[\Override]
    public function updateDb(array $fieldsData, $dbLogMsg = ''): void
    {
        $affectedRows = parent::updateDb($fieldsData, $dbLogMsg);
        if ($affectedRows) {
            Currency::updateCurrencyRateTodayAfterSend();
        }
    }

    #[\Override]
    public function deleteDb($dbLogMsg = ''): void
    {
        parent::deleteDb($dbLogMsg);
        Currency::updateCurrencyRateTodayAfterSend();
    }
}
