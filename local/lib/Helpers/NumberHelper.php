<?php

namespace Frizus\Helpers;

class NumberHelper
{
    /**
     * @see \Bitrix\Sale\Internals\BasketTable::getMap
     * @see \Bitrix\Main\ORM\Fields\FloatField::__construct
     * @see \Bitrix\Main\ORM\Fields\FloatField::getPrecision
     * @see \Bitrix\Main\DB\MysqliSqlHelper::getColumnTypeByField
     *
     *
     * @see https://stackoverflow.com/questions/13501459/decimal-datatype-is-rounding-the-values
     * @see https://stackoverflow.com/questions/1604696/php-float-calculation-2-decimal-point
     */
    public static function floatRound($value)
    {
        return sprintf("%.4f", round((float)$value, 4));
    }

    public static function formatWithDecimalNoZeros($value)
    {
        return static::formatWithDecimal($value, true);
    }

    public static function formatWithDecimal($value, $noZeros)
    {
        return static::format($value, true, $noZeros);
    }

    public static function format($value, $decimal = false, $noZeros = false)
    {
        if ($decimal === false) {
            $decimal = 0;
        } elseif ($decimal === true) {
            $decimal = 2;
        }

        $formatted = number_format($value, $decimal, '.', ' ');
        if ($noZeros) {
            $formatted = str_replace('.00', '', $formatted);
        }

        return $formatted;
    }
}