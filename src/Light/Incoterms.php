<?php

namespace Light;

class Incoterms {

    public static $incoterms = [
        "EXW" => "Ex Works",
        "FCA" => "Free Carrier",
        "FAS" => "Free Alongside Ship",
        "FOB" => "Free On Board",
        "CFR" => "Cost and Freight",
        "CIF" => "Cost, Insurance and Freight ",
        "CPT" => "Carriage Paid To",
        "CIP" => "Carriage and Insurance Paid to",
        "DAT" => "Delivered At Terminal",
        "DAP" => "Delivered At Point",
        "DDP" => "Delivered Duty Paid"
    ];

    public static function getOptions()
    {
        $keys = array_keys(self::$incoterms);
        return array_combine($keys, $keys);
    }

}
