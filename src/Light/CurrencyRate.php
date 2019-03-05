<?php


namespace Light;


class CurrencyRate implements \JsonSerializable
{
    /**
     * @var string
     */
    public $currencyIso;

    /** @var float */
    public $rateToRub;

    /**
     * @var string
     */
    public $validDate;

    public function __construct(string $currencyIso, $rateToRub, string $validDate = null)
    {
        $this->currencyIso = $currencyIso;
        $this->rateToRub = (float)$rateToRub;
        $this->validDate = $validDate;
    }

    public function jsonSerialize()
    {
        return [
            'CurrencyISO' => $this->currencyIso,
            'RateToRUB' => $this->rateToRub,
            'ValidDate' => $this->validDate,
        ];
    }
}
