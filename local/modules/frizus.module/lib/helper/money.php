<?
namespace Frizus\Module\Helper;

class Money
{
    public static function roubles($value, $isText = false)
    {
        $string = \CCurrencyLang::CurrencyFormat($value, CurrencyHelper::getRoubles(), true);
        return $isText ? text($string) : $string;
        //return str_replace('.00', '', number_format(floatval($value), 2, '.', ' ')) . ($addSuffix ? ' ₽' : '');
    }

    public static function roublesText($value)
    {
        return static::roubles($value, true);
    }
}