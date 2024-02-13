<?php
namespace Frizus\Cart;

class PriceHelper
{
    public static function convertCurrency($price, $fromCurrency, $toCurrency, $emptyOnError = false)
    {
        if (!$fromCurrency || !$toCurrency) {
            if ($emptyOnError) {
                return '';
            }
        }

        if ($fromCurrency === $toCurrency) {
            return $price;
        }

        return \CCurrencyRates::ConvertCurrency(
            $price,
            $fromCurrency,
            $toCurrency
        );
    }
}