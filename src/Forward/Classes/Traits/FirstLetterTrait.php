<?php
namespace Forward\Classes\Traits;

/**
 * Этот трейти пригождается для абстрактных классов, которые реализуют общие методы для симметричных модулей, таких как
 * CInvoice vs VInvoice; CPayment vs VPayment; CAct vs VAct
 */
trait FirstLetterTrait
{
    /**
     * @var string кеширующая переменная для метода getFirstLetter()
     */
    private $_firstLetter;

    protected function getFirstLetter()
    {
        if (!$this->_firstLetter) {
            $parts = explode("\\", get_class($this));
            $this->_firstLetter = end($parts)[0];
        }

        return $this->_firstLetter;
    }

    protected function getCustomerOrVendor()
    {
        return $this->getFirstLetter() === 'C' ? 'Customer' : 'Vendor';
    }
}