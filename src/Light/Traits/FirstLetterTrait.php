<?php

namespace Light\Traits;

/**
 * Этот трейти пригождается для абстрактных классов, которые реализуют общие методы для симметричных модулей, таких как
 * CInvoice vs VInvoice; CPayment vs VPayment; CAct vs VAct
 */
trait FirstLetterTrait
{
    /**
     * @var string кеширующая переменная для метода getFirstLetter()
     */
    private $firstLetter;

    public function getFirstLetter(): string
    {
        if (!$this->firstLetter) {
            $parts = explode("\\", get_class($this));
            $this->firstLetter = end($parts)[0];
        }

        return $this->firstLetter;
    }

    public function setFirstLetter(string $firstLetter): void
    {
        $this->firstLetter = $firstLetter;
    }

    public function getFirstLetterLower(): string
    {
        return strtolower($this->getFirstLetter());
    }

    public function getCustomerOrVendor(): string
    {
        return $this->getFirstLetter() === 'C' ? 'Customer' : 'Vendor';
    }

    public function getCustomerOrVendorLower(): string
    {
        return strtolower($this->getCustomerOrVendor());
    }

    public function isFirstLetterCustomer(): bool
    {
        return $this->getFirstLetter() === 'C';
    }

    public function isFirstLetterVendor(): bool
    {
        return $this->getFirstLetter() === 'V';
    }
}
